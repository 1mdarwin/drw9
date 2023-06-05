/**
 * @file
 * Provides Photobox integration for Image and Media fields.
 *
 * @tbd deprecated at 2.5 and and removed at 3.+, this library is unmaintained,
 * and has good replacements like PhotoSwipe, Splidebox, Slick Lightbox, etc.
 */

(function ($, Drupal, _doc) {

  'use strict';

  var _context = _doc;
  var _id = 'photobox';
  var _idOnce = 'b-' + _id;
  var _element = '[data-' + _id + '-gallery]';
  var _mounted = 'is-' + _id + '-mounted';

  /**
   * Blazy Photobox utility functions.
   *
   * @param {HTMLElement} box
   *   The photobox HTML element.
   */
  function process(box) {
    var $box = $(box);

    function callback(el) {
      if ($.isElm(el)) {
        var caption = $.next(el);
        if (caption) {
          var title = $.find(_context, '#pbCaption .title');
          if ($.isElm(title)) {
            title.innerHTML = caption.innerHTML;
          }
        }
      }
    }

    $box.photobox('a[data-photobox-trigger]', {
      thumb: '> [data-thumb]',
      thumbAttr: 'data-thumb'
    }, callback);

    $.addClass(box, _mounted);
  }

  /**
   * Attaches blazy photobox behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyPhotobox = {
    attach: function (context) {

      _context = $.context(context);

      // Converts jQuery.photobox into dBlazy.photobox to demonstrate the new
      // dBlazy plugin system post Blazy 2.6.
      if (jQuery && $.isFun(jQuery.fn.photobox) && !$.isFun($.fn.photobox)) {
        var _pb = jQuery.fn.photobox;

        $.fn.photobox = function (target, settings, callback) {
          return $(_pb.apply(this, arguments));
        };
      }

      $.once(process, _idOnce, _element + ':not(.' + _mounted + ')', _context);

    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $.once.removeSafely(_idOnce, _element, _context);
      }
    }
  };

}(dBlazy, Drupal, this.document));
