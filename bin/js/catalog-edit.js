function startEditing() {
  this.innerHTML = "<textarea>" + this.textContent + "</textarea>";
  this.firstElementChild.focus();
  this.firstElementChild.select();
  this.firstElementChild.ondblclick = window.catalogCancel;
  this.firstElementChild.onblur = stopEditing;

  this.firstElementChild.onkeydown = function (event) {
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
        this.onblur();
        window.catalogCancel(event);
        break;
    }
  };
}

function stopEditing() {
  const textarea = this;
  const request = new XMLHttpRequest();

  request.onload = function (event) {
    textarea.parentNode.innerHTML = request.response;
  };

  request.open(
    "POST",
    window.catalogTokenUrl(
      window.catalogActiveEntries[
        window.catalogActiveEntries.length - 1
      ].getAttribute("href")
    ),
    true
  );

  request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

  request.send(
    this.parentNode.previousElementSibling.innerHTML.toLowerCase() +
      "=" +
      window.encodeURIComponent(this.value)
  );
}

function initializeEdit() {
  const rights = document.getElementsByClassName("right");

  for (let rightIndex = 0; rightIndex < rights.length; rightIndex++) {
    const dds = rights.item(rightIndex).getElementsByTagName("dd");

    for (let ddIndex = 0; ddIndex < dds.length; ddIndex++) {
      dds.item(ddIndex).ondblclick = startEditing;
    }
  }
}

document.addEventListener("DOMContentLoaded", initializeEdit);
