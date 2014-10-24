<?php
/*
Plugin Name: Music Player by Feed.fm
Plugin URI: http://feed.fm/
Description: Enhance your WordPress site with popular music - from Beatles to Daft Punk - in minutes!
Version: 2.0
Author: support@feed.fm
Author URI: http://feed.fm/

History:
  0.4 - add 'v=' parameter. Don't require 'base=' parameter to deal with WP security
  1.0 - update to inline javascript based on mobify
  1.1 - better installation instructions, better styling, and authentication verification
  1.2 - rename from 'Feed Media Player' to 'Music Player by Feed.fm'
  1.3 - require name and phone number with registration
  1.4 - SSL all the things!
  1.5 - missing script tag
  2.0 - new onboarding
*/


define("VERSION", "2.0");
define("DEFAULT_FEED_TOKEN", "c2473cbba8254ac3f2918867d9c94e8f8cedd961");
define("DEFAULT_FEED_SECRET", "7d56a58e7b62ff5a870120724260eac0bd347e3d");

add_action('wp_head', 'feed_media_player_header_output', 0);
add_action('wp_footer', 'feed_media_player_footer_output', 100);
add_action('get_header', 'feed_media_player_header');

//
//
// Set up defaults on activation
//
//

$feed_default_options =register_activation_hook(__FILE__, 'feed_media_player_activation_hook');

function feed_media_player_activation_hook() {
  if (get_option('feed_media_player_options') === false) {
    $new_options =  array(
      "token" => DEFAULT_FEED_TOKEN,
      "secret" => DEFAULT_FEED_SECRET,
      "first_run" => true
      // email
      // auth_token
    );

    add_option('feed_media_player_options', $new_options);
  }
}

//
//
// Meat of plugin that installs the Feed header script
//
//

// start capturing page header info after a 'get_header' call
function feed_media_player_header($name) {
  ob_start('feed_media_header_filter');
}

// stick our script right after the HEAD
function feed_media_header_filter($buffer) {
  $options = get_option('feed_media_player_options');

  // don't forget hidden option!
  $script = '<script>!function(a,b,c,d){function e(a,c,d,e){var f=b.getElementsByTagName("script")[0];a.src=e,a.id=c,a.setAttribute("class",d),f.parentNode.insertBefore(a,f)}var f=a.Feed={};f.config=d;var g=/((; )|#|&|^)feedfm=(\d)/.exec(location.hash+"; "+b.cookie);if(g&&g[3]){if(!+g[3])return}else if(!c())return;b.write(\'<plaintext style="display:none">\'),setTimeout(function(){f.capturing=!0;var c=b.createElement("script"),h="feedfm",i=function(){var c=new Date;c.setTime(c.getTime()+3e5),b.cookie="feedfm=0; expires="+c.toGMTString()+"; path=/",a.location=a.location.href};c.onerror=i;var j=d.script||d.base+(g&&2==g[3]?"/test.js":"/pt.js");e(c,"feedfm-js",h,j)})}(window,document,function(){var a=/webkit|(firefox)[\/\s](\d+)|(opera)[\s\S]*version[\/\s](\d+)|(trident)[\/\s](\d+)/i.exec(navigator.userAgent);return a?a[1]&&+a[2]<21?!1:a[3]&&+a[4]<15?!1:a[5]&&+a[6]<11?!1:!0:!1},{base:"https://d3qmh30sjudxaa.cloudfront.net/wrap",token:"' . $options['token'] . '",secret:"' . $options['secret'] . '",v:"' . VERSION . '"});</script>';

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
  echo '<script>var _paq=_paq||[];!function(){if(window!==window.parent){var a=document.createElement("script"),b=document.getElementsByTagName("script")[0];_paq.push(["setTrackerUrl","https://feed.fm/api/v2/analytics"]),_paq.push(["setSiteId","' . $options['token'] . '"]),_paq.push(["trackPageView"]),a.type="text/javascript",a.defer=!0,a.async=!0,a.src="https://d3qmh30sjudxaa.cloudfront.net/wrap/piwik.js",b.parentNode.insertBefore(a,b)}}();</script>';
}

//
//
// Configure all the admin menus
//
//

add_action('admin_init', 'feed_media_player_admin_init');
add_action('admin_menu', 'feed_media_player_admin_menus');
add_action('admin_notices', 'feed_media_player_admin_notices');


// set up all admin styles, scripts, and settings
function feed_media_player_admin_init() {
  wp_register_script('jquery.validate', plugins_url('/jquery.validate.js', __FILE__ ), array('jquery') );
  wp_register_script('feed-media-json-script', plugins_url('/json2.js', __FILE__ ));
  wp_register_script('feed-media-config-page-script', plugins_url('/config-page-script.js', __FILE__ ), array( 'jquery.validate', 'feed-media-json-script'));

  wp_register_style('feed-media-config-page-style', plugins_url('/config-page-style.css', __FILE__));

  register_setting('feed_media_player_settings', 'feed_media_player_options', 'feed_media_player_validate_options');

  add_settings_section('feed_media_player_main_section', 'Authentication Settings', 'feed_media_player_main_setting_section_callback', 'feed_media_player_settings_section');
  add_settings_field('token', 'Token', 'feed_media_player_display_text_field', 'feed_media_player_settings_section', 'feed_media_player_main_section', array('name' => 'token'));
  add_settings_field('secret', 'Secret', 'feed_media_player_display_text_field', 'feed_media_player_settings_section', 'feed_media_player_main_section', array('name' => 'secret'));
}

// register 'Music Player' menu
function feed_media_player_admin_menus() {
  // config-page
  //$hook = add_menu_page('Music Player Configuration', 'Music Player', 'manage_options', 'feed-media-player/config-page.php');
  $hook = add_menu_page('Music Player Configuration', 'Music Player', 'manage_options', 'feed-media-player/config-page.php', '', plugins_url( 'images/logo-grey.png', __FILE__ ), '76');

  // prep the proper .js/.css
  add_action('admin_enqueue_scripts', 'feed_media_player_admin_scripts');
}

// load up the proper scripts when displaying our pages
function feed_media_player_admin_scripts($hook) {
  // config page scripts/styles
  if ($hook == 'feed-media-player/config-page.php') {
    wp_enqueue_script('feed-media-config-page-script');
    wp_enqueue_style('feed-media-config-page-style');
  }
}

//
//
// Hook to display bouncing logo until the user configures things
//
//

function feed_media_player_admin_notices() {
  $dir = plugin_dir_url(__FILE__);
  if ($options = get_option('feed_media_player_options')) {
    if (isset($options['first_run']) && ($options['first_run'] === true)) { 
      ?>
      <script type="text/javascript" >

            //var menuId = jQuery("#toplevel_page_feed-media-player-config-page");
            var logo = document.createElement('img');
            var div = document.createElement('div');
            div.style.position = 'absolute';
            div.style.right = '-25px';
            div.style.top = '-5px';
            div.style.zIndex = 9000;
            logo.setAttribute('src','<?=$dir?>images/logo-bounce.png');
            logo.style.marginTop = 0;
            div.appendChild(logo);
            bounce(logo);
            
            function bounce(elem) {
                var step = 0;
                var speed = 0.05;
                var bounce_height = 20;
                //console.log("ELEM " + elem);
            
                function animate() {
                    elem.style.marginTop = '-' + (Math.sin(step++*speed)+1)/3*bounce_height + 'px';
                    //console.log("elem" + elem.style.marginTop);      
                }
                
            id = setInterval(animate, 10);
                
            }
            
            //console.log(logo);
            document.getElementById("toplevel_page_feed-media-player-config-page").appendChild(div);


      </script>
      
        <!-- put the javascript here to animate the bouncing ball -->

        <div class='updated' style="overflow: hidden; _overflow: visible; zoom: 1;">
            <!-- <img style="margin: 8px; float: left" src="http://wrap.feed.fm/wrap/logo.png?url=<?php echo urlencode(get_option('home')); ?>"> -->
            <div>
              <p>
                Thanks for installing the Music Player by Feed.fm, which is live on your
                site right now! 
              </p>
              <p>
                To change the player style and music, visit the 
                <a href="options-general.php?page=feed-media-player">Music Player</a> section of your Settings page.
              </p>
            </div>
        </div>
      <?php 
      unset($options['first_run']);
      update_option('feed_media_player_options', $options);
    }
  }
}

function feed_media_player_validate_options($input) {
  $input['version'] = VERSION;
  return $input;
}

function feed_media_player_main_setting_section_callback() { ?>
  <p>
    The values below come from your account on <a href="https://developer.feed.fm/" target="_blank">feed.fm</a>. Log in to change your music selection, player style, or billing information.
  </p>
<?php }

function feed_media_player_display_text_field($data = array()) {
  extract($data);
  $options = get_option('feed_media_player_options');
?>
  <input type="text" name="feed_media_player_options[<?php echo $name; ?>]" value="<?php echo esc_html($options[$name] ); ?>"/><br/>
<?php
}

//
//
// Ajax routines
//
//

add_action('wp_ajax_feed_media_account_created', 'feed_media_account_created');

function feed_media_account_created() {
  header("Content-type: text/json");

  if (empty($_POST['auth_token'])) {
    echo '{"success":false,"error":{"message":"login token not provided"}}';
    die();
  }

  if (empty($_POST['email'])) {
    echo '{"success":false,"error":{"message":"email not provided"}}';
    die();
  }

  $options = get_option('feed_media_player_options');

  if ($options) {
    $options['email'] = $_POST['email'];
    $options['auth_token'] = $_POST['auth_token'];

    update_option('feed_media_player_options', $options);

    echo '{"success":true,"options":' . json_encode($options) . '}';
    die();

  } else {
    echo '{"success":false,"error":{"message":"no existing options!"}}';
    die();

  }
}

add_action('wp_ajax_feed_media_assign_credentials', 'feed_media_assign_credentials');

function feed_media_assign_credentials() {
  header("Content-type: text/json");

  if (empty($_POST['token'])) {
    echo '{"success":false,"error":{"message":"token not provided"}}';
    die();
  }

  if (empty($_POST['secret'])) {
    echo '{"success":false,"error":{"message":"secret not provided"}}';
    die();
  }

  $options = get_option('feed_media_player_options');

  if ($options) {
    $options['token'] = $_POST['token'];
    $options['secret'] = $_POST['secret'];

    update_option('feed_media_player_options', $options);

    echo '{"success":true,"options":' . json_encode($options) . '}';
    die();

  } else {
    echo '{"success":false,"error":{"message":"no existing options!"}}';
    die();

  }
}

add_action('wp_ajax_feed_media_reset', 'feed_media_reset');

function feed_media_reset() {
  delete_option('feed_media_player_options');

  feed_media_player_activation_hook();

  header("Content-type: text/json");
  echo '{"success":true}';
  die();
}
