const ACTIVE_CLASS = "active";
const UNMATCHED_CLASS = "unmatched";
const SEARCH_BAR_PLACEHOLDER = "type to search\u2026 (/)";
const SEARCH_TIMEOUT = 200;

function cancel(event) {
  event.preventDefault();
  event.stopPropagation();
}

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

    if (leftChild.nodeType === Node.ELEMENT_NODE) {
      let leftMatchCount = 0;

      const target = document.getElementById(
        leftChild.getAttribute("href").slice(1)
      );

      for (
        let targetChildIndex = 0;
        targetChildIndex < target.childNodes.length;
        targetChildIndex++
      ) {
        const targetChild = target.childNodes.item(targetChildIndex);

        if (
          targetChild.nodeType === Node.ELEMENT_NODE &&
          hasClass(targetChild, "left")
        ) {
          leftMatchCount += filterLeft(targetChild, query);
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
      toggleClass(target, UNMATCHED_CLASS, unmatched);

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

function initializeSearch() {
  let activeEntries = [];
  const lefts = document.getElementsByClassName("left");
  const entries = {};
  const gallery = document.getElementsByClassName("gallery").item(0);
  const rootLeft = lefts.item(0);
  const searchBar = document.createElement("input");
  let searchTimeout = 0;

  function clearActiveEntries() {
    clearActiveEntriesWithoutUpdatingHash();
    window.location.hash = "";
  }

  function clearActiveEntriesWithoutUpdatingHash() {
    for (let entryIndex = 0; entryIndex < activeEntries.length; entryIndex++) {
      toggleClass(activeEntries[entryIndex], ACTIVE_CLASS, false);
    }

    for (
      let activeEntryIndex = 0;
      activeEntryIndex < activeEntries.length;
      activeEntryIndex++
    ) {
      const target = document.getElementById(
        activeEntries[activeEntryIndex].getAttribute("href").slice(1)
      );

      if (target != null) {
        target.parentNode.scrollTop = 0;
      }
    }

    activeEntries = [];
  }

  function search() {
    if (searchBar.value.length !== 0) {
      searchBar.className =
        filterLeft(rootLeft, searchBar.value.toLowerCase()) !== 0
          ? "green"
          : "red";
    } else {
      searchBar.className = "";

      for (const href in entries) {
        toggleClass(entries[href], UNMATCHED_CLASS, false);
        toggleClass(
          document.getElementById(href.slice(1)),
          UNMATCHED_CLASS,
          false
        );
      }
    }
  }

  function selectEntry(entry) {
    const href = entry.getAttribute("href");
    let url = "";

    clearActiveEntriesWithoutUpdatingHash();

    for (let hrefBreak = 4; hrefBreak <= href.length; hrefBreak += 2) {
      const partialEntry = entries[href.slice(0, hrefBreak)];

      partialEntry.focus();
      partialEntry.blur();
      toggleClass(partialEntry, "active", true);
      activeEntries.push(partialEntry);
      url += parseInt(href.slice(hrefBreak - 2, hrefBreak), 16) + "/";
    }

    const dts = document
      .getElementById(href.slice(1))
      .getElementsByTagName("dt");

    if (dts.length !== 0) {
      const request = new XMLHttpRequest();

      request.onload = function (event) {
        const response = JSON.parse(request.response);

        for (let dtIndex = 0; dtIndex < dts.length; dtIndex++) {
          const dt = dts.item(dtIndex);
          const key = dt.innerHTML.toLowerCase();

          if (key in response) {
            let sibling;

            for (
              sibling = dt.nextSibling;
              sibling != null && sibling.nodeType !== Node.ELEMENT_NODE;
              sibling = sibling.nextSibling
            );

            if (sibling != null) {
              sibling.innerHTML =
                response[key].length !== 0 ? response[key] : EMPTY_MESSAGE;
            }
          }
        }
      };

      request.open(
        "GET",
        url + "?alt=json&html=1&v=" + new Date().getTime(),
        true
      );

      request.send();
    }

    window.location.href = href;
  }

  function selectEntryAndCancel(event) {
    selectEntry(event.target);
    cancel(event);
  }

  for (let leftIndex = 0; leftIndex < lefts.length; leftIndex++) {
    const leftEntries = lefts.item(leftIndex).getElementsByTagName("a");

    for (
      let leftEntryIndex = 0;
      leftEntryIndex < leftEntries.length;
      leftEntryIndex++
    ) {
      const leftEntry = leftEntries.item(leftEntryIndex);

      leftEntry.onclick = cancel;
      leftEntry.onmousedown = selectEntryAndCancel;
      entries[leftEntry.getAttribute("href")] = leftEntry;
    }
  }

  searchBar.placeholder = SEARCH_BAR_PLACEHOLDER;
  document.getElementsByTagName("main").item(0).appendChild(searchBar);

  document.onkeydown = function (event) {
    switch (event.key) {
      case "/":
        searchBar.focus();
        searchBar.select();
        cancel(event);
        break;
      case "ArrowDown":
      case "j":
        if (activeEntries.length >= 1) {
          let sibling;

          for (
            sibling = activeEntries[activeEntries.length - 1].nextSibling;
            sibling != null &&
            (sibling.nodeType !== Node.ELEMENT_NODE ||
              hasClass(sibling, UNMATCHED_CLASS));
            sibling = sibling.nextSibling
          );

          if (sibling != null) {
            selectEntry(sibling);
          }
        }

        cancel(event);
        break;
      case "ArrowLeft":
      case "h":
        if (activeEntries.length >= 2) {
          selectEntry(activeEntries[activeEntries.length - 2]);
        } else {
          clearActiveEntries();
        }

        cancel(event);
        break;
      case "ArrowRight":
      case "l":
        const left =
          activeEntries.length >= 1
            ? document
                .getElementById(
                  activeEntries[activeEntries.length - 1]
                    .getAttribute("href")
                    .slice(1)
                )
                .getElementsByClassName("left")
                .item(0)
            : rootLeft;

        if (left != null) {
          for (
            let leftChildIndex = 0;
            leftChildIndex < left.childNodes.length;
            leftChildIndex++
          ) {
            const leftChild = left.childNodes.item(leftChildIndex);

            if (
              leftChild.nodeType === Node.ELEMENT_NODE &&
              !hasClass(leftChild, UNMATCHED_CLASS)
            ) {
              selectEntry(leftChild);
              break;
            }
          }
        }

        cancel(event);
        break;
      case "ArrowUp":
      case "k":
        if (activeEntries.length >= 1) {
          let sibling;

          for (
            sibling = activeEntries[activeEntries.length - 1].previousSibling;
            sibling != null &&
            (sibling.nodeType !== Node.ELEMENT_NODE ||
              hasClass(sibling, UNMATCHED_CLASS));
            sibling = sibling.previousSibling
          );

          if (sibling != null) {
            selectEntry(sibling);
          }
        }

        cancel(event);
        break;
    }

    if ($("textarea").length) {
      if (e.keyCode == 9 || e.keyCode == 13 || e.keyCode == 27) {
        $("textarea").blur();
        return false;
      }

      return true;
    }
  };

  document.ontouchstart = function () {};

  searchBar.onkeydown = function (event) {
    switch (event.key) {
      case "/":
      case "ArrowDown":
      case "ArrowLeft":
      case "ArrowRight":
      case "ArrowUp":
      case "h":
      case "j":
      case "k":
      case "l":
        event.stopPropagation();
        break;
      case "Escape":
        searchBar.blur();
        cancel(event);
        break;
    }
  };

  searchBar.onkeyup = function () {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(search, SEARCH_TIMEOUT);
  };

  window.onhashchange = function (event) {
    if (window.location.hash.length <= 2) {
      clearActiveEntries();
    } else if (!hasClass(entries[window.location.hash], "active")) {
      selectEntry(entries[window.location.hash]);
    }

    cancel(event);
  };

  window.onhashchange(document.createEvent("HashChangeEvent"));
}

function toggleClass(element, className, value) {
  element.className = element.className.replace(classRegExp(className), "");

  if (value) {
    element.className += " " + className;
  }
}

document.addEventListener("DOMContentLoaded", initializeSearch);
