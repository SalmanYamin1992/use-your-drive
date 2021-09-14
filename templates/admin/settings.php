<?php
$network_wide_authorization = $this->get_processor()->is_network_authorized();

function wp_roles_and_users_input($name, $selected = [])
{
    if (!is_array($selected)) {
        $selected = ['administrator'];
    }

    // Workaround: Add temporarily selected value to prevent an empty selection in Tagify when only user ID 0 is selected
    $selected[] = '_______PREVENT_EMPTY_______';

    // Create value for imput field
    $value = implode(', ', $selected);

    // Input Field
    echo "<input class='useyourdrive-option-input-large useyourdrive-tagify useyourdrive-permissions-placeholders' type='text' name='{$name}' value='{$value}' placeholder='' />";
}

function create_color_boxes_table($colors, $settings)
{
    if (0 === count($colors)) {
        return '';
    }

    $table_html = '<table class="color-table">';

    foreach ($colors as $color_id => $color) {
        $value = isset($settings['colors'][$color_id]) ? sanitize_text_field($settings['colors'][$color_id]) : $color['default'];

        $table_html .= '<tr>';
        $table_html .= "<td>{$color['label']}</td>";
        $table_html .= "<td><input value='{$value}' data-default-color='{$color['default']}'  name='use_your_drive_settings[colors][{$color_id}]' id='colors-{$color_id}' type='text'  class='wpcp-color-picker' data-alpha-enabled='true' ></td>";
        $table_html .= '</tr>';
    }

    $table_html .= '</table>';

    return $table_html;
}

function create_upload_button_for_custom_images($option)
{
    $field_value = empty($option['value']) ? $option['default'] : $option['value'];

    $button_html = '<div class="upload_row">';

    $button_html .= '<div class="screenshot" id="'.$option['id'].'_image">'."\n";

    $button_html .= '<img src="'.$field_value.'" alt="" />'."\n";
    $button_html .= '<a href="javascript:void(0)" class="wpcp-image-remove-button">'.esc_html__('Remove', 'wpcloudplugins').'</a>'."\n";

    $button_html .= '</div>';

    $button_html .= '<input id="'.esc_attr($option['id']).'" class="upload useyourdrive-option-input-large" type="text" name="'.esc_attr($option['name']).'" value="'.esc_attr($field_value).'" autocomplete="off" />';
    $button_html .= '<input class="wpcp-image-select-button simple-button blue" type="button" value="'.esc_html__('Select Image', 'wpcloudplugins').'" title="'.esc_html__('Upload or select a file from the media library', 'wpcloudplugins').'" />';

    if ($field_value !== $option['default']) {
        $button_html .= '<input id="wpcp-default-image-button" class="wpcp-default-image-button simple-button" type="button" value="'.esc_html__('Default', 'wpcloudplugins').'" title="'.esc_html__('Fallback to the default value', 'wpcloudplugins').'"  data-default="'.$option['default'].'"/>';
    }

    $button_html .= '</div>'."\n";

    return $button_html;
}
?>

<div class="useyourdrive admin-settings">
  <form id="useyourdrive-options" method="post" action="options.php">
    <?php settings_fields('use_your_drive_settings'); ?>

    <div class="wrap">
      <div class="useyourdrive-header">
        <div class="useyourdrive-logo"><a href="https://www.wpcloudplugins.com" target="_blank"><img src="<?php echo USEYOURDRIVE_ROOTPATH; ?>/css/images/wpcp-logo-dark.svg" height="64" width="64"/></a></div>
        <div class="useyourdrive-form-buttons"> <div id="wpcp-save-settings-button" class="simple-button default"><?php esc_html_e('Save Settings', 'wpcloudplugins'); ?>&nbsp;<div class='wpcp-spinner'></div></div></div>
        <div class="useyourdrive-title"><?php esc_html_e('Settings', 'wpcloudplugins'); ?></div>
      </div>


      <div id="" class="useyourdrive-panel useyourdrive-panel-left">      
        <div class="useyourdrive-nav-header"><?php esc_html_e('Settings', 'wpcloudplugins'); ?> <a href="<?php echo admin_url('update-core.php'); ?>">(Ver: <?php echo USEYOURDRIVE_VERSION; ?>)</a></div>

        <ul class="useyourdrive-nav-tabs">
          <li id="settings_general_tab" data-tab="settings_general" class="current"><a ><?php esc_html_e('General', 'wpcloudplugins'); ?></a></li>

          <?php
          if ($this->is_activated()) {
              ?>
              <li id="settings_layout_tab" data-tab="settings_layout" ><a ><?php esc_html_e('Layout', 'wpcloudplugins'); ?></a></li>
              <li id="settings_userfolders_tab" data-tab="settings_userfolders" ><a ><?php esc_html_e('Private Folders', 'wpcloudplugins'); ?></a></li>
              <li id="settings_advanced_tab" data-tab="settings_advanced" ><a ><?php esc_html_e('Advanced', 'wpcloudplugins'); ?></a></li>
              <li id="settings_integrations_tab" data-tab="settings_integrations" ><a><?php esc_html_e('Integrations', 'wpcloudplugins'); ?></a></li>
              <li id="settings_notifications_tab" data-tab="settings_notifications" ><a ><?php esc_html_e('Notifications', 'wpcloudplugins'); ?></a></li>
              <li id="settings_permissions_tab" data-tab="settings_permissions" ><a><?php esc_html_e('Permissions', 'wpcloudplugins'); ?></a></li>
              <li id="settings_stats_tab" data-tab="settings_stats" ><a><?php esc_html_e('Statistics', 'wpcloudplugins'); ?></a></li>
              <li id="settings_tools_tab" data-tab="settings_tools" ><a><?php esc_html_e('Tools', 'wpcloudplugins'); ?></a></li>
              <?php
          }
          ?>
          <li id="settings_system_tab" data-tab="settings_system" ><a><?php esc_html_e('System information', 'wpcloudplugins'); ?></a></li>
          <li id="settings_help_tab" data-tab="settings_help" ><a><?php esc_html_e('Support', 'wpcloudplugins'); ?></a></li>
        </ul>

        <div class="useyourdrive-nav-header" style="margin-top: 50px;"><?php esc_html_e('Other Cloud Plugins', 'wpcloudplugins'); ?></div>
        <ul class="useyourdrive-nav-tabs">
          <li id="settings_help_tab" data-tab="settings_help"><a href="https://1.envato.market/vLjyO" target="_blank" style="color:#522058;">Dropbox <i class="fas fa-external-link-square-alt" aria-hidden="true"></i></a></li>
          <li id="settings_help_tab" data-tab="settings_help"><a href="https://1.envato.market/yDbyv" target="_blank" style="color:#522058;">OneDrive <i class="fas fa-external-link-square-alt" aria-hidden="true"></i></a></li>
          <li id="settings_help_tab" data-tab="settings_help"><a href="https://1.envato.market/M4B53" target="_blank" style="color:#522058;">Box <i class="fas fa-external-link-square-alt" aria-hidden="true"></i></a></li>
        </ul> 

        <div class="useyourdrive-nav-footer">
          <a href="https://www.wpcloudplugins.com/" target="_blank">
            <img alt="" height="auto" src="<?php echo USEYOURDRIVE_ROOTPATH; ?>/css/images/wpcloudplugins-logo-dark.png">
          </a>
        </div>
      </div>


      <div class="useyourdrive-panel useyourdrive-panel-right">

        <!-- General Tab -->
        <div id="settings_general" class="useyourdrive-tab-panel current">

          <div class="useyourdrive-tab-panel-header"><?php esc_html_e('General', 'wpcloudplugins'); ?></div>

          <?php if ($this->is_activated()) { ?>
              <div class="useyourdrive-option-title"><?php esc_html_e('Accounts', 'wpcloudplugins'); ?></div>
              <div class="useyourdrive-accounts-list">
                
                <?php

                if (false === $this->get_processor()->is_network_authorized() || ($this->get_processor()->is_network_authorized() && true === is_network_admin())) {
                    $app = $this->get_app();
                    $app->get_client()->setAccessType('offline');
                    $app->get_client()->setApprovalPrompt('force'); ?>
                    <div class='account account-new'>
                      <img class='account-image' src='<?php echo USEYOURDRIVE_ROOTPATH; ?>/css/images/google_drive_logo.svg'/>
                      <div class='account-info-container'>
                        <div class='account-info'>
                          <div class='account-actions'>
                            <div id='wpcp-add-account-button' type='button' class='simple-button blue' data-url="<?php echo $app->get_auth_url(); ?>" title="<?php esc_html_e('Add account', 'wpcloudplugins'); ?>"><i class='fas fa-plus-circle' aria-hidden='true'></i>&nbsp;<?php esc_html_e('Add account', 'wpcloudplugins'); ?></div>
                          </div>
                          <div class="account-info-name">
                            <?php esc_html_e('Link a new account to the plugin', 'wpcloudplugins'); ?>
                          </div>
                          <span class="account-info-space"><a href="#" id="wpcp-read-privacy-policy"><i class="fas fa-shield-alt"></i> <?php esc_html_e('What happens with my data when I authorize the plugin?', 'wpcloudplugins'); ?></a></span>   
                        </div>
                      </div>
                    </div>
                    <?php
                } else {
                    ?>
                    <div class='account account-new'>
                      <img class='account-image' src='<?php echo USEYOURDRIVE_ROOTPATH; ?>/css/images/google_drive_logo.svg'/>
                      <div class='account-info-container'>
                        <div class='account-info'>
                          <span class="account-info-space"><?php echo sprintf(wp_kses(__("The authorization is managed by the Network Admin via the <a href='%s'>Network Settings Page</a> of the plugin", 'wpcloudplugins'), ['a' => ['href' => []]]), network_admin_url('admin.php?page=UseyourDrive_network_settings')); ?>.</span>
                        </div>
                      </div>
                    </div>   
                    <?php
                }

                foreach ($this->get_main()->get_accounts()->list_accounts() as $account_id => $account) {
                    echo $this->get_plugin_authorization_box($account);
                }

                ?>
              </div>
              <?php
          }
          ?>
          <div class="useyourdrive-option-title"><?php esc_html_e('Plugin License', 'wpcloudplugins'); ?></div>
          <?php
          echo $this->get_plugin_activated_box();
          ?>
        </div>
        <!-- End General Tab -->


        <!-- Layout Tab -->
        <div id="settings_layout"  class="useyourdrive-tab-panel">
          <div class="useyourdrive-tab-panel-header"><?php esc_html_e('Layout', 'wpcloudplugins'); ?></div>

          <div class="useyourdrive-accordion">

            <div class="useyourdrive-accordion-title useyourdrive-option-title"><?php esc_html_e('Loading Spinner & Images', 'wpcloudplugins'); ?>         </div>
            <div>

              <div class="useyourdrive-option-title"><?php esc_html_e('Select Loader Spinner', 'wpcloudplugins'); ?></div>
              <select type="text" name="use_your_drive_settings[loaders][style]" id="loader_style">
                <option value="beat" <?php echo 'beat' === $this->settings['loaders']['style'] ? "selected='selected'" : ''; ?>><?php esc_html_e('Beat', 'wpcloudplugins'); ?></option>
                <option value="spinner" <?php echo 'spinner' === $this->settings['loaders']['style'] ? "selected='selected'" : ''; ?>><?php esc_html_e('Spinner', 'wpcloudplugins'); ?></option>
                <option value="custom" <?php echo 'custom' === $this->settings['loaders']['style'] ? "selected='selected'" : ''; ?>><?php esc_html_e('Custom Image (selected below)', 'wpcloudplugins'); ?></option>
              </select>

              <div class="useyourdrive-option-title"><?php esc_html_e('General Loader', 'wpcloudplugins'); ?></div>
              <?php
              $button = ['value' => $this->settings['loaders']['loading'], 'id' => 'loaders_loading', 'name' => 'use_your_drive_settings[loaders][loading]', 'default' => USEYOURDRIVE_ROOTPATH.'/css/images/loader_loading.gif'];
              echo create_upload_button_for_custom_images($button);
              ?>
              <div class="useyourdrive-option-title"><?php esc_html_e('Upload Loader', 'wpcloudplugins'); ?></div>
              <?php
              $button = ['value' => $this->settings['loaders']['upload'], 'id' => 'loaders_upload', 'name' => 'use_your_drive_settings[loaders][upload]', 'default' => USEYOURDRIVE_ROOTPATH.'/css/images/loader_upload.gif'];
              echo create_upload_button_for_custom_images($button);
              ?>
              <div class="useyourdrive-option-title"><?php esc_html_e('No Results', 'wpcloudplugins'); ?></div>
              <?php
              $button = ['value' => $this->settings['loaders']['no_results'], 'id' => 'loaders_no_results', 'name' => 'use_your_drive_settings[loaders][no_results]', 'default' => USEYOURDRIVE_ROOTPATH.'/css/images/loader_no_results.png'];
              echo create_upload_button_for_custom_images($button);
              ?>
              <div class="useyourdrive-option-title"><?php esc_html_e('Access Forbidden', 'wpcloudplugins'); ?></div>
              <?php
              $button = ['value' => $this->settings['loaders']['protected'], 'id' => 'loaders_protected', 'name' => 'use_your_drive_settings[loaders][protected]', 'default' => USEYOURDRIVE_ROOTPATH.'/css/images/loader_protected.png'];
              echo create_upload_button_for_custom_images($button);
              ?>
              <div class="useyourdrive-option-title"><?php esc_html_e('Error', 'wpcloudplugins'); ?></div>
              <?php
              $button = ['value' => $this->settings['loaders']['error'], 'id' => 'loaders_error', 'name' => 'use_your_drive_settings[loaders][error]', 'default' => USEYOURDRIVE_ROOTPATH.'/css/images/loader_error.png'];
              echo create_upload_button_for_custom_images($button);
              ?>
              <div class="useyourdrive-option-title"><?php esc_html_e('iFrame Loader', 'wpcloudplugins'); ?></div>
              <?php
              $button = ['value' => $this->settings['loaders']['iframe'], 'id' => 'loaders_iframe', 'name' => 'use_your_drive_settings[loaders][iframe]', 'default' => USEYOURDRIVE_ROOTPATH.'/css/images/wpcp-loader.svg'];
              echo create_upload_button_for_custom_images($button);
              ?>              
            </div>

            <div class="useyourdrive-accordion-title useyourdrive-option-title"><?php esc_html_e('Color Palette', 'wpcloudplugins'); ?></div>
            <div>

              <div class="useyourdrive-option-title"><?php esc_html_e('Theme Style', 'wpcloudplugins'); ?></div>
              <div class="useyourdrive-option-description"><?php esc_html_e('Select the general style of your theme', 'wpcloudplugins'); ?>.</div>
              <select name="skin_selectbox" id="wpcp_content_skin_selectbox" class="ddslickbox">
                <option value="dark" <?php echo 'dark' === $this->settings['colors']['style'] ? "selected='selected'" : ''; ?> data-imagesrc="<?php echo USEYOURDRIVE_ROOTPATH; ?>/css/images/skin-dark.png" data-description=""><?php esc_html_e('Dark', 'wpcloudplugins'); ?></option>
                <option value="light" <?php echo 'light' === $this->settings['colors']['style'] ? "selected='selected'" : ''; ?> data-imagesrc="<?php echo USEYOURDRIVE_ROOTPATH; ?>/css/images/skin-light.png" data-description=""><?php esc_html_e('Light', 'wpcloudplugins'); ?></option>
              </select>
              <input type="hidden" name="use_your_drive_settings[colors][style]" id="wpcp_content_skin" value="<?php echo esc_attr($this->settings['colors']['style']); ?>">

              <?php
              $colors = [
                  'background' => [
                      'label' => esc_html__('Content Background Color', 'wpcloudplugins'),
                      'default' => '#f2f2f2',
                  ],
                  'accent' => [
                      'label' => esc_html__('Accent Color', 'wpcloudplugins'),
                      'default' => '#522058',
                  ],
                  'black' => [
                      'label' => esc_html__('Black', 'wpcloudplugins'),
                      'default' => '#222',
                  ],
                  'dark1' => [
                      'label' => esc_html__('Dark 1', 'wpcloudplugins'),
                      'default' => '#666666',
                  ],
                  'dark2' => [
                      'label' => esc_html__('Dark 2', 'wpcloudplugins'),
                      'default' => '#999999',
                  ],
                  'white' => [
                      'label' => esc_html__('White', 'wpcloudplugins'),
                      'default' => '#fff',
                  ],
                  'light1' => [
                      'label' => esc_html__('Light 1', 'wpcloudplugins'),
                      'default' => '#fcfcfc',
                  ],
                  'light2' => [
                      'label' => esc_html__('Light 2', 'wpcloudplugins'),
                      'default' => '#e8e8e8',
                  ],
              ];

              echo create_color_boxes_table($colors, $this->settings);
              ?>
            </div>

            <div class="useyourdrive-accordion-title useyourdrive-option-title"><?php esc_html_e('Icons', 'wpcloudplugins'); ?></div>
            <div>

              <div class="useyourdrive-option-title"><?php esc_html_e('Icon Set', 'wpcloudplugins'); ?></div>
              <div class="useyourdrive-option-description"><?php _e(sprintf('Location to the icon set you want to use. When you want to use your own set, just make a copy of the default icon set folder (<code>%s</code>) and place it in the <code>wp-content/</code> folder', USEYOURDRIVE_ROOTPATH.'/css/icons/'), 'wpcloudplugins'); ?>.</div>

              <div class="wpcp-warning">
                <i><strong><?php esc_html_e('NOTICE', 'wpcloudplugins'); ?></strong>: <?php esc_html_e('Modifications to the default icons set will be lost during an update.', 'wpcloudplugins'); ?>.</i>
              </div>

              <input class="useyourdrive-option-input-large" type="text" name="use_your_drive_settings[icon_set]" id="icon_set" value="<?php echo esc_attr($this->settings['icon_set']); ?>">  
            </div>

            <div class="useyourdrive-accordion-title useyourdrive-option-title"><?php esc_html_e('Lightbox', 'wpcloudplugins'); ?></div>
            <div>
              <div class="useyourdrive-option-title"><?php esc_html_e('Lightbox Skin', 'wpcloudplugins'); ?></div>
              <div class="useyourdrive-option-description"><?php esc_html_e('Select which skin you want to use for the Inline Preview', 'wpcloudplugins'); ?>.</div>
              <select name="wpcp_lightbox_skin_selectbox" id="wpcp_lightbox_skin_selectbox" class="ddslickbox">
                <?php
                foreach (new DirectoryIterator(USEYOURDRIVE_ROOTDIR.'/vendors/iLightBox/') as $fileInfo) {
                    if ($fileInfo->isDir() && !$fileInfo->isDot() && (false !== strpos($fileInfo->getFilename(), 'skin'))) {
                        if (file_exists(USEYOURDRIVE_ROOTDIR.'/vendors/iLightBox/'.$fileInfo->getFilename().'/skin.css')) {
                            $selected = '';
                            $skinname = str_replace('-skin', '', $fileInfo->getFilename());

                            if ($skinname === $this->settings['lightbox_skin']) {
                                $selected = 'selected="selected"';
                            }

                            $icon = file_exists(USEYOURDRIVE_ROOTDIR.'/vendors/iLightBox/'.$fileInfo->getFilename().'/thumb.jpg') ? USEYOURDRIVE_ROOTPATH.'/vendors/iLightBox/'.$fileInfo->getFilename().'/thumb.jpg' : '';
                            echo '<option value="'.$skinname.'" data-imagesrc="'.$icon.'" data-description="" '.$selected.'>'.$fileInfo->getFilename()."</option>\n";
                        }
                    }
                }
                ?>
              </select>
              <input type="hidden" name="use_your_drive_settings[lightbox_skin]" id="wpcp_lightbox_skin" value="<?php echo esc_attr($this->settings['lightbox_skin']); ?>">


              <div class="useyourdrive-option-title">Lightbox Scroll</div>
              <div class="useyourdrive-option-description"><?php esc_html_e("Sets path for switching windows. Possible values are 'vertical' and 'horizontal' and the default is 'vertical", 'wpcloudplugins'); ?>.</div>
              <select type="text" name="use_your_drive_settings[lightbox_path]" id="lightbox_path">
                <option value="horizontal" <?php echo 'horizontal' === $this->settings['lightbox_path'] ? "selected='selected'" : ''; ?>><?php esc_html_e('Horizontal', 'wpcloudplugins'); ?></option>
                <option value="vertical" <?php echo 'vertical' === $this->settings['lightbox_path'] ? "selected='selected'" : ''; ?>><?php esc_html_e('Vertical', 'wpcloudplugins'); ?></option>
              </select>

              <div class="useyourdrive-option-title">Lightbox <?php esc_html_e('Image Source', 'wpcloudplugins'); ?></div>
              <div class="useyourdrive-option-description"><?php esc_html_e('Select the source of the images. Large thumbnails load fast, orignal files will take some time to load', 'wpcloudplugins'); ?>.</div>
              <select type="text" name="use_your_drive_settings[loadimages]" id="loadimages">
                <option value="googlethumbnail" <?php echo 'googlethumbnail' === $this->settings['loadimages'] ? "selected='selected'" : ''; ?>><?php esc_html_e('Fast - Large preview thumbnails', 'wpcloudplugins'); ?></option>
                <option value="original" <?php echo 'original' === $this->settings['loadimages'] ? "selected='selected'" : ''; ?>><?php esc_html_e('Slow - Show original files', 'wpcloudplugins'); ?></option>
              </select>

              <div class="useyourdrive-option-title"><?php esc_html_e('Allow Mouse Click on Image', 'wpcloudplugins'); ?>
                <div class="useyourdrive-onoffswitch">
                  <input type='hidden' value='No' name='use_your_drive_settings[lightbox_rightclick]'/>
                  <input type="checkbox" name="use_your_drive_settings[lightbox_rightclick]" id="lightbox_rightclick" class="useyourdrive-onoffswitch-checkbox" <?php echo ('Yes' === $this->settings['lightbox_rightclick']) ? 'checked="checked"' : ''; ?>/>
                  <label class="useyourdrive-onoffswitch-label" for="lightbox_rightclick"></label>
                </div>
              </div>
              <div class="useyourdrive-option-description"><?php esc_html_e('Should people be able to access the right click context menu to e.g. save the image?', 'wpcloudplugins'); ?>.</div>

              <div class="useyourdrive-option-title"><?php esc_html_e('Header', 'wpcloudplugins'); ?></div>
              <div class="useyourdrive-option-description"><?php esc_html_e('When should the header containing title and action-menu be shown', 'wpcloudplugins'); ?>.</div>
              <select type="text" name="use_your_drive_settings[lightbox_showheader]" id="lightbox_showheader">
                <option value="true" <?php echo 'true' === $this->settings['lightbox_showheader'] ? "selected='selected'" : ''; ?>><?php esc_html_e('Always', 'wpcloudplugins'); ?></option>
                <option value="click" <?php echo 'click' === $this->settings['lightbox_showheader'] ? "selected='selected'" : ''; ?>><?php esc_html_e('Show after clicking on the Lightbox', 'wpcloudplugins'); ?></option>
                <option value="mouseenter" <?php echo 'mouseenter' === $this->settings['lightbox_showheader'] ? "selected='selected'" : ''; ?>><?php esc_html_e('Show when hovering over the Lightbox', 'wpcloudplugins'); ?></option>
                <option value="false" <?php echo 'false' === $this->settings['lightbox_showheader'] ? "selected='selected'" : ''; ?>><?php esc_html_e('Never', 'wpcloudplugins'); ?></option>
              </select>  

              <div class="useyourdrive-option-title"><?php esc_html_e('Caption / Description', 'wpcloudplugins'); ?></div>
              <div class="useyourdrive-option-description"><?php esc_html_e('When should the description be shown in the Gallery Lightbox', 'wpcloudplugins'); ?>.</div>
              <select type="text" name="use_your_drive_settings[lightbox_showcaption]" id="lightbox_showcaption">
                <option value="true" <?php echo 'true' === $this->settings['lightbox_showcaption'] ? "selected='selected'" : ''; ?>><?php esc_html_e('Always', 'wpcloudplugins'); ?></option>
                <option value="click" <?php echo 'click' === $this->settings['lightbox_showcaption'] ? "selected='selected'" : ''; ?>><?php esc_html_e('Show after clicking on the Lightbox', 'wpcloudplugins'); ?></option>
                <option value="mouseenter" <?php echo 'mouseenter' === $this->settings['lightbox_showcaption'] ? "selected='selected'" : ''; ?>><?php esc_html_e('Show when hovering over the Lightbox', 'wpcloudplugins'); ?></option>
                <option value="false" <?php echo 'false' === $this->settings['lightbox_showcaption'] ? "selected='selected'" : ''; ?>><?php esc_html_e('Never', 'wpcloudplugins'); ?></option>
              </select>     

            </div>

            <div class="useyourdrive-accordion-title useyourdrive-option-title"><?php esc_html_e('Media Player', 'wpcloudplugins'); ?></div>
            <div>
              <div class="useyourdrive-option-description"><?php esc_html_e('Select which Media Player you want to use', 'wpcloudplugins'); ?>.</div>
              <select name="wpcp_mediaplayer_skin_selectbox" id="wpcp_mediaplayer_skin_selectbox" class="ddslickbox">
                <?php
                foreach (new DirectoryIterator(USEYOURDRIVE_ROOTDIR.'/skins/') as $fileInfo) {
                    if ($fileInfo->isDir() && !$fileInfo->isDot()) {
                        if (file_exists(USEYOURDRIVE_ROOTDIR.'/skins/'.$fileInfo->getFilename().'/js/Player.js')) {
                            $selected = '';
                            if ($fileInfo->getFilename() === $this->settings['mediaplayer_skin']) {
                                $selected = 'selected="selected"';
                            }

                            $icon = file_exists(USEYOURDRIVE_ROOTDIR.'/skins/'.$fileInfo->getFilename().'/Thumb.jpg') ? USEYOURDRIVE_ROOTPATH.'/skins/'.$fileInfo->getFilename().'/Thumb.jpg' : '';
                            echo '<option value="'.$fileInfo->getFilename().'" data-imagesrc="'.$icon.'" data-description="" '.$selected.'>'.$fileInfo->getFilename()."</option>\n";
                        }
                    }
                }
                ?>
              </select>
              <input type="hidden" name="use_your_drive_settings[mediaplayer_skin]" id="wpcp_mediaplayer_skin" value="<?php echo esc_attr($this->settings['mediaplayer_skin']); ?>">

              <br/><br/>
              <div class="useyourdrive-option-title"><?php esc_html_e('Load native MediaElement.js library', 'wpcloudplugins'); ?>
                <div class="useyourdrive-onoffswitch">
                  <input type='hidden' value='No' name='use_your_drive_settings[mediaplayer_load_native_mediaelement]'/>
                  <input type="checkbox" name="use_your_drive_settings[mediaplayer_load_native_mediaelement]" id="mediaplayer_load_native_mediaelement" class="useyourdrive-onoffswitch-checkbox" <?php echo ('Yes' === $this->settings['mediaplayer_load_native_mediaelement']) ? 'checked="checked"' : ''; ?>/>
                  <label class="useyourdrive-onoffswitch-label" for="mediaplayer_load_native_mediaelement"></label>
                </div>
              </div>
              <div class="useyourdrive-option-description"><?php esc_html_e('Is the layout of the Media Player all mixed up and is it not initiating properly? If that is the case, you might be encountering a conflict between media player libraries on your site. To resolve this, enable this setting to load the native MediaElement.js library.', 'wpcloudplugins'); ?></div>              
            </div>

            <div class="useyourdrive-accordion-title useyourdrive-option-title"><?php esc_html_e('Custom CSS', 'wpcloudplugins'); ?></div>
            <div>
              <div class="useyourdrive-option-description"><?php esc_html_e("If you want to modify the looks of the plugin slightly, you can insert here your custom CSS. Don't edit the CSS files itself, because those modifications will be lost during an update.", 'wpcloudplugins'); ?>.</div>
              <textarea name="use_your_drive_settings[custom_css]" id="custom_css" cols="" rows="10"><?php echo esc_attr($this->settings['custom_css']); ?></textarea> 
            </div>
          </div>

        </div>
        <!-- End Layout Tab -->

        <!-- UserFolders Tab -->
        <div id="settings_userfolders"  class="useyourdrive-tab-panel">
          <div class="useyourdrive-tab-panel-header"><?php esc_html_e('Private Folders', 'wpcloudplugins'); ?></div>

          <div class="useyourdrive-accordion">
            <div class="useyourdrive-accordion-title useyourdrive-option-title"><?php esc_html_e('Global settings Automatically linked Private Folders', 'wpcloudplugins'); ?> </div>
            <div>

              <div class="wpcp-warning">
                <i><strong>NOTICE</strong>: <?php esc_html_e('The following settings are only used for all shortcodes with automatically linked Private Folders', 'wpcloudplugins'); ?>. </i>
              </div>

              <div class="useyourdrive-option-title"><?php esc_html_e('Create Private Folders on registration', 'wpcloudplugins'); ?>
                <div class="useyourdrive-onoffswitch">
                  <input type='hidden' value='No' name='use_your_drive_settings[userfolder_oncreation]'/>
                  <input type="checkbox" name="use_your_drive_settings[userfolder_oncreation]" id="userfolder_oncreation" class="useyourdrive-onoffswitch-checkbox" <?php echo ('Yes' === $this->settings['userfolder_oncreation']) ? 'checked="checked"' : ''; ?>/>
                  <label class="useyourdrive-onoffswitch-label" for="userfolder_oncreation"></label>
                </div>
              </div>
              <div class="useyourdrive-option-description"><?php esc_html_e('Create a new Private Folders automatically after a new user has been created', 'wpcloudplugins'); ?>.</div>

              <div class="useyourdrive-option-title"><?php esc_html_e('Create all Private Folders on first visit', 'wpcloudplugins'); ?>
                <div class="useyourdrive-onoffswitch">
                  <input type='hidden' value='No' name='use_your_drive_settings[userfolder_onfirstvisit]'/>
                  <input type="checkbox" name="use_your_drive_settings[userfolder_onfirstvisit]" id="userfolder_onfirstvisit" class="useyourdrive-onoffswitch-checkbox" <?php echo ('Yes' === $this->settings['userfolder_onfirstvisit']) ? 'checked="checked"' : ''; ?>/>
                  <label class="useyourdrive-onoffswitch-label" for="userfolder_onfirstvisit"></label>
                </div>
              </div>
              <div class="useyourdrive-option-description"><?php esc_html_e('Create all Private Folders the first time the page with the shortcode is visited', 'wpcloudplugins'); ?>.</div>
              <div class="wpcp-warning">
                <i><strong><?php esc_html_e('NOTICE', 'wpcloudplugins'); ?></strong>: <?php esc_html_e("Creating User Folders takes around 1 sec per user, so it isn't recommended to create those on first visit when you have tons of users", 'wpcloudplugins'); ?>.</i>
              </div>


              <div class="useyourdrive-option-title"><?php esc_html_e('Update Private Folders after profile update', 'wpcloudplugins'); ?>
                <div class="useyourdrive-onoffswitch">
                  <input type='hidden' value='No' name='use_your_drive_settings[userfolder_update]'/>
                  <input type="checkbox" name="use_your_drive_settings[userfolder_update]" id="userfolder_update" class="useyourdrive-onoffswitch-checkbox" <?php echo ('Yes' === $this->settings['userfolder_update']) ? 'checked="checked"' : ''; ?>/>
                  <label class="useyourdrive-onoffswitch-label" for="userfolder_update"></label>
                </div>
              </div>
              <div class="useyourdrive-option-description"><?php esc_html_e('Update the folder name of the user after they have updated their profile', 'wpcloudplugins'); ?>.</div>

              <div class="useyourdrive-option-title"><?php esc_html_e('Remove Private Folders after account removal', 'wpcloudplugins'); ?>
                <div class="useyourdrive-onoffswitch">
                  <input type='hidden' value='No' name='use_your_drive_settings[userfolder_remove]'/>
                  <input type="checkbox" name="use_your_drive_settings[userfolder_remove]" id="userfolder_remove" class="useyourdrive-onoffswitch-checkbox" <?php echo ('Yes' === $this->settings['userfolder_remove']) ? 'checked="checked"' : ''; ?> />
                  <label class="useyourdrive-onoffswitch-label" for="userfolder_remove"></label>
                </div>
              </div>
              <div class="useyourdrive-option-description"><?php esc_html_e('Try to remove Private Folders after they are deleted', 'wpcloudplugins'); ?>.</div>

              <div class="useyourdrive-option-title"><?php esc_html_e('Name Template', 'wpcloudplugins'); ?></div>
              <div class="useyourdrive-option-description"><?php echo esc_html__('Template name for automatically created Private Folders.', 'wpcloudplugins').' '.esc_html__('The naming template can also be set per shortcode individually.', 'wpcloudplugins').' '.sprintf(esc_html__('Available placeholders: %s', 'wpcloudplugins'), '').'<code>%user_login%</code>,  <code>%user_firstname%</code>, <code>%user_lastname%</code>, <code>%user_email%</code>, <code>%display_name%</code>, <code>%ID%</code>, <code>%user_role%</code>, <code>%yyyy-mm-dd%</code>, <code>%hh:mm%</code>, <code>%uniqueID%</code>, <code>%directory_separator% (/)</code>'; ?>.</div>
              <input class="useyourdrive-option-input-large" type="text" name="use_your_drive_settings[userfolder_name]" id="userfolder_name" value="<?php echo esc_attr($this->settings['userfolder_name']); ?>">  
            </div>

            <div class="useyourdrive-accordion-title useyourdrive-option-title"><?php esc_html_e('Global settings Manually linked Private Folders', 'wpcloudplugins'); ?> </div>
            <div>

              <div class="wpcp-warning">
                <i><strong>NOTICE</strong>: <?php echo sprintf(esc_html__('You can manually link users to their Private Folder via the %s[Link Private Folders]%s menu page', 'wpcloudplugins'), '<a href="'.admin_url('admin.php?page=UseyourDrive_settings_linkusers').'" target="_blank">', '</a>'); ?>. </i>
              </div>

              <div class="useyourdrive-option-title"><?php esc_html_e('Access Forbidden notice', 'wpcloudplugins'); ?></div>
              <div class="useyourdrive-option-description"><?php esc_html_e("Message that is displayed when an user is visiting a shortcode with the Private Folders feature set to 'Manual' mode while it doesn't have Private Folder linked to its account", 'wpcloudplugins'); ?>.</div>

              <?php
              ob_start();
              wp_editor($this->settings['userfolder_noaccess'], 'use_your_drive_settings_userfolder_noaccess', [
                  'textarea_name' => 'use_your_drive_settings[userfolder_noaccess]',
                  'teeny' => true,
                  'tinymce' => false,
                  'textarea_rows' => 15,
                  'media_buttons' => false,
              ]);
              echo ob_get_clean();
              ?>

            </div>
            <?php
            $main_account = $this->get_processor()->get_accounts()->get_primary_account();

            if (!empty($main_account)) {
                ?>
                <div class="useyourdrive-accordion-title useyourdrive-option-title"><?php esc_html_e('Private Folders in WP Admin Dashboard', 'wpcloudplugins'); ?> </div>
                <div>

                  <div class="wpcp-warning">
                    <i><strong>NOTICE</strong>: <?php esc_html_e('This setting only restrict access of the File Browsers in the Admin Dashboard (e.g. the ones in the Shortcode Builder and the File Browser menu). To enable Private Folders for your own Shortcodes, use the Shortcode Builder', 'wpcloudplugins'); ?>. </i>
                  </div>

                  <div class="useyourdrive-option-description"><?php esc_html_e('Enable Private Folders in the Shortcode Builder and Back-End File Browser', 'wpcloudplugins'); ?>.</div>
                  <select type="text" name="use_your_drive_settings[userfolder_backend]" id="userfolder_backend" data-div-toggle="private-folders-auto" data-div-toggle-value="auto">
                    <option value="No" <?php echo 'No' === $this->settings['userfolder_backend'] ? "selected='selected'" : ''; ?>>No</option>
                    <option value="manual" <?php echo 'manual' === $this->settings['userfolder_backend'] ? "selected='selected'" : ''; ?>><?php esc_html_e('Yes, I link the users Manually', 'wpcloudplugins'); ?></option>
                    <option value="auto" <?php echo 'auto' === $this->settings['userfolder_backend'] ? "selected='selected'" : ''; ?>><?php esc_html_e('Yes, let the plugin create the User Folders for me', 'wpcloudplugins'); ?></option>
                  </select>
                  <div class="useyourdrive-suboptions private-folders-auto <?php echo ('auto' === ($this->settings['userfolder_backend'])) ? '' : 'hidden'; ?> ">
                    <div class="useyourdrive-option-title"><?php esc_html_e('Root folder for Private Folders', 'wpcloudplugins'); ?></div>
                    <div class="useyourdrive-option-description"><?php esc_html_e('Select in which folder the Private Folders should be created', 'wpcloudplugins'); ?>. <?php esc_html_e('Current selected folder', 'wpcloudplugins'); ?>:</div>
                    <?php
                    $private_auto_folder = $this->settings['userfolder_backend_auto_root'];

                if (empty($private_auto_folder)) {
                    $this->get_processor()->set_current_account($main_account);

                    try {
                        $root = $this->get_processor()->get_client()->get_root_folder();
                    } catch (\Exception $ex) {
                        $root = false;
                    }

                    if (false === $root) {
                        $private_auto_folder = [
                            'account' => $main_account->get_id(),
                            'id' => '',
                            'name' => '',
                            'view_roles' => ['administrator'],
                        ];
                    } else {
                        $private_auto_folder = [
                            'account' => $main_account->get_id(),
                            'id' => $root->get_entry()->get_id(),
                            'name' => $root->get_entry()->get_name(),
                            'view_roles' => ['administrator'],
                        ];
                    }
                }

                if (!isset($private_auto_folder['account']) || empty($private_auto_folder['account'])) {
                    $private_auto_folder['account'] = $main_account->get_id();
                }

                $account = $this->get_processor()->get_accounts()->get_account_by_id($private_auto_folder['account']);
                if (null !== $account) {
                    $this->get_processor()->set_current_account($account);
                } ?>
                    <input class="useyourdrive-option-input-large private-folders-auto-current" type="text" value="<?php echo $private_auto_folder['name']; ?>" disabled="disabled">
                    <input class="private-folders-auto-input-account" type='hidden' value='<?php echo $private_auto_folder['account']; ?>' name='use_your_drive_settings[userfolder_backend_auto_root][account]'/>
                    <input class="private-folders-auto-input-id" type='hidden' value='<?php echo $private_auto_folder['id']; ?>' name='use_your_drive_settings[userfolder_backend_auto_root][id]'/>
                    <input class="private-folders-auto-input-name" type='hidden' value='<?php echo $private_auto_folder['name']; ?>' name='use_your_drive_settings[userfolder_backend_auto_root][name]'/>
                    <div id="wpcp-select-root-button" type="button" class="button-primary private-folders-auto-button"><?php esc_html_e('Select Folder', 'wpcloudplugins'); ?>&nbsp;<div class='wpcp-spinner'></div></div>

                    <div id='uyd-embedded' style='clear:both;display:none'>
                      <?php
                      try {
                          echo $this->get_processor()->create_from_shortcode(
                              [
                                  'mode' => 'files',
                                  'singleaccount' => '0',
                                  'dir' => 'drive',
                                  'showfiles' => '1',
                                  'filesize' => '0',
                                  'filedate' => '0',
                                  'upload' => '0',
                                  'delete' => '0',
                                  'rename' => '0',
                                  'addfolder' => '0',
                                  'showbreadcrumb' => '1',
                                  'showfiles' => '0',
                                  'downloadrole' => 'none',
                                  'candownloadzip' => '0',
                                  'showsharelink' => '0',
                                  'mcepopup' => 'linktobackendglobal',
                                  'search' => '0',
                              ]
                          );
                      } catch (\Exception $ex) {
                      } ?>
                    </div>

                    <br/><br/>
                    <div class="useyourdrive-option-title"><?php esc_html_e('Full Access', 'wpcloudplugins'); ?></div>
                    <div class="useyourdrive-option-description"><?php esc_html_e('By default only Administrator users will be able to navigate through all Private Folders', 'wpcloudplugins'); ?>. <?php esc_html_e('When you want other User Roles to be able do browse to the Private Folders as well, please check them below', 'wpcloudplugins'); ?>.</div>

                    <?php
                    $selected = (isset($private_auto_folder['view_roles'])) ? $private_auto_folder['view_roles'] : [];
                wp_roles_and_users_input('use_your_drive_settings[userfolder_backend_auto_root][view_roles]', $selected); ?>
                  </div>
                </div>
                <?php
            }
            ?>
          </div>
        </div>
        <!-- End UserFolders Tab -->


        <!--  Advanced Tab -->
        <div id="settings_advanced"  class="useyourdrive-tab-panel">
          <div class="useyourdrive-tab-panel-header"><?php esc_html_e('Advanced', 'wpcloudplugins'); ?></div>

          <?php if (false === $network_wide_authorization) { ?>
              <div class="useyourdrive-option-title"><?php esc_html_e('"Lost Authorization" notification', 'wpcloudplugins'); ?></div>
              <div class="useyourdrive-option-description"><?php esc_html_e('If the plugin somehow loses its authorization, a notification email will be send to the following email address', 'wpcloudplugins'); ?>:</div>
              <input class="useyourdrive-option-input-large" type="text" name="use_your_drive_settings[lostauthorization_notification]" id="lostauthorization_notification" value="<?php echo esc_attr($this->settings['lostauthorization_notification']); ?>">  

              <div class="useyourdrive-option-title"><?php esc_html_e('Own App', 'wpcloudplugins'); ?>
                <div class="useyourdrive-onoffswitch">
                  <input type='hidden' value='No' name='use_your_drive_settings[googledrive_app_own]'/>
                  <input type="checkbox" name="use_your_drive_settings[googledrive_app_own]" id="googledrive_app_own" class="useyourdrive-onoffswitch-checkbox" <?php echo (empty($this->settings['googledrive_app_client_id']) || empty($this->settings['googledrive_app_client_secret'])) ? '' : 'checked="checked"'; ?> data-div-toggle="own-app"/>
                  <label class="useyourdrive-onoffswitch-label" for="googledrive_app_own"></label>
                </div>
              </div>

              <div class="useyourdrive-suboptions own-app <?php echo (empty($this->settings['googledrive_app_client_id']) || empty($this->settings['googledrive_app_client_secret'])) ? 'hidden' : ''; ?> ">
                <div class="useyourdrive-option-description">
                  <strong>Using your own Google App is <u>optional</u></strong>. For an easy setup you can just use the default App of the plugin itself by leaving the ID and Secret empty. The advantage of using your own app is limited. If you decided to create your own Google App anyway, please enter your settings. In the <a href="https://florisdeleeuwnl.zendesk.com/hc/en-us/articles/201804806--How-do-I-create-my-own-Google-Drive-App-" target="_blank">documentation</a> you can find how you can create a Google App.
                  <br/><br/>
                  <div class="wpcp-warning">
                    <i><strong><?php esc_html_e('NOTICE', 'wpcloudplugins'); ?></strong>: <?php esc_html_e('If you encounter any issues when trying to use your own App, please fall back on the default App by disabling this setting', 'wpcloudplugins'); ?>.</i>
                  </div>
                </div>

                <div class="useyourdrive-option-title">Google Client ID</div>
                <div class="useyourdrive-option-description"><?php esc_html_e('Only if you want to use your own App, insert your Client ID here', 'wpcloudplugins'); ?>.</div>
                <input class="useyourdrive-option-input-large" type="text" name="use_your_drive_settings[googledrive_app_client_id]" id="googledrive_app_client_id" value="<?php echo esc_attr($this->settings['googledrive_app_client_id']); ?>" placeholder="<--- <?php esc_html_e('Leave empty for easy setup', 'wpcloudplugins'); ?> --->" >

                <div class="useyourdrive-option-title">Google Client Secret</div>
                <div class="useyourdrive-option-description"><?php esc_html_e('If you want to use your own App, insert your Client Secret here', 'wpcloudplugins'); ?>.</div>
                <input class="useyourdrive-option-input-large" type="text" name="use_your_drive_settings[googledrive_app_client_secret]" id="googledrive_app_client_secret" value="<?php echo esc_attr($this->settings['googledrive_app_client_secret']); ?>" placeholder="<--- <?php esc_html_e('Leave empty for easy setup', 'wpcloudplugins'); ?> --->" >   

                <div>
                  <div class="useyourdrive-option-title">OAuth 2.0 Redirect URI</div>
                  <div class="useyourdrive-option-description"><?php esc_html_e('Set the redirect URI in your application to the following', 'wpcloudplugins'); ?>:</div>
                  <code style="user-select:initial">
                    <?php
                    if ($this->get_app()->has_plugin_own_app()) {
                        echo $this->get_app()->get_redirect_uri();
                    } else {
                        esc_html_e('Enter Client ID and Secret, save settings and reload the page to see the Redirect URI you will need', 'wpcloudplugins');
                    }
                    ?>
                  </code>
                </div>
              </div>

              <div>
                <div class="useyourdrive-option-title"><?php esc_html_e('Google Workspace Domain', 'wpcloudplugins'); ?></div>
                <div class="useyourdrive-option-description"><?php esc_html_e('If you have a Google Workspace Domain and you want to share your documents ONLY with users having an account in your Google Workspace Domain, please insert your domain. If you want your documents to be accessible to the public, leave this setting empty.', 'wpcloudplugins'); ?>.</div>
                <input class="useyourdrive-option-input-large" type="text" name="use_your_drive_settings[permission_domain]" id="permission_domain" placeholder="<?php esc_html_e('Leave empty if files need to publicly available', 'wpcloudplugins'); ?>" value="<?php echo esc_attr($this->settings['permission_domain']); ?>">   
              </div>

          <?php } ?>

          <div class="useyourdrive-option-title"><?php esc_html_e('Manage Permission', 'wpcloudplugins'); ?>
            <div class="useyourdrive-onoffswitch">
              <input type='hidden' value='No' name='use_your_drive_settings[manage_permissions]'/>
              <input type="checkbox" name="use_your_drive_settings[manage_permissions]" id="manage_permissions" class="useyourdrive-onoffswitch-checkbox" <?php echo ('Yes' === $this->settings['manage_permissions']) ? 'checked="checked"' : ''; ?> />
              <label class="useyourdrive-onoffswitch-label" for="manage_permissions"></label>
            </div>
            <div class="useyourdrive-option-description"><?php esc_html_e('If you want to manage the sharing permissions by manually yourself, disable the -Manage Permissions- function.', 'wpcloudplugins'); ?>.</div>
          </div>

          <div class="useyourdrive-option-title"><?php esc_html_e('Load Javascripts on all pages', 'wpcloudplugins'); ?>
            <div class="useyourdrive-onoffswitch">
              <input type='hidden' value='No' name='use_your_drive_settings[always_load_scripts]'/>
              <input type="checkbox" name="use_your_drive_settings[always_load_scripts]" id="always_load_scripts" class="useyourdrive-onoffswitch-checkbox" <?php echo ('Yes' === $this->settings['always_load_scripts']) ? 'checked="checked"' : ''; ?> />
              <label class="useyourdrive-onoffswitch-label" for="always_load_scripts"></label>
            </div>
            <div class="useyourdrive-option-description"><?php esc_html_e('By default the plugin will only load it scripts when the shortcode is present on the page. If you are dynamically loading content via AJAX calls and the plugin does not show up, please enable this setting', 'wpcloudplugins'); ?>.</div>
          </div>

          <div class="useyourdrive-option-title"><?php esc_html_e('Enable Font Awesome Library v4 compatibility', 'wpcloudplugins'); ?>
            <div class="useyourdrive-onoffswitch">
              <input type='hidden' value='No' name='use_your_drive_settings[fontawesomev4_shim]'/>
              <input type="checkbox" name="use_your_drive_settings[fontawesomev4_shim]" id="fontawesomev4_shim" class="useyourdrive-onoffswitch-checkbox" <?php echo ('Yes' === $this->settings['fontawesomev4_shim']) ? 'checked="checked"' : ''; ?> />
              <label class="useyourdrive-onoffswitch-label" for="fontawesomev4_shim"></label>
            </div>
            <div class="useyourdrive-option-description"><?php esc_html_e('If your theme is loading the old Font Awesome icon library (v4), it can cause conflict with the (v5) icons of this plugin. If you are having trouble with the icons, please enable this setting for backwards compatibility', 'wpcloudplugins'); ?>. <?php esc_html_e('To disable the Font Awesome library of this plugin completely, add this to your wp-config.php file', 'wpcloudplugins'); ?>: <code>define('WPCP_DISABLE_FONTAWESOME', true);</code></div>
          </div>            

          <div class="useyourdrive-option-title"><?php esc_html_e('Enable Gzip compression', 'wpcloudplugins'); ?>
            <div class="useyourdrive-onoffswitch">
              <input type='hidden' value='No' name='use_your_drive_settings[gzipcompression]'/>
              <input type="checkbox" name="use_your_drive_settings[gzipcompression]" id="gzipcompression" class="useyourdrive-onoffswitch-checkbox" <?php echo ('Yes' === $this->settings['gzipcompression']) ? 'checked="checked"' : ''; ?> />
              <label class="useyourdrive-onoffswitch-label" for="gzipcompression"></label>
            </div>
          </div>

          <div class="useyourdrive-option-description"><?php esc_html_e("Enables gzip-compression if the visitor's browser can handle it. This will increase the performance of the plugin if you are displaying large amounts of files and it reduces bandwidth usage as well. It uses the PHP ob_gzhandler() callback. Please use this setting with caution. Always test if the plugin still works on the Front-End as some servers are already configured to gzip content!", 'wpcloudplugins'); ?></div>

          <div class="useyourdrive-option-title"><?php esc_html_e('Nonce Validation', 'wpcloudplugins'); ?>
            <div class="useyourdrive-onoffswitch">
              <input type='hidden' value='No' name='use_your_drive_settings[nonce_validation]'/>
              <input type="checkbox" name="use_your_drive_settings[nonce_validation]" id="nonce_validation" class="useyourdrive-onoffswitch-checkbox" <?php echo ('Yes' === $this->settings['nonce_validation']) ? 'checked="checked"' : ''; ?> />
              <label class="useyourdrive-onoffswitch-label" for="nonce_validation"></label>
            </div></div>
          <div class="useyourdrive-option-description"><?php esc_html_e('The plugin uses, among others, the WordPress Nonce system to protect you against several types of attacks including CSRF. Disable this in case you are encountering a conflict with a plugin that alters this system', 'wpcloudplugins'); ?>. </div>
          <div class="wpcp-warning">
            <i><strong>NOTICE</strong>: Please use this setting with caution! Only disable it when really necessary.</i>
          </div>

          <div class="useyourdrive-option-title"><?php esc_html_e('Download method', 'wpcloudplugins'); ?></div>
          <div class="useyourdrive-option-description"><?php esc_html_e('Select the method that should be used to download your files. Default is to redirect the user to a temporarily url. If you want to use your server as a proxy just set it to Download via Server', 'wpcloudplugins'); ?>.</div>
          <select type="text" name="use_your_drive_settings[download_method]" id="download_method">
            <option value="redirect" <?php echo 'redirect' === $this->settings['download_method'] ? "selected='selected'" : ''; ?>><?php esc_html_e('Redirect to download url (fast)', 'wpcloudplugins'); ?></option>
            <option value="proxy" <?php echo 'proxy' === $this->settings['download_method'] ? "selected='selected'" : ''; ?>><?php esc_html_e('Use your Server as proxy (slow)', 'wpcloudplugins'); ?></option>
          </select>   

          <div class="useyourdrive-option-title"><?php esc_html_e('Delete settings on Uninstall', 'wpcloudplugins'); ?>
            <div class="useyourdrive-onoffswitch">
              <input type='hidden' value='No' name='use_your_drive_settings[uninstall_reset]'/>
              <input type="checkbox" name="use_your_drive_settings[uninstall_reset]" id="uninstall_reset" class="useyourdrive-onoffswitch-checkbox" <?php echo ('Yes' === $this->settings['uninstall_reset']) ? 'checked="checked"' : ''; ?> />
              <label class="useyourdrive-onoffswitch-label" for="uninstall_reset"></label>
            </div>
            </div>
          <div class="useyourdrive-option-description"><?php esc_html_e('When you uninstall the plugin, what do you want to do with your settings? You can save them for next time, or wipe them back to factory settings.', 'wpcloudplugins'); ?>. </div>
          <div class="wpcp-warning">
            <i><strong>NOTICE</strong>: <?php echo esc_html__('When you reset the settings, the plugin will not longer be linked to your accounts, but their authorization will not be revoked', 'wpcloudplugins').'. '.esc_html__('You can revoke the authorization via the General tab', 'wpcloudplugins').'.'; ?></a></i>
          </div>

        </div>
        <!-- End Advanced Tab -->

        <!-- Integrations Tab -->
        <div id="settings_integrations"  class="useyourdrive-tab-panel">
          <div class="useyourdrive-tab-panel-header"><?php esc_html_e('Integrations', 'wpcloudplugins'); ?></div>

          <div class="useyourdrive-accordion">
            <div class="useyourdrive-accordion-title useyourdrive-option-title">Social Sharing Buttons</div>
            <div>
              <div class="useyourdrive-option-description"><?php esc_html_e('Select which sharing buttons should be accessible via the sharing dialogs of the plugin.', 'wpcloudplugins'); ?></div>

              <div class="shareon shareon-settings">
                <?php foreach ($this->settings['share_buttons'] as $button => $value) {
                        $title = ucfirst($button);
                        echo "<button type='button' class='wpcp-shareon-toggle-button {$button} shareon-{$value} ' title='{$title}'></button>";
                        echo "<input type='hidden' value='{$value}' name='use_your_drive_settings[share_buttons][{$button}]'/>";
                    }
                ?>
              </div>
            </div>
          </div>


          <div class="useyourdrive-accordion">
            <div class="useyourdrive-accordion-title useyourdrive-option-title"><?php esc_html_e('Shortlinks API', 'wpcloudplugins'); ?></div>

            <div>
              <div class="useyourdrive-option-description"><?php esc_html_e('Select which Url Shortener Service you want to use', 'wpcloudplugins'); ?>.</div>
              <select type="text" name="use_your_drive_settings[shortlinks]" id="wpcp-shortlinks-selector">
                <option value="None"  <?php echo 'None' === $this->settings['shortlinks'] ? "selected='selected'" : ''; ?>>None</option>
                <!-- <option value="Firebase"  <?php echo 'Firebase' === $this->settings['shortlinks'] ? "selected='selected'" : ''; ?>>Google Firebase Dynamic Links</option> -->
                <option value="Shorte.st"  <?php echo 'Shorte.st' === $this->settings['shortlinks'] ? "selected='selected'" : ''; ?>>Shorte.st</option>
                <option value="Rebrandly"  <?php echo 'Rebrandly' === $this->settings['shortlinks'] ? "selected='selected'" : ''; ?>>Rebrandly</option>
                <option value="Bit.ly"  <?php echo 'Bit.ly' === $this->settings['shortlinks'] ? "selected='selected'" : ''; ?>>Bit.ly</option>
              </select>   

              <div class="useyourdrive-suboptions option shortest" <?php echo 'Shorte.st' !== $this->settings['shortlinks'] ? "style='display:none;'" : ''; ?>>
                <div class="useyourdrive-option-description"><?php esc_html_e('Sign up for Shorte.st', 'wpcloudplugins'); ?> and <a href="https://shorte<?php echo '.st/tools/api'; ?>" target="_blank">grab your API token</a></div>

                <div class="useyourdrive-option-title"><?php esc_html_e('API token', 'wpcloudplugins'); ?></div>
                <input class="useyourdrive-option-input-large" type="text" name="use_your_drive_settings[shortest_apikey]" id="shortest_apikey" value="<?php echo esc_attr($this->settings['shortest_apikey']); ?>">
              </div>

              <div class="useyourdrive-suboptions option bitly" <?php echo 'Bit.ly' !== $this->settings['shortlinks'] ? "style='display:none;'" : ''; ?>>
                <div class="useyourdrive-option-description"><a href="https://bitly.com/a/sign_up" target="_blank"><?php esc_html_e('Sign up for Bitly', 'wpcloudplugins'); ?></a> and <a href="http://bitly.com/a/your_api_key" target="_blank">generate an API key</a></div>

                <div class="useyourdrive-option-title">Bitly Login</div>
                <input class="useyourdrive-option-input-large" type="text" name="use_your_drive_settings[bitly_login]" id="bitly_login" value="<?php echo esc_attr($this->settings['bitly_login']); ?>">

                <div class="useyourdrive-option-title">Bitly API key</div>
                <input class="useyourdrive-option-input-large" type="text" name="use_your_drive_settings[bitly_apikey]" id="bitly_apikey" value="<?php echo esc_attr($this->settings['bitly_apikey']); ?>">
              </div> 

              <div class="useyourdrive-suboptions option rebrandly" <?php echo 'Rebrandly' !== $this->settings['shortlinks'] ? "style='display:none;'" : ''; ?>>
                <div class="useyourdrive-option-description"><a href="https://app.rebrandly.com/" target="_blank"><?php esc_html_e('Sign up for Rebrandly', 'wpcloudplugins'); ?></a> and <a href="https://app.rebrandly.com/account/api-keys" target="_blank">grab your API token</a></div>

                <div class="useyourdrive-option-title">Rebrandly API key</div>
                <input class="useyourdrive-option-input-large" type="text" name="use_your_drive_settings[rebrandly_apikey]" id="rebrandly_apikey" value="<?php echo esc_attr($this->settings['rebrandly_apikey']); ?>">

                <div class="useyourdrive-option-title">Rebrandly Domain (optional)</div>
                <input class="useyourdrive-option-input-large" type="text" name="use_your_drive_settings[rebrandly_domain]" id="rebrandly_domain" value="<?php echo esc_attr($this->settings['rebrandly_domain']); ?>">

                <div class="useyourdrive-option-title">Rebrandly WorkSpace ID (optional)</div>
                <input class="useyourdrive-option-input-large" type="text" name="use_your_drive_settings[rebrandly_workspace]" id="rebrandly_workspace" value="<?php echo esc_attr($this->settings['rebrandly_workspace']); ?>">
              </div>
            </div>
          </div> 
          
          <div class="useyourdrive-accordion">

            <div class="useyourdrive-accordion-title useyourdrive-option-title">ReCaptcha V3         </div>
            <div>

              <div class="useyourdrive-option-description"><?php esc_html_e('reCAPTCHA protects you against spam and other types of automated abuse. With this reCAPTCHA (V3) integration module, you can block abusive downloads of your files by bots. Create your own credentials via the link below.', 'wpcloudplugins'); ?> <br/><br/><a href="https://www.google.com/recaptcha/admin" target="_blank">Manage your reCAPTCHA API keys</a></div>

              <div class="useyourdrive-option-title"><?php esc_html_e('Site Key', 'wpcloudplugins'); ?></div>
              <input class="useyourdrive-option-input-large" type="text" name="use_your_drive_settings[recaptcha_sitekey]" id="recaptcha_sitekey" value="<?php echo esc_attr($this->settings['recaptcha_sitekey']); ?>">

              <div class="useyourdrive-option-title"><?php esc_html_e('Secret Key', 'wpcloudplugins'); ?></div>
              <input class="useyourdrive-option-input-large" type="text" name="use_your_drive_settings[recaptcha_secret]" id="recaptcha_secret" value="<?php echo esc_attr($this->settings['recaptcha_secret']); ?>">
            </div>
          </div>

          <div class="useyourdrive-accordion">

            <div class="useyourdrive-accordion-title useyourdrive-option-title"><?php esc_html_e('Video Advertisements (IMA/VAST)', 'wpcloudplugins'); ?> </div>
            <div>
              <div class="useyourdrive-option-description"><?php esc_html_e('The mediaplayer of the plugin supports VAST XML advertisments to offer monetization options for your videos. You can enable advertisments for the complete site and per Media Player shortcode. Currently, this plugin only supports Linear elements with MP4', 'wpcloudplugins'); ?>.</div>

              <div class="useyourdrive-option-title"><?php echo 'VAST XML Tag Url'; ?></div>
              <input class="useyourdrive-option-input-large" type="text" name="use_your_drive_settings[mediaplayer_ads_tagurl]" id="mediaplayer_ads_tagurl" value="<?php echo esc_attr($this->settings['mediaplayer_ads_tagurl']); ?>" placeholder="<?php echo esc_html__('Leave empty to disable Ads', 'wpcloudplugins'); ?>" />

              <div class="wpcp-warning">
                <i><strong><?php esc_html_e('NOTICE', 'wpcloudplugins'); ?></strong>: <?php esc_html_e('If you are unable to see the example VAST url below, please make sure you do not have an ad blocker enabled.', 'wpcloudplugins'); ?>.</i>
              </div>

              <a href="https://pubads.g.doubleclick.net/gampad/ads?sz=640x480&iu=/124319096/external/single_ad_samples&ciu_szs=300x250&impl=s&gdfp_req=1&env=vp&output=vast&unviewed_position_start=1&cust_params=deployment%3Ddevsite%26sample_ct%3Dskippablelinear&correlator=" rel="no-follow">Example Tag URL</a>

              <div class="useyourdrive-option-title"><?php esc_html_e('Enable Skip Button', 'wpcloudplugins'); ?>
                <div class="useyourdrive-onoffswitch">
                  <input type='hidden' value='No' name='use_your_drive_settings[mediaplayer_ads_skipable]'/>
                  <input type="checkbox" name="use_your_drive_settings[mediaplayer_ads_skipable]" id="mediaplayer_ads_skipable" class="useyourdrive-onoffswitch-checkbox" <?php echo ('Yes' === $this->settings['mediaplayer_ads_skipable']) ? 'checked="checked"' : ''; ?> data-div-toggle="ads_skipable"/>
                  <label class="useyourdrive-onoffswitch-label" for="mediaplayer_ads_skipable"></label>
                </div>
              </div>

              <div class="useyourdrive-suboptions ads_skipable <?php echo ('Yes' === $this->settings['mediaplayer_ads_skipable']) ? '' : 'hidden'; ?> ">
                <div class="useyourdrive-option-title"><?php esc_html_e('Skip button visible after (seconds)', 'wpcloudplugins'); ?></div>
                <input class="useyourdrive-option-input-large" type="text" name="use_your_drive_settings[mediaplayer_ads_skipable_after]" id="mediaplayer_ads_skipable_after" value="<?php echo esc_attr($this->settings['mediaplayer_ads_skipable_after']); ?>" placeholder="5">
                <div class="useyourdrive-option-description"><?php esc_html_e('Allow user to skip advertisment after after the following amount of seconds have elapsed', 'wpcloudplugins'); ?></div>
              </div>
            </div>
          </div>
        </div>  
        <!-- End Integrations info -->

        <!-- Notifications Tab -->
        <div id="settings_notifications"  class="useyourdrive-tab-panel">

          <div class="useyourdrive-tab-panel-header"><?php esc_html_e('Notifications', 'wpcloudplugins'); ?></div>

          <div class="useyourdrive-accordion">

            <div class="useyourdrive-accordion-title useyourdrive-option-title"><?php esc_html_e('Download Notifications', 'wpcloudplugins'); ?>         </div>
            <div>

              <div class="useyourdrive-option-title"><?php esc_html_e('Subject download notification', 'wpcloudplugins'); ?>:</div>
              <input class="useyourdrive-option-input-large" type="text" name="use_your_drive_settings[download_template_subject]" id="download_template_subject" value="<?php echo esc_attr($this->settings['download_template_subject']); ?>">

              <div class="useyourdrive-option-title"><?php esc_html_e('Subject zip notification', 'wpcloudplugins'); ?>:</div>
              <input class="useyourdrive-option-input-large" type="text" name="use_your_drive_settings[download_template_subject_zip]" id="download_template_subject_zip" value="<?php echo esc_attr($this->settings['download_template_subject_zip']); ?>">

              <div class="useyourdrive-option-title"><?php esc_html_e('Template download', 'wpcloudplugins'); ?> (HTML):</div>
              <?php
              ob_start();
              wp_editor($this->settings['download_template'], 'use_your_drive_settings_download_template', [
                  'textarea_name' => 'use_your_drive_settings[download_template]',
                  'teeny' => true,
                  'tinymce' => false,
                  'textarea_rows' => 15,
                  'media_buttons' => false,
              ]);
              echo ob_get_clean();
              ?>

              <br/>


              <div class="useyourdrive-option-description"><?php echo sprintf(esc_html__('Available placeholders: %s', 'wpcloudplugins'), ''); ?>
                <code>%site_name%</code>, 
                <code>%number_of_files%</code>, 
                <code>%user_name%</code>, 
                <code>%user_email%</code>, 
                <code>%admin_email%</code>,
                <code>%account_email%</code>,  
                <code>%file_name%</code>, 
                <code>%file_size%</code>, 
                <code>%file_icon%</code>, 
                <code>%file_relative_path%</code>, 
                <code>%file_absolute_path%</code>,
                <code>%file_cloud_shortlived_download_url%</code>, 
                <code>%file_cloud_preview_url%</code>, 
                <code>%file_cloud_shared_url%</code>, 
                <code>%file_download_url%</code>,
                <code>%folder_name%</code>,
                <code>%folder_relative_path%</code>,
                <code>%folder_absolute_path%</code>,
                <code>%folder_url%</code>,
                <code>%ip%</code>, 
                <code>%location%</code>, 
              </div>
            </div>


            <div class="useyourdrive-accordion-title useyourdrive-option-title"><?php esc_html_e('Upload Notifications', 'wpcloudplugins'); ?></div>
            <div>
              <div class="useyourdrive-option-title"><?php esc_html_e('Subject upload notification', 'wpcloudplugins'); ?>:</div>
              <input class="useyourdrive-option-input-large" type="text" name="use_your_drive_settings[upload_template_subject]" id="upload_template_subject" value="<?php echo esc_attr($this->settings['upload_template_subject']); ?>">

              <div class="useyourdrive-option-title"><?php esc_html_e('Template upload', 'wpcloudplugins'); ?> (HTML):</div>
              <?php
              ob_start();
              wp_editor($this->settings['upload_template'], 'use_your_drive_settings_upload_template', [
                  'textarea_name' => 'use_your_drive_settings[upload_template]',
                  'teeny' => true,
                  'tinymce' => false,
                  'textarea_rows' => 15,
                  'media_buttons' => false,
              ]);
              echo ob_get_clean();
              ?>

              <br/>

              <div class="useyourdrive-option-description"><?php echo sprintf(esc_html__('Available placeholders: %s', 'wpcloudplugins'), ''); ?>
                <code>%site_name%</code>, 
                <code>%number_of_files%</code>, 
                <code>%user_name%</code>, 
                <code>%user_email%</code>, 
                <code>%admin_email%</code>, 
                <code>%account_email%</code>,  
                <code>%file_name%</code>, 
                <code>%file_size%</code>, 
                <code>%file_icon%</code>, 
                <code>%file_relative_path%</code>,
                <code>%file_absolute_path%</code>,
                <code>%file_cloud_shortlived_download_url%</code>, 
                <code>%file_cloud_preview_url%</code>, 
                <code>%file_cloud_shared_url%</code>, 
                <code>%file_download_url%</code>, 
                <code>%folder_name%</code>,
                <code>%folder_relative_path%</code>,
                <code>%folder_absolute_path%</code>,
                <code>%folder_url%</code>,
                <code>%ip%</code>, 
                <code>%location%</code>, 
              </div>            
            </div>


            <div class="useyourdrive-accordion-title useyourdrive-option-title"><?php esc_html_e('Delete Notifications', 'wpcloudplugins'); ?>         </div>
            <div>
              <div class="useyourdrive-option-title"><?php esc_html_e('Subject deletion notification', 'wpcloudplugins'); ?>:</div>
              <input class="useyourdrive-option-input-large" type="text" name="use_your_drive_settings[delete_template_subject]" id="delete_template_subject" value="<?php echo esc_attr($this->settings['delete_template_subject']); ?>">
              <div class="useyourdrive-option-title"><?php esc_html_e('Template deletion', 'wpcloudplugins'); ?> (HTML):</div>

              <?php
              ob_start();
              wp_editor($this->settings['delete_template'], 'use_your_drive_settings_delete_template', [
                  'textarea_name' => 'use_your_drive_settings[delete_template]',
                  'teeny' => true,
                  'tinymce' => false,
                  'textarea_rows' => 15,
                  'media_buttons' => false,
              ]);
              echo ob_get_clean();
              ?>

              <br/>

              <div class="useyourdrive-option-description"><?php echo sprintf(esc_html__('Available placeholders: %s', 'wpcloudplugins'), ''); ?>
                <code>%site_name%</code>, 
                <code>%number_of_files%</code>, 
                <code>%user_name%</code>, 
                <code>%user_email%</code>, 
                <code>%admin_email%</code>, 
                <code>%account_email%</code>,  
                <code>%file_name%</code>, 
                <code>%file_size%</code>, 
                <code>%file_icon%</code>, 
                <code>%file_relative_path%</code>,
                <code>%file_absolute_path%</code>,
                <code>%file_cloud_shortlived_download_url%</code>, 
                <code>%file_cloud_preview_url%</code>, 
                <code>%file_cloud_shared_url%</code>, 
                <code>%file_download_url%</code>,
                <code>%folder_name%</code>,
                <code>%folder_relative_path%</code>,
                <code>%folder_absolute_path%</code>,
                <code>%folder_url%</code>,
                <code>%ip%</code>, 
                <code>%location%</code>, 
              </div>

            </div>

            <div class="useyourdrive-accordion-title useyourdrive-option-title"><?php esc_html_e('Template %filelist% placeholder', 'wpcloudplugins'); ?>         </div>
            <div>
              <div class="useyourdrive-option-description"><?php esc_html_e('Template for File item in File List in the download/upload/delete notification template', 'wpcloudplugins'); ?> (HTML).</div>
              <?php
              ob_start();
              wp_editor($this->settings['filelist_template'], 'use_your_drive_settings_filelist_template', [
                  'textarea_name' => 'use_your_drive_settings[filelist_template]',
                  'teeny' => true,
                  'tinymce' => false,
                  'textarea_rows' => 15,
                  'media_buttons' => false,
              ]);
              echo ob_get_clean();
              ?>

              <br/>

              <div class="useyourdrive-option-description"><?php echo sprintf(esc_html__('Available placeholders: %s', 'wpcloudplugins'), ''); ?>
                <code>%file_name%</code>, 
                <code>%file_size%</code>, 
                <code>%file_lastedited%</code>, 
                <code>%file_created%</code>,                
                <code>%file_icon%</code>, 
                <code>%file_cloud_shortlived_download_url%</code>, 
                <code>%file_cloud_preview_url%</code>, 
                <code>%file_cloud_shared_url%</code>, 
                <code>%file_download_url%</code>,
                <code>%file_relative_path%</code>, 
                <code>%file_absolute_path%</code>, 
                <code>%folder_relative_path%</code>,
                <code>%folder_absolute_path%</code>,
                <code>%folder_url%</code>,
              </div>

            </div>
          </div>

          <div id="wpcp-reset-notifications-button" type="button" class="simple-button blue"><?php esc_html_e('Reset to default notifications', 'wpcloudplugins'); ?>&nbsp;<div class="wpcp-spinner"></div></div>

        </div>
        <!-- End Notifications Tab -->

        <!--  Permissions Tab -->
        <div id="settings_permissions"  class="useyourdrive-tab-panel">
          <div class="useyourdrive-tab-panel-header"><?php esc_html_e('Permissions', 'wpcloudplugins'); ?></div>

          <div class="useyourdrive-accordion">

            <div class="useyourdrive-accordion-title useyourdrive-option-title"><?php esc_html_e('Change Plugin Settings', 'wpcloudplugins'); ?> </div>
            <div>
              <?php wp_roles_and_users_input('use_your_drive_settings[permissions_edit_settings]', $this->settings['permissions_edit_settings']); ?>
            </div>

            <div class="useyourdrive-accordion-title useyourdrive-option-title"><?php esc_html_e('Link Users to Private Folders', 'wpcloudplugins'); ?></div>
            <div>
              <?php wp_roles_and_users_input('use_your_drive_settings[permissions_link_users]', $this->settings['permissions_link_users']); ?>
            </div>

            <div class="useyourdrive-accordion-title useyourdrive-option-title"><?php esc_html_e('See Reports', 'wpcloudplugins'); ?></div>
            <div>
              <?php wp_roles_and_users_input('use_your_drive_settings[permissions_see_dashboard]', $this->settings['permissions_see_dashboard']); ?>
            </div>                 

            <div class="useyourdrive-accordion-title useyourdrive-option-title"><?php esc_html_e('See Back-End Filebrowser', 'wpcloudplugins'); ?></div>
            <div>
              <?php wp_roles_and_users_input('use_your_drive_settings[permissions_see_filebrowser]', $this->settings['permissions_see_filebrowser']); ?>
            </div>

            <div class="useyourdrive-accordion-title useyourdrive-option-title"><?php esc_html_e('Add Plugin Shortcodes', 'wpcloudplugins'); ?></div>
            <div>
              <?php wp_roles_and_users_input('use_your_drive_settings[permissions_add_shortcodes]', $this->settings['permissions_add_shortcodes']); ?>
            </div>

            <div class="useyourdrive-accordion-title useyourdrive-option-title"><?php esc_html_e('Add Direct links', 'wpcloudplugins'); ?></div>
            <div>
              <?php wp_roles_and_users_input('use_your_drive_settings[permissions_add_links]', $this->settings['permissions_add_links']); ?>
            </div>

            <div class="useyourdrive-accordion-title useyourdrive-option-title"><?php esc_html_e('Embed Documents', 'wpcloudplugins'); ?></div>
            <div>
              <?php wp_roles_and_users_input('use_your_drive_settings[permissions_add_embedded]', $this->settings['permissions_add_embedded']); ?>
            </div>

          </div>

        </div>
        <!-- End Permissions Tab -->

        <!--  Statistics Tab -->
        <div id="settings_stats"  class="useyourdrive-tab-panel">
          <div class="useyourdrive-tab-panel-header"><?php esc_html_e('Statistics', 'wpcloudplugins'); ?></div>

          <div class="useyourdrive-option-title"><?php esc_html_e('Log Events', 'wpcloudplugins'); ?>
            <div class="useyourdrive-onoffswitch">
              <input type='hidden' value='No' name='use_your_drive_settings[log_events]'/>
              <input type="checkbox" name="use_your_drive_settings[log_events]" id="log_events" class="useyourdrive-onoffswitch-checkbox" <?php echo ('Yes' === $this->settings['log_events']) ? 'checked="checked"' : ''; ?> data-div-toggle="events_options"/>
              <label class="useyourdrive-onoffswitch-label" for="log_events"></label>
            </div>
          </div>
          <div class="useyourdrive-option-description"><?php esc_html_e('Register all plugin events', 'wpcloudplugins'); ?>.</div>

          <div class="useyourdrive-suboptions events_options <?php echo ('Yes' === $this->settings['log_events']) ? '' : 'hidden'; ?> ">
            <div class="useyourdrive-option-title"><?php esc_html_e('Summary Email', 'wpcloudplugins'); ?>
              <div class="useyourdrive-onoffswitch">
                <input type='hidden' value='No' name='use_your_drive_settings[event_summary]'/>
                <input type="checkbox" name="use_your_drive_settings[event_summary]" id="event_summary" class="useyourdrive-onoffswitch-checkbox" <?php echo ('Yes' === $this->settings['event_summary']) ? 'checked="checked"' : ''; ?> data-div-toggle="event_summary"/>
                <label class="useyourdrive-onoffswitch-label" for="event_summary"></label>
              </div>
            </div>
            <div class="useyourdrive-option-description"><?php esc_html_e('Email a summary of all the events that are logged with the plugin', 'wpcloudplugins'); ?>.</div>

            <div class="event_summary <?php echo ('Yes' === $this->settings['event_summary']) ? '' : 'hidden'; ?> ">

              <div class="useyourdrive-option-title"><?php esc_html_e('Interval', 'wpcloudplugins'); ?></div>
              <div class="useyourdrive-option-description"><?php esc_html_e('Please select the interval the summary needs to be send', 'wpcloudplugins'); ?>.</div>
              <select type="text" name="use_your_drive_settings[event_summary_period]" id="event_summary_period">
                <option value="daily"  <?php echo 'daily' === $this->settings['event_summary_period'] ? "selected='selected'" : ''; ?>><?php esc_html_e('Every day', 'wpcloudplugins'); ?></option>
                <option value="weekly"  <?php echo 'weekly' === $this->settings['event_summary_period'] ? "selected='selected'" : ''; ?>><?php esc_html_e('Weekly', 'wpcloudplugins'); ?></option>
                <option value="monthly"  <?php echo 'monthly' === $this->settings['event_summary_period'] ? "selected='selected'" : ''; ?>><?php esc_html_e('Monthly', 'wpcloudplugins'); ?></option>
              </select>   

              <div class="useyourdrive-option-title"><?php esc_html_e('Recipients', 'wpcloudplugins'); ?></div>
              <div class="useyourdrive-option-description"><?php esc_html_e('Send the summary to the following email address(es)', 'wpcloudplugins'); ?>:</div>
              <input class="useyourdrive-option-input-large" type="text" name="use_your_drive_settings[event_summary_recipients]" id="event_summary_recipients" value="<?php echo esc_attr($this->settings['event_summary_recipients']); ?>" placeholder="<?php echo get_option('admin_email'); ?>">  
            </div>
          </div>


          <div class="useyourdrive-option-title"><?php esc_html_e('Google Analytics', 'wpcloudplugins'); ?>
            <div class="useyourdrive-onoffswitch">
              <input type='hidden' value='No' name='use_your_drive_settings[google_analytics]'/>
              <input type="checkbox" name="use_your_drive_settings[google_analytics]" id="google_analytics" class="useyourdrive-onoffswitch-checkbox" <?php echo ('Yes' === $this->settings['google_analytics']) ? 'checked="checked"' : ''; ?> />
              <label class="useyourdrive-onoffswitch-label" for="google_analytics"></label>
            </div>
          </div>
          <div class="useyourdrive-option-description"><?php esc_html_e('Would you like to see some statistics in Google Analytics?', 'wpcloudplugins'); ?>. <?php echo sprintf(esc_html__('If you enable this feature, please make sure you already added your %s Google Analytics web tracking %s code to your site.', 'wpcloudplugins'), "<a href='https://support.google.com/analytics/answer/1008080' target='_blank'>", '</a>'); ?>.</div>
        </div>
        <!-- End Statistics Tab -->

        <!-- System info Tab -->
        <div id="settings_system"  class="useyourdrive-tab-panel">
          <div class="useyourdrive-tab-panel-header"><?php esc_html_e('System information', 'wpcloudplugins'); ?></div>
          <?php echo $this->get_system_information(); ?>
        </div>
        <!-- End System info -->

        <!-- Tools Tab -->
        <div id="settings_tools"  class="useyourdrive-tab-panel">
          <div class="useyourdrive-tab-panel-header"><?php esc_html_e('Tools', 'wpcloudplugins'); ?></div>

          <div class="useyourdrive-option-title"><?php esc_html_e('Cache', 'wpcloudplugins'); ?></div>
          <?php echo $this->get_plugin_reset_cache_box(); ?>

          <div class="useyourdrive-option-title"><?php esc_html_e('Enable API log', 'wpcloudplugins'); ?>
            <div class="useyourdrive-onoffswitch">
              <input type='hidden' value='No' name='use_your_drive_settings[api_log]'/>
              <input type="checkbox" name="use_your_drive_settings[api_log]" id="api_log" class="useyourdrive-onoffswitch-checkbox" <?php echo ('Yes' === $this->settings['api_log']) ? 'checked="checked"' : ''; ?> />
              <label class="useyourdrive-onoffswitch-label" for="api_log"></label>
            </div>
            <div class="useyourdrive-option-description"><?php echo sprintf(wp_kses(__('When enabled, all API requests will be logged in the file <code>/wp-content/%s-cache/api.log</code>. Please note that this log file is not accessible via the browser on Apache servers.', 'wpcloudplugins'), ['code' => []]), 'use-your-drive'); ?>.</div>
          </div>

          <div class="useyourdrive-option-title"><?php esc_html_e('Reset to Factory Settings', 'wpcloudplugins'); ?></div>
          <?php echo $this->get_plugin_reset_plugin_box(); ?>

        </div>  
        <!-- End Tools -->

        <!-- Help Tab -->
        <div id="settings_help"  class="useyourdrive-tab-panel">
          <div class="useyourdrive-tab-panel-header"><?php esc_html_e('Support', 'wpcloudplugins'); ?></div>

          <div class="useyourdrive-option-title"><?php esc_html_e('Support & Documentation', 'wpcloudplugins'); ?></div>
          <div id="message">
            <p><?php esc_html_e('Check the documentation of the plugin in case you encounter any problems or are looking for support.', 'wpcloudplugins'); ?></p>
            <div id='wpcp-open-docs-button' type='button' class='simple-button blue'><?php esc_html_e('Open Documentation', 'wpcloudplugins'); ?></div>
          </div>
        </div>  
        <!-- End Help info -->
      </div>
    </div>
  </form>

  <!-- End Privacy Policy -->
  <div id="wpcp-privacy-policy" style='clear:both;display:none'>  
    <div class="useyourdrive useyourdrive-tb-content">
      <div class="useyourdrive-option-title"><?php esc_html_e('Requested scopes and justification', 'wpcloudplugins'); ?></div>
      <div class="useyourdrive-option-description"> <?php echo sprintf(esc_html__('In order to display your content stored on %s, you have to authorize it with your %s account.', 'wpcloudplugins'), 'Google Drive', 'Google'); ?> <?php _e('The authorization will ask you to grant the application the following scopes:', 'wpcloudplugins'); ?>

      <br/><br/>
      <table class="widefat">
        <thead>
          <tr>
            <th>Scope</th>
            <th>Reason</th>
          </tr>
        </thead>
        <tbody>        
          <tr>
            <td><code>https://www.googleapis.com/auth/drive</code></td>
            <td><?php echo sprintf(esc_html__('Allow the plugin to see, edit, create, and delete all of your %s files and files that are shared with you', 'wpcloudplugins'), 'Google Drive'); ?>.</td>
          </tr>
          <tr>
            <td><code>https://www.googleapis.com/auth/userinfo.email</code></td>
            <td><?php echo sprintf(esc_html__('Allow the plugin to see your primary %s email address. The email address will be displayed on this page for easy account identification.', 'wpcloudplugins'), 'Google Account'); ?> <?php esc_html__('This information will only be displayed on this page for easy account identification.', 'wpcloudplugins'); ?></td>
          </tr>
          <tr>
            <td><code>https://www.googleapis.com/auth/userinfo.profile</code></td>
            <td><?php (esc_html_e('Allow the plugin to see your publicly available personal info, like name and profile picture. Your name and profile picture will be displayed on this page for easy account identification.', 'wpcloudplugins')); ?></td>
          </tr>
        </tbody>
      </table>

      <br/>
      <div class="useyourdrive-option-title"><?php esc_html_e('Information about the data', 'wpcloudplugins'); ?></div>
      The authorization tokens will be stored, encrypted, on this server and is not accessible by the developer or any third party. When you use the Application, all communications are strictly between your server and the cloud storage service servers. The communication is encrypted and the communication will not go through WP Cloud Plugins servers. We do not collect and do not have access to your personal data.
      
      <br/><br/>
      <i class="fas fa-shield-alt"></i> <?php echo sprintf(esc_html__('Read the full %sPrivacy Policy%s if you have any further privacy concerns.', 'wpcloudplugins'), '<a href="https://www.wpcloudplugins.com/privacy-policy/privacy-policy-use-your-drive/">', '</a>'); ?></div>
    </div>
  </div>
  <!-- End Short Privacy Policy -->
  

</div>