(function ($) {
  "use strict";

  var reduceMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  function revealElements() {
    var targets = document.querySelectorAll(
      ".content-body .card, .content-body .table-responsive, .content-body > .container-fluid > .d-flex:first-child, .content-body > .container-fluid > .form-head:first-child, .authincation-content"
    );

    if (reduceMotion || !("IntersectionObserver" in window)) {
      targets.forEach(function (target) {
        target.classList.add("is-visible");
      });
      return;
    }

    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.08, rootMargin: "0px 0px -40px 0px" }
    );

    targets.forEach(function (target, index) {
      target.classList.add("bc-reveal");
      target.style.transitionDelay = Math.min(index * 35, 220) + "ms";
      observer.observe(target);
    });
  }

  function enhanceTables() {
    $(".table-responsive table").each(function () {
      var $table = $(this);
      if (!$table.hasClass("table-hover")) {
        $table.addClass("table-hover");
      }
    });
  }

  function addPressedState() {
    $(document)
      .on("mousedown touchstart", ".btn, .nav-link, .dlabnav .metismenu a, .jobs", function () {
        if (!reduceMotion) {
          $(this).addClass("bc-pressing");
        }
      })
      .on("mouseup mouseleave touchend touchcancel", ".btn, .nav-link, .dlabnav .metismenu a, .jobs", function () {
        $(this).removeClass("bc-pressing");
      });
  }

  function syncDynamicContent() {
    $(document).ajaxComplete(function () {
      enhanceTables();
    });
  }

  $(function () {
    document.documentElement.classList.add("bestcopro-refresh");
    revealElements();
    enhanceTables();
    addPressedState();
    syncDynamicContent();
  });
})(jQuery);
