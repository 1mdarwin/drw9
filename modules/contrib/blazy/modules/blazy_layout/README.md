
# ABOUT BLAZY LAYOUT

Provides a single layout with dynamic regions for Layout Builder.

## INSTALLATION
Install the module as usual, more info can be found on:

[Installing Drupal 8 Modules](https://drupal.org/node/1897420)


## USAGE / CONFIGURATION
Visit layout builder pages, and add a Blazy Layout there.


## KNOWN ISSUES/ LIMITATIONS
* This module does not provide a CSS framework integration, instead using the
  existing grid solutions with few tweaks to support regular floating elements
  commonly seen at one-dimensional layouts.
* When changing **Region count** option, or at initial setup, the layout is not
  immediately updated. **Solutions**:
  * Hit **Configure section** link of **Blazy dynamic layout** again.
  * Hit **Update** button in modal again for the second time.
  * Hit **Save layout** button before working.

  The issue might be cached layout is not properly cleared up on AJAX.
  The second or third pass (hitting Update button) should fix the glitch.


# AUTHOR/MAINTAINER/CREDITS
* [Gaus Surahman](https://www.drupal.org/user/159062)
* CHANGELOG.txt for helpful souls with their patches, suggestions and reports.


## READ MORE
See the project page on drupal.org for more updated info:

[Blazy module](https://drupal.org/project/blazy)
