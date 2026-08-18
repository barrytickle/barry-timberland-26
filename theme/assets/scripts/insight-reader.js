const readerRoot = document.querySelector("[data-insight-reader]");

if (readerRoot) {
  const article = readerRoot.querySelector(".article-prose");
  const headings = article ? [...article.children].filter((element) => element.tagName === "H2") : [];

  if (article && headings.length) {
    const slugify = (value) =>
      value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9\s-]/g, "")
        .replace(/\s+/g, "-")
        .replace(/-+/g, "-") || "chapter";

    const usedSlugs = new Set();
    const uniqueSlug = (value) => {
      const base = slugify(value);
      let slug = base;
      let suffix = 2;

      while (usedSlugs.has(slug) || (document.getElementById(slug) && !headings.includes(document.getElementById(slug)))) {
        slug = `${base}-${suffix}`;
        suffix += 1;
      }

      usedSlugs.add(slug);
      return slug;
    };

    headings.forEach((heading) => {
      heading.id = uniqueSlug(heading.id || heading.textContent);
      heading.tabIndex = -1;
    });

    const chapters = [];
    let chapter = document.createElement("section");
    chapter.className = "insight-reader__chapter";
    chapter.dataset.chapterSlug = uniqueSlug("introduction");
    chapter.dataset.chapterTitle = "Introduction";

    [...article.childNodes].forEach((node) => {
      if (node.nodeType === Node.ELEMENT_NODE && node.tagName === "H2") {
        if (chapter.childNodes.length) {
          chapters.push(chapter);
        }

        chapter = document.createElement("section");
        chapter.className = "insight-reader__chapter";
        chapter.dataset.chapterSlug = node.id;
        chapter.dataset.chapterTitle = node.textContent.trim();
      }

      chapter.append(node);
    });

    if (chapter.childNodes.length) {
      chapters.push(chapter);
    }

    const intro = chapters[0];
    const introHasContent = intro && [...intro.childNodes].some(
      (node) => node.nodeType === Node.ELEMENT_NODE || node.textContent.trim()
    );

    if (intro && intro.dataset.chapterSlug === "introduction" && !introHasContent) {
      chapters.shift();
    }

    article.replaceChildren(...chapters);

    const controls = document.createElement("div");
    controls.className = "insight-reader__controls";
    controls.innerHTML = `
      <div class="insight-reader__modes" aria-label="Reading mode">
        <button type="button" data-reader-mode="chapter">Chapter view</button>
        <button type="button" data-reader-mode="full">Full article</button>
      </div>
      <details class="insight-reader__contents">
        <summary>Contents</summary>
        <ol></ol>
      </details>
      <p class="insight-reader__status" data-reader-status aria-live="polite"></p>
      <div class="insight-reader__progress" aria-hidden="true"><span></span></div>
    `;

    const footer = document.createElement("nav");
    footer.className = "insight-reader__navigation";
    footer.setAttribute("aria-label", "Chapter navigation");
    footer.innerHTML = `
      <button type="button" data-reader-previous>← Previous</button>
      <button type="button" data-reader-next>Next →</button>
    `;

    readerRoot.prepend(controls);
    readerRoot.append(footer);

    const contentsList = controls.querySelector("ol");
    chapters.forEach((item, index) => {
      const listItem = document.createElement("li");
      const link = document.createElement("a");
      link.href = `#${item.dataset.chapterSlug}`;
      link.dataset.chapterIndex = index;
      link.textContent = item.dataset.chapterTitle;
      listItem.append(link);
      contentsList.append(listItem);
    });

    const modeButtons = [...controls.querySelectorAll("[data-reader-mode]")];
    const previousButton = footer.querySelector("[data-reader-previous]");
    const nextButton = footer.querySelector("[data-reader-next]");
    const status = controls.querySelector("[data-reader-status]");
    const progress = controls.querySelector(".insight-reader__progress span");
    const contents = controls.querySelector(".insight-reader__contents");
    const contentsLinks = [...contentsList.querySelectorAll("a")];
    const storageKey = "insight-reader-mode";
    let currentIndex = 0;
    let mode = "chapter";

    try {
      mode = sessionStorage.getItem(storageKey) === "full" ? "full" : "chapter";
    } catch (error) {
      // Storage is optional; chapter mode remains fully usable without it.
    }

    const indexFromHash = () => {
      let slug = window.location.hash.slice(1);
      try {
        slug = decodeURIComponent(slug);
      } catch (error) {
        // Leave malformed hashes untouched rather than breaking the reader.
      }
      return chapters.findIndex((item) => item.dataset.chapterSlug === slug);
    };

    const scrollToReader = () => {
      const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
      readerRoot.scrollIntoView({ behavior: reduceMotion ? "auto" : "smooth", block: "start" });
    };

    const updateHash = (slug, replace = false) => {
      const url = `${window.location.pathname}${window.location.search}#${encodeURIComponent(slug)}`;
      window.history[replace ? "replaceState" : "pushState"]({ insightChapter: slug }, "", url);
    };

    const render = ({ focus = false, scroll = false } = {}) => {
      readerRoot.dataset.readerMode = mode;
      modeButtons.forEach((button) => {
        const active = button.dataset.readerMode === mode;
        button.setAttribute("aria-pressed", active.toString());
      });

      chapters.forEach((item, index) => {
        item.hidden = mode === "chapter" && index !== currentIndex;
      });

      contentsLinks.forEach((link, index) => {
        if (index === currentIndex) {
          link.setAttribute("aria-current", "location");
        } else {
          link.removeAttribute("aria-current");
        }
      });

      const chapterNumber = currentIndex + 1;
      status.textContent = `Chapter ${chapterNumber} of ${chapters.length}`;
      progress.style.width = `${(chapterNumber / chapters.length) * 100}%`;
      previousButton.hidden = mode !== "chapter" || currentIndex === 0;
      nextButton.hidden = mode !== "chapter" || currentIndex === chapters.length - 1;
      footer.hidden = mode !== "chapter";

      if (scroll) {
        scrollToReader();
      }

      if (focus) {
        const focusTarget = chapters[currentIndex].querySelector("h2") || controls;
        if (focusTarget === controls) {
          controls.tabIndex = -1;
        }
        window.requestAnimationFrame(() => focusTarget.focus({ preventScroll: true }));
      }
    };

    const goToChapter = (index, { history = true, focus = true, scroll = true } = {}) => {
      if (index < 0 || index >= chapters.length) return;
      currentIndex = index;
      mode = "chapter";
      if (history) updateHash(chapters[index].dataset.chapterSlug);
      contents.open = false;
      render({ focus, scroll });
    };

    previousButton.addEventListener("click", () => goToChapter(currentIndex - 1));
    nextButton.addEventListener("click", () => goToChapter(currentIndex + 1));

    contentsList.addEventListener("click", (event) => {
      const link = event.target.closest("a[data-chapter-index]");
      if (!link) return;
      event.preventDefault();
      const index = Number(link.dataset.chapterIndex);

      if (mode === "full") {
        currentIndex = index;
        updateHash(chapters[index].dataset.chapterSlug);
        contents.open = false;
        render();
        const heading = chapters[index].querySelector("h2") || chapters[index];
        heading.tabIndex = -1;
        heading.scrollIntoView({ block: "start" });
        heading.focus({ preventScroll: true });
      } else {
        goToChapter(index);
      }
    });

    modeButtons.forEach((button) => {
      button.addEventListener("click", () => {
        mode = button.dataset.readerMode;
        try {
          sessionStorage.setItem(storageKey, mode);
        } catch (error) {
          // Storage is optional.
        }
        render();
      });
    });

    const handleHistory = () => {
      const hashIndex = indexFromHash();
      if (hashIndex >= 0) {
        currentIndex = hashIndex;
        mode = "chapter";
        render({ focus: true, scroll: true });
      }
    };

    window.addEventListener("popstate", handleHistory);
    window.addEventListener("hashchange", handleHistory);

    const initialIndex = indexFromHash();
    if (initialIndex >= 0) {
      currentIndex = initialIndex;
      mode = "chapter";
    }
    if (!window.location.hash && mode === "chapter") {
      updateHash(chapters[currentIndex].dataset.chapterSlug, true);
    }

    readerRoot.classList.add("is-enhanced");
    render();
  }
}
