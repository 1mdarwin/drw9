// JavaScript Document
(function($) {
  $(document).ready(function() {
      //Theia Sticky Sidebar
      if ($('.sidebar-first .region-sidebar-first').length > 0 ) {
          $('.sidebar-first').theiaStickySidebar({
              // Settings
              additionalMarginTop: 30
          });
      }

      $('#block-headercontactinfo').clone().appendTo(".mm-panel").removeAttr('id').attr("class", "block-headercontactinfo-mobile");


      $('header .name').clone().prependTo(".mm-panel");
      $('header .logo').clone().prependTo(".mm-panel");

      $(".webform-submission-form .captcha").insertBefore(".webform-actions");
  }); //end ready

})(jQuery);
