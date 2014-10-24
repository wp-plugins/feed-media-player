/*global jQuery:false, ajaxurl: false, FeedMedia:false */

/**
 *
 * Modal handler
 *
 **/

jQuery( function ( $ ) {

  //
  // Handle various messages from the feed site
  //

  function closeDialog() {
		$( document ).off( 'focusin' ) ;
		$( 'body' ).css( { 'overflow': 'auto' } );
		$( '.iframe_modal-close' ).off( 'click' );
		$( '#iframe_modal_dialog').remove( ) ;

    updateDisplay();
  }

  function onAccountCreated(msg) {
    $.ajax({
      url: ajaxurl,
      type: 'POST',
      data: {
        action: 'feed_media_account_created',
        auth_token: msg.auth_token,
        email: msg.email
      }
    }).then(function(resp) {
      if (resp.success) {
        FeedMedia.options = resp.options;

        // nothin' else to do for now
      }
    });
  }

  function onCredentials(msg) {
    $.ajax({
      url: ajaxurl,
      type: 'POST',
      data: {
        action: 'feed_media_assign_credentials',
        token: msg.token,
        secret: msg.secret
      }
    }).then(function(resp) {
      if (resp.success) {
        FeedMedia.options = resp.options;

        updateDisplay();
      }
    });
  }

  function receiveMessage(e) {
    var msg;
    try {
      msg = JSON.parse(e.data);
    } catch (error) {
      // not ours
      return;
    }

    switch (msg.feedfm) {
      case 'close':           closeDialog(); break;
      case 'account':         onAccountCreated(msg); break;
      case 'credentials':     onCredentials(msg); break;
      default: // nada!
    }
  }
  window.addEventListener('message', receiveMessage, false);

  function appendParameters(url, params) {
    if (params) {
      // pass on config values to iframe
      var urlParams = [];
      for (var name in params) {
        if (params.hasOwnProperty(name)) {
          urlParams.push(name + '=' + encodeURIComponent(params[name]));
        }
      }
      if (urlParams.length > 0) {
        url = url + '?' + urlParams.join('&');
      }
    }

    return url;
  }

  /*
   * Reset everything (for testing)
   */

  function reset() {
    $.ajax({
      url: ajaxurl,
      type: 'POST',
      data: {
        action: 'feed_media_reset'
      }

    }).then(function(response) {
      if (response.success) {
        window.location = FeedMedia.plugins;
      }
    });
  }

  function displayPlaylist() {
    displayModal('/wordpress/station', {
      wp_auth_token: FeedMedia.options.auth_token,
      email: FeedMedia.options.email,
      token: FeedMedia.options.token,
      secret: FeedMedia.options.secret
    });
  }

  function displaySkin() {
    displayModal('/wordpress/skin', {
      wp_auth_token: FeedMedia.options.auth_token,
      email: FeedMedia.options.email,
      token: FeedMedia.options.token,
      secret: FeedMedia.options.secret
    });
  }

  function displaySubscription() {
    displayModal('/wordpress/subscription', {
      wp_auth_token: FeedMedia.options.auth_token,
      email: FeedMedia.options.email
    });
  }

  function displayOnboarding() {
    displayModal('/onboarding/modal', {
      home: FeedMedia.home,
      description: FeedMedia.description
    });
  }

  function displayModal(path, params) {
    var url = appendParameters(FeedMedia.iframe + path, params);

    var $dialogHTML = $('<div tabindex="0" id="iframe_modal_dialog" role="dialog"><div class="iframe_modal" ><a role="button" class="iframe_modal-close" href="#" title="Close"><span class="iframe_modal-icon ir">Close</span></a><div class="iframe_modal-content"><div class="topbar"><div class="support"><p>We&apos;re here to help.</p><a>support&#64;feed.fm</a><p>1-650-479-4481</p></div></div><iframe id="iframe_modal-frame" src="" scrolling="no" frameborder="0" allowtransparency="true"></iframe></div></div><div class="iframe_modal-backdrop" role="presentation"></div></div>' );

		// Sets the URL of the iframe
		$dialogHTML.find('#iframe_modal-frame' ).attr('src' , url);

		// Attach the close button event handler.
		$dialogHTML.find( '.iframe_modal-close' ).on( 'click' , function(e) {
      e.preventDefault();
      e.stopPropagation();
      closeDialog();
    });
		
		// When the user shifts focus (typically through pressing the tab key ).
		// If the new focus target is not a child of the modal or the modal itself,
		// set the focus on the modal -- thus resetting the tab order.
		$( document ).on( 'focusin' , function( e ) {
			var $dialog = jQuery( '#iframe_modal_dialog' );
			if ( $dialog.length && $dialog[0] !== e.target && !$dialog.has( e.target ).length ) {
				$dialog.focus();
			}
		} ) ;

		// Set overflow to hidden on the body, preventing the user from scrolling the
		// disabled content and append the dialog to the body.
		$( 'body' ).css( { 'overflow': 'hidden' } ).append( $dialogHTML );

    //Resizing the iframe height to be consistent with the modal container.
    $( window ).resize(function() {
      $( '#iframe_modal-frame' ).css( 'height', $('.iframe_modal-content').height() );
    });
	}

  //
  // Various event handlers
  //

  $('#feed-media-sign-up').on('click', displayOnboarding);
  $('#feed-media-change-playlist').on('click', displayPlaylist);
  $('#feed-media-change-skin').on('click', displaySkin);
  $('#feed-media-change-subscription').on('click', displaySubscription);

  //
  // Initialization
  //
 
  var $notRegistered = $('#feed-media-not-registered'),
      $registered = $('#feed-media-registered'),
      $upsell = $('#feed-media-upsell');

  function updateDisplay(firstCheck) {
    if (!FeedMedia.options.token || 
        !FeedMedia.options.secret ||
        ((FeedMedia.defaultToken === FeedMedia.options.token) && 
          (FeedMedia.defaultSecret === FeedMedia.options.secret))) {
      // user hasn't registered
      $registered.addClass('hidden');
      $notRegistered.removeClass('hidden');
      $upsell.addClass('hidden');

      // pop up the onboarding modal right when the page loads
      if (firstCheck) {
        displayOnboarding();
      }

    } else {
      // user has registered
      $registered.removeClass('hidden');
      $notRegistered.addClass('hidden');
      $upsell.addClass('hidden');

      // kick off a check of their subscription plan
      $.ajax({
        url: FeedMedia.iframe + '/wordpress/subscription',
        type: 'GET',
        dataType: 'jsonp',
        data: {
          wp_auth_token: FeedMedia.options.auth_token,
          email: FeedMedia.options.email
        }
      }).then(function(result) {
        if (result.success && (!result.subscription || result.subscription.free)) {
          $('#feed-media-upsell').removeClass('hidden');
        }
      });

    }
  } 

  var resetIndex = 0, resetString = 'RESET';
  $('body').on('keyup', function(e) {
    if (resetString[resetIndex] === String.fromCharCode(e.keyCode)) {
      resetIndex++;

      if (resetIndex === resetString.length) {
        reset();
      }
    } else {
      resetIndex = 0;
    }
  });

  updateDisplay(true);

});

