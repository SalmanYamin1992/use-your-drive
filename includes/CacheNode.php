<?php

namespace TheLion\UseyourDrive;

class CacheNode
{
    /**
     * ID of the Node = ID of the Cached Entry.
     *
     * @var mixed
     */
    private $_id;

    /**
     * ID of the Account.
     *
     * @var mixed
     */
    private $_account_id;

    /**
     * ID of the Drive the entry is on.
     *
     * @var mixed
     */
    private $_drive_id;

    /**
     * The NAME of the node = NAME of the Cached Entry.
     *
     * @var string
     */
    private $_name;

    /**
     * The cached Entry.
     *
     * @var Entry
     */
    private $_entry;

    /**
     * Is this node for a folder or a file.
     *
     * @var bool
     */
    private $_is_dir = false;

    /**
     * Contains the parent note.
     *
     * @var CacheNode
     */
    private $_parent;

    /**
     * Is the parent of this node already found/cached?
     *
     * @var bool
     */
    private $_parent_found = false;

    /**
     * Contains the array of children.
     *
     * @var CacheNode[]
     */
    private $_children = [];

    /**
     * Are the children already found/cached?
     *
     * @var bool
     */
    private $_children_loaded = false;

    /**
     * Are all subfolders inside this node found.
     *
     * @var bool
     */
    private $_all_childfolders_loaded = false;

    /**
     * Is the node the root of account?
     *
     * @var bool
     */
    private $_root = false;

    /**
     * When does this node expire? Value is set in the Cache of the Cloud Service.
     *
     * @var int
     */
    private $_expires;

    /**
     * Entry is only loaded via GetFolder or GetEntry, not when the tree is built.
     *
     * @var bool
     */
    private $_loaded = false;

    // Trashed entries will be deleted when the cache is saved
    private $_trashed = false;

    // In some special cases, an entry or folder should be hidden
    private $_hidden = false;

    /**
     * ID of original node if is shortcut.
     */
    private $_original_node_id;

    /**
     * IDs of entries that are a shortcut to this node;.
     *
     * @var array|bool
     */
    private $_original_node_for = [];
    /**
     * Contains the file handle in case the plugin has to work
     * with a file for unlocking/locking.
     *
     * @var type
     */
    private $_cache_file_handle;

    /**
     *  @var \TheLion\UseyourDrive\Cache
     */
    private $_cache;

    /**
     * Is set to true when a change has been made in the cache.
     * Forcing the plugin to save the cache when needed.
     *
     * @var bool
     */
    private $_updated = false;

    /**
     * Is this CacheNode already initialized via a cache file (i.e. is $entry already present.
     *
     * @var bool
     */
    private $_initialized = false;

    /**
     * Folders that only have a structural function and cannot be used to perform any actions (e.g. delete/rename/zip)
     * Team Drives and Computers are such folders.
     */
    private $_virtual_folder = false;

    public function __construct(Cache $cache, $params = null)
    {
        $this->_cache = $cache;

        if (!empty($params)) {
            foreach ($params as $key => $val) {
                $this->{$key} = $val;
            }
        }
    }

    public function __destruct()
    {
        $this->update_cache();
    }

    public function __sleep()
    {
        $keys = get_object_vars($this);

        if (!empty($this->_entry) && !$this->is_dir()) {
            $this->_initialized = true;
        } else {
            unset($keys['_initialized']);
        }

        unset($keys['_cache'], $keys['_cache_location'], $keys['_cache_file_handle'], $keys['_updated'], $keys['_children'], $keys['_parent']);

        return array_keys($keys);
    }

    public function get_id()
    {
        return $this->_id;
    }

    public function get_drive_id()
    {
        return $this->_drive_id;
    }

    public function get_account_id()
    {
        return $this->_account_id;
    }

    public function set_name($name)
    {
        $this->_name = $name;

        return $this;
    }

    public function get_name()
    {
        return $this->_name;
    }

    public function has_entry()
    {
        return null !== $this->get_entry();
    }

    /**
     * @return \TheLion\UseyourDrive\Entry
     */
    public function get_entry()
    {
        if (false === $this->_initialized) {
            if ($this->is_dir()) {
                $this->load();
            } else {
                if ($this->has_parent()) {
                    $this->get_parent()->load();
                }
            }
        }

        return $this->_entry;
    }

    /**
     * @param \TheLion\UseyourDrive\Entry $entry
     *
     * @return \TheLion\UseyourDrive\CacheNode
     */
    public function set_entry($entry)
    {
        $this->_entry = $entry;
        $this->set_updated();

        return $this;
    }

    public function has_parent()
    {
        return null !== $this->get_parent();
    }

    /**
     * @return \TheLion\UseyourDrive\CacheNode
     */
    public function get_parent()
    {
        return $this->_parent;
    }

    public function set_parent(CacheNode $pnode)
    {
        if (false === $this->get_parent_found()) {
            $this->remove_parent();
            $this->_parent_found = true;
        }

        $pnode->add_child($this);
        $this->_parent = $pnode;
        $this->set_parent_found();

        return $this;
    }

    public function remove_parent()
    {
        if (false === $this->has_parent()) {
            return;
        }

        if (($this->get_parent() instanceof CacheNode)) {
            $this->get_parent()->remove_child($this);
        }

        $this->_parent = null;

        $this->set_updated();
    }

    public function is_in_folder($parent_id)
    {
        if (null === $this->get_entry()) {
            error_log('[WP Cloud Plugin message]: EMPTY node');
            $fresh_entry = $this->get_processor()->get_client()->get_entry($this->get_id(), true);
            if (empty($fresh_entry)) {
                error_log('[WP Cloud Plugin message]: Found via API');
            } else {
                error_log('[WP Cloud Plugin message]: NOT found via API');

                return false;
            }
        }

        // Is node just the folder?
        if ($this->get_id() === $parent_id) {
            return true;
        }

        if (in_array($this->get_virtual_folder(), ['drive'])) {
            return false; // This is the root node, there are no parents beyond
        }

        // Check if this parent is in folder
        if ($this->has_parent() && true === $this->get_parent()->is_in_folder($parent_id)) {
            return true;
        }

        // Check if this has shortcuts
        $shortcut_nodes = $this->get_cache()->get_shortcut_nodes_by_id($this->get_id());

        if (count($shortcut_nodes)) {
            //No Shortcuts found
        }

        foreach ($shortcut_nodes as $other_node) {
            // First check if one of the nodes is the root folder

            if (!empty($other_node) && true === $other_node->is_in_folder($parent_id)) {
                return true;
            }
        }

        // If we end up here, we haven't found the parent yet.
        // It might be possible that we are dealing with a shortcut, of which the parent is not yet loaded in the Cache
        $all_shortcuts = $this->get_processor()->get_client()->get_shortcuts_for_entry($this);
        $missing_shortcuts = array_diff_key($all_shortcuts, $shortcut_nodes);

        if (count($all_shortcuts)) {
        }

        foreach ($missing_shortcuts as $shortcut_node) {
            // First check if one of the nodes is the root folder
            if (empty($shortcut_node)) {
                continue;
            }

            if (is_string($shortcut_node)) {
                $shortcut_node = $this->get_cache()->get_node_by_id($shortcut_node);
            }

            if (true === $shortcut_node->is_in_folder($parent_id)) {
                return true;
            }
        }

        return false;
    }

    public function set_root($value = true)
    {
        $this->_root = $value;

        return $this;
    }

    public function is_root()
    {
        return $this->_root;
    }

    public function set_parent_found($value = true)
    {
        $this->_parent_found = $value;

        return $this;
    }

    public function get_parent_found()
    {
        return $this->_parent_found;
    }

    public function has_children()
    {
        return count($this->_children) > 0;
    }

    /**
     * @return \TheLion\UseyourDrive\CacheNode[]
     */
    public function get_children()
    {
        return $this->_children;
    }

    public function add_child(CacheNode $cnode)
    {
        $child_id = $cnode->get_id();
        $this->_children[$child_id] = $cnode;

        $key = array_search($child_id, $this->_children);
        if (false !== $key) {
            unset($this->_children[$key]);
        }

        return $this;
    }

    public function remove_child_by_id($id)
    {
        $this->set_updated();
        unset($this->_children[$id]);
    }

    public function remove_child(CacheNode $cnode)
    {
        $this->set_updated();
        unset($this->_children[$cnode->get_id()]);

        return $this;
    }

    public function remove_children()
    {
        foreach ($this->get_children() as $child) {
            $this->remove_child($child);
        }

        return $this;
    }

    public function has_loaded_children()
    {
        return $this->_children_loaded;
    }

    public function set_loaded_children($value = true)
    {
        $this->_children_loaded = $value;

        return $this->_children_loaded;
    }

    public function has_loaded_all_childfolders()
    {
        return $this->_all_childfolders_loaded;
    }

    public function set_loaded_all_childfolders($value = true)
    {
        foreach ($this->get_all_sub_folders() as $child_folder) {
            $child_folder->set_loaded_all_childfolders($value);
        }

        $this->_all_childfolders_loaded = $value;

        return $this->_all_childfolders_loaded;
    }

    public function is_expired()
    {
        if (null === $this->get_entry()) {
            return true;
        }

        if (!$this->is_loaded()) {
            return true;
        }

        // Folders itself cannot expire
        if ($this->get_entry()->is_dir() && $this->has_loaded_children() && !$this->has_children()) {
            return false;
        }

        // Check if the entry needs to be refreshed
        if ($this->get_entry()->is_file() && $this->_expires < time()) {
            return true;
        }

        // Some special folders can't expire
        if ($this->is_virtual_folder()) {
            return false;
        }

        // Also check if the files in a folder are still OK
        if ($this->has_children()) {
            foreach ($this->get_children() as $child) {
                if (!($child instanceof CacheNode)) {
                    return true;
                }

                if (!$child->has_entry()) {
                    return true;
                }

                if ($child->get_entry()->is_file() && $child->_expires < time()) {
                    return true;
                }
            }
        }

        return false;
    }

    public function get_all_child_folders()
    {
        $list = [];
        if ($this->has_children()) {
            foreach ($this->get_children() as $child) {
                if ($child->has_entry() && $child->get_entry()->is_dir()) {
                    $list[$child->get_id()] = $child;
                }

                if ($child->has_children()) {
                    $folders_in_child = $child->get_all_child_folders();
                    $list = array_merge($list, $folders_in_child);
                }
            }
        }

        return $list;
    }

    public function get_all_sub_folders()
    {
        $list = [];
        if ($this->has_children()) {
            foreach ($this->get_children() as $child) {
                if ($child->has_entry() && $child->get_entry()->is_dir()) {
                    $list[$child->get_id()] = $child;
                }
            }
        }

        return $list;
    }

    public function get_all_parent_folders()
    {
        $list = [];
        if ($this->has_parent()) {
            $list[$this->get_parent()->get_id()] = $this->get_parent();
            $list = array_merge($list, $this->get_parent()->get_all_parent_folders());
        }

        return $list;
    }

    public function get_linked_users()
    {
        $linked_users = [];
        $all_parent_folders = $this->get_all_parent_folders();

        // First obtain all users that are manually linked to the entry or its parents
        global $wpdb;

        $meta_query = [
            'relation' => 'OR',
            [
                'key' => $wpdb->prefix.'use_your_drive_linkedto',
                'value' => '"'.$this->get_id().'"',
                'compare' => 'LIKE',
            ],
        ];

        if (count($all_parent_folders) > 0) {
            foreach ($all_parent_folders as $parent_folder) {
                $meta_query[] = [
                    'key' => $wpdb->prefix.'use_your_drive_linkedto',
                    'value' => '"'.$parent_folder->get_id().'"',
                    'compare' => 'LIKE',
                ];
            }
        }

        $manually_linked_users = (get_users(['meta_query' => $meta_query]));

        foreach ($manually_linked_users as $userdata) {
            $linked_users[$userdata->ID] = $userdata;
        }

        /* Secondly obtain all users that are automatically linked to the entry or its parents
         * The folder has to contain the email address of the user */

        $all_parent_folders[] = $this; // Add current entry to prevent duplicate code

        foreach ($all_parent_folders as $parent) {
            $extracted_email = Helpers::extract_email_from_string($parent->get_name());

            if (false === $extracted_email) {
                continue;
            }

            $userdata = \WP_User::get_data_by('email', $extracted_email);

            if (!$userdata) {
                continue;
            }

            $linked_users[$userdata->ID] = $userdata;
        }

        return $linked_users;
    }

    public function get_path($to_parent_id)
    {
        if ($to_parent_id === $this->get_id()) {
            return '/'.$this->get_entry()->get_name();
        }

        if ($this->has_parent()) {
            if ($this->get_parent()->get_id() === $to_parent_id) {
                return '/'.$this->get_entry()->get_name();
            }

            $path = $this->get_parent()->get_path($to_parent_id);
            if (false !== $path) {
                return $path.'/'.$this->get_entry()->get_name();
            }
        }

        if ($this->is_root()) {
            return '';
        }

        return false;
    }

    public function set_expired($value)
    {
        return $this->_expires = $value;
    }

    public function get_expired()
    {
        return $this->_expires;
    }

    public function set_loaded($value)
    {
        return $this->_loaded = $value;
    }

    public function is_loaded()
    {
        return $this->_loaded;
    }

    public function set_initialized($value)
    {
        return $this->_initialized = $value;
    }

    public function is_initialized()
    {
        return $this->_initialized;
    }

    public function set_hidden($value)
    {
        return $this->_hidden = $value;
    }

    public function is_hidden()
    {
        return $this->_hidden;
    }

    public function set_trashed($value = true)
    {
        return $this->_trashed = $value;
    }

    public function is_trashed()
    {
        return true === $this->_trashed;
    }

    public function set_is_dir($value = true)
    {
        return $this->_is_dir = $value;
    }

    public function is_dir()
    {
        return true === $this->_is_dir;
    }

    public function is_shortcut()
    {
        return null !== $this->_original_node_id;
    }

    /**
     * @return \TheLion\UseyourDrive\CacheNode
     */
    public function get_original_node()
    {
        if (empty($this->get_original_node_id())) {
            return null;
        }

        return $this->get_processor()->get_client()->get_entry($this->get_original_node_id(), false);
    }

    public function get_original_node_id()
    {
        return $this->_original_node_id;
    }

    public function set_original_node_id($node_id)
    {
        $this->_original_node_id = $node_id;

        return $this;
    }

    public function get_original_node_for()
    {
        return $this->_original_node_for;
    }

    public function add_original_node_for($_original_node_for = [])
    {
        $this->_original_node_for = array_unique(array_merge($this->_original_node_for, $_original_node_for));

        return $this;
    }

    public function get_virtual_folder()
    {
        return $this->_virtual_folder;
    }

    public function set_virtual_folder($value)
    {
        $this->_virtual_folder = $value;
    }

    public function is_virtual_folder()
    {
        return false !== $this->_virtual_folder;
    }

    public function is_updated()
    {
        return $this->_updated;
    }

    public function set_updated($value = true)
    {
        $this->_updated = (bool) $value;

        return $this->_updated;
    }

    public function get_cache_name()
    {
        $prefix = (in_array($this->_id, ['drive', 'shared-with-me', 'shared-drives', 'computers'])) ? $this->get_processor()->get_current_account()->get_id() : '';

        return Helpers::filter_filename($prefix, false).'_'.$this->_id.'.index';
    }

    public function get_cache_location()
    {
        return USEYOURDRIVE_CACHEDIR.'/'.$this->get_cache_name();
    }

    public function load()
    {
        $cache = false;

        $cache = $this->_read_local_cache('close');

        if (function_exists('gzdecode')) {
            $cache = @gzdecode($cache);
        }

        if (!empty($cache) && !is_array($cache)) {
            $cache = $this->_unserialize_for_folder($cache);
        }

        $this->set_initialized(true);
    }

    public function update_cache($clear_request_cache = true)
    {
        if ($this->is_updated() && $this->is_dir()) {
            if (false === $this->is_initialized()) {
                return false;
            }

            $this->_save_local_cache();

            $this->set_updated(false);
        }
    }

    public function delete_cache()
    {
        if ($this->is_dir()) {
            @unlink($this->get_cache_location());
        }
    }

    public function to_index()
    {
        $_children = [];
        foreach ($this->_children as $child) {
            if ($child instanceof CacheNode) {
                $_children[] = $child->get_id();
            } else {
                $_children[] = $child;
            }
        }

        return [
            '_id' => $this->_id,
            '_account_id' => $this->_account_id,
            '_drive_id' => $this->_drive_id,
            '_name' => $this->_name,
            '_parent' => ($this->has_parent()) ? $this->get_parent()->get_id() : null,
            '_children' => $_children,
            '_original_node_id' => $this->_original_node_id,
            '_original_node_for' => $this->_original_node_for,
            '_is_dir' => $this->_is_dir,
        ];
    }

    /**
     * @return \TheLion\UseyourDrive\Cache
     */
    public function get_cache()
    {
        return $this->_cache;
    }

    /**
     * @return \TheLion\UseyourDrive\Processor
     */
    public function get_processor()
    {
        return $this->_cache->get_processor();
    }

    protected function _set_cache_file_handle($handle)
    {
        return $this->_cache_file_handle = $handle;
    }

    protected function _get_cache_file_handle()
    {
        return $this->_cache_file_handle;
    }

    protected function _read_local_cache($close = false)
    {
        $handle = $this->_get_cache_file_handle();
        if (empty($handle)) {
            $this->_create_local_lock(LOCK_SH);
        }

        clearstatcache();
        rewind($this->_get_cache_file_handle());

        $data = null;
        if (filesize($this->get_cache_location()) > 0) {
            $data = fread($this->_get_cache_file_handle(), filesize($this->get_cache_location()));
        }

        if (false !== $close) {
            $this->_unlock_local_cache();
        }

        return $data;
    }

    protected function _create_local_lock($type)
    {
        // Check if file exists
        $file = $this->get_cache_location();

        if (!file_exists($file)) {
            $data = $this->_serialize_for_folder();

            @file_put_contents($file, $data);

            if (!is_writable($file)) {
                error_log('[WP Cloud Plugin message]: '.sprintf('Cache file (%s) is not writable', $file));

                exit(sprintf('Cache file (%s) is not writable', $file));
            }
        }

        // Check if the file is more than 1 minute old.
        $requires_unlock = ((filemtime($file) + 60) < (time()));

        // Temporarily workaround when flock is disabled. Can cause problems when plugin is used in multiple processes
        if (false !== strpos(ini_get('disable_functions'), 'flock')) {
            $requires_unlock = false;
        }

        // Check if file is already opened and locked in this process
        $handle = $this->_get_cache_file_handle();
        if (empty($handle)) {
            $handle = fopen($file, 'c+');
            if (!is_resource($handle)) {
                error_log('[WP Cloud Plugin message]: '.sprintf('Cache file (%s) is not writable', $file));

                exit(sprintf('Cache file (%s) is not writable', $file));
            }
            $this->_set_cache_file_handle($handle);
        }

        @set_time_limit(60);

        if (!flock($this->_get_cache_file_handle(), $type)) {
            /*
             * If the file cannot be unlocked and the last time
             * it was modified was 1 minute, assume that
             * the previous process died and unlock the file manually
             */
            if ($requires_unlock) {
                $this->_unlock_local_cache();
                $handle = fopen($file, 'c+');
                $this->_set_cache_file_handle($handle);
            }
            // Try to lock the file again
            flock($this->_get_cache_file_handle(), LOCK_EX);
        }
        @set_time_limit(60);

        touch($file);

        return true;
    }

    protected function _save_local_cache()
    {
        if (!$this->_create_local_lock(LOCK_EX)) {
            return false;
        }

        $data = $this->_serialize_for_folder();

        ftruncate($this->_get_cache_file_handle(), 0);
        rewind($this->_get_cache_file_handle());

        $result = fwrite($this->_get_cache_file_handle(), $data);

        $this->_unlock_local_cache();
        $this->set_updated(false);

        return true;
    }

    protected function _unlock_local_cache()
    {
        $handle = $this->_get_cache_file_handle();
        if (!empty($handle)) {
            flock($this->_get_cache_file_handle(), LOCK_UN);
            fclose($this->_get_cache_file_handle());
            $this->_set_cache_file_handle(null);
        }

        clearstatcache();

        return true;
    }

    private function _serialize_for_folder()
    {
        $keys = get_object_vars($this);
        unset($keys['_initialized'], $keys['_cache'], $keys['_cache_location'], $keys['_cache_file_handle'], $keys['_updated'], $keys['_children'], $keys['_parent']);

        $children = $this->get_children();

        $data = [
            'folder' => $keys,
            'children' => $children,
        ];

        $data_str = serialize($data);

        if (function_exists('gzencode')) {
            $data_str = gzencode($data_str);
        }

        return $data_str;
    }

    private function _unserialize_for_folder($data)
    {
        $values = unserialize($data);

        if (false === $values) {
            return;
        }

        if (!empty($values['children'])) {
            foreach ($values['children'] as $child) {
                if (!($child instanceof CacheNode)) {
                    continue;
                }

                $child_node = $this->get_cache()->get_node_by_id($child->get_id(), false);
                if (empty($child_node)) {
                    continue;
                }

                $child_node->_entry = $child->_entry;
                $child_node->_parent_found = $child->_parent_found;
                $child_node->_children_loaded = $child->_children_loaded;
                $child_node->_all_childfolders_loaded = $child->_all_childfolders_loaded;
                $child_node->_root = $child->_root;
                $child_node->_expires = $child->_expires;
                $child_node->_loaded = $child->_loaded;
                $child_node->_trashed = $child->_trashed;
                $child_node->_hidden = $child->_hidden;
                $child_node->_original_node_id = $child->_original_node_id;
                $child_node->_original_node_for = $child->_original_node_for;
                $child_node->_virtual_folder = $child->_virtual_folder;
                $child_node->_initialized = (false === $child_node->_initialized && false === $child->_is_dir) ? true : $child_node->_initialized;
            }
        }

        $node = $this->get_cache()->get_node_by_id($this->_id, false);
        foreach ($values['folder'] as $key => $value) {
            $node->{$key} = $value;
        }
    }
}
