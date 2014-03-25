<?php

if (!defined('WP_UNINSTALL_PLUGIN'))
  exit;

if (get_option('feed_media_player_options')) {
  delete_option('feed_media_player_options');
}

?>
