/*
 * Progressive enhancement only. Every feature here has a working no-JS path:
 * the appearance form submits normally, and sentences can still be selected
 * and copied by hand.
 */
(function () {
  "use strict";

  /* Appearance and language: submit on change, hide the fallback button. */
  document.querySelectorAll("[data-autosubmit]").forEach(function (form) {
    form.querySelectorAll("[data-autosubmit-button]").forEach(function (button) {
      button.classList.add("hidden");
    });
    form.addEventListener("change", function () {
      form.submit();
    });
  });

  /* Copy to clipboard, with a fallback for browsers without the async API
     and for pages served over plain HTTP. */
  function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }

    return new Promise(function (resolve, reject) {
      var area = document.createElement("textarea");
      area.value = text;
      area.setAttribute("readonly", "");
      area.style.position = "fixed";
      area.style.top = "-1000px";
      document.body.appendChild(area);
      area.select();
      try {
        document.execCommand("copy") ? resolve() : reject(new Error("copy failed"));
      } catch (error) {
        reject(error);
      } finally {
        document.body.removeChild(area);
      }
    });
  }

  function flash(element) {
    var done = element.getAttribute("data-copied-label");
    if (!done) {
      return;
    }
    var status = element.querySelector("[data-copy-status]");
    if (!status) {
      return;
    }
    var previous = status.textContent;
    status.textContent = done;
    element.classList.add("is-copied");
    window.setTimeout(function () {
      status.textContent = previous;
      element.classList.remove("is-copied");
    }, 1600);
  }

  document.addEventListener("click", function (event) {
    var trigger = event.target.closest("[data-copy]");
    if (!trigger) {
      return;
    }

    var source = trigger.getAttribute("data-copy");
    var text = source === "self"
      ? (trigger.getAttribute("data-copy-text") || trigger.textContent.trim())
      : (document.getElementById(source) || {}).textContent;

    if (!text) {
      return;
    }

    event.preventDefault();
    copyText(text).then(function () {
      flash(trigger);
    }).catch(function () {
      /* Leave the text on screen so it can still be selected by hand. */
    });
  });
})();
