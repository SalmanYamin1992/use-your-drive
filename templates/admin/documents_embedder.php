<?php

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Exit if no permission to embed files
if (!(\TheLion\UseyourDrive\Helpers::check_user_role($this->settings['permissions_add_embedded']))) {
    exit();
}

// Add own styles and script and remove default ones
$this->load_scripts();
$this->load_styles();

function UseyourDrive_remove_all_scripts()
{
    global $wp_scripts;
    $wp_scripts->queue = [];

    wp_enqueue_script('jquery-effects-fade');
    wp_enqueue_script('jquery');
    wp_enqueue_script('UseyourDrive');
    wp_enqueue_script('UseyourDrive.DocumentEmbedder');
}

function UseyourDrive_remove_all_styles()
{
    global $wp_styles;
    $wp_styles->queue = [];
    wp_enqueue_style('UseyourDrive.ShortcodeBuilder');
    wp_enqueue_style('UseyourDrive.CustomStyle');
    wp_enqueue_style('Awesome-Font-5');
}

add_action('wp_print_scripts', 'UseyourDrive_remove_all_scripts', 1000);
add_action('wp_print_styles', 'UseyourDrive_remove_all_styles', 1000);

?>
<!DOCTYPE html
  PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title><?php echo esc_html__('Embed Files', 'wpcloudplugins'); ?></title>
  <?php wp_print_scripts(); ?>
  <?php wp_print_styles(); ?>
</head>

<body class="useyourdrive">
  <form action=" #" data-callback="<?php echo isset($_REQUEST['callback']) ? $_REQUEST['callback'] : ''; ?>">

    <div class="wrap">
      <div class="useyourdrive-header">

        <div class="useyourdrive-logo"><a href="https://www.wpcloudplugins.com" target="_blank"><img src="<?php echo USEYOURDRIVE_ROOTPATH; ?>/css/images/wpcp-logo-dark.svg" height="64" width="64"/></a></div>

        <div class="useyourdrive-form-buttons">
          <div id="do_embed" class="simple-button default">
            <?php esc_html_e('Embed Files', 'wpcloudplugins'); ?>&nbsp;<i class="fas fa-chevron-circle-right"
              aria-hidden="true"></i></div>
        </div>

        <div class="useyourdrive-title"><?php echo esc_html__('Embed Files', 'wpcloudplugins'); ?></div>
      </div>
      <div class="useyourdrive-panel useyourdrive-panel-full">
        <p><?php esc_html_e('Please note that the embedded files need to be public (with link)', 'wpcloudplugins'); ?></p>
        <?php

      // Add File Browser
      $atts = [
          'singleaccount' => '0',
          'dir' => 'drive',
          'mode' => 'files',
          'showfiles' => '1',
          'upload' => '0',
          'delete' => '0',
          'rename' => '0',
          'addfolder' => '0',
          'viewrole' => 'all',
          'candownloadzip' => '0',
          'search' => '1',
          'searchcontents' => '1',
          'showsharelink' => '0',
          'previewinline' => '0',
          'mcepopup' => 'embedded',
          'includeext' => '*',
          '_random' => 'embed',
      ];

      $user_folder_backend = apply_filters('useyourdrive_use_user_folder_backend', $this->settings['userfolder_backend']);

      if ('No' !== $user_folder_backend) {
          $atts['userfolders'] = $user_folder_backend;

          $private_root_folder = $this->settings['userfolder_backend_auto_root'];
          if ('auto' === $user_folder_backend && !empty($private_root_folder) && isset($private_root_folder['id'])) {
              if (!isset($private_root_folder['account']) || empty($private_root_folder['account'])) {
                  $main_account = $this->get_processor()->get_accounts()->get_primary_account();
                  $atts['account'] = $main_account->get_id();
              } else {
                  $atts['account'] = $private_root_folder['account'];
              }

              $atts['dir'] = $private_root_folder['id'];

              if (!isset($private_root_folder['view_roles']) || empty($private_root_folder['view_roles'])) {
                  $private_root_folder['view_roles'] = ['none'];
              }
              $atts['viewuserfoldersrole'] = implode('|', $private_root_folder['view_roles']);
          }
      }

      echo $this->create_template($atts);
      ?>

      </div>

      <div class="footer"></div>

    </div>
  </form>
</body>
</html>