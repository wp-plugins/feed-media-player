<?php
/*
Plugin Name: Feed Media Player
Plugin URI: http://feed.fm/
Description: Plugin that puts a persistant audio player on your wordpress site
Version: 1.1
Author: support@feed.fm
Author URI: http://feed.fm/

History:
  0.4 - add 'v=' parameter. Don't require 'base=' parameter to deal with WP security
  1.0 - update to inline javascript based on mobify
  1.1 - better installation instructions, better styling, and authentication verification
*/

define("VERSION", "1.0");
define("DEFAULT_FEED_TOKEN", "c2473cbba8254ac3f2918867d9c94e8f8cedd961");
define("DEFAULT_FEED_SECRET", "7d56a58e7b62ff5a870120724260eac0bd347e3d");

add_action('wp_head', 'feed_media_player_header_output', 0);
add_action('wp_footer', 'feed_media_player_footer_output', 100);

add_action('admin_menu', 'feed_media_player_settings_menu');
add_action('admin_init', 'feed_media_player_admin_init');
add_action('admin_notices', 'feed_media_player_admin_notices');

add_action('get_header', 'feed_media_player_header');

// start capturing page header info after a 'get_header' call
function feed_media_player_header($name) {
  ob_start('feed_media_header_filter');
}

// stick our script right after the HEAD
function feed_media_header_filter($buffer) {
  $options = get_option('feed_media_player_options');

  // don't forget hidden option!
  $script = '<script>!function(a,b,c,d){function e(a,c,d,e){var f=b.getElementsByTagName("script")[0];a.src=e,a.id=c,a.setAttribute("class",d),f.parentNode.insertBefore(a,f)}var f=a.Feed={};f.config=d;var g=/((; )|#|&|^)feedfm=(\d)/.exec(location.hash+"; "+b.cookie);if(g&&g[3]){if(!+g[3])return}else if(!c())return;b.write(\'<plaintext style="display:none">\'),setTimeout(function(){f.capturing=!0;var c=b.createElement("script"),g="feedfm",h=function(){var c=new Date;c.setTime(c.getTime()+3e5),b.cookie="feedfm=0; expires="+c.toGMTString()+"; path=/",a.location=a.location.href};c.onerror=h;var i=d.script||(d.base||"http://wrap.feed.fm/wrap")+"/pt.js";e(c,"feedfm-js",g,i)})}(window,document,function(){var a=/webkit|(firefox)[\/\s](\d+)|(opera)[\s\S]*version[\/\s](\d+)|(trident)[\/\s](\d+)|3ds/i.exec(navigator.userAgent);return a?a[1]&&+a[2]<4?!1:a[3]&&+a[4]<11?!1:a[5]&&+a[6]<6?!1:!0:!1},{token:"' . $options['token'] . '",secret:"' . $options['secret'] . '",v:"' . VERSION . '"});</script>';

  $buffer = preg_replace(
    '/<(head)([^>]*)>/i', 
    '<${1}${2}>' . $script,
    $buffer );
  
  return $buffer;
}

function feed_media_player_header_output() {
  ob_end_flush();
}

function feed_media_player_footer_output() {
  echo '<script>var _paq=_paq||[];!function(){if(window!==window.parent){var a=document.createElement("script"),b=document.getElementsByTagName("script")[0];_paq.push(["setTrackerUrl","https://feed.fm/api/v2/analytics"]),_paq.push(["setSiteId","' . $options['token'] . '"]),_paq.push(["trackPageView"]),a.type="text/javascript",a.defer=!0,a.async=!0,a.src="http://wrap.feed.fm/wrap/piwik.js",b.parentNode.insertBefore(a,b)}}();</script>';
}

register_activation_hook(__FILE__, 'feed_media_player_activation_hook');

function feed_media_player_activation_hook() {
  if (get_option('feed_media_player_options') === false) {
    // 'WordPress plugin' app by 'feeddemo'
    $new_options['token'] = DEFAULT_FEED_TOKEN;
    $new_options['secret'] = DEFAULT_FEED_SECRET;
    $new_options['first_run'] = true;
    add_option('feed_media_player_options', $new_options);
  }
}

// make sure all our options are registered
function feed_media_player_admin_init() {
  wp_register_script('jquery.validate-1.12.0', 'http://ajax.aspnetcdn.com/ajax/jquery.validate/1.12.0/jquery.validate.js', array('jquery') );
  wp_register_script('feed-script', 'http://feed.fm/js/wp/feed-without-jquery.js', array('jquery') );
  wp_register_script('feed-media-admin-script', plugins_url('/script.js', __FILE__ ), array('jquery.validate-1.12.0') );
  wp_register_style('feed-style', plugins_url('/style.css', __FILE__));

  register_setting('feed_media_player_settings', 'feed_media_player_options', 'feed_media_player_validate_options');
  add_settings_section('feed_media_player_main_section', 'Authentication Settings', 'feed_media_player_main_setting_section_callback', 'feed_media_player_settings_section');
  add_settings_field('token', 'Token', 'feed_media_player_display_text_field', 'feed_media_player_settings_section', 'feed_media_player_main_section', array('name' => 'token'));
  add_settings_field('secret', 'Secret', 'feed_media_player_display_text_field', 'feed_media_player_settings_section', 'feed_media_player_main_section', array('name' => 'secret'));
}

// tell the user how to get things started
function feed_media_player_admin_notices() {
  if ($options = get_option('feed_media_player_options')) {
    if ($options['first_run'] === true) { 
      ?>
        <div class='updated' style="overflow: hidden; _overflow: visible; zoom: 1;">
            <img style="margin: 8px; float: left" src="http://wrap.feed.fm/wrap/logo.png?url=<?php echo urlencode(get_option('home')); ?>">
            <div>
              <p>
                Thanks for installing the Feed Media Player, which is live on your
                site right now! 
              </p>
              <p>
                To change the player style and music, visit the 
                <a href="options-general.php?page=feed-media-player">Feed Media Player</a> section of your Settings page.
              </p>
            </div>
        </div>
      <?php 

      unset($options['first_run']);
      update_option('feed_media_player_options', $options);
    }
  }

}

// register sub-menu of 'Settings' menu
function feed_media_player_settings_menu() {
  $hook = add_options_page('Feed Media Player Configuration', 'Feed Media Player', 'manage_options', 'feed-media-player', 'feed_media_player_config_page');
  add_action('admin_print_scripts-' . $hook, 'feed_media_player_print_scripts');
  add_action('admin_print_styles-' . $hook, 'feed_media_player_print_styles');
}

function feed_media_player_print_scripts() {
  wp_enqueue_script('feed-script');
  wp_enqueue_script('feed-media-admin-script');
}

function feed_media_player_print_styles() {
  wp_enqueue_style('feed-style');
}

// render our settings page
function feed_media_player_config_page() { ?>
  <div id="feed_media_player-general" class="wrap">
    <div id="feed-media-icon" class="icon32"></div>
    <h2>Feed Media Player Settings</h2>

<?php $options = get_option('feed_media_player_options'); if ($options['token'] === DEFAULT_FEED_TOKEN ) { ?>

    <form id="feed-media-create-account-form" method="post" action="http://developer.feed.fm/account" target="_blank">
      <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
          <div id="post-body-content">

            <div class="postbox">
              <h3>Sign up with Feed Media!</h3>

              <div class="inside">
                <p>
                  Your visitors are currently listening to a demo music station constructed and
                  managed by Feed.fm.
                </p>
                <p>
                  If you would like to create your own music station or change the styling of the
                  player, you'll need to create an account with Feed.fm and retrieve your 'token' and
                  'secret' values that you enter below.
                </p>
                <p>
                  If you have already have an account on Feed.fm, you can log in <a href="http://developer.feed.fm/" target="_blank">here</a>, 
                  otherwise you can create an account with this form:
                </p>

                <table class="form-table">
                  <tbody>
                    <tr>
                      <th scope="row">Email address</th>
                      <td>
                        <input type="text" name="email_address" value="<?php echo get_option("admin_email"); ?>" class="regular-text">
                      </td>
                    </tr>
                    <tr>
                      <th scope="row">Desired password:</th>
                      <td>
                        <input id="password" type="password" name="password" class="regular-text">
                      </td>
                    </tr>
                    <tr>
                      <th scope="row">Desired password (again):</th>
                      <td>
                        <input type="password" name="password_again" class="regular-text">
                      </td>
                    </tr>
                    <tr>
                      <td colspan="2">
                        <label>
                          <input type="checkbox" name="accept" value="true"> I have read and accept the <a href="https://developer.feed.fm/terms_and_conditions.html">Terms and Conditions</a>.
                        </label>
                      </td>
                    </tr>
                    <tr>
                      <td colspan="2">
                        <input type="hidden" name="source" value="wordpress">
                        <input type="hidden" name="url" value="<?php echo get_option("home"); ?>">
                        <input type="submit" value="Create Feed.fm Account" class="button-primary">
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div> <!-- /inside -->
            </div> <!-- /postbox -->
          </div> <!-- /post-body-content -->
        </div> <!-- /post-body -->
        <br class="clear">
      </div> <!-- /poststuff -->
    </form>

<?php } ?>

    <form name="feed_media_player_settings_api" method="post" action="options.php">
     <?php settings_fields('feed_media_player_settings'); ?>
     <?php do_settings_sections('feed_media_player_settings_section'); ?>
     <div id="feed-status">Validating authentication values...</div>
     <input type="submit" value="Submit" class="button-primary"/>
    </form>
  </div>
  <div class="clear"></div>
<?php }

function feed_media_player_validate_options($input) {
  $input['version'] = VERSION;
  return $input;
}

function feed_media_player_main_setting_section_callback() { ?>

  <p>Enter the values given to you on <a href="http://feed.fm/" target="_blank">feed.fm</a></p>

<?php }

function feed_media_player_display_text_field($data = array()) {
  extract($data);
  $options = get_option('feed_media_player_options');
?>
  <input type="text" name="feed_media_player_options[<?php echo $name; ?>]" value="<?php echo esc_html($options[$name] ); ?>"/><br/>
<?php
}


