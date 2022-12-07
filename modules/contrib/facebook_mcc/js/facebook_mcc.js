(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.facebook_mcc = {
    attach: function (context, settings) {

      // SDK Source with localization.
      var sdkSrc = 'https://connect.facebook.net/'+drupalSettings.facebook_mcc_locale+'/sdk/xfbml.customerchat.js';

      // Block code to inject.
      var code = '<div class="fb-customerchat" \
        page_id="' + drupalSettings.facebook_mcc_page_id + '" \
        theme_color="' + drupalSettings.facebook_mcc_theme_color + '" \
        logged_in_greeting="' + drupalSettings.facebook_mcc_logged_in_greeting + '" \
        logged_out_greeting="' + drupalSettings.facebook_mcc_logged_out_greeting + '" \
        greeting_dialog_delay="' + drupalSettings.facebook_mcc_greeting_dialog_delay + '"';

      // Check the mode of greeting display, if it's not default, then add it.
      if(drupalSettings.facebook_mcc_greeting_dialog_display != 'default') {
         code+= 'greeting_dialog_display="'+drupalSettings.facebook_mcc_greeting_dialog_display+'"';
      }
      code+='></div>';

      // Script to initialize the SDK.
      var script = '<script> \
        window.fbAsyncInit=function(){ \
          FB.init({ \
            xfbml:!0, \
            version:"v' + drupalSettings.facebook_mcc_sdk_version + '", \
            appId:"' + drupalSettings.facebook_mcc_app_id + '" \
          }) \
        },function(e,t,n){ \
          var c,o=e.getElementsByTagName(t)[0]; \
          e.getElementById(n)||((c=e.createElement(t)).id=n,c.src="'+sdkSrc+'",o.parentNode.insertBefore(c,o)) \
        }(document,"script","facebook-jssdk"); \
        </script>';

      // Inject code.
      $(code).prependTo('body');
      $(script).prependTo('body');
      $('<div id="fb-root"></div>').prependTo('body');

    }
  };
})(jQuery, Drupal);
