
<?php
  $feedOptions = get_option('feed_media_player_options');
?>

<div id="feed_media_player-general" class="wrap">
    <div id="feed-media-icon" class="icon32"></div>
    <h2>Music Player Settings</h2>

    <script>
      var FeedMedia = {
        iframe:        'https://developer.feed.fm/',
        defaultToken:  '<?php echo DEFAULT_FEED_TOKEN; ?>',
        defaultSecret: '<?php echo DEFAULT_FEED_SECRET; ?>',
        plugins:       '<?php echo admin_url("plugins.php"); ?>',
        home:          '<?php echo get_option('home'); ?>',
        description:   '<?php echo get_option('blogdescription'); ?>',
        options:       <?php echo json_encode(get_option('feed_media_player_options')); ?>
      };
    </script>

    <div id="feed-media-not-registered" class="hidden wrapper">
      <div class="content">
      <h3>Sign up with Feed.fm to customize your music player</h3>
      <div class="clearfix">
        <div class="button-wrapper">
          <button class="button-primary" id="feed-media-sign-up">Sign-Up!</button>
        </div>
      </div>
      
      </div>
    </div>

    <div id="feed-media-registered" class="hidden wrapper">
      <div class="content">
        <h3>The Feed.fm Music Player is active on your site</h3>
        <div class="clearfix">
          <div class="button-wrapper">
          <button class="button-primary" id="feed-media-change-playlist">Change your playlist</button>
          </div>
          <div class="button-wrapper">
          <button class="button-primary" id="feed-media-change-skin">Change your player style</button>
          </div>
          <div id="feed-media-upsell" class="hidden button-wrapper">
            <button class="button-primary" id="feed-media-change-subscription">Upgrade to Premium!</button>
          </div>
          </div>
        </div>
      </div>
    </div>

  </div>
  <div class="clear"></div>
