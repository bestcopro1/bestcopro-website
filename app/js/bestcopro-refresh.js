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

  function clearDropdownHosts() {
    $(".bc-dropdown-host").removeClass("bc-dropdown-host");
  }

  function markDropdownHost($select) {
    clearDropdownHosts();
    $select
      .parents(".form-group, .card-body, .card, .modal-body, .modal-content, .tab-content")
      .addClass("bc-dropdown-host");
  }

  function manageDropdownLayers() {
    $(document)
      .on("click keyup", ".nice-select", function () {
        var $select = $(this);
        window.requestAnimationFrame(function () {
          if ($select.hasClass("open")) {
            markDropdownHost($select);
          } else {
            clearDropdownHosts();
          }
        });
      })
      .on("select2:open", "select", function () {
        markDropdownHost($(this));
      })
      .on("select2:close", "select", clearDropdownHosts)
      .on("hidden.bs.modal", ".modal", clearDropdownHosts);
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
    manageDropdownLayers();
    syncDynamicContent();
  });
})(jQuery);
