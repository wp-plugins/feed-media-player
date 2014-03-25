<?php
/*
Plugin Name: Feed Media Player
Plugin URI: http://feed.fm/documentation
Description: Plugin that puts a persistant audio player on your wordpress site
Version: 1.0
Author: support@feed.fm
Author URI: http://feed.fm/

History:
  0.4 - add 'v=' parameter. Don't require 'base=' parameter to deal with WP security
  1.0 - update to inline javascript based on mobify
*/

define("VERSION", "1.0");

add_action('wp_head', 'feed_media_player_header_output', 0);
add_action('wp_footer', 'feed_media_player_footer_output', 100);

add_action('admin_menu', 'feed_media_player_settings_menu');
add_action('admin_init', 'feed_media_player_admin_init');

add_action('get_header', 'feed_media_player_header');

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

function feed_media_player_header($name) {
  ob_start('feed_media_header_filter');
}

register_activation_hook(__FILE__, 'feed_media_player_activation_hook');

function feed_media_player_header_output() {
  ob_end_flush();
}

function feed_media_player_footer_output() {
  echo '<script>var _paq=_paq||[];!function(){if(window!==window.parent){var a=document.createElement("script"),b=document.getElementsByTagName("script")[0];_paq.push(["setTrackerUrl","https://feed.fm/api/v2/analytics"]),_paq.push(["setSiteId","' . $options['token'] . '"]),_paq.push(["trackPageView"]),a.type="text/javascript",a.defer=!0,a.async=!0,a.src="http://wrap.feed.fm/wrap/piwik.js",b.parentNode.insertBefore(a,b)}}();</script>';
}

function feed_media_player_activation_hook() {
  if (get_option('feed_media_player_options') === false) {
    // 'WordPress plugin' app by 'feeddemo'
    $new_options['token'] = 'c2473cbba8254ac3f2918867d9c94e8f8cedd961';
    $new_options['secret'] = '7d56a58e7b62ff5a870120724260eac0bd347e3d';
    add_option('feed_media_player_options', $new_options);
  }
}

function feed_media_player_settings_menu() {
  add_options_page('Feed Media Player Configuration', 'Feed Media Player', 'manage_options', 'feed-media-player', 'feed_media_player_config_page');
}

function feed_media_player_config_page() { ?>
  <div id="feed_media_player-general" class="wrap">
    <h2>Feed Media Player - Settings</h2>
    <form name="feed_media_player_settings_api" method="post" action="options.php">
     <?php settings_fields('feed_media_player_settings'); ?>
     <?php do_settings_sections('feed_media_player_settings_section'); ?>
     <input type="submit" value="Submit" class="button-primary"/>
    </form>
  </div>
<?php }

function feed_media_player_admin_init() {
  register_setting('feed_media_player_settings', 'feed_media_player_options', 'feed_media_player_validate_options');
  add_settings_section('feed_media_player_main_section', 'Main Settings', 'feed_media_player_main_setting_section_callback', 'feed_media_player_settings_section');
  add_settings_field('token', 'Authentication Token', 'feed_media_player_display_text_field', 'feed_media_player_settings_section', 'feed_media_player_main_section', array('name' => 'token'));
  add_settings_field('secret', 'Authentication Secret', 'feed_media_player_display_text_field', 'feed_media_player_settings_section', 'feed_media_player_main_section', array('name' => 'secret'));
}

function feed_media_player_validate_options($input) {
  $input['version'] = VERSION;
  return $input;
}

function feed_media_player_main_setting_section_callback() { ?>
  <p>Enter the values given to you on <a href="http://feed.fm/">feed.fm</a></p>
<?php }

function feed_media_player_display_text_field($data = array()) {
  extract($data);
  $options = get_option('feed_media_player_options');
?>
  <input type="text" name="feed_media_player_options[<?php echo $name; ?>]" value="<?php echo esc_html($options[$name] ); ?>"/><br/>
<?php
}


