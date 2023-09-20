/**
 * @file
 * Provides Intersection Observer API AJAX helper.
 *
 * Blazy IO works fine with AJAX, until using VIS, or alike. Adds a helper.
 * Required to fix for what Native lazy doesn't support Blur, Video, BG.
 * Similar to core responsive_image/ajax fix, only different approach.
 *
 * @todo remove once bio.js plays nice for media, VIS, blocks, or if core/once
 * fixes this type of issue when min D9.2.
 */

(function ($, Drupal) {

  'use strict';

  var D_BLAZY = Drupal.blazy || {};
  var D_AJAX = Drupal.Ajax || {};
  var PROTO = D_AJAX.prototype;
  var REV_TIMER;

  if (!PROTO) {
    return;
  }

  // Overrides Drupal.Ajax.prototype.success to re-observe new AJAX contents.
  PROTO.success = (function (D_AJAX) {
    return function (response, status) {
      var me = D_BLAZY.init;
      var opts;

      if (me) {
        opts = D_BLAZY.options;

        clearTimeout(REV_TIMER);

        // DOM ready fix. Be sure Views "Use field template" is disabled.
        REV_TIMER = setTimeout(function () {
          var elms = $.findAll(document, $.selector(opts, true));
          if (elms.length) {
            // ::load() means forcing them to load at once, great for small
            // amount of items, bad for large amount.
            // ::revalidate() means re-observe newly loaded AJAX contents
            // without forcing all images to load at once, great for large, bad
            // for small.
            // Unfortunately revalidate() not always work, likely layout reflow.
            me.load(elms, true, opts);
          }
        }, 100);
      }

      return D_AJAX.apply(this, arguments);
    };
  })(PROTO.success);

})(dBlazy, Drupal);
