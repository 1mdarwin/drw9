
# <a name="top"> </a>CONTENTS OF THIS FILE

 * [Introduction](#introduction)
 * [Broken vs. working library versions](#broken)
 * [Requirements](#requirements)
 * [Recommended modules](#recommended-modules)
 * [Features](#features)
 * [Installation](#installation)
 * [Uninstallation](#uninstallation)
 * [Configuration](#configuration)
 * [Slick Formatters](#formatters)
 * [Troubleshooting](#troubleshooting)
 * [Benchmarking & Performance Guidelines](#benchmarking)
 * [Strategic Optimization Checklist](#optimization)
 * [FAQ](#faq)
 * [Contribution](#contribution)
 * [Maintainers](#maintainers)

***
## <a name="introduction"></a>INTRODUCTION

Visit **/admin/help/slick_ui** once Slick UI installed to read this in comfort.

Slick is a powerful, performant, and fully responsive slideshow/carousel
integration leveraging
[Ken Wheeler's Slick carousel](http://kenwheeler.github.io/slick).
Engineered to satisfy modern **Core Web Vitals**, it transforms the traditional
slideshow into a robust, prioritized media delivery system.

* [Samples](https://www.drupal.org/project/slick_extras)
* [Demo](http://kenwheeler.github.io/slick/)

Slick has gazillion options, please start with the very basic working
samples.

Slick library v2.x was out 2015/9/21, and is not supported now, 2023/09.

***
## <a name="first"> </a>FIRST THINGS FIRST!
Read more at:
* [Github](https://git.drupalcode.org/project/blazy/-/blob/3.0.x/docs/README.md#first-things-first)
* [Blazy UI](/admin/help/blazy_ui#first)


> [!WARNING]
> ### <a name="broken"> </a>Broken vs. Working Library Versions
> * **Supported versions**:
  [Slick library](https://github.com/kenwheeler/slick/releases)
  (**1.6+ and <= 1.8.0**).
> * **Version Discrepancies (v1.8.0)**:
  In October 2021, breaking changes and misleading versioning were identified
  in the source. Ensure the version in your **package.json** matches the version
  written in **slick.js**. For example, release 1.8.1 often contains
  `package.json` 1.8.1 but a misleading `slick.js` 1.8.0. If these do not match,
  they are unsupported.
> * **The Fix**:
  Warnings and corrective solutions are provided when saving option set forms.
  If using samples, they will remain broken until the options are updated.
  **Do not post issues for higher versions** as they are broken out-of-the-box.
> * **Known Failures**:
  Broken dots, unwanted anonymous `<div>` wrapping, stacking slides, and
  out-of-sync navigation.
> * **The "Battle-Tested" Choice**:
  **Version 1.6.0** is the most stable and least problematic release. It lacks
  *non-essential* features but ensures structural integrity.


***
## <a name="requirements"> </a>REQUIREMENTS
* [Blazy](https://drupal.org/project/blazy)
* [Slick library](https://github.com/kenwheeler/slick/releases)
  (**1.6+ and <= 1.8.0**).
  [Read more](https://www.drupal.org/project/slick/issues/3241495#comment-14251300)

  **Standard version**
  * **Note**: The Master branch is not supported. Download and rename an
  official release to `slick` so assets are available at:
    * `/libraries/slick/slick/slick.css`
    * `/libraries/slick/slick/slick-theme.css` (optional)
    * `/libraries/slick/slick/slick.min.js`
    + Or any path supported by core library finder as per Drupal 8.9+.
      If using composer, it starts with:

      `/libraries/slick-carousel/slick/`

      Open one of them in a browser, ensure no 404 or 403 errors.

  **Accessible version**

   * [Accessible Slick releases](https://github.com/Accessible360/accessible-slick/releases) (**>= 1.0.1**)
   * Extract and rename the folder to `accessible-slick`, so the
     assets are at:
     + **/libraries/accessible-slick/slick/slick.css**
     + **/libraries/accessible-slick/slick/slick-theme.css** (optional)
     + **/libraries/accessible-slick/slick/slick.min.js**
     + Or any path supported by core library finder as per Drupal 8.9+.
   * **Warning!** This library was based on broken 1.8.1 version, so it
     inherits the above-mentioned problems. A workaround was provided, but it
     demands your attentions on few specific options as prompted when saving
     the Optionset forms: `rows`, `slidesPerRow`, `slidesToShow`, etc.
* **[Optional]** [jqeasing](https://github.com/gdsmith/jquery.easing) at
  `/libraries/easing/jquery.easing.min.js`.
  (Fallback for legacy browsers; ignorable if using CSS3 easing alone).


***
## <a name="installation"> </a>INSTALLATION

* [Installation Manual](https://drupal.org/node/1897420)
* **Composer**:

  ```
   $ composer require npm-asset/slick-carousel:1.8.0 \
   npm-asset/jquery-mousewheel \
   npm-asset/jquery.easing \
   drupal/blazy \
   drupal/slick
   ```
  See [Blazy Composer Guidelines](/admin/help/blazy_ui#composer).

## Upgrading from 1.x to 2.x or 3+

Please refer to the [Blazy upgrade path](https://www.drupal.org/project/blazy#blazy-upgrade). Review the
[Update SOP](https://git.drupalcode.org/project/blazy/blob/3.0.x/docs/UPDATING.md#update-sop) if you encounter trouble.

## Similar Modules

* [Splide](https://drupal.org/project/splide): The vanilla JavaScript slider.
Slick’s successor with enhanced accessibility and plugin support.

***
## <a name="uninstallation"> </a>UNINSTALLATION
Should be fine now, however please check out below if any issues:

* [Slick 7.x](https://www.drupal.org/project/slick/issues/3261726#comment-14406766)
* [Slick 8.x+](https://www.drupal.org/project/slick/issues/3257390)


***
## <a name="configuration"> </a>CONFIGURATION
Visit the following to configure Slick:

1. `/admin/config/media/slick`

   Enable Slick UI sub-module first, otherwise regular **Access denied**.

2. Visit any entity types:

   + `/admin/structure/types`
   + `/admin/structure/block/block-content/types`
   + `/admin/structure/paragraphs_type`
   + etc.

    Use Slick as a formatter under **Manage display** for multi-value fields:
    Image, Media, Paragraphs, Entity reference, or even Text.
    Check out [SLICK FORMATTERS](#formatters) section for details.

3. `/admin/structure/views`

   Use Slick as standalone blocks, or pages.


***
## <a name="recommended-modules"> </a>RECOMMENDED MODULES
Slick works with fields and Views, and supports enhancements for image, video,
audio, SVG, CSS backgrounds and HTML media types with more complex layouts.

### OPTIONAL
* [Colorbox](https://drupal.org/project/colorbox), to have grids/slides that
   open up image/ video in overlay.
* [Paragraphs](https://drupal.org/project/paragraphs), to get more complex
  slides at field level.
* [Mousewheel](https://github.com/brandonaaron/jquery-mousewheel) at:
  + **/libraries/mousewheel/jquery.mousewheel.min.js**


### SUB-MODULES
The Slick module has several sub-modules:
* Slick UI, included, to manage optionsets, can be uninstalled at production.

* Slick Media, included as a plugin since Slick 2.x.

* [Slick Views](https://drupal.org/project/slick_views)
  to get the most complex slides you can imagine.

* [Slick Paragraphs](https://drupal.org/project/slick_paragraphs)
  to get more complex slides at field level.

* [Slick Lightbox](https://drupal.org/project/slick_lightbox)
  to get Slick within lightbox for modern features: responsive, swipes, etc.

* [Slick Entityreference](https://drupal.org/project/slick_entityreference)
  to get Slick for entityreference and entityreference revisions.

* [ElevateZoom Plus](https://drupal.org/project/elevatezoomplus)
  to get ElevateZoom Plus with Slick Carousel and lightboxes, commerce ready.

* [Slick Example](https://drupal.org/project/slick_extras)
  to get up and running Slick quickly.

***
## <a name="features"></a>FEATURES

* **Deep Integration**:

  Seamlessly works with Core Media, Views, Paragraphs, and Media contrib
  modules. Supports Image, Responsive image, (local|remote|iframe) videos, SVG,
  DIV (CSS backgrounds), either inline, fields, views, or within lightboxes.
* **LCP & CLS Management**:

  Engineered for a **"CLS-zero" strategy**, our framework integrates
  **sophisticated preloading** alongside native `fetchpriority` and `decoding`
  to systematically eliminate LCP discovery delays. We provide rigorous
  optimization for every asset—from **standard images**, **CSS backgrounds**
  and **responsive picture elements** to **optimized video posters**. While we
  leverage modern CSS `aspect-ratio` for layout stability, we maintain a refined
  **padding-bottom fallback** to ensure backward compatibility (BC) without
  sacrificing precision.
* **Intelligent Lazy-loading**:

  Sophisticated preloading via the Blazy engine for images, CSS backgrounds,
  iframes, SVG, HTML5 video, audio, and HTML media type.
  * Multi-serving lazyloaded images, including multi-breakpoint CSS backgrounds.
* **Privacy & GDPR Compliance**:

  Utilizes a **Two-Click Media Loader** via the "Image to Iframe" option.
  No third-party tracking scripts are initialized until the user actively
  engages with the play button—satisfying strict **GDPR and ePrivacy**
  requirements.
* **Developer Friendly**:

  Features a "Vanilla" mode and a
  [robust API](https://git.drupalcode.org/project/slick/blob/3.0.x/slick.api.php)
  for custom/theme implementations.
* **Modular Skins & Versatile Designs**:

  Fullscreen, Split, multi-row, or Grid layouts built with pure CSS and zero
  JavaScript beyond the initializer.
* **Nested Sliders/Overlays**:

  Multiple carousels within a single Slick via Slick Paragraphs and Slick Views.
* **Randomization**:

  A strategic solution for refreshing cached content (ads, e-commerce) to ensure
  a dynamic user experience across pages without compromising performance.
* **Robust Content Supports:**

  HTML, responsive image/ picture, responsive iframe, SVG, video, audio and
  third party contents.
* **Inline & lightbox Mixed-media:**

  A single **Media switcher** option for various interactions: image to content,
  iframe, and (quasi-)lightboxes: Slick lightbox, Colorbox, PhotoSwipe, Flybox,
  Magnific Popup, Zooming, etc.
* **Editor Friendly:**

  `Splide Filter` using simple shortcodes, see [Filter tips](/filter/tips).
* **Navigation/ Pager Options**:

  Arrows, Dots (circle, static grid, or hoverable), Tabs, and Image Thumbnails.
  + **Arrows**
  + **Dots**, circle dots, dots as static grid thumbnails, and dots with
    hoverable thumbnails.
  + [**Text tabs**](https://www.drupal.org/project/issues/search?issue_tags=slick%20tabs), just provide Thumbnail caption, and leave Thumbnail
    style/image empty to achieve:

    * [Vertical tabs](https://www.drupal.org/files/issues/Bildschirmfoto%202016-03-16%20um%2012.09.36.png)
    * [Inline tabs](https://www.drupal.org/files/issues/thumbnail-caption-or-any-text-as-navigation.png)
  + [**Image thumbnails/tabs**](https://www.drupal.org/project/issues/search?issue_tags=slick%20asnavfor)
