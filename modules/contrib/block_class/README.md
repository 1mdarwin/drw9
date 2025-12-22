<img src="https://git.drupalcode.org/project/block_class/-/raw/4.0.x/logo.png"
width="3%" align="left">

# &nbsp;&nbsp; Block Class

<img src="https://www.drupal.org/files/project-images/block-class-config.png"
width="30%" align="right" style="margin-left:15px;">

Block Class provides a field to add CSS classes to a block from the user
interface.\
No change needed to the theme and no tech knowledge required to control CSS
classes for any block from the standard admin interfaces.\
Exports Block classes to configuration and integrates with all Drupal core API
modules.

[[_TOC_]]

<img src="https://www.drupal.org/files/block_class-view-attribute-value1a.png"
width="30%" align="right" style="margin-left:15px;">

## Usage example

A pretty cool and simple setup or application of this module would be, for
example, to add a simple standard CSS class to a Views or Custom Content block
that could be displayed on the front page.\
Very practical for setting fixed CSS classes for fixed blocks such as in the
footer (copyright, footer-left, footer-notices, etc...) or in the header. Since
the classes are exported with the block configuration, the changes can be
committed to the project's code base and easily deployed.

## Should I use version 3 or 4?

### &#128994; [Block Class 3.x][1] - stable (Legacy: port from D7)

* Does very well what it says it does, a single thing: Adding custom CSS classes
to blocks from the admin back-end UI.
* Very stable, simple and lightweight, fully Tests covered and well maintained.
* Easy to use: No settings or documentation, just enable the module and get any
kind of user (developer, content editor) started immediately with adding CSS
classes to blocks.
* Recommended and _preferred_ upgrade path from Drupal 7.

### &#128992; [Block Class 4.x][2] - Experimental (Latest features and developments)

* Provides more advanced/comprehensive features:
  * CSS classes: Allows adding classes to blocks, stored in config to be reused
for other blocks and selected through an AJAX autocomplete field on the block
config form.
  * HTML ID: Allows overriding the HTML ID of a block.
  * HTML Attributes: Allows adding any types of HTML Attributes sets to the code
of the block: `custom-attribute="custom-value"`.
  * Provides several management pages, bulk operations, settings, etc...
* Much larger code base, limited tests coverage, user documentation, field
descriptions or help texts and limited maintenance.
* With a wider set of features, the module is also harder to understand: A
long/comprehensive list of settings, more fields, more management list views and
pages.
* No Drupal 7 upgrade support, use 3.x.
* Experimental: Certain features may have unexpected results on sites, break,
show error messages or may not provide the intended behavior.

It is possible to upgrade from 3.x to 4.x, but not the other way around.

The 3.x branch still provides an intermediate step for sites upgrading from
Drupal 7 already satisfied with the features of a direct port of the D7 module.\
An upgrade to 4.x can still be considered afterwards. &#128076;

### Upgrading from prior versions: 8.x-1.x or 2.x

For any upgrade from versions prior to the 3.x or 4.x, see documentation page
[Block Class Version compatibility][3].\
&#128721; Do not upgrade from 2.0.x to 3.0.x.

## Installation

1. Prerequisites:\
Requires the [Block][4] Core module to be enabled.

1. Installation:\
Download and install Block Class as you would any other module, see
[Installing Modules][5].

## Configuration

<img src="https://www.drupal.org/files/block_class-edit-field-css-classes1a.png"
width="30%" align="right" style="margin-left:15px;">

[See screenshot on the right][6]

* After successful installation, browse to the [Block Layout][7] page
(Administration » Structure » Block Layout) and edit/configure any block, for
example for the 'Main page content' block, the configuration page would be
something like:\
`/admin/structure/block/manage/olivero_content`\
 &nbsp;\
The form should now have a new text field `CSS classes` allowing to add custom
classes to the block.

* Enter the classes in the field called `CSS class(es)` and save the block.

* Open the page where the block is rendered and inspect the block element.\
It should be possible to see the classes added in the tag
`<div class="block ...">` element of the block.

* By default block classes use a `Multiple field` for classes and one class per
line can be added.\
By default `10` classes per block can be added using `add more` and `remove`
item in the block configuration page.\
But if needed, this default value could be updated by changing the value of the
field `Quantity of classes per block` on module's settings form page, at
`/admin/config/content/block-class/settings`.

* You can set the weight to organize better the field items in the block
settings.\
This field can be configured for class, ID and attributes as well.

* The autocomplete settings is enabled by default and can be used to
autocomplete the classes inside multiple class field items.

* By default on Block Class there is a textfield with `255` chars in the maxlength
but there is a settings where you can modify if necessary.\
You can go to Administration » Configuration » Content authoring » Block Class
or you can open the url directly at `/admin/config/content/block-class/settings`
and there you can select the fieldtype between textfield (default) and
multiple.\
You can select the `Maxlength` of the field (by default: `255` chars).

* You can enable an option to use attributes, and you're free to customize that
if you want.\
To do it you can go to `admin/config/content/block-class/settings`
and select the option `Enable attributes`.\
With that the next time that you go to block settings page you'll be able to see
a textarea where you can insert your attributes.\
You need to use `key | value` format, and one per line. For example:\
`data-block-type|info`

* Using attributes on block class you can use `10` attributes per block by
default but you can modify this value in the `Quantity of attributes per block`
field.

* You can enable the ID personalization to allow you to update the block ID in
the front-end only. If you remove that the default block ID will continue
appearing.\
You can also define the Maxlength and Weight items that will be removed if
you disable the ID option.\
&nbsp;\
In the ID you can also remove the default block ID using the value `<none>`.
Using that the default block ID will be removed and won't show in the front-end
for the end-users.

* There is a block class list where you can see all blocks and theirs attributes
and classes at:\
`Administration » Configuration » Content authoring » Block Class » List`\
This list can have a lot of items depending on your database, for this reason
there is a pagination and you can define the items per page in the settings page
`admin/config/content/block-class/settings` and update the field:\
`Block Class List » Items per page` (by default: `50` items per page).

* "Advanced" On this field group that is closed by default you can set the array
to be used in the `Html::cleanCssIdentifier`. This one is used to filter special
characters in the classes. You can use one per line using `key|value` format,
for example, to replace underline with hyphen:\
`_|-`

## Bulk operations

There is a Bulk Operation to update classes automatically to help. To do this
you can go to:\
`Admin » Configuration » Content authoring » Block Class » Bulk Operations`
and there you can select 2 options:

1) Insert Class: With this option you can insert with a bulk operation classes
to all blocks that you have.

2) Insert Attributes: With this option you can insert with a bulk operation
attributes to all blocks that you have.

3) Convert all block classes to uppercase: With this operation you can convert
all block classes that you have to use uppercase.

4) Convert all block classes to lowercase: With this operation you can convert
all block classes that you have to use lowercase.

5) Update class: With that option you can insert a current class that you have
in the field "Current class" and in the other field "New class" you can insert
the new class that you want to use. After this you'll be redirected to another
page to review that and update all classes.

6) Delete all block classes: With this option the bulk operation will remove all
block classes on blocks. After this form you'll be redirected to another page to
confirm that operation.

7) Delete all attributes: With this option the bulk operation will remove all
attributes on blocks. After this form you'll be redirected to another page to
confirm that operation.

8) Remove all custom IDs: On this option will remove the replacement of block ID
in the front-end. But the ID will continue working with the default block ID.

## Integration

### With other block modules

This module plays well and has been tested with any block provided by Core, such
as [Custom block][8] (`block_content`), Views, User, but also with other contrib
modules such as [Simple Block][9] or [Page Manager][10].

### With any theme

As long as the theme block twig template allows printing block attributes, all
the changes made to the back-end configuration should be reflected immediately
in the HTML of the theme.

### Exported with block configuration

The CSS attributes being stored in the block `third_party_settings` config
property, they can be exported to configuration files with its corresponding
block.

## Similar modules

- [Block Attributes][11]:\
For users looking for more theming flexibility, capabilities or with more
advanced requirements, the Block Attributes module allows to specify additional
HTML attributes for blocks, through the block's configuration interface, such as
HTML id, style, title, align, class and more.

- [Block Class Styles][12]:\
Extends the Block Class module to incorporate styles (or themes) rather than css
classes. Adds style-based tpl suggestions. Allows HTML in your block titles.

- [Block Classes][13]:\
Block Classes allows users to add classes to block title, content, and wrapper
of any block through the block's configuration interface. This module extends
the Block Class module features. In some cases, we have to write twig file for
blocks, if we want to add separate classes for block wrapper, title, and its
content. Instead of writing twig's, we can handle it using the Block Classes
module.

- [Layout Builder Component Attributes][14]:\
Layout Builder Component Attributes allows editors to add HTML attributes to
Layout Builder components. The following attributes are supported:
  - ID
  - Class(es)
  - Style
  - `Data-*` Attributes

## Support and maintenance

Releases of the module can be requested or will generally be created based on
the state of the development branch or the priority of committed patches.

Feel free to follow up in the [issue queue][15] for any contributions, bug
reports, feature requests:

* [Create a ticket in module's issue tracker][16] to describe the problem
encountered,
* Document a feature request,
* [Create a merge request][17],
* Comments, testing, etc...

Any contribution is greatly appreciated!

[1]: https://git.drupalcode.org/project/block_class/-/commits/3.0.x
[2]: https://git.drupalcode.org/project/block_class/-/commits/4.0.x
[3]: https://www.drupal.org/docs/contributed-modules/block-class/version-compatibility
[4]: https://www.drupal.org/docs/core-modules-and-themes/core-modules/block-module
[5]: https://www.drupal.org/docs/extending-drupal/installing-modules
[6]: https://www.drupal.org/files/block_class-edit-field-css-classes1a.png
[7]: https://www.drupal.org/docs/core-modules-and-themes/core-modules/block-module/managing-blocks
[8]: https://www.drupal.org/docs/user_guide/en/block-create-custom.html
[9]: https://www.drupal.org/project/simple_block
[10]: https://www.drupal.org/project/page_manager
[11]: https://www.drupal.org/project/block_attributes
[12]: https://www.drupal.org/project/block_class_styles
[13]: https://www.drupal.org/project/block_classes
[14]: https://www.drupal.org/project/layout_builder_component_attributes
[15]: https://www.drupal.org/project/issues/block_class
[16]: https://www.drupal.org/node/add/project-issue/block_class?version=3.0.x-dev
[17]: https://www.drupal.org/docs/develop/git/using-gitlab-to-contribute-to-drupal/creating-merge-requests
