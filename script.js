/*global jQuery:false, Feed:false */

jQuery(function($) {
  $('#feed-media-create-account-form').validate({
    errorPlacement: function($error, $element) {
      var $parent = $element.parent();

      $error.css({ display: 'block', color: 'red' });
      $parent.append($error);
    },
    errorClass: 'form-invalid',
    rules: {
      name: {
        required: true,
      },
      phone: {
        required: true
      },
      email_address: {
        required: true,
        email: true
      },
      accept: {
        required: true
      },
      password: {
        required: true,
        minlength: 6
      },
      password_again: {
        equalTo: '#password'
      }
    },
    messages: {
      name: {
        required: 'Please enter your name'
      },
      phone: {
        required: 'Please enter your phone number'
      },
      email_address: {
        required: 'Please enter an email address',
        email: 'Please enter a valid email address'
      },
      password: {
        required: 'Please enter a password for your feed.fm account'
      },
      password_again: {
        equalTo: 'Your passwords do not match'
      },
      accept: {
        required: 'Please indicate your acceptance of the terms and conditions'
      }
    }
  });

  var $form = $('form[name="feed_media_player_settings_api"]'),
      $token = $form.find('[name*=token]'),
      $secret = $form.find('[name*=secret]'),
      $status = $('#feed-status');


  function checkTokenSecret() {
    var session = new Feed.Session($token.val(), $secret.val());

    session.on('not-in-us', function() {
      $status.html('Cannot confirm token/secret because you do not appear to be in the United States.').removeClass('error');
    });

    session.on('invalid-credentials', function() {
      $status.html('These credentials don\'t appear to be valid. Please double check for typos.').addClass('error');
    });

    session.on('placement', function(placement) {
      $status.html('This token/secret pair successfully maps to "' + placement.name + '".').removeClass('error');
    });

    session.tune();
  }

  $token.on('change', checkTokenSecret);
  $secret.on('change', checkTokenSecret);

  checkTokenSecret();
});
