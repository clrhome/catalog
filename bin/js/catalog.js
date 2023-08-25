const ACTIVE_CLASS = "active";
const UNMATCHED_CLASS = "unmatched";
const SEARCH_BAR_PLACEHOLDER = "type to search\u2026 (/)";
const SEARCH_TIMEOUT = 200;

window.catalogCancel = function (event) {
  event.preventDefault();
  event.stopPropagation();
};

window.catalogTokenUrl = function (href) {
  let url = "";

  for (let hrefBreak = 4; hrefBreak <= href.length; hrefBreak += 2) {
    url += parseInt(href.slice(hrefBreak - 2, hrefBreak), 16) + "/";
  }

  return url;
};

function classRegExp(className) {
  return new RegExp("\\s*" + className + "\\b");
}

function filterLeft(left, query) {
  let matchCount = 0;

  for (
    let leftChildIndex = 0;
    leftChildIndex < left.children.length;
    leftChildIndex++
  ) {
    const leftChild = left.children.item(leftChildIndex);
    let leftMatchCount = 0;

    const target = document.getElementById(
      leftChild.getAttribute("href").slice(1)
    );

    for (
      let targetChildIndex = 0;
      targetChildIndex < target.children.length;
      targetChildIndex++
    ) {
      const targetChild = target.children.item(targetChildIndex);

      if (hasClass(targetChild, "left")) {
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

  return matchCount;
}

function hasClass(element, className) {
  return classRegExp(className).test(element.className);
}

function initializeSearch() {
  window.catalogActiveEntries = [];

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
    for (
      let entryIndex = 0;
      entryIndex < window.catalogActiveEntries.length;
      entryIndex++
    ) {
      toggleClass(window.catalogActiveEntries[entryIndex], ACTIVE_CLASS, false);
    }

    for (
      let activeEntryIndex = 0;
      activeEntryIndex < window.catalogActiveEntries.length;
      activeEntryIndex++
    ) {
      const target = document.getElementById(
        window.catalogActiveEntries[activeEntryIndex]
          .getAttribute("href")
          .slice(1)
      );

      if (target != null) {
        target.parentNode.scrollTop = 0;
      }
    }

    window.catalogActiveEntries = [];
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

    clearActiveEntriesWithoutUpdatingHash();

    for (let hrefBreak = 4; hrefBreak <= href.length; hrefBreak += 2) {
      const partialEntry = entries[href.slice(0, hrefBreak)];

      partialEntry.focus();
      partialEntry.blur();
      toggleClass(partialEntry, "active", true);
      window.catalogActiveEntries.push(partialEntry);
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

          if (key in response && dt.nextElementSibling != null) {
            dt.nextElementSibling.innerHTML =
              response[key].length !== 0 ? response[key] : EMPTY_MESSAGE;
          }
        }
      };

      request.open(
        "GET",
        window.catalogTokenUrl(href) +
          "?alt=json&html=1&v=" +
          new Date().getTime(),
        true
      );

      request.send();
    }

    window.location.href = href;
  }

  function selectEntryAndCancel(event) {
    selectEntry(event.target);
    window.catalogCancel(event);
  }

  for (let leftIndex = 0; leftIndex < lefts.length; leftIndex++) {
    const leftEntries = lefts.item(leftIndex).getElementsByTagName("a");

    for (
      let leftEntryIndex = 0;
      leftEntryIndex < leftEntries.length;
      leftEntryIndex++
    ) {
      const leftEntry = leftEntries.item(leftEntryIndex);

      leftEntry.onclick = window.catalogCancel;
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
        window.catalogCancel(event);
        break;
      case "ArrowDown":
      case "j":
        if (window.catalogActiveEntries.length >= 1) {
          const sibling =
            window.catalogActiveEntries[window.catalogActiveEntries.length - 1]
              .nextElementSibling;

          if (sibling != null) {
            selectEntry(sibling);
          }
        }

        window.catalogCancel(event);
        break;
      case "ArrowLeft":
      case "h":
        if (window.catalogActiveEntries.length >= 2) {
          selectEntry(
            window.catalogActiveEntries[window.catalogActiveEntries.length - 2]
          );
        } else {
          clearActiveEntries();
        }

        window.catalogCancel(event);
        break;
      case "ArrowRight":
      case "l":
        const left =
          window.catalogActiveEntries.length >= 1
            ? document
                .getElementById(
                  window.catalogActiveEntries[
                    window.catalogActiveEntries.length - 1
                  ]
                    .getAttribute("href")
                    .slice(1)
                )
                .getElementsByClassName("left")
                .item(0)
            : rootLeft;

        if (left != null) {
          for (
            let leftChildIndex = 0;
            leftChildIndex < left.children.length;
            leftChildIndex++
          ) {
            const leftChild = left.children.item(leftChildIndex);

            if (!hasClass(leftChild, UNMATCHED_CLASS)) {
              selectEntry(leftChild);
              break;
            }
          }
        }

        window.catalogCancel(event);
        break;
      case "ArrowUp":
      case "k":
        if (window.catalogActiveEntries.length >= 1) {
          const sibling =
            window.catalogActiveEntries[window.catalogActiveEntries.length - 1]
              .previousElementSibling;

          if (sibling != null) {
            selectEntry(sibling);
          }
        }

        window.catalogCancel(event);
        break;
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
        window.catalogCancel(event);
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

    window.catalogCancel(event);
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
