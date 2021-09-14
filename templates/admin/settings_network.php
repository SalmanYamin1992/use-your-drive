<?php
$network_wide_authorization = $this->get_processor()->is_network_authorized();
?>
<div class="useyourdrive admin-settings">
  <form id="useyourdrive-options" method="post" action="<?php echo network_admin_url('edit.php?action='.$this->plugin_network_options_key); ?>">
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
          <?php if ($network_wide_authorization) { ?>
              <li id="settings_advanced_tab" data-tab="settings_advanced" ><a ><?php esc_html_e('Advanced', 'wpcloudplugins'); ?></a></li>
          <?php } ?>
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

          <div class="useyourdrive-option-title"><?php esc_html_e('Plugin License', 'wpcloudplugins'); ?></div>
          <?php
          echo $this->get_plugin_activated_box();
          ?>

          <?php if (is_plugin_active_for_network(USEYOURDRIVE_SLUG)) { ?>
              <div class="useyourdrive-option-title"><?php esc_html_e('Network Wide Authorization', 'wpcloudplugins'); ?>
                <div class="useyourdrive-onoffswitch">
                  <input type='hidden' value='No' name='use_your_drive_settings[network_wide]'/>
                  <input type="checkbox" name="use_your_drive_settings[network_wide]" id="wpcp-network_wide-button" class="useyourdrive-onoffswitch-checkbox" <?php echo (empty($network_wide_authorization)) ? '' : 'checked="checked"'; ?> data-div-toggle="network_wide"/>
                  <label class="useyourdrive-onoffswitch-label" for="wpcp-network_wide-button"></label>
                </div>
              </div>


              <?php
              if ($network_wide_authorization) {
                  ?>
                  <div class="useyourdrive-option-title"><?php esc_html_e('Accounts', 'wpcloudplugins'); ?></div>
                  <div class="useyourdrive-accounts-list">
                    <?php
                    $app = $this->get_app();
                  //$app->get_client()->setPrompt('select_account');
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
                    foreach ($this->get_main()->get_accounts()->list_accounts() as $account_id => $account) {
                        echo $this->get_plugin_authorization_box($account);
                    } ?>
                  </div>
                  <?php
              }
              ?>

              <?php
          }
          ?>

        </div>
        <!-- End General Tab -->


        <!--  Advanced Tab -->
        <?php if ($network_wide_authorization) { ?>
            <div id="settings_advanced"  class="useyourdrive-tab-panel">
              <div class="useyourdrive-tab-panel-header"><?php esc_html_e('Advanced', 'wpcloudplugins'); ?></div>

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

            </div>
        <?php } ?>
        <!-- End Advanced Tab -->

        <!-- System info Tab -->
        <div id="settings_system"  class="useyourdrive-tab-panel">
          <div class="useyourdrive-tab-panel-header"><?php esc_html_e('System information', 'wpcloudplugins'); ?></div>
          <?php echo $this->get_system_information(); ?>
        </div>
        <!-- End System info -->

        <!-- Help Tab -->
        <div id="settings_help"  class="useyourdrive-tab-panel">
          <div class="useyourdrive-tab-panel-header"><?php esc_html_e('Support', 'wpcloudplugins'); ?></div>

          <div class="useyourdrive-option-title"><?php esc_html_e('Support & Documentation', 'wpcloudplugins'); ?></div>
          <div id="message">
            <p><?php esc_html_e('Check the documentation of the plugin in case you encounter any problems or are looking for support.', 'wpcloudplugins'); ?></p>
            <div id='wpcp-open-docs-button' type='button' class='simple-button blue'><?php esc_html_e('Open Documentation', 'wpcloudplugins'); ?></div>
          </div>
          <br/>
          <div class="useyourdrive-option-title"><?php esc_html_e('Cache', 'wpcloudplugins'); ?></div>
          <?php echo $this->get_plugin_reset_cache_box(); ?>

        </div>  
      </div>
      <!-- End Help info -->
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