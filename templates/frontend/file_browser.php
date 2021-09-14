<div class="list-container" style="width:<?php echo $this->options['maxwidth']; ?>;max-width:<?php echo $this->options['maxwidth']; ?>;">
  <?php
  if ('1' === $this->options['show_breadcrumb'] || '1' === $this->options['search'] || '1' === $this->options['show_refreshbutton']
          || $this->get_user()->can_add_folders() || $this->get_user()->can_download_zip() || $this->get_user()->can_delete_files() || $this->get_user()->can_delete_folders() || $this->get_user()->can_move_folders() || $this->get_user()->can_move_files()) {
      ?>
      <div class="nav-header"><?php if ('1' === $this->options['show_breadcrumb']) { ?>
            <a class="nav-home" title="<?php esc_html_e('Back to our first folder', 'wpcloudplugins'); ?>">
              <i class="fas fa-home"></i>
            </a>
            <div class="nav-title"><?php esc_html_e('Loading...', 'wpcloudplugins'); ?></div>
            <?php
            if ('1' === $this->options['search']) {
                ?>
                <a class="nav-search">
                  <i class="fas fa-search"></i>
                </a>

                  <div class="tippy-content-holder search-div">
                  <div class="search-remove"><i class="fas fa-times-circle fa-lg"></i></div>
                  <input name="q" type="text" size="40" aria-label="<?php echo esc_html__('Search', 'wpcloudplugins'); ?>" placeholder="<?php echo esc_html__('Search filenames', 'wpcloudplugins').(('1' === $this->options['searchcontents'] && '1' === $this->options['show_files']) ? ' '.esc_html__('and content', 'wpcloudplugins') : ''); ?>" class="search-input" />
                  </div>
            <?php
            } ?>

                        <a class="nav-sort" title="<?php esc_html_e('Sort options', 'wpcloudplugins'); ?>">
              <i class="fas fa-sort-amount-up"></i>
            </a>
            <div class="tippy-content-holder sort-div">
              <ul class='nav-sorting-list'>
                <li><a class="nav-sorting-field nav-name <?php echo ('name' === $this->options['sort_field']) ? 'sort-selected' : ''; ?>" data-field="name" title="<?php esc_html_e('Name', 'wpcloudplugins'); ?>">
                    <?php esc_html_e('Name', 'wpcloudplugins'); ?>
                  </a>
                </li>
                <li><a class="nav-sorting-field nav-size <?php echo ('size' === $this->options['sort_field']) ? 'sort-selected' : ''; ?>" data-field="size" title="<?php esc_html_e('Size', 'wpcloudplugins'); ?>">
                    <?php esc_html_e('Size', 'wpcloudplugins'); ?>
                  </a>
                </li>
                <li><a class="nav-sorting-field nav-modified <?php echo ('modified' === $this->options['sort_field']) ? 'sort-selected' : ''; ?>" data-field="modified" title="<?php esc_html_e('Modified', 'wpcloudplugins'); ?>">
                   <?php esc_html_e('Modified', 'wpcloudplugins'); ?>
                  </a>
                </li>
                <li><a class="nav-sorting-field nav-created <?php echo ('created' === $this->options['sort_field']) ? 'sort-selected' : ''; ?>" data-field="created" title="<?php esc_html_e('Date of creation', 'wpcloudplugins'); ?>">
                   <?php esc_html_e('Date of creation', 'wpcloudplugins'); ?>
                  </a>
                </li>                 
                <li class="list-separator">&nbsp;</li>
                <li><a class="nav-sorting-order nav-asc <?php echo ('asc' === $this->options['sort_order']) ? 'sort-selected' : ''; ?>" data-order="asc" title="<?php esc_html_e('Ascending', 'wpcloudplugins'); ?>">
                   <?php esc_html_e('Ascending', 'wpcloudplugins'); ?>
                  </a>
                </li>
                <li><a class="nav-sorting-order nav-desc <?php echo ('desc' === $this->options['sort_order']) ? 'sort-selected' : ''; ?>" data-order="desc" title="<?php esc_html_e('Descending', 'wpcloudplugins'); ?>">
                   <?php esc_html_e('Descending', 'wpcloudplugins'); ?>
                  </a>
                </li>
              </ul>
            </div>
            
            <a class="nav-gear" title="<?php esc_html_e('More actions', 'wpcloudplugins'); ?>">
              <i class="fas fa-ellipsis-v"></i>
            </a>
            <div class="tippy-content-holder gear-div" data-token="<?php echo $this->listtoken; ?>">
              <ul data-id="<?php echo $this->listtoken; ?>">
         
                <?php

                if ($this->get_user()->can_add_folders()) {
                    ?>
                    <li><a class="nav-new-folder newfolder" data-mimetype='application/vnd.google-apps.folder' title="<?php esc_html_e('Add folder', 'wpcloudplugins'); ?>"><i class="fas fa-folder-plus"></i><?php esc_html_e('Add folder', 'wpcloudplugins'); ?></a></li>
                    <?php
                }

                if ($this->get_user()->can_create_document()) {
                    ?>
                    <li>
                      <a class="nav-new-entry" title="<?php esc_html_e('Create document', 'wpcloudplugins'); ?>"><i class="fas fa-file-plus"></i><?php esc_html_e('Create document', 'wpcloudplugins'); ?></a>
                      <ul>
                        <li><a class="newentry" data-mimetype='application/vnd.google-apps.document' title="<?php esc_html_e('Google Doc', 'wpcloudplugins'); ?>"><img src="<?php echo \TheLion\UseyourDrive\Helpers::get_default_thumbnail_icon('application/vnd.google-apps.document'); ?>"><?php esc_html_e('Google Doc', 'wpcloudplugins'); ?></a></li>
                        <li><a class="newentry" data-mimetype='application/vnd.google-apps.spreadsheet' title="<?php esc_html_e('Google Sheet', 'wpcloudplugins'); ?>"><img src="<?php echo \TheLion\UseyourDrive\Helpers::get_default_thumbnail_icon('application/vnd.google-apps.spreadsheet'); ?>"><?php esc_html_e('Google Sheet', 'wpcloudplugins'); ?></a></li>
                        <li><a class="newentry" data-mimetype='application/vnd.google-apps.presentation' title="<?php esc_html_e('Google Slide', 'wpcloudplugins'); ?>"><img src="<?php echo \TheLion\UseyourDrive\Helpers::get_default_thumbnail_icon('application/vnd.google-apps.presentation'); ?>"><?php esc_html_e('Google Slide', 'wpcloudplugins'); ?></a></li>
                      </ul>
                    </li>
                <?php
                }

                if ($this->get_user()->can_upload()) {
                    ?>
                    <li><a class="nav-upload" title="<?php esc_html_e('Upload files', 'wpcloudplugins'); ?>"><i class="fas fa-upload"></i><?php esc_html_e('Upload files', 'wpcloudplugins'); ?></a></li>
                    <?php
                }

                if ($this->get_user()->can_move_folders() || $this->get_user()->can_move_files() || $this->get_user()->can_delete_files() || $this->get_user()->can_delete_folders() || $this->get_user()->can_download_zip()) {
                    ?>
                    <li><a class='select-all' title='" <?php esc_html_e('Select all', 'wpcloudplugins'); ?>"'><i class='fas fa-check-square'></i><?php esc_html_e('Select all', 'wpcloudplugins'); ?></a></li>
                    <li style="display:none"><a class='deselect-all' title='" <?php esc_html_e('Deselect all', 'wpcloudplugins'); ?>"'><i class='fas fa-square'></i><?php esc_html_e('Deselect all', 'wpcloudplugins'); ?></a></li>
                    <?php
                }

                if ($this->get_user()->can_download_zip()) {
                    ?>
                    <li><a class="all-files-to-zip" download><i class='fas fa-archive'></i><?php esc_html_e('Download folder', 'wpcloudplugins'); ?> (.zip)</a></li>
                    <li><a class="selected-files-to-zip" download><i class='fas fa-archive'></i><?php esc_html_e('Download selected', 'wpcloudplugins'); ?> (.zip)</a></li>
                    <?php
                }

                if ($this->get_user()->can_move_folders() || $this->get_user()->can_move_files()) {
                    ?>
                    <li><a class='selected-files-move' title='" <?php esc_html_e('Move to', 'wpcloudplugins'); ?>"'><i class='fas fa-folder-open'></i><?php esc_html_e('Move to', 'wpcloudplugins'); ?></a></li>
                    <?php
                }

                if ($this->get_user()->can_delete_files() || $this->get_user()->can_delete_folders()) {
                    ?>
                    <li><a class="selected-files-delete" title="<?php esc_html_e('Delete', 'wpcloudplugins'); ?>"><i class="fas fa-trash"></i><?php esc_html_e('Delete', 'wpcloudplugins'); ?></a></li>
                    <?php
                }

                if ($this->get_user()->can_deeplink()) {
                    ?>
                    <li><a class='entry_action_deeplink_folder' title='<?php esc_html_e('Direct link', 'wpcloudplugins'); ?>'><i class='fas fa-link'></i><?php esc_html_e('Direct link', 'wpcloudplugins'); ?></a></li>
                    <?php
                }

                if ($this->get_user()->can_share()) {
                    ?>
                    <li><a class='entry_action_shortlink_folder' title='<?php esc_html_e('Share folder', 'wpcloudplugins'); ?>'><i class='fas fa-share-alt'></i><?php esc_html_e('Share folder', 'wpcloudplugins'); ?></a></li>
                    <?php
                }
                ?>
                <li style="display: none"><a class="nav-layout nav-layout-grid" title="<?php esc_html_e('Thumbnails view', 'wpcloudplugins'); ?>">
                    <i class="fas fa-th-large"></i><?php esc_html_e('Thumbnails view', 'wpcloudplugins'); ?>
                  </a>
                </li>
                <li><a class="nav-layout nav-layout-list" title="<?php esc_html_e('List view', 'wpcloudplugins'); ?>">
                    <i class="fas fa-th-list"></i><?php esc_html_e('List view', 'wpcloudplugins'); ?>
                  </a>
                </li> 
                <li class='gear-menu-no-options' style="display: none"><a><i class='fas fa-info-circle'></i><?php esc_html_e('No options...', 'wpcloudplugins'); ?></a></li>
              </ul>
            </div><?php
        }
      if ('1' === $this->options['show_refreshbutton']) {
          ?>
            <a class="nav-refresh" title="<?php esc_html_e('Refresh', 'wpcloudplugins'); ?>">
              <i class="fas fa-sync"></i>
            </a>
            <?php
      }

      if ('0' === $this->options['single_account']) {
          $current_account = $this->get_accounts()->get_account_by_id($dataaccountid);
          $primary_account = $this->get_accounts()->get_primary_account();

          if (null === $current_account) {
              echo "<div class='nav-account-selector' data-account-id='{$primary_account->get_id()}' title='{$primary_account->get_name()}'><img src='{$primary_account->get_image()}' onerror='this.src=\"".USEYOURDRIVE_ROOTPATH."/css/images/usericon.png\"' /><span>{$primary_account->get_name()}</span></div>\n";
          } else {
              echo "<div class='nav-account-selector' data-account-id='{$current_account->get_id()}' title='{$current_account->get_name()}'><img src='{$current_account->get_image()}' onerror='this.src=\"".USEYOURDRIVE_ROOTPATH."/css/images/usericon.png\"' /><span>{$current_account->get_name()}</span></div>\n";
          }

          echo "<div class='nav-account-selector-content'>";
          foreach ($this->get_accounts()->list_accounts() as $account_id => $account) {
              echo "<div class='nav-account-selector' data-account-id='{$account->get_id()}' title='{$account->get_name()}'><img src='{$account->get_image()}' onerror='this.src=\"".USEYOURDRIVE_ROOTPATH."/css/images/usericon.png\"' /><span>{$account->get_name()}</span></div>\n";
          }
          echo '</div>';
      } ?></div>
  <?php
  } ?>
  <div class="file-container">
    <div class="loading initialize"><?php
      $loaders = $this->get_setting('loaders');

      switch ($loaders['style']) {
          case 'custom':
              break;

          case 'beat':
              ?>
              <div class='loader-beat'></div>
              <?php
              break;

          case 'spinner':
              ?>
              <svg class="loader-spinner" viewBox="25 25 50 50">
              <circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="3" stroke-miterlimit="10"></circle>
              </svg>
              <?php
              break;
      }
      ?></div>
    <div class="ajax-filelist" style="<?php echo (!empty($this->options['maxheight'])) ? 'max-height:'.$this->options['maxheight'].';overflow-y: scroll;' : ''; ?>">
      <div class='files'>
        <div class="folders-container"><?php \TheLion\UseyourDrive\Skeleton::get_browser_placeholders('folder', 3); ?></div>
        <div class="files-container"><?php \TheLion\UseyourDrive\Skeleton::get_browser_placeholders('file', 5); ?></div>
      </div>
    </div>
    <div class="scroll-to-top"><a><i class="fas fa-chevron-circle-up fa-2x"></i></a></div>
  </div>
</div>

