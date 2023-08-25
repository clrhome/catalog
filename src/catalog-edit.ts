function startEditing(this: GlobalEventHandlers): void {
  const dd = this as HTMLElement;

  dd.innerHTML = "<textarea>" + dd.textContent + "</textarea>";

  const textarea = dd.firstElementChild as HTMLTextAreaElement;

  textarea.focus();
  textarea.select();
  textarea.ondblclick = window.catalogCancel;
  textarea.onblur = stopEditing;

  textarea.onkeydown = function (event: KeyboardEvent): void {
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
        this.onblur!(document.createEvent("FocusEvent"));
        window.catalogCancel(event);
        break;
    }
  };
}

function stopEditing(this: GlobalEventHandlers): void {
  const textarea = this as HTMLTextAreaElement;
  const request = new XMLHttpRequest();

  request.onload = function (event: Event): void {
    textarea.parentElement!.innerHTML = request.response;
  };

  request.open(
    "POST",
    window.catalogTokenUrl(
      window.catalogActiveEntries[
        window.catalogActiveEntries.length - 1
      ].getAttribute("href")!
    ),
    true
  );

  request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

  request.send(
    textarea.parentElement!.previousElementSibling!.innerHTML.toLowerCase() +
      "=" +
      window.encodeURIComponent(textarea.value)
  );
}

function initializeEdit(): void {
  const rights = document.getElementsByClassName("right");

  for (let rightIndex = 0; rightIndex < rights.length; rightIndex++) {
    const dds = rights.item(rightIndex)!.getElementsByTagName("dd");

    for (let ddIndex = 0; ddIndex < dds.length; ddIndex++) {
      dds.item(ddIndex)!.ondblclick = startEditing;
    }
  }
}

document.addEventListener("DOMContentLoaded", initializeEdit);
