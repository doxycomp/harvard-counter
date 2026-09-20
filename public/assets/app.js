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

  /* Live preview of the Discord template.
     This mirrors App\Formatter so typing shows a result immediately. The
     server renders the same preview on load and on save, and stays the
     authority — if the two ever disagree, the server is right. */
  (function () {
    var preview = document.querySelector("[data-preview]");
    if (!preview) {
      return;
    }

    var vars, lines;
    try {
      vars = JSON.parse(preview.getAttribute("data-preview-vars") || "{}");
      lines = JSON.parse(preview.getAttribute("data-preview-lines") || "[]");
    } catch (error) {
      return;
    }

    var warning = document.querySelector("[data-preview-warning]");
    var fields = Array.prototype.slice.call(
      document.querySelectorAll("[data-preview-field]")
    );

    function value(id) {
      var element = document.getElementById(id);
      if (!element) {
        return "";
      }
      return element.type === "checkbox" ? element.checked : element.value;
    }

    function fill(template, map) {
      return Object.keys(map).reduce(function (text, key) {
        return text.split(key).join(map[key]);
      }, template);
    }

    function sharedMap() {
      return {
        "{item_no}": String(vars.item_no || ""),
        "{list_no}": String(vars.item_no || ""),
        "{count}": String(vars.count || ""),
        "{context}": vars.context || "",
        "{coach}": vars.coach || "",
        "{student}": vars.student || "",
        "{collection}": vars.collection || "",
        "{item_label}": vars.item_label || "",
        "{date}": vars.date || "",
        "{n}": "",
        "{global_no}": "",
        "{sentence}": ""
      };
    }

    function render() {
      var header = value("fmt_header");
      var line = value("fmt_line");
      var footer = value("fmt_footer");
      var isBlock = value("fmt_codeblock");
      var language = String(value("fmt_codeblock_lang")).replace(/[^a-z0-9+-]/gi, "");

      var parts = [];
      if (header.trim() !== "") {
        parts.push(fill(header, sharedMap()));
      }
      lines.forEach(function (sentence, index) {
        var map = sharedMap();
        map["{n}"] = String(index + 1);
        map["{global_no}"] = String(index + 1);
        map["{sentence}"] = sentence;
        parts.push(fill(line, map));
      });
      if (footer.trim() !== "") {
        parts.push(fill(footer, sharedMap()));
      }

      var body = parts.join("\n");
      preview.textContent = isBlock ? "```" + language + "\n" + body + "\n```" : body;

      if (warning) {
        var markdown = /\*\*|__|~~|^\s*>|\*(?=\S)/m.test(header + line + footer);
        warning.classList.toggle("hidden", !(isBlock && markdown));
      }
    }

    fields.forEach(function (element) {
      element.addEventListener("input", render);
      element.addEventListener("change", render);
    });
  })();

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
