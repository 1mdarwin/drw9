# INTRODUCTION
Facebook Messenger Customer Chat Plugin For Drupal.

A simple module that provides the ability to add
Facebook Messenger Customer Chat Pluginto your Drupal site.

For More Information about the plugin:
https://developers.facebook.com/docs/messenger-platform/discovery/customer-chat-plugin

# REQUIREMENTS
  1. Whitelist the domain of your website
    For security reasons, the plugin will only render when loaded on a
    domain that you have whitelisted.
    As Facebook states, your domain must be served over HTTPS,
    and Uses a fully qualified domain name,
    such as https://www.messenger.com/.
    IP addresses and localhost are not supported for whitelisting.
    To whitelist your domain:
      - Go to your Facebook page
      - Click Settings at the top of your Page
      - Click Messenger Platform on the left
      - Edit whitelisted domains for your page in the
        Whitelisted Domains section

  2. Create Facebook App
    You will need to create a Facebook app to be able to get an App ID.
    This will serve as an API Key for Facebook so you can access their services.
    Go to https://developers.facebook.com to get one.


# INSTALLATION
  Open your terminal and download with composer.
  composer require drupal/facebook_mcc

  OR

  1. Download the module to your DRUPAL_ROOT/modules directory,
     or where ever you install contrib modules on your site.
  2. Go to Admin > Extend and enable the module.

# CONFIGURATION
  1. Go to admin/config/services/facebook_mcc
  2. Set your Page ID & App ID and save the configurations
  3. Place the Facebook MCC Block in your block layout

# AUTHOR
Yasser Samman
https://jo.linkedin.com/in/yasseralsamman
y.samman@codersme.com
