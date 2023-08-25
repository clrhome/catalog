"use strict";
function startEditing() {
    var dd = this;
    dd.innerHTML = "<textarea>" + dd.textContent + "</textarea>";
    var textarea = dd.firstElementChild;
    textarea.focus();
    textarea.select();
    textarea.ondblclick = window.catalogCancel;
    textarea.onblur = stopEditing;
    textarea.onkeydown = function (event) {
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
                this.onblur(document.createEvent("FocusEvent"));
                window.catalogCancel(event);
                break;
        }
    };
}
function stopEditing() {
    var textarea = this;
    var request = new XMLHttpRequest();
    request.onload = function (event) {
        textarea.parentElement.innerHTML = request.response;
    };
    request.open("POST", window.catalogTokenUrl(window.catalogActiveEntries[window.catalogActiveEntries.length - 1].getAttribute("href")), true);
    request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    request.send(textarea.parentElement.previousElementSibling.innerHTML.toLowerCase() +
        "=" +
        window.encodeURIComponent(textarea.value));
}
function initializeEdit() {
    var rights = document.getElementsByClassName("right");
    for (var rightIndex = 0; rightIndex < rights.length; rightIndex++) {
        var dds = rights.item(rightIndex).getElementsByTagName("dd");
        for (var ddIndex = 0; ddIndex < dds.length; ddIndex++) {
            dds.item(ddIndex).ondblclick = startEditing;
        }
    }
}
document.addEventListener("DOMContentLoaded", initializeEdit);
