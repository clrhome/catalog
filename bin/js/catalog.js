const ACTIVE_CLASS = "active";
const UNMATCHED_CLASS = "unmatched";

function classRegExp(className) {
  return new RegExp("\\s*" + className + "\\b");
}

function filterLeft(left, query) {
  let matchCount = 0;

  for (
    let leftChildIndex = 0;
    leftChildIndex < left.childNodes.length;
    leftChildIndex++
  ) {
    const leftChild = left.childNodes.item(leftChildIndex);

    if (leftChild.nodeName === "A") {
      let leftMatchCount = 0;

      const right = document.getElementById(
        leftChild.getAttribute("href").slice(1)
      );

      for (
        let rightChildIndex = 0;
        rightChildIndex < right.childNodes.length;
        rightChildIndex++
      ) {
        const rightChild = right.childNodes.item(rightChildIndex);

        if (
          rightChild.nodeType === Node.ELEMENT_NODE &&
          /\bleft\b/.test(rightChild.className)
        ) {
          leftMatchCount += filterLeft(rightChild, query);
          break;
        }
      }

      if (
        leftMatchCount === 0 &&
        leftChild.innerText.toLowerCase().indexOf(query) !== -1
      ) {
        leftMatchCount = 1;
      }

      const unmatched = leftMatchCount === 0;

      toggleClass(leftChild, UNMATCHED_CLASS, unmatched);
      toggleClass(right, UNMATCHED_CLASS, unmatched);

      if (!unmatched) {
        matchCount += leftMatchCount;
      }
    }
  }

  return matchCount;
}

function hasClass(element, className) {
  return classRegExp(className).test(element.className);
}

function toggleClass(element, className, value) {
  element.className = element.className.replace(classRegExp(className), "");

  if (value) {
    element.className += " " + className;
  }
}

function initializeSearch() {
  let activeEntries = [];
  const divs = document.getElementsByTagName("div");
  let gallery;
  const entries = {};
  let searchTimeout = 0;

  function clearActiveEntries() {
    for (let entryIndex = 0; entryIndex < activeEntries.length; entryIndex++) {
      toggleClass(activeEntries[entryIndex], ACTIVE_CLASS, false);
    }

    activeEntries = [];
  }

  function handleMouseDown(event) {
    selectEntry(event.target);
    event.preventDefault();
    event.stopPropagation();
  }

  function scrollLeft() {
    gallery.scrollLeft = 0;
  }

  function selectEntry(entry) {
    const href = entry.getAttribute("href");

    clearActiveEntries();

    for (let hrefBreak = 4; hrefBreak <= href.length; hrefBreak += 2) {
      const partialEntry = entries[href.slice(0, hrefBreak)];

      partialEntry.focus();
      partialEntry.blur();
      toggleClass(partialEntry, "active", true);
      activeEntries.push(partialEntry);
    }

    window.setTimeout(scrollLeft, 0);
    window.location.href = href;
  }

  for (let divIndex = 0; divIndex < divs.length; divIndex++) {
    const div = divs.item(divIndex);

    if (hasClass(div, "gallery")) {
      gallery = div;
    } else if (hasClass(div, "left")) {
      divEntries = div.getElementsByTagName("a");

      for (
        let divEntryIndex = 0;
        divEntryIndex < divEntries.length;
        divEntryIndex++
      ) {
        const divEntry = divEntries.item(divEntryIndex);

        divEntry.onmousedown = handleMouseDown;
        entries[divEntry.getAttribute("href")] = divEntry;
      }
    }
  }

  $(".left a")
    .mousedown(function () {
      var e = $(this).attr("href");
      var f = $(e);
      if (f.children("dl").length) {
        $.get(
          parseInt(e.slice(2, 4), 16) +
            (e.length > 4 ? "/" + parseInt(e.slice(4, 6), 16) : "") +
            "/?alt=json&html=1&" +
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
                    .html(e[k] ? e[k] : EMPTY_MESSAGE);
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

      var f = $(".left a.active");

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
            var g = f.nextUntil(":not(.unmatched)");
            f = g.length ? g.last().next() : f.next();

            if (f.length) f.mousedown();

            return false;
          }
        case 13:
        case 39:
          if (!f.length) {
            $(".left a:not(.unmatched)").first().mousedown();
            return false;
          }

          $(f.attr("href") + " .left a:not(.unmatched)")
            .first()
            .mousedown();
          return false;
        case 38:
        case 75:
          if (!f.length) {
            $(".left").first().children("a:not(.unmatched)").last().mousedown();
            return false;
          }

          f = f.last();
          var g = f.prevUntil(":not(.unmatched)");
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
    .attr("placeholder", "type to search\u2026 (/)")
    .keydown(function (e) {
      e.stopPropagation();

      if (e.keyCode == 27) $(this).blur();
    })
    .keyup(function () {
      clearTimeout(u);

      u = setTimeout(function () {
        var e = $("input").removeClass("red green").val().toLowerCase();
        window.location.hash = "";

        if (e.length)
          $("input").addClass(
            filterLeft($(".gallery > div > div > .left")[0], e)
              ? "green"
              : "red"
          );
        else $(".unmatched").removeClass("unmatched");
      }, 100);
    });

  window.onhashchange = function () {
    if (window.location.hash.length <= 2) {
      clearActiveEntries();
    } else {
      if (!hasClass(entries[window.location.hash], "active")) {
        selectEntry(entries[window.location.hash]);
      }
    }
  };

  window.onhashchange(document.createEvent("HashChangeEvent"));
}

document.addEventListener("DOMContentLoaded", initializeSearch);
