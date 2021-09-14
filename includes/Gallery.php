<?php

namespace TheLion\UseyourDrive;

class Gallery
{
    /**
     * @var \TheLion\UseyourDrive\Processor
     */
    private $_processor;
    private $_search = false;

    public function __construct(Processor $_processor)
    {
        $this->_processor = $_processor;
    }

    /**
     * @return \TheLion\UseyourDrive\Processor
     */
    public function get_processor()
    {
        return $this->_processor;
    }

    public function get_images_list()
    {
        $this->_folder = $this->get_processor()->get_client()->get_folder();

        if ((false !== $this->_folder)) {
            // Create Image Array
            $this->imagesarray = $this->createImageArray();

            $this->renderImagesList();
        }
    }

    public function search_image_files()
    {
        $this->_search = true;
        $this->_folder = $this->get_processor()->get_client()->get_folder();

        $search = new Search($this->get_processor());
        $this->_folder['contents'] = $search->do_search($_REQUEST['query'], $this->_folder['folder']);

        if ((false !== $this->_folder)) {
            //Create Gallery array
            $this->imagesarray = $this->createImageArray();

            $this->renderImagesList();
        }
    }

    public function setFolder($folder)
    {
        $this->_folder = $folder;
    }

    public function setParentFolder()
    {
        if (true === $this->_search) {
            return;
        }

        $currentfolder = $this->_folder['folder']->get_entry()->get_id();
        if ($currentfolder !== $this->get_processor()->get_root_folder()) {
            // Get parent folder from known folder path
            $cacheparentfolder = $this->get_processor()->get_client()->get_folder($this->get_processor()->get_root_folder());
            $folder_path = $this->get_processor()->get_folder_path();
            $parentid = end($folder_path);
            if (false !== $parentid) {
                $cacheparentfolder = $this->get_processor()->get_client()->get_folder($parentid);
            }

            /* Check if parent folder indeed is direct parent of entry
             * If not, return all known parents */
            $parentfolders = [];
            if (false !== $cacheparentfolder && $cacheparentfolder['folder']->has_children() && array_key_exists($currentfolder, $cacheparentfolder['folder']->get_children())) {
                $parentfolders[] = $cacheparentfolder['folder']->get_entry();
            } elseif ($this->_folder['folder']->has_parent()) {
                $parentfolders[] = $this->_folder['folder']->get_parent()->get_entry();
            }
            $this->_parentfolders = $parentfolders;
        }
    }

    public function renderImagesList()
    {
        // Create HTML Filelist
        $imageslist_html = '';

        if (count($this->imagesarray) > 0) {
            $imageslist_html = "<div class='images image-collage'>";
            foreach ($this->imagesarray as $item) {
                // Render folder div
                if ($item->is_dir()) {
                    $imageslist_html .= $this->renderDir($item);
                }
            }
        }

        $imageslist_html .= $this->renderNewFolder();

        if (count($this->imagesarray) > 0) {
            $i = 0;
            foreach ($this->imagesarray as $item) {
                // Render file div
                if (!$item->is_dir()) {
                    $hidden = (('0' !== $this->get_processor()->get_shortcode_option('maximages')) && ($i >= $this->get_processor()->get_shortcode_option('maximages')));
                    $imageslist_html .= $this->renderFile($item, $hidden);
                    ++$i;
                }
            }

            $imageslist_html .= '</div>';
        } else {
            if (true === $this->_search) {
                $imageslist_html .= '<div class="no_results">'.esc_html__('This folder is empty', 'wpcloudplugins').'</div>';
            }
        }

        // Create HTML Filelist title
        $file_path = '<ol class="wpcp-breadcrumb">';
        $folder_path = $this->get_processor()->get_folder_path();
        $root_folder_id = $this->get_processor()->get_root_folder();
        if (!isset($this->_folder['folder'])) {
            $this->_folder['folder'] = $this->get_processor()->get_client()->get_entry($this->get_processor()->get_requested_entry());
        }

        $current_id = $this->_folder['folder']->get_entry()->get_id();
        $current_folder_name = $this->_folder['folder']->get_entry()->get_name();

        if ($root_folder_id === $current_id) {
            $file_path .= "<li class='first-breadcrumb'><a href='#{$current_id}' class='folder current_folder' data-id='".$current_id."'>".$this->get_processor()->get_shortcode_option('root_text').'</a></li>';
        } elseif (false === $this->_search || 'parent' === $this->get_processor()->get_shortcode_option('searchfrom')) {
            foreach ($folder_path as $parent_id) {
                if ($parent_id === $root_folder_id) {
                    $file_path .= "<li class='first-breadcrumb'><a href='#{$parent_id}' class='folder' data-id='".$parent_id."'>".$this->get_processor()->get_shortcode_option('root_text').'</a></li>';
                } else {
                    $parent_folder = $this->get_processor()->get_client()->get_folder($parent_id);
                    $parent_folder_name = apply_filters('useyourdrive_gallery_entry_text', $parent_folder['folder']->get_name(), $parent_folder['folder']->get_entry(), $this);
                    $file_path .= "<li><a href='#{$parent_id}' class='folder' data-id='".$parent_id."'>".$parent_folder_name.'</a></li>';
                }
            }

            $current_folder_name = apply_filters('useyourdrive_gallery_entry_text', $current_folder_name, $this->_folder['folder']->get_entry(), $this);
            $file_path .= "<li><a href='#{$current_id}' class='folder current_folder' data-id='".$current_id."'>".$current_folder_name.'</a></li>';
        }

        if (true === $this->_search) {
            $file_path .= "<li><a href='javascript:void(0)' class='folder'>".sprintf(esc_html__('Results for %s', 'wpcloudplugins'), "'".$_REQUEST['query']."'").'</a></li>';
        }

        $file_path .= '</ol>';

        // lastFolder contains current folder path of the user

        if (true !== $this->_search && (end($folder_path) !== $this->_folder['folder']->get_entry()->get_id())) {
            $folder_path[] = $this->_folder['folder']->get_entry()->get_id();
        }

        if (true === $this->_search) {
            $lastFolder = $this->get_processor()->get_last_folder();
        } else {
            $lastFolder = $this->_folder['folder']->get_entry()->get_id();
        }

        $response = json_encode([
            'folderPath' => base64_encode(json_encode($folder_path)),
            'lastFolder' => $lastFolder,
            'accountId' => $this->_folder['folder']->get_account_id(),
            'virtual' => false === $this->_search && in_array($this->_folder['folder']->get_virtual_folder(), ['drive', 'shared-drives', 'computers', 'shared-with-me']),
            'breadcrumb' => $file_path,
            'html' => $imageslist_html,
            'hasChanges' => defined('HAS_CHANGES'),
        ]);

        if (false === defined('HAS_CHANGES')) {
            $cached_request = new CacheRequest($this->get_processor());
            $cached_request->add_cached_response($response);
        }

        echo $response;

        exit();
    }

    public function renderDir($item)
    {
        $return = '';

        $target_height = $this->get_processor()->get_shortcode_option('targetheight');
        $target_width = round($target_height * (4 / 3));

        $classmoveable = ($this->get_processor()->get_user()->can_move_folders()) ? 'moveable' : '';

        // Check if $item is parent
        $folder_path = $this->get_processor()->get_folder_path();
        $parentid = end($folder_path);
        $isparent = (!empty($parentid) && $item->get_id() === $parentid);

        $folder_thumbnails = $item->get_folder_thumbnails();
        $has_thumbnails = (isset($folder_thumbnails['expires']) && $folder_thumbnails['expires'] > time());

        if ($isparent) {
            $return .= "<div class='image-container image-folder pf' data-id='".$item->get_id()."' data-name='".$item->get_basename()."'>";
        } else {
            $loadthumbs = $has_thumbnails ? '' : 'loadthumbs';
            $return .= "<div class='image-container image-folder entry {$classmoveable} {$loadthumbs}' data-id='".$item->get_id()."' data-name='".$item->get_basename()."'>";
        }

        $return .= "<a title='".$item->get_name()."'>";

        $return .= "<div class='preloading'></div>";

        $return .= "<img class='image-folder-img' src='".USEYOURDRIVE_ROOTPATH."/css/images/transparant.png' width='{$target_width}' height='{$target_height}' style='width:{$target_width}px !important;height:{$target_height}px !important; '/>";

        if ($has_thumbnails) {
            $iimages = 1;

            foreach ($folder_thumbnails['thumbs'] as $folder_thumbnail) {
                $thumb_url = $item->get_thumbnail_with_size('h'.round($target_height * 1).'-w'.round($target_width * 1).'-c-nu', $folder_thumbnail);
                $thumb_url = (false === strpos($thumb_url, 'useyourdrive-thumbnail')) ? $thumb_url : $thumb_url.'&account_id='.$this->_folder['folder']->get_account_id().'&listtoken='.$this->get_processor()->get_listtoken();

                $return .= "<div class='folder-thumb thumb{$iimages}' style='width:".$target_width.'px;height:'.$target_height.'px;background-image: url('.$thumb_url.")'></div>";
                ++$iimages;
            }
        }

        $text = $item->get_name();
        $text = apply_filters('useyourdrive_gallery_entry_text', $text, $item, $this);

        $return .= "<div class='folder-text'><i class='fas fa-folder'></i>&nbsp;&nbsp;".($isparent ? '<strong>'.esc_html__('Previous folder', 'wpcloudplugins').' ('.$text.')</strong>' : $text).'</div>';
        $return .= '</a>';

        if (!$isparent) {
            $return .= "<div class='entry-info'>";
            $return .= $this->renderDescription($item);
            $return .= $this->renderButtons($item);
            $return .= $this->renderActionMenu($item);

            if ($this->get_processor()->get_user()->can_download_zip() || $this->get_processor()->get_user()->can_delete_files() || $this->get_processor()->get_user()->can_move_files()) {
                $return .= "<div class='entry_checkbox entry-info-button '><input type='checkbox' name='selected-files[]' class='selected-files' value='".$item->get_id()."' id='checkbox-info-{$this->get_processor()->get_listtoken()}-{$item->get_id()}'/><label for='checkbox-info-{$this->get_processor()->get_listtoken()}-{$item->get_id()}'></label></div>";
            }

            $return .= '</div>';
        }

        $return .= "<div class='entry-top-actions'>";
        $return .= $this->renderDescription($item);
        $return .= $this->renderButtons($item);
        $return .= $this->renderActionMenu($item);

        if ($this->get_processor()->get_user()->can_download_zip() || $this->get_processor()->get_user()->can_delete_folders() || $this->get_processor()->get_user()->can_move_folders()) {
            $return .= "<div class='entry_checkbox entry-info-button '><input type='checkbox' name='selected-files[]' class='selected-files' value='".$item->get_id()."' id='checkbox-{$this->get_processor()->get_listtoken()}-{$item->get_id()}'/><label for='checkbox-{$this->get_processor()->get_listtoken()}-{$item->get_id()}'></label></div>";
        }

        $return .= '</div>';

        $return .= "</div>\n";

        return $return;
    }

    public function renderFile($item, $hidden = false)
    {
        $class = ($hidden) ? 'hidden' : '';
        $target_height = $this->get_processor()->get_shortcode_option('targetheight');

        $classmoveable = ($this->get_processor()->get_user()->can_move_files()) ? 'moveable' : '';

        $return = "<div class='image-container {$class} entry {$classmoveable}' data-id='".$item->get_id()."' data-name='".$item->get_name()."'>";

        $thumbnail = 'data-options="thumbnail: \''.$item->get_thumbnail_with_size('w200-h200-nu').'\'"';

        $link = USEYOURDRIVE_ADMIN_URL."?action=useyourdrive-download&account_id={$this->get_processor()->get_current_account()->get_id()}&id=".urlencode($item->get_id()).'&dl=1&listtoken='.$this->get_processor()->get_listtoken();
        if ('googlethumbnail' === $this->get_processor()->get_setting('loadimages')) {
            $link = $item->get_thumbnail_large();
        }

        $description = htmlentities($item->get_description(), ENT_QUOTES | ENT_HTML401);
        $data_description = ((!empty($item->description)) ? "data-caption='{$description}'" : '');

        $return .= "<a href='".$link."' title='".$item->get_basename()."' class='ilightbox-group' data-type='image' {$thumbnail} rel='ilightbox[".$this->get_processor()->get_listtoken()."]' data-entry-id='{$item->get_id()}' {$data_description}><span class='image-rollover'></span>";

        $return .= "<div class='preloading'></div>";

        $width = $height = $target_height;
        if ($item->get_media('width')) {
            $width = round(($target_height / $item->get_media('height')) * $item->get_media('width'));
        }

        $return .= "<img referrerPolicy='no-referrer' class='preloading {$class}' alt='{$description} 'src='".USEYOURDRIVE_ROOTPATH."/css/images/transparant.png' data-src='".$item->get_thumbnail_with_size('h'.round($target_height * 1).'-nu')."' data-src-retina='".$item->get_thumbnail_with_size('h'.round($target_height * 2).'-nu')."' width='{$width}' height='{$height}' style='width:{$width}px !important;height:{$height}px !important; '/>";

        $text = '';
        if ('1' === $this->get_processor()->get_shortcode_option('show_filenames')) {
            $text = $item->get_basename();
            $text = apply_filters('useyourdrive_gallery_entry_text', $text, $item, $this);
            $return .= "<div class='entry-text'>".$text.'</div>';
        }

        $return .= '</a>';

        if (false === empty($item->description)) {
            $return .= '<div class="entry-inline-description '.('1' === $this->get_processor()->get_shortcode_option('show_descriptions_on_top') ? ' description-visible ' : '').('1' === $this->get_processor()->get_shortcode_option('show_filenames') ? ' description-above-name ' : '').'"><span>'.nl2br($item->get_description()).'</span></div>';
        }

        $return .= "<div class='entry-info'>";
        $return .= "<div class='entry-info-name'>";
        $caption = apply_filters('useyourdrive_gallery_lightbox_caption', $item->get_basename(), $item, $this);
        $return .= '<span>'.$caption.'</span></div>';
        $return .= $this->renderButtons($item);
        $return .= "</div>\n";

        $return .= "<div class='entry-top-actions'>";

        if ('1' === $this->get_processor()->get_shortcode_option('show_filenames')) {
            $return .= $this->renderDescription($item);
        }

        $return .= $this->renderButtons($item);
        $return .= $this->renderActionMenu($item);

        if ($this->get_processor()->get_user()->can_download_zip() || $this->get_processor()->get_user()->can_delete_files() || $this->get_processor()->get_user()->can_move_files()) {
            $return .= "<div class='entry_checkbox entry-info-button '><input type='checkbox' name='selected-files[]' class='selected-files' value='".$item->get_id()."' id='checkbox-{$this->get_processor()->get_listtoken()}-{$item->get_id()}'/><label for='checkbox-{$this->get_processor()->get_listtoken()}-{$item->get_id()}'></label></div>";
        }

        $return .= '</div>';
        $return .= "</div>\n";

        return $return;
    }

    public function renderDescription($item)
    {
        $html = '';

        $has_description = (false === empty($item->description));

        $metadata = [
            'modified' => "<i class='fas fa-history'></i> ".$item->get_last_edited_str(),
            'size' => ($item->get_size() > 0) ? Helpers::bytes_to_size_1024($item->get_size()) : '',
        ];

        $html .= "<div class='entry-info-button entry-description-button ".(($has_description) ? '-visible' : '')."' tabindex='0'><i class='fas fa-info-circle'></i>\n";
        $html .= "<div class='tippy-content-holder'>";
        $html .= "<div class='description-textbox'>";
        $html .= ($has_description) ? "<div class='description-text'>".nl2br($item->get_description()).'</div>' : '';
        $html .= "<div class='description-file-info'>".implode(' &bull; ', array_filter($metadata)).'</div>';

        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function renderButtons($item)
    {
        $html = '';

        if ($this->get_processor()->get_user()->can_share()) {
            $html .= "<div class='entry-info-button entry_action_shortlink' title='".esc_html__('Direct link', 'wpcloudplugins')."' tabindex='0'><i class='fas fa-share-alt'></i>\n";
            $html .= '</div>';
        }

        if ($this->get_processor()->get_user()->can_deeplink()) {
            $html .= "<div class='entry-info-button entry_action_deeplink' title='".esc_html__('Share', 'wpcloudplugins')."' tabindex='0'><i class='fas fa-link'></i>\n";
            $html .= '</div>';
        }

        if ($this->get_processor()->get_user()->can_download() && $item->is_file()) {
            $html .= "<div class='entry-info-button entry_action_download' title='".esc_html__('Download', 'wpcloudplugins')."' tabindex='0'><a href='".USEYOURDRIVE_ADMIN_URL."?action=useyourdrive-download&account_id={$this->get_processor()->get_current_account()->get_id()}&id=".$item->get_id().'&dl=1&listtoken='.$this->get_processor()->get_listtoken()."' download='".$item->get_name()."' class='entry_action_download' title='".esc_html__('Download', 'wpcloudplugins')."'><i class='fas fa-arrow-down'></i></a>\n";
            $html .= '</div>';
        }

        return $html;
    }

    public function renderActionMenu($item)
    {
        $html = '';

        $permissions = $item->get_permissions();

        $usercanread = $this->get_processor()->get_user()->can_download();
        $usercanrename = $permissions['canrename'] && ($item->is_dir()) ? $this->get_processor()->get_user()->can_rename_folders() : $this->get_processor()->get_user()->can_rename_files();
        $usercanmove = $permissions['canmove'] && (($item->is_dir()) ? $this->get_processor()->get_user()->can_move_folders() : $this->get_processor()->get_user()->can_move_files());
        $usercandelete = $permissions['candelete'] && (($item->is_dir()) ? $this->get_processor()->get_user()->can_delete_folders() : $this->get_processor()->get_user()->can_delete_files());
        $usercaneditdescription = $this->get_processor()->get_user()->can_edit_description();

        // Download
        if ($usercanread && $item->is_dir() && '1' === $this->get_processor()->get_shortcode_option('can_download_zip')) {
            $html .= "<li><a class='entry_action_download' download='".$item->get_name()."' data-filename='".$item->get_name()."' title='".esc_html__('Download', 'wpcloudplugins')."'><i class='fas fa-arrow-down '></i>&nbsp;".esc_html__('Download', 'wpcloudplugins').'</a></li>';
        }

        // Descriptions
        if ($usercaneditdescription) {
            if (empty($item->description)) {
                $html .= "<li><a class='entry_action_description' title='".esc_html__('Add description', 'wpcloudplugins')."'><i class='fas fa-comment-alt '></i>&nbsp;".esc_html__('Add description', 'wpcloudplugins').'</a></li>';
            } else {
                $html .= "<li><a class='entry_action_description' title='".esc_html__('Edit description', 'wpcloudplugins')."'><i class='fas fa-comment-alt '></i>&nbsp;".esc_html__('Edit description', 'wpcloudplugins').'</a></li>';
            }
        }

        // Rename
        if ($usercanrename) {
            $html .= "<li><a class='entry_action_rename' title='".esc_html__('Rename', 'wpcloudplugins')."'><i class='fas fa-tag '></i>&nbsp;".esc_html__('Rename', 'wpcloudplugins').'</a></li>';
        }

        // Move
        if ($usercanmove) {
            $html .= "<li><a class='entry_action_move' title='".esc_html__('Move to', 'wpcloudplugins')."'><i class='fas fa-folder-open '></i>&nbsp;".esc_html__('Move to', 'wpcloudplugins').'</a></li>';
        }

        // Delete
        if ($usercandelete && ($item->get_permission('candelete') || $item->get_permission('cantrash'))) {
            $html .= "<li><a class='entry_action_delete' title='".esc_html__('Delete', 'wpcloudplugins')."'><i class='fas fa-trash '></i>&nbsp;".esc_html__('Delete', 'wpcloudplugins').'</a></li>';
        }

        if ('' !== $html) {
            return "<div class='entry-info-button entry-action-menu-button' title='".esc_html__('More actions', 'wpcloudplugins')."' tabindex='0'><i class='fas fa-ellipsis-v'></i><div id='menu-".$item->get_id()."' class='entry-action-menu-button-content tippy-content-holder'><ul data-id='".$item->get_id()."' data-name='".$item->get_basename()."'>".$html."</ul></div></div>\n";
        }

        return $html;
    }

    public function renderNewFolder()
    {
        $html = '';

        if (
            false === $this->get_processor()->get_user()->can_add_folders()
            || true === $this->_search
            || '1' === $this->get_processor()->get_shortcode_option('show_breadcrumb')
            ) {
            return $html;
        }

        $height = $this->get_processor()->get_shortcode_option('targetheight');
        $html .= "<div class='image-container image-folder image-add-folder grey newfolder' data-mimetype='application/vnd.google-apps.folder'>";
        $html .= "<a title='".esc_html__('Add folder', 'wpcloudplugins')."'>";
        $html .= "<img class='preloading' src='".USEYOURDRIVE_ROOTPATH."/css/images/transparant.png' data-src='".plugins_url('css/images/gallery-add-folder.png', dirname(__FILE__))."' data-src-retina='".plugins_url('css/images/gallery-add-folder.png', dirname(__FILE__))."' width='{$height}' height='{$height}' style='width:".$height.'px;height:'.$height."px;'/>";
        $html .= "<div class='folder-text'><i class='fas fa-folder-plus'></i>&nbsp;&nbsp;".esc_html__('Add folder', 'wpcloudplugins').'</div>';
        $html .= '</a>';
        $html .= "</div>\n";

        return $html;
    }

    public function createImageArray()
    {
        $imagearray = [];

        $this->setParentFolder();

        //Add folders and files to filelist
        if (count($this->_folder['contents']) > 0) {
            foreach ($this->_folder['contents'] as $node) {
                $child = $node->get_entry();

                // Check if entry is allowed
                if (!$this->get_processor()->_is_entry_authorized($node)) {
                    continue;
                }

                // Check if entry has thumbnail
                if (!$child->has_own_thumbnail() && $child->is_file()) {
                    continue;
                }

                $imagearray[] = $child;
            }

            $imagearray = $this->get_processor()->sort_filelist($imagearray);
        }

        // Limit the number of files if needed
        if ('-1' !== $this->get_processor()->get_shortcode_option('max_files')) {
            $imagearray = array_slice($imagearray, 0, $this->get_processor()->get_shortcode_option('max_files'));
        }

        // Add 'back to Previous folder' if needed
        if (isset($this->_folder['folder'])) {
            $folder = $this->_folder['folder']->get_entry();

            if ($this->_search || $folder->get_id() === $this->get_processor()->get_root_folder()) {
                return $imagearray;
            }
            if ('1' === $this->get_processor()->get_shortcode_option('show_breadcrumb')) {
                return $imagearray;
            }

            // Get previous folder ID from Folder Path if possible//
            $folder_path = $this->get_processor()->get_folder_path();
            $parentid = end($folder_path);
            if (!empty($parentid)) {
                $parentfolder = $this->get_processor()->get_client()->get_folder($parentid);
                array_unshift($imagearray, $parentfolder['folder']->get_entry());

                return $imagearray;
            }

            // Otherwise, list the parents directly
            foreach ($this->_parentfolders as $parentfolder) {
                array_unshift($imagearray, $parentfolder);
            }
        }

        return $imagearray;
    }
}
