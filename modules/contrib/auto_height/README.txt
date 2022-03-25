INTRODUCTION
------------

This module integrates the 'jQuery AutoHeight' plugin by monocult - https://github.com/monocult. It dynamically adjust column heights, matching the biggest column in each Row.

FEATURES
--------

'jQuery AutoHeight' plugin is:

  - Lightweight

  - Responsive

  - Customizable

REQUIREMENTS
------------

Libraries API module - https://www.drupal.org/project/libraries

'jQuery AutoHeight' plugin - https://raw.githubusercontent.com/monocult/jquery-autoheight/master/jquery.autoheight.js

INSTALLATION
------------

1. Download 'jQuery Auto Height' module archive - https://www.drupal.org/project/auto_height

2. Extract and place it in the root modules directory i.e. /modules/auto_height

3. Create a libraries directory in the root, if not already there i.e. /libraries

4. Create a autoheight directory inside it i.e. /libraries/autoheight

5. Download 'jQuery AutoHeight' plugin - https://raw.githubusercontent.com/monocult/jquery-autoheight/master/jquery.autoheight.js

6. Place it in the /libraries/autoheight directory i.e. /libraries/autoheight/jquery.autoheight.js

7. Now, enable 'jQuery Auto Height' module

USAGE
-----

Lets try to understand its usage with an example. 

Here, there are 2 rows having 2 and 4 columns respectively. Varying content will make these columns to have different heights in each row.

<div id="Row1">
  <div>There are many patterns..</div>
  <div>Lorem Ipsum is simply dummy text of the printing and typesetting..</div>
</div>

<div id="Row2">
  <div>The standard chunk of Lorem Ipsum..</div>
  <div>Cicero are also reproduced in their exact original form, accompanied by English versions..</div>
  <div>All the Lorem Ipsum generators on the Internet tend to..</div>
  <div>The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic..</div>
</div>

This is where 'jQuery Auto Height' module comes into the picture. 

How does it Work?
=============

1. Just assign same class to all columns in a Row as below.

<div id="Row1">
  <div class="box1">There are many patterns..</div>
  <div class="box1">Lorem Ipsum is simply dummy text of the printing and typesetting..</div>
</div>

<div id="Row2">
  <div class="box2">The standard chunk of Lorem Ipsum..</div>
  <div class="box2">Cicero are also reproduced in their exact original form, accompanied by English versions..</div>
  <div class="box2">All the Lorem Ipsum generators on the Internet tend to..</div>
  <div class="box2">The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic..</div>
</div>

2. Now, go to the module's configuration page: /admin/config/media/auto_height

3. Here, add the respective jQuery class selectors from STEP 1 (each one in a new line).

e.g.,

.box1
.box2

4. Save configuration. That's it!

'jQuery Auto Height' module will now dynamically adjust column heights, matching the biggest column in each Row.

MAINTAINERS
-----------

Current maintainer:

 * Binu Varghese - https://www.drupal.org/u/binu-varghese

DEMO
-----

https://rawgit.com/monocult/jquery-autoheight/master/demo.html
