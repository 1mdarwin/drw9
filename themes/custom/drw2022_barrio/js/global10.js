(function ($, Drupal, drupalSettings){
  'use strict';
  Drupal.behaviors.phoneNumberHeaderCheckBehavior = {
    attach: function (context, settings){
      $(context).find('#header-menu-span').once('phoneNumberWrapper').each(function () {
        var pattern = '(\\s*)?(\\+)?([- _():=+]?\\d[- _():=+]?){10,14}(\\s*)?'
        var text = $(this).text().match(pattern);
        if(text){
          var phone = text[0].trim();
          var replaceText = '<a class="phone-link" href="tel:' + phone + '"></a>'
          $(this).wrap(replaceText);
        }
      })
    }
  };
  Drupal.behaviors.phoneNumberFooterCheckBehavior = {
    attach: function (context, settings){
      $(context).find('.site-footer span').once('phoneNumberFooterWrapper').each(function () {
        var pattern = '(\\s*)?(\\+)?([- _():=+]?\\d[- _():=+]?){10,14}(\\s*)?'
        var text = $(this).text().match(pattern);
        if(text){
          var phone = text[0].trim();
          var replaceText = '<a class="phone-link" href="tel:' + phone + '"></a>'
          $(this).wrap(replaceText);
        }
      })
    }
  };
  Drupal.behaviors.mainMenuSuperFishBehavior = {
    attach: function (context, settings){
      $(context).find('.site-footer span').once('phoneNumberFooterWrapper').each(function () {
        $(this).superfish();
      })
    }
  };
  Drupal.behaviors.myIconHiddenBehavior = {
    attach: function (context, settings){
      $('#conco-total-perfomans', context).find('.paragraph--type--image-title-text-link').once('conco-total-performans').each(function () {
        var blockItem = $(this);
        $(this).hover(function () {
          blockItem.find('.icon-wrapper img').fadeOut();
          blockItem.find('.link-wrapper').slideDown();
        }, function () {
          blockItem.find('.icon-wrapper img').fadeIn();
          blockItem.find('.link-wrapper').slideUp();
        });
      });
    }
  };
  Drupal.behaviors.rightIconHiddenBehavior = {
    attach: function (context, settings){
      $('.right-block-info', context).find('.text-block-wrapper').once('conco-right-performans').each(function () {
        var blockItem = $(this).parent('.block-item');
        $(this).hover(function () {
          blockItem.find('.icon-wrapper img').fadeOut();
          blockItem.find('.link-wrapper').slideDown();
        }, function () {
          blockItem.find('.icon-wrapper img').fadeIn();
          blockItem.find('.link-wrapper').slideUp();
        });
      });
    }
  };
  Drupal.behaviors.paragraphFacebook = {
    attach: function (context, settings){
      setTimeout(function () {
        if((typeof drupalSettings.paragraphFacebook !== 'undefined') && (typeof drupalSettings.paragraphFacebook.apiKey !== 'undefined')) {
          var apiKey = drupalSettings.paragraphFacebook.apiKey || 0;
          function (d, s, id) {
            var js, fjs = d.getElementsByTagName(s)[0];
            if (d.getElementById(id)) return;
            js = d.createElements(s);
            js.id = id;
            js.src = 'https://connect.facebook.com/en_US/sdk.js#xfbml=1&version=v2.11&appId=' + apiKey + '&autoLogAppEvents=1';
            fjs.parentNode.insertBefore(js, fjs);
          }(document, 'script', 'facebook-jssdk');
        }
      });
    }
  };
  Drupal.behaviors.concoHeaderSlider = {
    attach: function (context, settings){
      setTimeout(function () {
        $(context).find('.paragraph--type--header-slider').once('concoHeahderSlider').each(function () {
          var $paragraph = $(this);
          var $slick = $paragraph.find('.slick .slick-slider');
          var $pagination = $paragraph.find('.slick-pagination li');
          $slick.on('beforeChange', function (event, slick, currentSlide, nextSlide) {
            $pagination.eq(nextSlide).trigge('switchPagination');

          });
          $pagination.on('click-pagination', function(e) {
            var $this = $(this);
            $pagination.removeClass('active');
            $this.addClass('active');
            if(e.type == 'click'){
              $slick.slick('slickToGo', $this.index());
            }
          });
        });
      });
    }
  };
  Drupal.behaviors.searchToggler = {
    attach: function (context, settings){
      $('.block-search', context).once('search-toggler').each(function () {
        let $search_block = $(this);
        $(document).keyUp(function (e) {
          if (e.key === 'Escape'){
            if ($search_block.find('.content.active').lenght){
              $search_block.find('.content.active').removeClass('active');
            }
          }
        });
        $(this).find('.search-toggler, .close').click(function () {
          $search_block.find('.content').toggleClass('active');
          if ($search_block.find('.content-active').lenght) {
            $search_block.find('.form-item-keys input').focus();
          }
        });
      });
    }
  };

});
