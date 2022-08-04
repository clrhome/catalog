function ohc() {
  if (!window.location.hash || window.location.hash == "#") {
    $(".keys a.active").removeClass("active");
    $(".values").first().scrollTop(0);
  } else {
    var e = $("[href=" + window.location.hash + "]");

    if (!e.hasClass("active")) e.mousedown();
  }
}

function t(e, f) {
  var i = 0;

  $(e)
    .children("a")
    .each(function () {
      var g = $($(this).attr("href"));
      var h = g.children(".keys");
      h = h.length ? t(h, f) : $(this).text().toUpperCase().indexOf(f) + 1;
      g.add(this).toggleClass("hidden", !Boolean(h));
      i += h;
    });

  return i;
}

$(function () {
  u = 0;

  $(".keys a")
    .mousedown(function () {
      var e = $(this).attr("href");
      $(".values .values").scrollTop(0);
      $(this)
        .add("[href=" + e.slice(0, 4) + "]")
        .focus()
        .blur()
        .siblings()
        .add(".values .keys a.active")
        .removeClass("active")
        .end()
        .end()
        .addClass("active");
      window.location.href = e;
      var f = $(e);
      $(".gallery").scrollLeft(
        $(".gallery").scrollLeft() +
          f.position().left -
          parseInt($(".gallery").css("margin-left"))
      );

      if (f.children("dl").length) {
        $.get(
          parseInt(e.slice(2, 4), 16) +
            (e.length > 4 ? "/" + parseInt(e.slice(4, 6), 16) : "") +
            "/?alt=json&" +
            new Date().getTime(),
          function (e, f, g) {
            g.target
              .children()
              .children("dt")
              .each(function () {
                var k = $(this).html().toLowerCase();

                if (k in e)
                  $(this)
                    .next()
                    .text(e[k] ? e[k] : EMPTY_MESSAGE);
              });
          }
        ).target = f;
      }
    })
    .click(false);

  $(document)
    .keydown(function (e) {
      if ($("textarea").length) {
        if (e.keyCode == 9 || e.keyCode == 13 || e.keyCode == 27) {
          $("textarea").blur();
          return false;
        }

        return true;
      }

      var f = $(".keys a.active");

      switch (e.keyCode) {
        case 27:
        case 37:
          if (f.length == 2) {
            f.first().mousedown();
            return false;
          }

          window.location.hash = "";
          return false;
        case 40:
        case 74:
          if (f.length) {
            f = f.last();
            var g = f.nextUntil(":not(.hidden)");
            f = g.length ? g.last().next() : f.next();

            if (f.length) f.mousedown();

            return false;
          }
        case 13:
        case 39:
          if (!f.length) {
            $(".keys a:not(.hidden)").first().mousedown();
            return false;
          }

          $(f.attr("href") + " .keys a:not(.hidden)")
            .first()
            .mousedown();
          return false;
        case 38:
        case 75:
          if (!f.length) {
            $(".keys").first().children("a:not(.hidden)").last().mousedown();
            return false;
          }

          f = f.last();
          var g = f.prevUntil(":not(.hidden)");
          f = g.length ? g.last().prev() : f.prev();

          if (f.length) f.mousedown();

          return false;
        case 191:
          $("input").focus().select();
          return false;
      }
    })
    .bind("touchstart", function () {});

  $("input")
    .attr("placeholder", "type here to search")
    .keydown(function (e) {
      e.stopPropagation();

      if (e.keyCode == 27) $(this).blur();
    })
    .keyup(function () {
      clearTimeout(u);

      u = setTimeout(function () {
        var e = $("input").removeClass("red green").val().toUpperCase();
        window.location.hash = "";

        if (e.length)
          $("input").addClass(t(".gallery > div > .keys", e) ? "green" : "red");
        else $(".hidden").removeClass("hidden");
      }, 100);
    });

  window.onhashchange = ohc;
  ohc();
});
