(function ($) {

  /**
   * The recommended way for producing HTML markup through JavaScript is to write
   * theming functions. These are similiar to the theming functions that you might
   * know from 'phptemplate' (the default PHP templating engine used by most
   * Drupal themes including Omega). JavaScript theme functions accept arguments
   * and can be overriden by sub-themes.
   *
   * In most cases, there is no good reason to NOT wrap your markup producing
   * JavaScript in a theme function.
   */
  Drupal.theme.prototype.drw2020ExampleButton = function (path, title) {
    // Create an anchor element with jQuery.
    return $('<a href="' + path + '" title="' + title + '">' + title + '</a>');
  };

  /**
   * Behaviors are Drupal's way of applying JavaScript to a page. In short, the
   * advantage of Behaviors over a simple 'document.ready()' lies in how it
   * interacts with content loaded through Ajax. Opposed to the
   * 'document.ready()' event which is only fired once when the page is
   * initially loaded, behaviors get re-executed whenever something is added to
   * the page through Ajax.
   *
   * You can attach as many behaviors as you wish. In fact, instead of overloading
   * a single behavior with multiple, completely unrelated tasks you should create
   * a separate behavior for every separate task.
   *
   * In most cases, there is no good reason to NOT wrap your JavaScript code in a
   * behavior.
   *
   * @param context
   *   The context for which the behavior is being executed. This is either the
   *   full page or a piece of HTML that was just added through Ajax.
   * @param settings
   *   An array of settings (added through drupal_add_js()). Instead of accessing
   *   Drupal.settings directly you should use this because of potential
   *   modifications made by the Ajax callback that also produced 'context'.
   */
  Drupal.behaviors.drw2020Behavior = {
    attach: function (context, settings) {
      // By using the 'context' variable we make sure that our code only runs on
      // the relevant HTML. Furthermore, by using jQuery.once() we make sure that
      // we don't run the same piece of code for an HTML snippet that we already
      // processed previously. By using .once('foo') all processed elements will
      // get tagged with a 'foo-processed' class, causing all future invocations
      // of this behavior to ignore them.
      /*$('.some-selector', context).once('foo', function () {
        // Now, we are invoking the previously declared theme function using two
        // settings as arguments.
        var $anchor = Drupal.theme('drw2020ExampleButton', settings.myExampleLinkPath, settings.myExampleLinkTitle);

        // The anchor is then appended to the current element.
        $anchor.appendTo(this);
      });*/
      // flex-active-slide
      // var $elems = $('.noticias');
      var fullheight = $(document).height();
      
      
      //alert(fullheight);
      // after: function(slider){
      //   var thumnails = $('.gallery-thumbnails').children();
      //   thumnails.removeClass('active');
      //   thumnails.eq(slider.currentSlide).addClass('active');
      // }
      $('.flexslider').bind({
        before: function(e) {
          $(this).find(".flex-active-slide").find('.titleh2').each(function(){
            $(this).removeClass("animated zoomIn");
          });
        },
        after: function(e) {
          $(this).find(".flex-active-slide").find('.titleh2').addClass("animated zoomIn");
        }
      });
      // if($('.flexslider li').hasClass('flex-active-slide')){
      //   $('.flexslider li h2').addClass('bounceInUp animated');
      // }
      
      $effectEstacion = 'zoomInDown';
      $effectNoticia = 'bounceInRight';
      $('.servicios1').addClass('bounceInUp animated delay-0s');
      $('.servicios2').addClass('bounceInUp animated delay-1s');
      $('.servicios3').addClass('bounceInUp animated delay-2s');
      // -----------------------------------------------------------------------
      $(window).scroll(function(){
        wintop = $(window).scrollTop(); // calculate distance from top of window

        // loop through each item to check when it animates
        anima_block('.clientes1',$effectEstacion, wintop, 0);
        anima_block('.clientes2',$effectEstacion, wintop, 1);
        anima_block('.clientes3',$effectEstacion, wintop, 2);

        anima_block('.noticias1',$effectNoticia, wintop, 0);
        anima_block('.noticias2',$effectNoticia, wintop, 1);
        anima_block('.noticias3',$effectNoticia, wintop, 2);
        
        anima_block('.weare',$effectNoticia, wintop, 0);
        anima_block('.ourblog',$effectNoticia, wintop, 1);
        anima_block('.ourfaqs',$effectNoticia, wintop, 2);
        anima_block('.contactblk',$effectNoticia, wintop, 3);
      }); // end scroll
      // -----------------------------------------------------------------------
      // Start scroll control
      $(window).scroll(function() {
        /* Act on the event */
        if ($(this).scrollTop() > 10){
          $('.logo').addClass('smaller');
        }else{
          $('.logo').removeClass('smaller');
        }
      });
      // End scroll control
      // -----------------------------------------------------------------------
      function anima_block($name, $effect, $wintop, $delay){
        winheight = $(window).height();
        wintop = $wintop;
        $elems = $($name);
        $elems.each(function(){
          $elm = $(this);
          if($elm.hasClass('animated')) { return true; } // if already animated skip to the next item
          topcoords = $elm.offset().top; // element's distance from top of page in pixels
          if(wintop > (topcoords - (winheight*.75))) {
            // animate when top of the window is 3/4 above the element
            if($name != 'animatedblock'){
              $elm.addClass($effect);
              $elm.addClass('delay-' + $delay + "s"); // Time lapse it for appear
            }
            $elm.addClass('animated');
            
          }
        }); // end each
      }
      // -----------------------------------------------------------------------
      // Start scroll control
      $(window).scroll(function() {
        /* Act on the event */
        if ($(this).scrollTop() > 100){
          $('header').addClass('smaller');
        }else{
          $('header').removeClass('smaller');
        }
      });
      // End scroll control

      //Show or hide "ask a question" block
    $('.contactus button').click(function() {
      $('.webform-contactus-block').fadeToggle(50);
    });

    //"Ask a question closing button"
    $('.webform-contactus-block').append( "<div class=\"closebutton\"></div>");
    $('.webform-contactus-block .closebutton').click(function() {
      $('.webform-contactus-block').fadeOut(50);
    });
    /* Control over searchbox*/
    /*var isOpen = false;
    var searchBox = $('.form-search');
    var inputBox = $('.form-text');
    var submitIcon = $('.icon');
    submitIcon.mouseover(function() {

      if(isOpen == false){
        searchBox.addClass('busca-open');
        inputBox.focus();
        isOpen = true;
        inputBox.css('width','75%');
        console.log('ingreso aqui');
      }
    });
    inputBox.focusout(function() {

      searchBox.removeClass('busca-open');
      isOpen = false;
      inputBox.css('width','0');
      console.log('salio de aqui');
    });
*/

    /* End de control searchbox*/


    }
  };

})(jQuery);

