<?php

namespace TheLion\UseyourDrive;

class Client
{
    public $apifilefields = 'capabilities(canEdit,canRename,canDelete,canShare,canTrash,canMoveItemWithinDrive),shared,sharedWithMeTime,description,fileExtension,iconLink,id,driveId,imageMediaMetadata(height,rotation,width,time),mimeType,createdTime,modifiedTime,name,ownedByMe,parents,size,thumbnailLink,trashed,videoMediaMetadata(height,width,durationMillis),webContentLink,webViewLink,exportLinks,permissions(id,type,role,domain),copyRequiresWriterPermission,shortcutDetails,resourceKey';
    public $apifilefieldsexpire = 'id,driveId,thumbnailLink,webContentLink,webViewLink';
    public $apilistfilesfields = 'files(capabilities(canEdit,canRename,canDelete,canShare,canTrash,canMoveItemWithinDrive),shared,sharedWithMeTime,description,fileExtension,iconLink,id,driveId,imageMediaMetadata(height,rotation,width,time),mimeType,createdTime,modifiedTime,name,ownedByMe,parents,size,thumbnailLink,trashed,videoMediaMetadata(height,width,durationMillis),webContentLink,webViewLink,exportLinks,permissions(id,type,role,domain),copyRequiresWriterPermission,shortcutDetails,resourceKey),nextPageToken';
    public $apilistfilesexpirefields = 'files(id,driveId,thumbnailLink,webContentLink,webViewLink),nextPageToken';
    public $apilistchangesfields = 'changes(file(capabilities(canEdit,canRename,canDelete,canShare,canTrash,canMoveItemWithinDrive),shared,sharedWithMeTime,description,fileExtension,iconLink,id,driveId,imageMediaMetadata(height,rotation,width,time),mimeType,createdTime,modifiedTime,name,ownedByMe,parents,size,thumbnailLink,trashed,videoMediaMetadata(height,width,durationMillis),webContentLink,webViewLink,exportLinks,permissions(id,type,role,domain),copyRequiresWriterPermission,shortcutDetails,resourceKey),removed, changeType, fileId),newStartPageToken,nextPageToken';

    /**
     * @var \TheLion\UseyourDrive\App
     */
    private $_app;

    /**
     * @var \TheLion\UseyourDrive\Processor
     */
    private $_processor;

    public function __construct(App $_app, Processor $_processor = null)
    {
        $this->_app = $_app;
        $this->_processor = $_processor;

        $this->apifilefields = apply_filters('useyourdrive_set_apifilefields', $this->apifilefields);
        $this->apilistfilesfields = apply_filters('useyourdrive_set_apilistfilesfields', $this->apilistfilesfields);
        $this->apilistchangesfields = apply_filters('useyourdrive_set_apilistchangesfields', $this->apilistchangesfields);
    }

    /*
     * Get AccountInfo
     *
     * @return mixed|WP_Error
     */

    public function get_account_info()
    {
        return $this->get_app()->get_user()->userinfo->get();
    }

    /*
     * Get DriveInfo
     *
     * @return mixed|WP_Error
     */

    public function get_drive_info()
    {
        return $this->get_app()->get_drive()->about->get(['fields' => 'importFormats,kind,storageQuota,user']);
    }

    public function get_root_folder()
    {
        $root_node = $this->get_cache()->get_root_node();

        if (false !== $root_node && null !== $root_node->get_entry()) {
            return $root_node;
        }

        // Top Google Drive Folder
        $root_api = new \UYDGoogle_Service_Drive_DriveFile();
        $root_api->setId('drive');
        $root_api->setDriveId('drive');
        $root_api->setName('Google (Virtual Folder)');
        $root_api->setMimeType('application/vnd.google-apps.folder');
        $root_entry = new Entry($root_api, 'drive');
        $cached_root_node = $this->get_cache()->add_to_cache($root_entry);
        $cached_root_node->set_root();
        $cached_root_node->set_loaded_children(true);
        $cached_root_node->set_virtual_folder('drive');

        $cached_root_node->set_updated();
        $this->get_cache()->set_root_node_id('drive');

        $this->get_computers(false);
        $this->get_shared_drives(false);
        $this->get_shared_with_me(false, 'init');
        $this->get_my_drive(false);

        $this->get_cache()->set_updated();
        $this->get_cache()->update_cache();

        return $this->get_cache()->get_root_node();
    }

    public function get_my_drive($checkauthorized = true)
    {
        $cached_root = $this->get_root_folder();

        foreach ($cached_root->get_children() as $cached_child) {
            if ('mydrive' === $cached_child->get_virtual_folder()) {
                return $this->get_folder($cached_child->get_id(), false);
            }
        }

        try {
            $mydrive_api = $this->get_app()->get_drive()->files->get('root', ['fields' => $this->apifilefields]);
        } catch (\Exception $ex) {
            error_log('[WP Cloud Plugin message]: '.sprintf('API Error on line %s: %s', __LINE__, $ex->getMessage()));

            return false;
        }

        $mydrive_api->setDriveId('mydrive');
        $mydrive_api->setParents(['drive']);
        $mydrive_entry = new Entry($mydrive_api, 'mydrive');
        $cached_mydrive_node = $this->get_cache()->add_to_cache($mydrive_entry);
        $cached_mydrive_node->set_virtual_folder('mydrive');
        $cached_mydrive_node->set_updated();
        $this->get_cache()->set_updated();

        return $this->get_folder($cached_mydrive_node->get_id(), $checkauthorized);
    }

    public function get_shared_drives($checkauthorized = true)
    {
        $cached_shared_drives_node = $this->get_cache()->is_cached('shared-drives', 'id', false);

        if (false === $cached_shared_drives_node) {
            // Shared Drives (Google Workspaces)
            $shared_drives_api = new \UYDGoogle_Service_Drive_DriveFile();
            $shared_drives_api->setId('shared-drives');
            $shared_drives_api->setName(esc_html__('Shared Drives', 'wpcloudplugins'));
            $shared_drives_api->setMimeType('application/vnd.google-apps.folder');
            $shared_drives_api->setParents(['drive']);
            $shared_drives_entry = new Entry($shared_drives_api, 'shared-drives');
            $cached_shared_drives_node = $this->get_cache()->add_to_cache($shared_drives_entry);
            $cached_shared_drives_node->set_virtual_folder('shared-drives');
            $cached_shared_drives_node->set_updated();

            $shared_drives = [];
            $params = [
                'fields' => 'kind,nextPageToken,drives(kind,id,name,capabilities,backgroundImageFile,backgroundImageLink)',
                'pageSize' => 50,
            ];

            $nextpagetoken = null;
            // Get all files in folder
            while ($nextpagetoken || null === $nextpagetoken) {
                try {
                    if (null !== $nextpagetoken) {
                        $params['pageToken'] = $nextpagetoken;
                    }

                    $more_drives = $this->get_app()->get_drive()->drives->listDrives($params);
                    $shared_drives = array_merge($shared_drives, $more_drives->getDrives());
                    $nextpagetoken = (null !== $more_drives->getNextPageToken()) ? $more_drives->getNextPageToken() : false;
                } catch (\Exception $ex) {
                    error_log('[WP Cloud Plugin message]: '.sprintf('API Error on line %s: %s', __LINE__, $ex->getMessage()));

                    return false;
                }
            }

            foreach ($shared_drives as $drive) {
                $drive_item = new EntryDrive($drive, 'shared-drive');
                $drive_item->set_parent_id('shared-drives');
                $cached_drive_node = $this->get_cache()->add_to_cache($drive_item);
                $cached_drive_node->set_virtual_folder('shared-drive');
            }

            $this->get_cache()->set_updated();
        }

        // Check if folder is in the shortcode-set rootfolder
        if (true === $checkauthorized) {
            if (!$this->get_processor()->_is_entry_authorized($cached_shared_drives_node)) {
                return false;
            }
        }

        return ['folder' => $cached_shared_drives_node, 'contents' => $cached_shared_drives_node->get_children()];
    }

    public function get_computers($checkauthorized = true)
    {
        // Computers
        // Not yet fully supported by the API
        //
        $cached_computers_node = $this->get_cache()->get_node_by_id('computers');

        if (false === $cached_computers_node) {
            $computers_api = new \UYDGoogle_Service_Drive_DriveFile();
            $computers_api->setId('computers');
            $computers_api->setName(esc_html__('Computers', 'wpcloudplugins').' ('.esc_html__('Limited Support!', 'wpcloudplugins').')');
            $computers_api->setMimeType('application/vnd.google-apps.folder');
            $computers_api->setParents(['drive']);
            $computers_entry = new Entry($computers_api, 'computers');
            $cached_computers_node = $this->get_cache()->add_to_cache($computers_entry);
            $cached_computers_node->set_virtual_folder('computers');
            $cached_computers_node->set_loaded_children(true); // Can't yet read data in Computers via API
            $cached_computers_node->set_updated();
            $this->get_cache()->set_updated();
        }

        // Check if folder is in the shortcode-set rootfolder
        if (true === $checkauthorized) {
            if (!$this->get_processor()->_is_entry_authorized($cached_computers_node)) {
                return false;
            }
        }

        return ['folder' => $cached_computers_node, 'contents' => $cached_computers_node->get_children()];
    }

    public function get_shared_with_me($checkauthorized = true, $init = false)
    {
        $cached_shared_with_me_node = $this->get_cache()->is_cached('shared-with-me', 'id', false);

        if (false === $cached_shared_with_me_node) {
            $shared_api = new \UYDGoogle_Service_Drive_DriveFile();
            $shared_api->setId('shared-with-me');
            $shared_api->setName(esc_html__('Shared with me', 'wpcloudplugins'));
            $shared_api->setMimeType('application/vnd.google-apps.folder');
            $shared_api->setParents(['drive']);
            $shared_entry = new Entry($shared_api, 'shared-with-me');
            $cached_shared_with_me_node = $this->get_cache()->add_to_cache($shared_entry);
            $cached_shared_with_me_node->set_virtual_folder('shared-with-me');

            if ($init) {
                // Only load the folder node itself on init
                // To prevent performance issues with very large Shared with Me folders
                return ['folder' => $cached_shared_with_me_node, 'contents' => $cached_shared_with_me_node->get_children()];
            }

            $params = ['q' => 'sharedWithMe = true and trashed = false', 'fields' => $this->apilistfilesfields, 'pageSize' => 999, 'supportsAllDrives' => true, 'includeItemsFromAllDrives' => true];

            $shared_entries = [];
            $nextpagetoken = null;

            while ($nextpagetoken || null === $nextpagetoken) {
                try {
                    if (null !== $nextpagetoken) {
                        $params['pageToken'] = $nextpagetoken;
                    }

                    $more_shared_entries = $this->get_app()->get_drive()->files->listFiles($params);
                    $shared_entries = array_merge($shared_entries, $more_shared_entries->getFiles());
                    $nextpagetoken = (null !== $more_shared_entries->getNextPageToken()) ? $more_shared_entries->getNextPageToken() : false;
                } catch (\Exception $ex) {
                    error_log('[WP Cloud Plugin message]: '.sprintf('API Error on line %s: %s', __LINE__, $ex->getMessage()));

                    return false;
                }
            }

            foreach ($shared_entries as $api_entry) {
                if (empty($api_entry->getParents())) {
                    // Add root parent for shared files which are located in the root 'Shared with me' virtual folder
                    $api_entry->setParents('shared-with-me');
                }
                // Shared files are by definition not owned by the linked account
                $api_entry->setOwnedByMe(false);

                $entry = new Entry($api_entry);
                $this->get_cache()->add_to_cache($entry);
            }

            $cached_shared_with_me_node->set_loaded_children(true);
            $cached_shared_with_me_node->set_updated();

            $this->get_cache()->update_cache();
        }

        if (true === $checkauthorized) {
            if (!$this->get_processor()->_is_entry_authorized($cached_shared_with_me_node)) {
                return false;
            }
        }

        return ['folder' => $cached_shared_with_me_node, 'contents' => $cached_shared_with_me_node->get_children()];
    }

    public function get_multiple_entries($entries)
    {
        if (1 === count($entries)) {
            $api_entry = $this->get_app()->get_drive()->files->get(reset($entries), ['supportsAllDrives' => true, 'fields' => $this->apifilefields]);

            return [$api_entry];
        }

        $this->get_library()->setUseBatch(true);
        $batch = new \UYDGoogle_Http_Batch($this->get_library());

        foreach ($entries as $entryid) {
            $batch->add($this->get_app()->get_drive()->files->get($entryid, ['fields' => $this->apifilefields, 'supportsAllDrives' => true]), $entryid);
        }

        try {
            if (defined('GOOGLE_API_BATCH')) {
                usleep(mt_rand(10000, 500000));
            } else {
                define('GOOGLE_API_BATCH', true);
            }
            $batch_result = $batch->execute();
        } catch (\Exception $ex) {
            error_log('[WP Cloud Plugin message]: '.sprintf('API Error on line %s: %s', __LINE__, $ex->getMessage()));

            throw $ex;
            //return false; CAN CAUSE CORRUPT CACHE
        }
        $this->get_library()->setUseBatch(false);

        return $batch_result;
    }

    public function get_entries_in_subfolders(CacheNode $cachedfolder, $checkauthorized = true)
    {
        $result = $this->_get_files_recursive($cachedfolder);
        $entries_in_searchedfolder = [];

        foreach ($result['files'] as $file) {
            $cached_entry = $this->get_entry($file['ID'], $checkauthorized);

            if (empty($cached_entry)) {
                continue;
            }

            $entries_in_searchedfolder[$cached_entry->get_id()] = $cached_entry;
        }

        return $entries_in_searchedfolder;
    }

    // Get entry

    public function get_entry($entryid = false, $checkauthorized = true)
    {
        if (false === $entryid) {
            $entryid = $this->get_processor()->get_requested_entry();
        }

        // Load the root folder when needed
        $this->get_root_folder();

        // Get entry from cache
        $cachedentry = $this->get_cache()->is_cached($entryid);

        // If entry isn't cached
        if (!$cachedentry) {
            try {
                $api_entry = $this->get_app()->get_drive()->files->get($entryid, ['supportsAllDrives' => true, 'fields' => $this->apifilefields]);
                $entry = new Entry($api_entry);

                if (false === $entry->is_dir()) {
                    $cachedentry = $this->get_cache()->add_to_cache($entry);
                } else {
                    $folder = $this->get_folder($entryid, $checkauthorized);

                    return $folder['folder'];
                }
            } catch (\Exception $ex) {
                error_log('[WP Cloud Plugin message]: '.sprintf('API Error on line %s: %s', __LINE__, $ex->getMessage()));

                return false;
            }
        }

        if (true === $checkauthorized) {
            if ('root' !== $entryid && !$this->get_processor()->_is_entry_authorized($cachedentry)) {
                return false;
            }
        }

        if ($cachedentry->is_shortcut()) {
            return $this->get_entry($cachedentry->get_original_node_id(), $checkauthorized);
        }

        return $cachedentry;
    }

    // Get folders and files
    public function get_folder($folderid = false, $checkauthorized = true)
    {
        if (false === $folderid) {
            $folderid = $this->get_processor()->get_requested_entry();
        }

        // Load the root folder when needed
        $root_folder = $this->get_root_folder();

        if ('shared-drives' === $folderid) {
            return $this->get_shared_drives($checkauthorized);
        }

        if ('shared-with-me' === $folderid) {
            return $this->get_shared_with_me($checkauthorized);
        }

        if ('computers' === $folderid) {
            return $this->get_computers($checkauthorized);
        }

        $cachedfolder = $this->get_cache()->is_cached($folderid, 'id', false);

        if (!$cachedfolder) {
            $params = ['q' => "'".$folderid."' in parents and trashed = false", 'fields' => $this->apilistfilesfields, 'pageSize' => 999, 'supportsAllDrives' => true, 'includeItemsFromAllDrives' => true];

            $this->get_library()->setUseBatch(true);
            $batch = new \UYDGoogle_Http_Batch($this->get_library());

            $batch->add($this->get_app()->get_drive()->files->get($folderid, ['fields' => $this->apifilefields, 'supportsAllDrives' => true]), 'folder');
            $batch->add($this->get_app()->get_drive()->files->listFiles($params), 'foldercontents');

            try {
                if (defined('GOOGLE_API_BATCH')) {
                    usleep(50000);
                } else {
                    define('GOOGLE_API_BATCH', true);
                }
                $results = $batch->execute();
            } catch (\Exception $ex) {
                error_log('[WP Cloud Plugin message]: '.sprintf('API Error on line %s: %s', __LINE__, $ex->getMessage()));

                return false;
            }

            $this->get_library()->setUseBatch(false);
            $folder = $results['response-folder'];

            if ($folder instanceof \Exception) {
                error_log('[WP Cloud Plugin message]: '.sprintf('API Error (Folder does not exist on the Google Drive) on line %s: %s', __LINE__, $folder->getMessage()));

                return false;
            }

            if ($results['response-foldercontents'] instanceof \Exception) {
                error_log('[WP Cloud Plugin message]: '.sprintf('API Error on line %s: %s', __LINE__, $results['response-foldercontents']->getMessage()));

                return false;
            }

            $files_in_folder = $results['response-foldercontents']->getFiles();
            $nextpagetoken = (null !== $results['response-foldercontents']->getNextPageToken()) ? $results['response-foldercontents']->getNextPageToken() : false;

            // Get all files in folder
            while ($nextpagetoken) {
                try {
                    $params['pageToken'] = $nextpagetoken;
                    $more_files = $this->get_app()->get_drive()->files->listFiles($params);
                    $files_in_folder = array_merge($files_in_folder, $more_files->getFiles());
                    $nextpagetoken = (null !== $more_files->getNextPageToken()) ? $more_files->getNextPageToken() : false;
                } catch (\Exception $ex) {
                    error_log('[WP Cloud Plugin message]: '.sprintf('API Error on line %s: %s', __LINE__, $ex->getMessage()));

                    return false;
                }
            }

            // Convert the items to Framework Entry
            $virtual_folder = false;
            if ($folder->getId() === $folder->getDriveId()) {
                // Folder is a Shared Drive
                $virtual_folder = 'shared-drive';
                $folder->setParents(['shared-drives']);
            } elseif ($cached_my_drive = $this->get_cache()->get_node_by_id($folder->getId())) {
                if ($cached_my_drive->has_entry() && 'mydrive' === $cached_my_drive->get_entry()->get_virtual_folder()) {
                    // Folder is a My Drive
                    $virtual_folder = 'mydrive';
                    $folder->setParents(['drive']);
                }
            }

            $folder_entry = new Entry($folder, $virtual_folder);

            // BUG FIX normal API returning different name for Shared Drive Name
            if ($cached_team_drive = $this->get_cache()->get_node_by_id($folder_entry->get_id())) {
                if ($cached_team_drive->has_entry() && 'shared-drive' === $cached_team_drive->get_entry()->get_virtual_folder()) {
                    $folder_entry->set_name($cached_team_drive->get_name());
                }
            }
            // END BUG FIX

            $folder_items = [];
            foreach ($files_in_folder as $entry) {
                $folder_items[] = new Entry($entry);
            }

            $cachedfolder = $this->get_cache()->add_to_cache($folder_entry);
            $cachedfolder->set_loaded_children(true);

            // Add all entries in folder to cache
            foreach ($folder_items as $item) {
                $newitem = $this->get_cache()->add_to_cache($item);
            }

            $this->get_cache()->update_cache();
        }

        $folder = $cachedfolder;
        $files_in_folder = $cachedfolder->get_children();

        // Check if folder is in the shortcode-set rootfolder
        if (true === $checkauthorized) {
            if (!$this->get_processor()->_is_entry_authorized($cachedfolder)) {
                return false;
            }
        }

        if ($folder->is_shortcut()) {
            return $this->get_folder($folder->get_original_node_id(), $checkauthorized);
        }

        return ['folder' => $folder, 'contents' => $files_in_folder];
    }

    /**
     * Get (and create) sub folder by path.
     *
     * @param string $parent_folder_id
     * @param string $subfolder_path
     * @param bool   $create_if_not_exists
     *
     * @return bool|\TheLion\UseyourDrive\CacheNode
     */
    public function get_sub_folder_by_path($parent_folder_id, $subfolder_path, $create_if_not_exists = false)
    {
        $cached_parent_folder = $this->get_folder($parent_folder_id, false);

        if (empty($cached_parent_folder)) {
            return false;
        }

        if (empty($subfolder_path)) {
            return $cached_parent_folder['folder'];
        }

        $subfolders = array_filter(explode('/', $subfolder_path));
        $current_folder = array_shift($subfolders);

        //Try to load the subfolder at once
        $cached_sub_folder = $this->get_cache()->get_node_by_name($current_folder, $parent_folder_id);

        /* If folder isn't in cache yet,
         * Update the parent folder to make sure the latest version is loaded */
        if (false === $cached_sub_folder) {
            $this->get_cache()->pull_for_changes($parent_folder_id, true, -1);
            $cached_sub_folder = $this->get_cache()->get_node_by_name($current_folder, $parent_folder_id);
        }

        if (false === $cached_sub_folder && false === $create_if_not_exists) {
            return false;
        }

        // If the subfolder can't be found, create the sub folder
        if (!$cached_sub_folder) {
            $_new_entry = new \UYDGoogle_Service_Drive_DriveFile();
            $_new_entry->setName($current_folder);
            $_new_entry->setMimeType('application/vnd.google-apps.folder');
            $_new_entry->setParents([$parent_folder_id]);

            try {
                $api_entry = $this->get_app()->get_drive()->files->create($_new_entry, ['fields' => $this->apifilefields,  'supportsAllDrives' => true, 'enforceSingleParent' => true]);

                if (null !== $api_entry) {
                    // Add new file to our Cache
                    $newentry = new Entry($api_entry);
                    $cached_sub_folder = $this->get_cache()->add_to_cache($newentry);

                    do_action('useyourdrive_log_event', 'useyourdrive_created_entry', $cached_sub_folder);
                }
            } catch (\Exception $ex) {
                error_log('[WP Cloud Plugin message]: '.sprintf('API Error on line %s: %s', __LINE__, $ex->getMessage()));

                return false;
            }
        }

        return $this->get_sub_folder_by_path($cached_sub_folder->get_id(), implode('/', $subfolders), $create_if_not_exists);
    }

    public function get_shortcuts_for_entry(CacheNode $entry_node)
    {
        $shortcuts_found = [];
        $api_entries_found = [];

        if ($entry_node->is_virtual_folder()) {
            // Virtual folders can't have shortcuts
            return $shortcuts_found;
        }

        if (null !== $entry_node->get_original_node_for()) {
            return $entry_node->get_original_node_for();
        }

        // Find all items containing query
        $params = [
            'q' => "shortcutDetails.targetId='{$entry_node->get_id()}' and trashed = false",
            'fields' => $this->apilistfilesfields,
            'pageSize' => 500,
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true,
        ];

        do {
            try {
                $search_response = $this->get_app()->get_drive()->files->listFiles($params);
            } catch (\Exception $ex) {
                error_log('[WP Cloud Plugin message]: '.sprintf('API Error on line %s: %s', __LINE__, $ex->getMessage()));

                return $shortcuts_found;
            }

            // Process the response
            $more_files = $search_response->getFiles();
            $api_entries_found = array_merge($api_entries_found, $more_files);

            $nextpagetoken = $search_response->getNextPageToken();
            $params['pageToken'] = $nextpagetoken;
        } while (null !== $nextpagetoken);

        $entries_found = [];
        $new_parent_folders = [];

        foreach ($api_entries_found as $api_entry) {
            $entry = new Entry($api_entry);
            $entries_found[] = $entry;

            if ($entry->has_parent()) {
                $parent_id = $entry->get_parent_id();
                if (false === $this->get_cache()->get_node_by_id($parent_id, false)) {
                    $new_parent_folders[$parent_id] = $parent_id;
                }
            }
        }

        // Load all new parents at once
        $new_parents_folders_api = $this->get_multiple_entries($new_parent_folders);
        foreach ($new_parents_folders_api as $parent) {
            if (!($parent instanceof EntryAbstract)) {
                $parent = new Entry($parent);
            }

            $this->get_cache()->add_to_cache($parent);
        }

        foreach ($entries_found as $entry) {
            // Check if entries are in cache
            $cachedentry = $this->get_cache()->is_cached($entry->get_id(), 'id', true);

            // If not found, add to cache
            if (false === $cachedentry) {
                $cachedentry = $this->get_cache()->add_to_cache($entry);
            } else {
                // Update Thumbnails
                $cached_entry_node = $cachedentry->get_entry();
                $cached_entry_node->set_thumbnail_icon($entry->get_thumbnail_icon());
                $cached_entry_node->set_thumbnail_small($entry->get_thumbnail_small());
                $cached_entry_node->set_thumbnail_small_cropped($entry->get_thumbnail_small_cropped());
                $cached_entry_node->set_thumbnail_large($entry->get_thumbnail_large());
                $cached_entry_node->set_thumbnail_original($entry->get_thumbnail_original());

                $this->get_cache()->set_updated();
            }

            $shortcuts_found[] = $cachedentry;
        }

        $entry_node->add_original_node_for(array_keys($shortcuts_found));
        // Update the cache already here so that the Search Output is cached
        $this->get_cache()->update_cache();

        return $shortcuts_found;
    }

    public function delete_entries($entries_to_delete = [])
    {
        $deleted_entries = [];

        $batch = new \UYDGoogle_Http_Batch($this->get_library());

        foreach ($entries_to_delete as $target_entry_path) {
            $this->get_library()->setUseBatch(false);
            $target_cached_entry = $this->get_entry($target_entry_path);
            $this->get_library()->setUseBatch(true);

            if (false === $target_cached_entry) {
                continue;
            }

            $target_entry = $target_cached_entry->get_entry();

            if ($target_entry->is_file() && false === $this->get_processor()->get_user()->can_delete_files()) {
                error_log('[WP Cloud Plugin message]: '.sprintf('Failed to delete %s as user is not allowed to remove files.', $target_entry->get_path()));

                continue;
            }

            if ($target_entry->is_dir() && false === $this->get_processor()->get_user()->can_delete_folders()) {
                error_log('[WP Cloud Plugin message]: '.sprintf('Failed to delete %s as user is not allowed to remove folders.', $target_entry->get_path()));

                continue;
            }

            if ('1' === $this->get_processor()->get_shortcode_option('demo')) {
                continue;
            }

            $deleted_entries[$target_entry->get_id()] = $target_cached_entry;

            if (
                    '1' === $this->get_processor()->get_shortcode_option('deletetotrash')
                    || (false === $target_entry->get_permission('candelete') && true === $target_entry->get_permission('cantrash'))
            ) {
                $updateentry = new \UYDGoogle_Service_Drive_DriveFile();
                $updateentry->setTrashed(true);
                $batch->add($this->get_app()->get_drive()->files->update($target_entry->get_id(), $updateentry, ['supportsAllDrives' => true]), $target_entry->get_id());
            } else {
                $batch->add($this->get_app()->get_drive()->files->delete($target_entry->get_id(), ['supportsAllDrives' => true]), $target_entry->get_id());
            }
        }

        // Execute the Batch Call
        try {
            if (defined('GOOGLE_API_BATCH')) {
                usleep(50000);
            } else {
                define('GOOGLE_API_BATCH', true);
            }
            $batch_result = $batch->execute();
        } catch (\Exception $ex) {
            error_log('[WP Cloud Plugin message]: '.sprintf('API Error on line %s: %s', __LINE__, $ex->getMessage()));

            return $deleted_entries;
        }

        $this->get_library()->setUseBatch(false);

        // Process Batch Response
        foreach ($batch_result as $key => $api_entry) {
            $original_id = str_replace('response-', '', $key);
            do_action('useyourdrive_log_event', 'useyourdrive_deleted_entry', $deleted_entries[$original_id], ['to_trash' => $this->get_processor()->get_shortcode_option('deletetotrash')]);
            $this->get_cache()->remove_from_cache($original_id, 'deleted');
        }

        // Send email if needed
        if ('1' === $this->get_processor()->get_shortcode_option('notificationdeletion')) {
            $this->get_processor()->send_notification_email('deletion_multiple', $deleted_entries);
        }

        // Clear Cached Requests
        CacheRequest::clear_request_cache();

        return $deleted_entries;
    }

    // Rename entry from Google Drive

    public function rename_entry($new_filename = null)
    {
        if ('1' === $this->get_processor()->get_shortcode_option('demo')) {
            return new \WP_Error('broke', esc_html__('Failed to rename entry', 'wpcloudplugins'));
        }

        if (null === $new_filename && '1' === $this->get_processor()->get_shortcode_option('debug')) {
            return new \WP_Error('broke', esc_html__('No new name set', 'wpcloudplugins'));
        }

        // Get entry meta data
        $cachedentry = $this->get_cache()->is_cached($this->get_processor()->get_requested_entry());

        if (false === $cachedentry) {
            $cachedentry = $this->get_entry($this->get_processor()->get_requested_entry());
            if (false === $cachedentry) {
                if ('1' === $this->get_processor()->get_shortcode_option('debug')) {
                    return new \WP_Error('broke', esc_html__('Invalid entry', 'wpcloudplugins'));
                }

                return new \WP_Error('broke', esc_html__('Failed to rename entry', 'wpcloudplugins'));
            }
        }

        // Check if user is allowed to delete from this dir
        if (!$cachedentry->is_in_folder($this->get_processor()->get_last_folder())) {
            return new \WP_Error('broke', esc_html__('You are not authorized to rename files in this directory', 'wpcloudplugins'));
        }

        $entry = $cachedentry->get_entry();

        // Check user permission
        if (!$entry->get_permission('canrename')) {
            return new \WP_Error('broke', esc_html__('You are not authorized to rename this file or folder', 'wpcloudplugins'));
        }

        // Check if entry is allowed
        if (!$this->get_processor()->_is_entry_authorized($cachedentry)) {
            return new \WP_Error('broke', esc_html__('You are not authorized to rename this file or folder', 'wpcloudplugins'));
        }

        if (($entry->is_dir()) && (false === $this->get_processor()->get_user()->can_rename_folders())) {
            return new \WP_Error('broke', esc_html__('You are not authorized to rename folder', 'wpcloudplugins'));
        }

        if (($entry->is_file()) && (false === $this->get_processor()->get_user()->can_rename_files())) {
            return new \WP_Error('broke', esc_html__('You are not authorized to rename this file', 'wpcloudplugins'));
        }

        $extension = $entry->get_extension();
        $name = (!empty($extension)) ? $new_filename.'.'.$extension : $new_filename;
        $updateentry = new \UYDGoogle_Service_Drive_DriveFile();
        $updateentry->setName($name);

        try {
            $renamed_entry = $this->update_entry($entry->get_id(), $updateentry);

            if (false !== $renamed_entry && null !== $renamed_entry) {
                $this->get_cache()->update_cache();
            }

            do_action('useyourdrive_log_event', 'useyourdrive_renamed_entry', $renamed_entry, ['old_name' => $entry->get_name()]);
        } catch (\Exception $ex) {
            error_log('[WP Cloud Plugin message]: '.sprintf('API Error on line %s: %s', __LINE__, $ex->getMessage()));

            if ('1' === $this->get_processor()->get_shortcode_option('debug')) {
                return new \WP_Error('broke', $ex->getMessage());
            }

            return new \WP_Error('broke', esc_html__('Failed to rename entry', 'wpcloudplugins'));
        }

        return $renamed_entry;
    }

    // Copy entry

    public function copy_entry($cached_entry = null, $cached_parent = null, $new_name = null)
    {
        if (null === $cached_entry) {
            $cached_entry = $this->get_entry($this->get_processor()->get_requested_entry());
        }

        if (null === $cached_parent) {
            $cached_parent = $this->get_entry($this->get_processor()->get_last_folder());
        }

        if (false === $cached_entry) {
            $message = '[WP Cloud Plugin message]: '.sprintf('Failed to copy the file %s.', '');

            error_log($message);

            return new \WP_Error('broke', $message);
        }

        // Add the new Parent to the Entry
        $update_params = [
            'fields' => $this->apifilefields,

            'supportsAllDrives' => true,
            'addParents' => $cached_parent->get_id(),
        ];

        // Create an the entry for Patch
        $patch_entry = new \UYDGoogle_Service_Drive_DriveFile();

        $entry = $cached_entry->get_entry();

        if (($entry->is_dir()) && (false === $this->get_processor()->get_user()->can_copy_folders())) {
            $message = '[WP Cloud Plugin message]: '.sprintf('Failed to move %s as user is not allowed to move folders.', $cached_parent->get_path($this->get_processor()->get_root_folder()));

            error_log($message);

            return new \WP_Error('broke', $message);
        }

        if (($entry->is_file()) && (false === $this->get_processor()->get_user()->can_copy_files())) {
            $message = '[WP Cloud Plugin message]: '.sprintf('Failed to copy %s as user is not allowed to copy files.', $cached_parent->get_path($this->get_processor()->get_root_folder()));

            error_log($message);

            return new \WP_Error('broke', $message);
        }

        if ('1' === $this->get_processor()->get_shortcode_option('demo')) {
            $message = '[WP Cloud Plugin message]: '.sprintf('Failed to copy the file %s.', $cached_entry->get_path($this->get_processor()->get_root_folder()));

            error_log($message);

            return new \WP_Error('broke', $message);
        }

        // Check if user is allowed to copy from this dir
        if (!$cached_entry->is_in_folder($cached_parent->get_id())) {
            $message = '[WP Cloud Plugin message]: '.sprintf('Failed to copy %s as user is not allowed to copy items in this directory.', $cached_parent->get_path($this->get_processor()->get_root_folder()));

            error_log($message);

            return new \WP_Error('broke', $message);
        }

        $extension = $entry->get_extension();
        $name = (!empty($extension)) ? $new_name.'.'.$extension : $new_name;
        $updateentry = new \UYDGoogle_Service_Drive_DriveFile();
        $updateentry->setName($name);

        try {
            $params = [
                'fields' => 'id',

                'supportsAllDrives' => true,
            ];
            $result = $this->get_app()->get_drive()->files->copy($entry->get_id(), $updateentry, $params);
            $api_entry = $this->get_app()->get_drive()->files->get($result->getId(), ['fields' => $this->apifilefields, 'supportsAllDrives' => true]);

            $new_entry = new Entry($api_entry);
            $copied_entry = $this->get_cache()->add_to_cache($new_entry);

            if (false !== $copied_entry && null !== $copied_entry) {
                $this->get_cache()->update_cache();
            }

            do_action('useyourdrive_log_event', 'useyourdrive_copied_entry', $copied_entry, ['original' => $entry->get_name()]);
        } catch (\Exception $ex) {
            error_log('[WP Cloud Plugin message]: '.sprintf('API Error on line %s: %s', __LINE__, $ex->getMessage()));

            if ('1' === $this->get_processor()->get_shortcode_option('debug')) {
                return new \WP_Error('broke', $ex->getMessage());
            }

            return new \WP_Error('broke', esc_html__('Failed to copy entry', 'wpcloudplugins'));
        }

        // Clear Cached Requests
        CacheRequest::clear_local_cache_for_shortcode($this->get_processor()->get_current_account()->get_id(), $this->get_processor()->get_listtoken());

        return $copied_entry;
    }

    // Move entry Google Drive

    public function move_entries($entries, $target, $copy = false)
    {
        $entries_to_move = [];

        $cached_target = $this->get_entry($target);
        $cached_current_folder = $this->get_entry($this->get_processor()->get_last_folder());

        if (false === $cached_target) {
            error_log('[WP Cloud Plugin message]: '.sprintf('Failed to move as target folder %s is not found.', $target));

            return $entries_to_move;
        }

        // Add the new Parent to the Entry
        $update_params = [
            'fields' => $this->apifilefields,

            'supportsAllDrives' => true,
            'addParents' => $cached_target->get_id(),
        ];

        // Remove old Parent
        if (false === $copy) {
            $update_params['removeParents'] = $cached_current_folder->get_id();
        }

        // Create an the entry for Patch
        $patch_entry = new \UYDGoogle_Service_Drive_DriveFile();

        $batch = new \UYDGoogle_Http_Batch($this->get_library());

        foreach ($entries as $entry_id) {
            $this->get_library()->setUseBatch(false);
            $cached_entry = $this->get_entry($entry_id);
            $this->get_library()->setUseBatch(true);

            if (false === $cached_entry) {
                continue;
            }

            $entry = $cached_entry->get_entry();

            if (($entry->is_dir()) && (false === $this->get_processor()->get_user()->can_move_folders())) {
                error_log('[WP Cloud Plugin message]: '.sprintf('Failed to move %s as user is not allowed to move folders.', $cached_entry->get_path($this->get_processor()->get_root_folder())));
                $entries_to_move[$cached_entry->get_id()] = false;

                continue;
            }

            if (($entry->is_file()) && (false === $this->get_processor()->get_user()->can_move_files())) {
                error_log('[WP Cloud Plugin message]: '.sprintf('Failed to move %s as user is not allowed to move files.', $cached_entry->get_path($this->get_processor()->get_root_folder())));
                $entries_to_move[$cached_entry->get_id()] = false;

                continue;
            }

            if ('1' === $this->get_processor()->get_shortcode_option('demo')) {
                $entries_to_move[$cached_entry->get_id()] = false;

                continue;
            }

            // Check if user is allowed to delete from this dir
            if (!$cached_entry->is_in_folder($cached_current_folder->get_id())) {
                error_log('[WP Cloud Plugin message]: '.sprintf('Failed to move %s as user is not allowed to move items in this directory.', $cached_target->get_path($this->get_processor()->get_root_folder())));
                $entries_to_move[$cached_entry->get_id()] = false;

                continue;
            }

            // Check user permission
            if (!$entry->get_permission('canmove')) {
                error_log('[WP Cloud Plugin message]: '.sprintf('Failed to move %s as the sharing permissions on it prevent this.', $cached_entry->get_path($this->get_processor()->get_root_folder())));
                $entries_to_move[$cached_entry->get_id()] = false;

                continue;
            }

            $entries_to_move[$cached_entry->get_id()] = false; // Set after Batch Request $cached_entry;

            $batch->add($this->get_app()->get_drive()->files->update($entry_id, $patch_entry, $update_params), $entry_id);
        }

        // Execute the Batch Call
        try {
            if (defined('GOOGLE_API_BATCH')) {
                usleep(50000);
            } else {
                define('GOOGLE_API_BATCH', true);
            }
            $batch_result = $batch->execute();

            $this->get_library()->setUseBatch(false);

            foreach ($batch_result as $key => $api_entry) {
                $updated_entry = new Entry($api_entry);

                if (!$copy) {
                    $this->get_cache()->remove_from_cache($updated_entry->get_id(), 'moved');
                }

                $cached_updated_entry = $this->get_cache()->add_to_cache($updated_entry);
                $entries_to_move[$cached_updated_entry->get_id()] = $cached_updated_entry;

                do_action('useyourdrive_log_event', 'useyourdrive_moved_entry', $cached_updated_entry);
            }
        } catch (\Exception $ex) {
            error_log('[WP Cloud Plugin message]: '.sprintf('API Error on line %s: %s', __LINE__, $ex->getMessage()));

            return $entries_to_move;
        }

        // Clear Cached Requests
        CacheRequest::clear_local_cache_for_shortcode($this->get_processor()->get_current_account()->get_id(), $this->get_processor()->get_listtoken());

        return $entries_to_move;
    }

    // Edit descriptions entry from Google Drive

    public function update_description($new_description = null)
    {
        if (null === $new_description && '1' === $this->get_processor()->get_shortcode_option('debug')) {
            return new \WP_Error('broke', esc_html__('No new description set', 'wpcloudplugins'));
        }

        // Get entry meta data
        $cachedentry = $this->get_cache()->is_cached($this->get_processor()->get_requested_entry());

        if (false === $cachedentry) {
            $cachedentry = $this->get_entry($this->get_processor()->get_requested_entry());
            if (false === $cachedentry) {
                if ('1' === $this->get_processor()->get_shortcode_option('debug')) {
                    return new \WP_Error('broke', esc_html__('Invalid entry', 'wpcloudplugins'));
                }

                return new \WP_Error('broke', esc_html__('Failed to edit entry', 'wpcloudplugins'));

                return new \WP_Error('broke', esc_html__('Failed to edit entry', 'wpcloudplugins'));
            }
        }

        // Check if user is allowed to delete from this dir
        if (!$cachedentry->is_in_folder($this->get_processor()->get_last_folder())) {
            return new \WP_Error('broke', esc_html__('You are not authorized to edit files in this directory', 'wpcloudplugins'));
        }

        $entry = $cachedentry->get_entry();

        // Check user permission
        if (!$entry->get_permission('canrename')) {
            return new \WP_Error('broke', esc_html__('You are not authorized to edit this file or folder', 'wpcloudplugins'));
        }

        // Check if entry is allowed
        if (!$this->get_processor()->_is_entry_authorized($cachedentry)) {
            return new \WP_Error('broke', esc_html__('You are not authorized to edit this file or folder', 'wpcloudplugins'));
        }

        // Create an the entry for Patch
        $updated_entry = new \UYDGoogle_Service_Drive_DriveFile();
        $updated_entry->setDescription($new_description);

        try {
            $edited_entry = $this->update_entry($entry->get_id(), $updated_entry);

            do_action('useyourdrive_log_event', 'useyourdrive_updated_metadata', $edited_entry, ['metadata_field' => 'Description']);
        } catch (\Exception $ex) {
            error_log('[WP Cloud Plugin message]: '.sprintf('API Error on line %s: %s', __LINE__, $ex->getMessage()));

            if ('1' === $this->get_processor()->get_shortcode_option('debug')) {
                return new \WP_Error('broke', $ex->getMessage());
            }

            return new \WP_Error('broke', esc_html__('Failed to edit entry', 'wpcloudplugins'));
        }

        return $edited_entry->get_entry()->get_description();
    }

    // Update entry from Google Drive

    public function update_entry($entry_id, \UYDGoogle_Service_Drive_DriveFile $entry, $_params = [])
    {
        $params = array_merge([
            'fields' => 'id', //$this->apifilefields,

            'supportsAllDrives' => true,
        ], $_params);

        try {
            $result = $this->get_app()->get_drive()->files->update($entry_id, $entry, $params);
            $api_entry = $this->get_app()->get_drive()->files->get($entry_id, ['fields' => $this->apifilefields, 'supportsAllDrives' => true]);
            $entry = new Entry($api_entry);
            $cachedentry = $this->get_cache()->add_to_cache($entry);
        } catch (\Exception $ex) {
            error_log('[WP Cloud Plugin message]: '.sprintf('API Error on line %s: %s', __LINE__, $ex->getMessage()));

            return false;
        }

        return $cachedentry;
    }

    // Add entry to Google Drive

    public function add_entry($new_name, $mimetype)
    {
        if ('1' === $this->get_processor()->get_shortcode_option('demo')) {
            return new \WP_Error('broke', esc_html__('Failed to add entry', 'wpcloudplugins'));
        }

        if (null === $new_name && '1' === $this->get_processor()->get_shortcode_option('debug')) {
            return new \WP_Error('broke', esc_html__('No new name set', 'wpcloudplugins'));
        }

        // Get entry meta data of current folder
        $cachedentry = $this->get_cache()->is_cached($this->get_processor()->get_last_folder());

        if (false === $cachedentry) {
            $cachedentry = $this->get_entry($this->get_processor()->get_last_folder());
            if (false === $cachedentry) {
                if ('1' === $this->get_processor()->get_shortcode_option('debug')) {
                    return new \WP_Error('broke', esc_html__('Invalid entry', 'wpcloudplugins'));
                }

                return new \WP_Error('broke', esc_html__('Failed to add entry', 'wpcloudplugins'));

                return new \WP_Error('broke', esc_html__('Failed to add entry', 'wpcloudplugins'));
            }
        }

        if (!$this->get_processor()->_is_entry_authorized($cachedentry)) {
            return new \WP_Error('broke', esc_html__('You are not authorized to add entry in this directory', 'wpcloudplugins'));
        }

        $currentfolder = $cachedentry->get_entry();

        // Check user permission
        if (!$currentfolder->get_permission('canadd')) {
            return new \WP_Error('broke', esc_html__('You are not authorized to add an entry', 'wpcloudplugins'));
        }

        $_new_entry = new \UYDGoogle_Service_Drive_DriveFile();
        $_new_entry->setName($new_name);
        $_new_entry->setMimeType($mimetype);
        $_new_entry->setParents([$currentfolder->get_id()]);

        try {
            $api_entry = $this->get_app()->get_drive()->files->create($_new_entry, ['fields' => $this->apifilefields,  'supportsAllDrives' => true, 'enforceSingleParent' => true]);

            if (null !== $api_entry) {
                // Add new file to our Cache
                $newentry = new Entry($api_entry);
                $new_cached_entry = $this->get_cache()->add_to_cache($newentry);

                do_action('useyourdrive_log_event', 'useyourdrive_created_entry', $new_cached_entry);
            }
        } catch (\Exception $ex) {
            error_log('[WP Cloud Plugin message]: '.sprintf('API Error on line %s: %s', __LINE__, $ex->getMessage()));

            if ('1' === $this->get_processor()->get_shortcode_option('debug')) {
                return new \WP_Error('broke', $ex->getMessage());
            }

            return new \WP_Error('broke', esc_html__('Failed to add entry', 'wpcloudplugins'));
        }

        return $newentry;
    }

    public function copy_folder_recursive(CacheNode $templatefolder, CacheNode $newfolder)
    {
        if (empty($templatefolder) || empty($newfolder)) {
            return false;
        }

        if (false === $templatefolder->has_children()) {
            return false;
        }

        $template_folder_children = $templatefolder->get_children();

        $batch = new \UYDGoogle_Http_Batch($this->get_library());
        $new_entries = false;

        foreach ($template_folder_children as $cached_child) {
            $this->get_library()->setUseBatch(false);
            $child = $cached_child->get_entry();

            $entry_exists = $this->get_cache()->get_node_by_name($child->get_name(), $newfolder);
            if (false !== $entry_exists) {
                continue;
            }

            $new_entries = true;

            $this->get_library()->setUseBatch(true);
            if ($child->is_dir()) {
                // Create child folder in user folder
                $newchildfolder = new \UYDGoogle_Service_Drive_DriveFile();
                $newchildfolder->setName($child->get_name());
                $newchildfolder->setMimeType('application/vnd.google-apps.folder');
                $newchildfolder->setParents([$newfolder->get_id()]);

                $batch->add($this->get_app()->get_drive()->files->create($newchildfolder, ['fields' => $this->apifilefields,  'supportsAllDrives' => true, 'enforceSingleParent' => true]), $child->get_id());
            } else {
                // Copy file to new folder
                $newfile = new \UYDGoogle_Service_Drive_DriveFile();
                $newfile->setName($child->get_name());
                $newfile->setParents([$newfolder->get_id()]);

                $batch->add($this->get_app()->get_drive()->files->copy($child->get_id(), $newfile, ['fields' => $this->apifilefields,  'supportsAllDrives' => true, 'enforceSingleParent' => true]), $child->get_id());
            }
        }

        if (false === $new_entries) {
            return true;
        }

        // Execute the Batch Call
        try {
            if (defined('GOOGLE_API_BATCH')) {
                usleep(50000);
            } else {
                define('GOOGLE_API_BATCH', true);
            }
            $batch_result = $batch->execute();
        } catch (\Exception $ex) {
            error_log('[WP Cloud Plugin message]: '.sprintf('API Error on line %s: %s', __LINE__, $ex->getMessage()));

            return false;
        }

        $this->get_library()->setUseBatch(false);

        // Process the result

        foreach ($batch_result as $key => $api_childentry) {
            $newchildentry = new Entry($api_childentry);
            $cachednewchildentry = $this->get_cache()->add_to_cache($newchildentry);
            $original_id = str_replace('response-', '', $key);
            $template_entry = $template_folder_children[$original_id];

            if ($template_entry->get_entry()->is_dir()) {
                // Copy contents of child folder to new create child user folder
                $cached_child_template_folder = $this->get_folder($template_entry->get_id(), false);
                $this->copy_folder_recursive($cached_child_template_folder['folder'], $cachednewchildentry);
            }
        }

        return true;
    }

    /**
     * Create thumbnails for Google docs which need a accesstoken.
     */
    public function build_thumbnail()
    {
        $cached = $this->get_cache()->is_cached($this->get_processor()->get_requested_entry());

        if (false === $cached) {
            $cachedentry = $this->get_entry($this->get_processor()->get_requested_entry());
        } else {
            $cachedentry = $cached;
        }

        if (false === $cachedentry) {
            exit();
        }

        // Check if entry is allowed
        if (!$this->get_processor()->_is_entry_authorized($cachedentry)) {
            exit();
        }

        $thumbnail_original = $cachedentry->get_entry()->get_thumbnail_original();
        if (empty($thumbnail_original)) {
            header('Location: '.$cachedentry->get_entry()->get_default_thumbnail_icon());

            exit();
        }

        // Set the thumbnail attributes & file
        switch ($_REQUEST['s']) {
            case 'icon':
                $thumbnail_attributes = '=h16-c-nu';

                break;

            case 'small':
                $thumbnail_attributes = '=w400-h300-p-k';

                break;

            case 'cropped':
                $thumbnail_attributes = 'w500-h375-c-nu';

                break;

            case 'large':
                $thumbnail_attributes = '=s0';

                break;
        }

        // Check if file already exists
        $thumbnail_file = $cachedentry->get_id().$thumbnail_attributes.'.png';
        if (file_exists(USEYOURDRIVE_CACHEDIR.'/thumbnails/'.$thumbnail_file) && (filemtime(USEYOURDRIVE_CACHEDIR.'/thumbnails/'.$thumbnail_file) === strtotime($cachedentry->get_entry()->get_last_edited()))) {
            $url = USEYOURDRIVE_CACHEURL.'/thumbnails/'.$thumbnail_file;

            // Update the cached node
            switch ($_REQUEST['s']) {
                case 'icon':
                    $cachedentry->get_entry()->set_thumbnail_icon($url);
                    // no break
                case 'small':
                    $cachedentry->get_entry()->set_thumbnail_small($url);

                    break;

                case 'cropped':
                    $cachedentry->get_entry()->set_thumbnail_small_cropped($url);

                    break;

                case 'large':
                    $cachedentry->get_entry()->set_thumbnail_large($url);
                    $thumbnail_attributes = '=s0';

                    break;
            }
            $this->get_cache()->set_updated(true);
            $this->get_cache()->update_cache();

            header('Location: '.$url);

            exit();
        }

        // Build the thumbnail URL where we fetch the thumbnail

        $downloadlink = $cachedentry->get_entry()->get_thumbnail_original();
        $downloadlink = str_replace('=s220', $thumbnail_attributes, $downloadlink);

        // Do the request
        try {
            $token = json_decode($this->get_library()->getAccessToken());
            $request = new \UYDGoogle_Http_Request($downloadlink, 'GET');
            $this->get_library()->getIo()->setOptions([CURLOPT_SSL_VERIFYPEER => false, CURLOPT_FOLLOWLOCATION => true]);
            $httpRequest = $this->get_library()->getAuth()->authenticatedRequest($request);

            // Process the reponse
            $headers = $httpRequest->getResponseHeaders();

            if (!file_exists(USEYOURDRIVE_CACHEDIR.'/thumbnails')) {
                @mkdir(USEYOURDRIVE_CACHEDIR.'/thumbnails', 0755);
            }

            if (!is_writable(USEYOURDRIVE_CACHEDIR.'/thumbnails')) {
                @chmod(USEYOURDRIVE_CACHEDIR.'/thumbnails', 0755);
            }

            // Save the thumbnail locally
            @file_put_contents(USEYOURDRIVE_CACHEDIR.'/thumbnails/'.$thumbnail_file, $httpRequest->getResponseBody()); //New SDK: $response->getBody()
            touch(USEYOURDRIVE_CACHEDIR.'/thumbnails/'.$thumbnail_file, strtotime($cachedentry->get_entry()->get_last_edited()));
            $url = USEYOURDRIVE_CACHEURL.'/thumbnails/'.$thumbnail_file;

            // Update the cached node
            switch ($_REQUEST['s']) {
                case 'icon':
                    $cachedentry->get_entry()->set_thumbnail_icon($url);
                    // no break
                case 'small':
                    $cachedentry->get_entry()->set_thumbnail_small($url);

                    break;

                case 'cropped':
                    $cachedentry->get_entry()->set_thumbnail_small_cropped($url);

                    break;

                case 'large':
                    $cachedentry->get_entry()->set_thumbnail_large($url);
                    $thumbnail_attributes = '=s0';

                    break;
            }
            $this->get_cache()->set_updated(true);
            header('Location: '.$url);
        } catch (\Exception $ex) {
            error_log('[WP Cloud Plugin message]: '.sprintf('API Error on line %s: %s', __LINE__, $ex->getMessage()));
        }

        exit();
    }

    public function get_folder_thumbnails()
    {
        $thumbnails = [];
        $maximages = 3;
        $target_height = $this->get_processor()->get_shortcode_option('targetheight');
        $target_width = round($target_height * (4 / 3));

        $folder = $this->get_folder();

        if (false === $folder) {
            return;
        }

        $all_subfolders = $folder['folder']->get_all_sub_folders();
        $folders_id = [];

        foreach ($all_subfolders as $subfolder) {
            $subfolder_entry = $subfolder->get_entry();
            $folder_thumbnails = $subfolder_entry->get_folder_thumbnails();

            // 1: First if the cache still has valid thumbnails available
            if (isset($folder_thumbnails['expires']) && $folder_thumbnails['expires'] > time()) {
                $iimages = 1;
                $thumbnails_html = '';

                foreach ($folder_thumbnails['thumbs'] as $folder_thumbnail) {
                    $thumb_url = $subfolder_entry->get_thumbnail_with_size('h'.round($target_height * 1).'-w'.round($target_width * 1).'-c-nu', $folder_thumbnail);
                    $thumbnails_html .= "<div class='folder-thumb thumb{$iimages}' style='width:".$target_width.'px;height:'.$target_height.'px;background-image: url('.$thumb_url.")'></div>";
                    ++$iimages;
                }
                $thumbnails[$subfolder->get_id()] = $thumbnails_html;
            } else {
                $cachedentry = $this->get_cache()->is_cached($subfolder->get_id(), 'id', false);
                // 2: Check if we can use the content of the folder itself
                if (false !== $cachedentry && !$cachedentry->is_expired()) {
                    $iimages = 1;
                    $thumbnails_html = '';

                    $children = $subfolder->get_children();
                    foreach ($children as $cached_child) {
                        $entry = $cached_child->get_entry();
                        if ($iimages > $maximages) {
                            break;
                        }

                        if (!$entry->has_own_thumbnail() || !$entry->is_file()) {
                            continue;
                        }

                        $thumbnail = $entry->get_thumbnail_with_size('h'.round($target_height * 1).'-w'.round($target_width * 1).'-c-nu');
                        $thumbnails_html .= "<div class='folder-thumb thumb{$iimages}' style='width:".$target_width.'px;height:'.$target_height.'px;background-image: url('.$thumbnail.")'></div>";
                        ++$iimages;
                    }

                    $thumbnails[$subfolder->get_id()] = $thumbnails_html;
                } else {
                    // 3: If we don't have thumbnails available, get them
                    $folders_id[] = $subfolder->get_id();
                }
            }
        }

        if (count($folders_id) > 0) {
            // Find all items containing query
            $params = [
                'fields' => 'files(id,thumbnailLink,parents),nextPageToken',
                'pageSize' => $maximages,
                'includeItemsFromAllDrives' => true,
                'supportsAllDrives' => true,
            ];

            $this->get_library()->setUseBatch(true);
            $batch = new \UYDGoogle_Http_Batch($this->get_library());

            foreach ($folders_id as $folder_id) {
                $params['q'] = "'{$folder_id}' in parents and (mimeType = 'image/gif' or mimeType = 'image/png' or mimeType = 'image/jpeg' or mimeType = 'x-ms-bmp') and trashed = false";
                $batch->add($this->get_app()->get_drive()->files->listFiles($params), $folder_id);
            }

            try {
                $batch_results = $batch->execute();
            } catch (\Exception $ex) {
                error_log('[WP Cloud Plugin message]: '.sprintf('API Error on line %s: %s', __LINE__, $ex->getMessage()));

                throw $ex;
            }
            $this->get_library()->setUseBatch(false);

            foreach ($batch_results as $batchkey => $result) {
                $folderid = str_replace('response-', '', $batchkey);
                $subfolder = $all_subfolders[$folderid];

                $images = $result->getFiles();

                if (!is_array($images)) {
                    continue;
                }

                $iimages = 1;
                $thumbnails_html = '';
                $folder_thumbs = [];

                foreach ($images as $image) {
                    $entry = new Entry($image);
                    $folder_thumbs[] = $entry->get_thumbnail_small();
                    $thumbnail = $entry->get_thumbnail_with_size('h'.round($target_height * 1).'-w'.round($target_width * 1).'-c-nu');
                    $thumbnails_html .= "<div class='folder-thumb thumb{$iimages}' style='display:none; width:".$target_width.'px;height:'.$target_height.'px;background-image: url('.$thumbnail.")'></div>";
                    ++$iimages;
                }

                $subfolder->get_entry()->set_folder_thumbnails(['expires' => time() + 1800, 'thumbs' => $folder_thumbs]);
                $thumbnails[$folderid] = $thumbnails_html;
            }

            $this->get_cache()->set_updated();
        }

        CacheRequest::clear_local_cache_for_shortcode($this->get_processor()->get_current_account()->get_id(), $this->get_processor()->get_listtoken());

        return $thumbnails;
    }

    public function preview_entry()
    {
        // Check if file is cached and still valid
        $cached = $this->get_cache()->is_cached($this->get_processor()->get_requested_entry());

        if (false === $cached) {
            $cachedentry = $this->get_entry($this->get_processor()->get_requested_entry());
        } else {
            $cachedentry = $cached;
        }

        if (false === $cachedentry) {
            exit();
        }

        //Use the orginial entry if the file/folder is a shortcut
        if ($cachedentry->is_shortcut()) {
            $original_node = $cachedentry->get_original_node();

            if (!empty($original_node)) {
                $cachedentry = $original_node;
            }
        }

        $entry = $cachedentry->get_entry();

        if (false === $entry->get_can_preview_by_cloud()) {
            exit('-1');
        }

        // get the last-modified-date of this very file
        $lastModified = strtotime($entry->get_last_edited());
        // get a unique hash of this file (etag)
        $etagFile = md5($lastModified);
        // get the HTTP_IF_MODIFIED_SINCE header if set
        $ifModifiedSince = (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false);
        // get the HTTP_IF_NONE_MATCH header if set (etag: unique file hash)
        $etagHeader = (isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : false);

        header('Last-Modified: '.gmdate('D, d M Y H:i:s', $lastModified).' GMT');
        header("Etag: {$etagFile}");
        header('Expires: '.gmdate('D, d M Y H:i:s', time() + 60 * 5).' GMT');
        header('Cache-Control: must-revalidate');

        // check if page has changed. If not, send 304 and exit
        if (false !== $cached) {
            if (@strtotime($ifModifiedSince) == $lastModified || $etagHeader == $etagFile) {
                // Send email if needed
                if ('1' === $this->get_processor()->get_shortcode_option('notificationdownload')) {
                    $this->get_processor()->send_notification_email('download', [$cachedentry]);
                }

                do_action('useyourdrive_preview', $cachedentry);

                do_action('useyourdrive_log_event', 'useyourdrive_previewed_entry', $cachedentry);

                header('HTTP/1.1 304 Not Modified');

                exit;
            }
        }

        // Check if entry is allowed
        if (!$this->get_processor()->_is_entry_authorized($cachedentry)) {
            exit();
        }

        $previewurl = $this->get_embed_url($cachedentry);

        if (false === $previewurl) {
            error_log('[WP Cloud Plugin message]: '.sprintf('Cannot generate preview/embed link on line %s', __LINE__));

            exit();
        }

        if ('0' === $this->get_processor()->get_shortcode_option('previewinline') && $this->get_processor()->get_user()->can_download()) {
            $previewurl = str_replace('preview?rm=minimal', 'view', $previewurl);

            /* View Rendering mode can cause issues with some DOCX files due to bugs in the Google Doc viewer.
              Add the following code to point the user to a different file preview */
        //     $mimetype = $cachedentry->get_entry()->get_mimetype();

        //     switch ($mimetype) {
        //        case 'application/msword':
        //        case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
        //        case 'application/vnd.google-apps.document':
        //            $previewurl = 'http://drive.google.com/open?id='.$cachedentry->get_id();

        //            break;

        //        default:
        //    }
        }

        header('Location: '.$previewurl);

        do_action('useyourdrive_preview', $cachedentry);
        do_action('useyourdrive_log_event', 'useyourdrive_previewed_entry', $cachedentry);

        exit();
    }

    public function edit_entry()
    {
        // Check if file is cached and still valid
        $cached = $this->get_cache()->is_cached($this->get_processor()->get_requested_entry());

        if (false === $cached) {
            $cachedentry = $this->get_entry($this->get_processor()->get_requested_entry());
        } else {
            $cachedentry = $cached;
        }

        if (false === $cachedentry) {
            exit();
        }

        $entry = $cachedentry->get_entry();

        if ($entry->is_dir() || false === $entry->get_can_edit_by_cloud()) {
            exit('-1');
        }

        // get the last-modified-date of this very file
        $lastModified = strtotime($entry->get_last_edited());
        // get a unique hash of this file (etag)
        $etagFile = md5($lastModified);
        // get the HTTP_IF_MODIFIED_SINCE header if set
        $ifModifiedSince = (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false);
        // get the HTTP_IF_NONE_MATCH header if set (etag: unique file hash)
        $etagHeader = (isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : false);

        header('Last-Modified: '.gmdate('D, d M Y H:i:s', $lastModified).' GMT');
        header("Etag: {$etagFile}");
        header('Expires: '.gmdate('D, d M Y H:i:s', time() + 60 * 5).' GMT');
        header('Cache-Control: must-revalidate');

        // check if page has changed. If not, send 304 and exit
        if (false !== $cached) {
            if (@strtotime($ifModifiedSince) == $lastModified || $etagHeader == $etagFile) {
                do_action('useyourdrive_edit', $cachedentry);
                do_action('useyourdrive_log_event', 'useyourdrive_edited_entry', $cachedentry);

                header('HTTP/1.1 304 Not Modified');

                exit;
            }
        }

        $edit_link = $this->get_edit_url($cachedentry);

        if (empty($edit_link)) {
            error_log('[WP Cloud Plugin message]: '.sprintf('Cannot create a editable link %s', __LINE__));

            exit();
        }

        do_action('useyourdrive_edit', $cachedentry);
        do_action('useyourdrive_log_event', 'useyourdrive_edited_entry', $cachedentry);

        header('Location: '.$edit_link);

        exit();
    }

    // Download file

    public function download_entry()
    {
        // Check if file is cached and still valid
        $cached = $this->get_cache()->is_cached($this->get_processor()->get_requested_entry());

        if (false === $cached) {
            $cachedentry = $this->get_entry($this->get_processor()->get_requested_entry());
        } else {
            $cachedentry = $cached;
        }

        if (false === $cachedentry) {
            exit();
        }

        $entry = $cachedentry->get_entry();

        // get the last-modified-date of this very file
        $lastModified = strtotime($entry->get_last_edited());
        // get a unique hash of this file (etag)
        $etagFile = md5($lastModified);
        // get the HTTP_IF_MODIFIED_SINCE header if set
        $ifModifiedSince = (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false);
        // get the HTTP_IF_NONE_MATCH header if set (etag: unique file hash)
        $etagHeader = (isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : false);

        header('Last-Modified: '.gmdate('D, d M Y H:i:s', $lastModified).' GMT');
        header("Etag: {$etagFile}");
        header('Expires: '.gmdate('D, d M Y H:i:s', time() + 60 * 5).' GMT');
        header('Cache-Control: must-revalidate');

        // check if page has changed. If not, send 304 and exit
        if (false !== $cached) {
            if (@strtotime($ifModifiedSince) == $lastModified || $etagHeader == $etagFile) {
                // Send email if needed
                if ('1' === $this->get_processor()->get_shortcode_option('notificationdownload')) {
                    $this->get_processor()->send_notification_email('download', [$cachedentry]);
                }

                do_action('useyourdrive_download', $cachedentry);

                $event_type = (isset($_REQUEST['action']) && 'useyourdrive-stream' === $_REQUEST['action']) ? 'useyourdrive_streamed_entry' : 'useyourdrive_downloaded_entry';
                do_action('useyourdrive_log_event', $event_type, $cachedentry);

                header('HTTP/1.1 304 Not Modified');

                exit;
            }
        }

        // Check if entry is allowed
        if (!$this->get_processor()->_is_entry_authorized($cachedentry)) {
            exit();
        }

        $download = new Download($cachedentry, $this->get_processor());

        $download->start_download();

        exit();
    }

    public function stream_entry()
    {
        // Check if file is cached and still valid
        $cached = $this->get_cache()->is_cached($this->get_processor()->get_requested_entry());

        if (false === $cached) {
            $cachedentry = $this->get_entry($this->get_processor()->get_requested_entry());
        } else {
            $cachedentry = $cached;
        }

        if (false === $cachedentry) {
            exit();
        }

        $entry = $cachedentry->get_entry();

        $extension = $entry->get_extension();
        $allowedextensions = ['mp4', 'm4v', 'ogg', 'ogv', 'webmv', 'mp3', 'm4a', 'oga', 'wav', 'webm', 'vtt'];

        if (empty($extension) || !in_array($extension, $allowedextensions)) {
            exit();
        }

        if ('vtt' === $extension) {
            // Download Captions directly
            $download = new Download($cachedentry, $this->get_processor(), 'default', true);
        } else {
            $download = new Download($cachedentry, $this->get_processor());
        }

        $download->start_download();

        exit();
    }

    public function get_embed_url(CacheNode $cachedentry)
    {
        $entry = $cachedentry->get_entry();
        $mimetype = $entry->get_mimetype();

        // Check the permissions and set it if possible
        if (!$this->has_permission($cachedentry)) {
            $this->set_permission($cachedentry);
        }

        $arguments = 'preview?rm=demo';

        switch ($mimetype) {
            case 'application/msword':
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
            case 'application/vnd.google-apps.document':
                $arguments = 'preview?rm=minimal'; //rm=minimal&overridemobile=true'; Causing errors on iPads
                $preview = 'https://docs.google.com/document/d/'.$cachedentry->get_id().'/'.$arguments;

                break;

            case 'application/vnd.ms-excel':
            case 'application/vnd.ms-excel.sheet.macroenabled.12':
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
            case 'application/vnd.google-apps.spreadsheet':
                $preview = 'https://docs.google.com/spreadsheets/d/'.$cachedentry->get_id().'/'.$arguments;

                break;

            case 'application/vnd.ms-powerpoint':
            case 'application/vnd.openxmlformats-officedocument.presentationml.slideshow':
            case 'application/vnd.google-apps.presentation':
                $preview = 'https://docs.google.com/presentation/d/'.$cachedentry->get_id().'/'.$arguments;

                break;

            case 'application/vnd.google-apps.folder':
                $preview = 'https://drive.google.com/open?id='.$cachedentry->get_id();

                break;

            case 'application/vnd.google-apps.drawing':
                $preview = 'https://docs.google.com/drawings/d/'.$cachedentry->get_id();

                break;

            case 'application/vnd.google-apps.form':
                $preview = 'https://docs.google.com/forms/d/'.$cachedentry->get_id().'/viewform';

                break;

            default:
                $preview = 'https://drive.google.com/file/d/'.$cachedentry->get_id().'/preview?rm=minimal';

                break;
        }

        // Add Resources key to give permission to access the item via a shared link
        if ($entry->has_resourcekey()) {
            $preview .= "&resourcekey={$entry->get_resourcekey()}";
        }

        // For images, just return the actual file
        if (in_array($cachedentry->get_entry()->get_extension(), ['jpg', 'jpeg', 'gif', 'png'])) {
            $preview = $cachedentry->get_entry()->get_thumbnail_large();
        }

        return apply_filters('useyourdrive_set_embed_url', $preview, $cachedentry);
    }

    public function get_edit_url(CacheNode $cachedentry)
    {
        $entry = $cachedentry->get_entry();
        $mimetype = $entry->get_mimetype();

        // Check the permissions and set it if possible
        if (!$this->has_permission($cachedentry, ['writer'])) {
            $this->set_permission($cachedentry, 'writer');
        }
        $arguments = 'edit?usp=drivesdk';

        switch ($mimetype) {
            case 'application/msword':
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
            case 'application/vnd.google-apps.document':
                $edit_url = 'https://docs.google.com/document/d/'.$cachedentry->get_id().'/'.$arguments;

                break;

            case 'application/vnd.ms-excel':
            case 'application/vnd.ms-excel.sheet.macroenabled.12':
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
            case 'application/vnd.google-apps.spreadsheet':
                $edit_url = 'https://docs.google.com/spreadsheets/d/'.$cachedentry->get_id().'/'.$arguments;

                break;

            case 'application/vnd.ms-powerpoint':
            case 'application/vnd.openxmlformats-officedocument.presentationml.slideshow':
            case 'application/vnd.google-apps.presentation':
                $edit_url = 'https://docs.google.com/presentation/d/'.$cachedentry->get_id().'/'.$arguments;

                break;

            case 'application/vnd.google-apps.drawing':
                $edit_url = 'https://docs.google.com/drawings/d/'.$cachedentry->get_id().'/'.$arguments;

                break;

            default:
                $edit_url = false;

                break;
        }

        // Add Resources key to give permission to access the item via a shared link
        if ($entry->has_resourcekey()) {
            $edit_url .= "&resourcekey={$entry->get_resourcekey()}";
        }

        return apply_filters('useyourdrive_set_edit_url', $edit_url, $cachedentry);
    }

    public function has_permission(CacheNode $cachedentry, $permission_role = ['reader', 'writer'], $force_update = false)
    {
        $entry = $cachedentry->get_entry();
        $permission_type = ('' === $this->get_processor()->get_setting('permission_domain')) ? 'anyone' : 'domain';
        $permission_domain = ('' === $this->get_processor()->get_setting('permission_domain')) ? null : $this->get_processor()->get_setting('permission_domain');

        $users = $entry->get_permission('users');

        // If the permissions are not yet set, grab them via the API
        if (empty($users) && $cachedentry->get_entry()->get_permission('canshare') || true === $force_update) {
            $users = [];

            $params = [
                'fields' => 'kind,nextPageToken,permissions(kind,type,role,domain,permissionDetails(permissionType,role))',
                'pageSize' => 100,
                'supportsAllDrives' => true,
            ];

            $nextpagetoken = null;
            // Get all files in folder
            while ($nextpagetoken || null === $nextpagetoken) {
                try {
                    if (null !== $nextpagetoken) {
                        $params['pageToken'] = $nextpagetoken;
                    }

                    $more_permissions = $this->get_app()->get_drive()->permissions->listPermissions($entry->get_id(), $params);
                    $users = array_merge($users, $more_permissions->getPermissions());
                    $nextpagetoken = (null !== $more_permissions->getNextPageToken()) ? $more_permissions->getNextPageToken() : false;
                } catch (\Exception $ex) {
                    error_log('[WP Cloud Plugin message]: '.sprintf('API Error on line %s: %s', __LINE__, $ex->getMessage()));

                    return false;
                }
            }

            $entry_permission = [];
            foreach ($users as $user) {
                $entry_permission[$user->getId()] = ['type' => $user->getType(), 'role' => $user->getRole(), 'domain' => $user->getDomain()];
            }
            $entry->set_permissions_by_key('users', $entry_permission);
            $this->get_cache()->add_to_cache($entry);
        }

        $users = $entry->get_permission('users');

        if (count($users) > 0) {
            foreach ($users as $user) {
                if (($user['type'] === $permission_type) && (in_array($user['role'], $permission_role)) && ($user['domain'] === $permission_domain)) {
                    return true;
                }
            }
        }

        /* For shared files not owned by account, the sharing permissions cannot be viewed or set.
         * In that case, just check if the file is public shared
         */
        if (in_array('reader', $permission_role)) {
            $check_url = 'https://drive.google.com/file/d/'.$cachedentry->get_id().'/view';

            // Add Resources key to give permission to access the item via a shared link
            if ($entry->has_resourcekey()) {
                $check_url .= "&resourcekey={$entry->get_resourcekey()}";
            }

            $request = new \UYDGoogle_Http_Request($check_url, 'GET');
            $this->get_library()->getIo()->setOptions([CURLOPT_FOLLOWLOCATION => 0]);
            $httpRequest = $this->get_library()->getIo()->makeRequest($request);
            curl_close($this->get_library()->getIo()->getHandler());

            if (200 == $httpRequest->getResponseHttpCode()) {
                $users['anyoneWithLink'] = ['type' => 'anyone', 'role' => 'reader', 'domain' => $permission_domain];
                $entry->set_permissions_by_key('users', $users);
                $this->get_cache()->add_to_cache($entry);

                return true;
            }
        }

        return false;
    }

    public function set_permission(CacheNode $cachedentry, $permission_role = 'reader')
    {
        $permission_type = ('' === $this->get_processor()->get_setting('permission_domain')) ? 'anyone' : 'domain';
        $permission_domain = ('' === $this->get_processor()->get_setting('permission_domain')) ? null : $this->get_processor()->get_setting('permission_domain');

        // Set new permission if needed
        if ('Yes' === $this->get_processor()->get_setting('manage_permissions') && $cachedentry->get_entry()->get_permission('canshare')) {
            $new_permission = new \UYDGoogle_Service_Drive_Permission();
            $new_permission->setType($permission_type);
            $new_permission->setRole($permission_role);
            $new_permission->setAllowFileDiscovery(false);
            if (null !== $permission_domain) {
                $new_permission->setDomain($permission_domain);
            }

            $params = [
                'supportsAllDrives' => true,
            ];

            try {
                $updated_permission = $this->get_app()->get_drive()->permissions->create($cachedentry->get_id(), $new_permission, $params);

                $users = $cachedentry->get_entry()->get_permission('users');
                $users[$updated_permission->getId()] = ['type' => $updated_permission->getType(), 'role' => $updated_permission->getRole(), 'domain' => $updated_permission->getDomain()];
                $cachedentry->get_entry()->set_permissions_by_key('users', $users);
                $this->get_cache()->add_to_cache($cachedentry->get_entry());

                do_action('useyourdrive_log_event', 'useyourdrive_updated_metadata', $cachedentry, ['metadata_field' => 'Sharing Permissions']);

                return true;
            } catch (\Exception $ex) {
                error_log('[WP Cloud Plugin message]: '.sprintf('API Error on line %s: %s', __LINE__, $ex->getMessage()));

                return false;
            }
        }

        return false;
    }

    public function create_link(CacheNode $cachedentry = null, $shorten_url = true)
    {
        $link = false;
        $error = false;
        $shorten = (('None' !== $this->get_processor()->get_setting('shortlinks')) && $shorten_url);

        if ((null === $cachedentry)) {
            // Check if file is cached and still valid
            $cached = $this->get_cache()->is_cached($this->get_processor()->get_requested_entry());

            // Get the file if not cached
            if (false === $cached) {
                $cachedentry = $this->get_entry($this->get_processor()->get_requested_entry());
            } else {
                $cachedentry = $cached;
            }
        }

        $viewlink = false;
        $embedlink = false;

        if (null !== $cachedentry && false !== $cachedentry) {
            $entry = $cachedentry->get_entry();
            $embedurl = $this->get_embed_url($cachedentry);

            // Build Direct link
            $viewurl = str_replace('edit?usp=drivesdk', 'view', $embedurl);
            $viewurl = str_replace('preview?rm=minimal', 'view', $embedurl);
            $viewurl = str_replace('preview', 'view', $embedurl);
            // For images, just return the actual file

            $type = 'iframe';
            // For images, just return the actual file
            if (in_array($cachedentry->get_entry()->get_extension(), ['jpg', 'jpeg', 'gif', 'png'])) {
                $type = 'image';
                $viewurl = 'https://docs.google.com/file/d/'.$cachedentry->get_entry()->get_id().'/view';
                $embedurl = USEYOURDRIVE_ADMIN_URL."?action=useyourdrive-embed-image&account_id={$cachedentry->get_account_id()}&id=".$cachedentry->get_id();
            }

            if (!empty($embedurl)) {
                $embedlink = ($shorten) ? $this->shorten_url($entry, $embedurl) : $embedurl;
                $viewlink = ($shorten) ? $this->shorten_url($entry, $viewurl) : $viewurl;
            } else {
                $error = esc_html__("Can't create link", 'wpcloudplugins');
            }
        }

        $resultdata = [
            'id' => $entry->get_id(),
            'name' => $entry->get_name(),
            'link' => $viewlink,
            'embeddedlink' => $embedlink,
            'type' => $type,
            'size' => Helpers::bytes_to_size_1024($entry->get_size()),
            'error' => $error,
        ];

        do_action('useyourdrive_created_link', $cachedentry);

        do_action('useyourdrive_log_event', 'useyourdrive_created_link_to_entry', $cachedentry, ['url' => $viewlink]);

        return $resultdata;
    }

    public function create_links($shorten = true)
    {
        $links = ['links' => []];

        foreach ($_REQUEST['entries'] as $entry) {
            $cached = $this->get_cache()->is_cached($entry);

            // Get the file if not cached or doesn't have permissions yet
            if (false === $cached) {
                $cachedentry = $this->get_entry($entry);
            } else {
                $cachedentry = $cached;
            }

            $links['links'][] = $this->create_link($cachedentry, $shorten);
        }

        return $links;
    }

    public function shorten_url($entry, $url)
    {
        if (false !== strpos($url, 'localhost')) {
            // Most APIs don't support localhosts
            return $url;
        }

        try {
            switch ($this->get_processor()->get_setting('shortlinks')) {
                case 'Bit.ly':
                    $response = wp_remote_post('https://api-ssl.bitly.com/v4/shorten', [
                        'body' => json_encode(
                            [
                                'long_url' => $url,
                            ]
                        ),
                        'headers' => [
                            'Authorization' => 'Bearer '.$this->get_processor()->get_setting('bitly_apikey'),
                            'Content-Type' => 'application/json',
                        ],
                    ]);

                    $data = json_decode($response['body'], true);

                    return $data['link'];

                case 'Shorte.st':
                    $response = wp_remote_get('https://api.shorte'.'.st/s/'.$this->get_processor()->get_setting('shortest_apikey').'/'.$url);

                    $data = json_decode($response['body'], true);

                    return $data['shortenedUrl'];

                case 'Rebrandly':
                    $response = wp_remote_post('https://api.rebrandly.com/v1/links', [
                        'body' => json_encode(
                            [
                                'title' => $entry->get_name(),
                                'destination' => $url,
                                'domain' => ['fullName' => $this->get_processor()->get_setting('rebrandly_domain')],
                            ]
                        ),
                        'headers' => [
                            'apikey' => $this->get_processor()->get_setting('rebrandly_apikey'),
                            'Content-Type' => 'application/json',
                            'workspace' => $this->get_processor()->get_setting('rebrandly_workspace'),
                        ],
                    ]);

                    $data = json_decode($response['body'], true);

                    return 'https://'.$data['shortUrl'];

                case 'None':
                default:
                    break;
            }
        } catch (\Exception $ex) {
            error_log('[WP Cloud Plugin message]: '.sprintf('API Error on line %s: %s', __LINE__, $ex->getMessage()));

            return $url;
        }

        return $url;
    }

    public function get_changes_starttoken($drive_id)
    {
        $params = [
            'supportsAllDrives' => true,
            'driveId' => ('mydrive' === $drive_id) ? null : $drive_id,
        ];

        try {
            $result = $this->get_app()->get_drive()->changes->getStartPageToken($params);

            return $result->getStartPageToken();
        } catch (\Exception $ex) {
            error_log('[WP Cloud Plugin message]: '.sprintf('API Error on line %s: %s', __LINE__, $ex->getMessage()));

            return false;
        }
    }

    public function get_changes($drive_id, $change_token = false)
    {
        // Load the root folder when needed
        $root_folder = $this->get_root_folder();

        if (empty($change_token)) {
            return [$this->get_changes_starttoken($drive_id), []];
        }

        $params = [
            'fields' => $this->apilistchangesfields,
            'pageSize' => 999,
            'restrictToMyDrive' => false,
            'includeItemsFromAllDrives' => true,
            'supportsAllDrives' => true,
            'spaces' => 'drive',
            'driveId' => ('mydrive' === $drive_id) ? null : $drive_id,
        ];

        $changes = [];

        while (null != $change_token) {
            try {
                $result = $this->get_app()->get_drive()->changes->listChanges($change_token, $params);
                $change_token = $result->getNextPageToken();

                if (null != $result->getNewStartPageToken()) {
                    // Last page, save this token for the next polling interval
                    $new_change_token = $result->getNewStartPageToken();
                }

                $changes = array_merge($changes, $result->getChanges());
            } catch (\Exception $ex) {
                error_log('[WP Cloud Plugin message]: '.sprintf('API Error on line %s: %s', __LINE__, $ex->getMessage()));

                return false;
            }
        }

        $list_of_update_entries = [];
        foreach ($changes as $change) {
            if ('drive' === $change->getChangeType()) {
                // Changes to the Shared Drives aren't processed
                continue;
            }

            if (true === $change->getRemoved()) {
                // File is removed
                $list_of_update_entries[$change->getFileId()] = 'deleted';
            } elseif ($change->getFile()->getTrashed()) {
                // File is trashed
                $list_of_update_entries[$change->getFileId()] = 'deleted';
            } else {
                // File is updated
                $list_of_update_entries[$change->getFileId()] = new Entry($change->getFile());
            }
        }

        return [$new_change_token, $list_of_update_entries];
    }

    /**
     * @return \TheLion\UseyourDrive\Processor
     */
    public function get_processor()
    {
        return $this->_processor;
    }

    /**
     * @return \TheLion\UseyourDrive\Cache
     */
    public function get_cache()
    {
        return $this->get_processor()->get_cache();
    }

    /**
     * @return \TheLion\UseyourDrive\App
     */
    public function get_app()
    {
        return $this->_app;
    }

    /**
     * @return \UYDGoogle_Client
     */
    public function get_library()
    {
        return $this->_app->get_client();
    }

    public function _get_files_recursive(CacheNode $cached_entry, $currentpath = '', &$dirlisting = ['folders' => [], 'files' => [], 'bytes' => 0, 'bytes_total' => 0])
    {
        // Get entry meta data
        if (empty($cached_entry) || null === $cached_entry->has_entry()) {
            return $dirlisting;
        }

        // Check if entry is allowed
        if (!$this->get_processor()->_is_entry_authorized($cached_entry)) {
            return $dirlisting;
        }

        //Use the orginial entry if the file/folder is a shortcut
        if ($cached_entry->is_shortcut()) {
            $original_node = $cached_entry->get_original_node();

            if (!empty($original_node)) {
                $cached_entry = $original_node;
            }
        }

        if ($cached_entry->is_dir()) {
            $folder_path = $currentpath.$cached_entry->get_name().'/';

            $dirlisting['folders'][] = $folder_path;
            $cached_folder = $this->get_folder($cached_entry->get_id());

            foreach ($cached_folder['folder']->get_children() as $cached_child) {
                $dirlisting = $this->_get_files_recursive($cached_child, $folder_path, $dirlisting);
            }
        } else {
            $entry = $cached_entry->get_entry();

            $entry_path = $currentpath.$cached_entry->get_name();
            if (null === $entry->get_direct_download_link()) {
                $formats = $entry->get_save_as();
                $format = reset($formats);
                $downloadlink = 'https://www.googleapis.com/drive/v3/files/'.$entry->get_id().'/export?mimeType='.urlencode($format['mimetype']).'&alt=media';
                $entry_path .= '.'.$format['extension'];
            } else {
                $downloadlink = 'https://www.googleapis.com/drive/v3/files/'.$entry->get_id().'?alt=media';
            }

            $dirlisting['files'][] = ['ID' => $entry->get_id(), 'path' => $entry_path, 'url' => $downloadlink, 'bytes' => $entry->get_size()];
            $dirlisting['bytes_total'] += $entry->get_size();
        }

        return $dirlisting;
    }
}
