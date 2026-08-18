import hljs from "highlight.js/lib/core";
import bash from "highlight.js/lib/languages/bash";
import css from "highlight.js/lib/languages/css";
import javascript from "highlight.js/lib/languages/javascript";
import json from "highlight.js/lib/languages/json";
import php from "highlight.js/lib/languages/php";
import scss from "highlight.js/lib/languages/scss";
import twig from "highlight.js/lib/languages/twig";
import typescript from "highlight.js/lib/languages/typescript";
import xml from "highlight.js/lib/languages/xml";

hljs.registerLanguage("bash", bash);
hljs.registerLanguage("css", css);
hljs.registerLanguage("javascript", javascript);
hljs.registerLanguage("json", json);
hljs.registerLanguage("php", php);
hljs.registerLanguage("scss", scss);
hljs.registerLanguage("twig", twig);
hljs.registerLanguage("typescript", typescript);
hljs.registerLanguage("xml", xml);

function highlightCodeBlocks() {
  document.querySelectorAll("pre code").forEach((code) => {
    if (!code.dataset.highlighted) {
      hljs.highlightElement(code);
    }
  });
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", highlightCodeBlocks);
} else {
  highlightCodeBlocks();
}
