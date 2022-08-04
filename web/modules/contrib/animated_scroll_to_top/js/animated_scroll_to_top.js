(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.animated_scroll_to_top = {
    attach: function (context, settings) {
      $('body').once('animated_scroll_to_top').append('<a href="#" class="scrollup">Scroll</a>');
      var position = drupalSettings.animated_scroll_to_top_position;
      var button_bg_color = drupalSettings.animated_scroll_to_top_button_bg_color;
      var hover_button_bg_color = drupalSettings.animated_scroll_to_top_button_hover_bg_color;
      var button_height = drupalSettings.animated_scroll_to_top_button_height;
      var button_width = drupalSettings.animated_scroll_to_top_button_width;
      var button_position_bottom = drupalSettings.animated_scroll_to_top_button_bottom + "px";
      var button_position_left_right = drupalSettings.animated_scroll_to_top_button_position + "px";
      if (position == 1) {
        $('.scrollup').css({"left": "100px", "background-color": button_bg_color});
        $('.scrollup').css({
          "left": button_position_left_right,
          "background-color": button_bg_color,
          "height": button_height,
          "width": button_width,
          "bottom": button_position_bottom
        });
      } else {
        $('.scrollup').css({"right": "100px", "background-color": button_bg_color});
        $('.scrollup').css({
          "right": button_position_left_right,
          "background-color": button_bg_color,
          "height": button_height,
          "width": button_width,
          "bottom": button_position_bottom
        });
      }
      $(".scrollup").hover(function () {
        $(this).css("background-color", hover_button_bg_color);
      }, function () {
        $(this).css("background-color", button_bg_color);
      });
      $(window).scroll(function () {
        if ($(this).scrollTop() > 100) {
          $('.scrollup').fadeIn();
        } else {
          $('.scrollup').fadeOut();
        }
      });
      $(".scrollup").click(function () {
        $("html, body").animate({
          scrollTop: 0
        }, 600);
        return false;
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
