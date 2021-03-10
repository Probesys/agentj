jQuery(function ($) {

  if (window.innerWidth < 1280) {
    $(".page-wrapper").toggleClass("toggled");
  }

  // Dropdown menu
  $(".sidebar-dropdown > a").click(function () {
    $(".sidebar-submenu").slideUp(200);
    if ($(this).parent().hasClass("active")) {
      $(".sidebar-dropdown").removeClass("active");
      $(this).parent().removeClass("active");
    } else {
      $(".sidebar-dropdown").removeClass("active");
      $(this).next(".sidebar-submenu").slideDown(200);
      $(this).parent().addClass("active");
    }

  });

  $(".sidebar-dropdown.active").each(function () {
    $(this).children(".sidebar-submenu").slideDown(200);
    $(this).parent().addClass("active");

  });

  //toggle sidebar
  $("#toggle-sidebar").click(function () {
    $(".page-wrapper").toggleClass("toggled");
  });



  //toggle sidebar overlay
  $("#overlay").click(function () {
    $(".page-wrapper").toggleClass("toggled");
  });

  $(".sidebar-content").mCustomScrollbar({
    axis: "y",
    autoHideScrollbar: true,
    scrollInertia: 300
  });
});
