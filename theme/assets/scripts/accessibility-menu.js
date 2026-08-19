(() => {
	const menu = document.querySelector("[data-accessibility-menu]");

	if (!menu) {
		return;
	}

	const trigger = menu.querySelector("[data-accessibility-trigger]");
	const panel = menu.querySelector("[data-accessibility-panel]");
	const readabilityToggle = menu.querySelector("[data-readability-toggle]");
	const darkThemeToggle = menu.querySelector("[data-dark-theme-toggle]");
	const storageKey = "barrytickle-display-mode";
	const legacyStorageKey = "barrytickle-readability-mode";

	if (!trigger || !panel || !readabilityToggle || !darkThemeToggle) {
		return;
	}

	const setDisplayMode = (mode) => {
		const readabilityEnabled = mode === "readability";
		const darkThemeEnabled = mode === "dark";

		document.documentElement.toggleAttribute("data-readability", readabilityEnabled);
		document.documentElement.toggleAttribute("data-dark-theme", darkThemeEnabled);
		readabilityToggle.setAttribute("aria-checked", String(readabilityEnabled));
		darkThemeToggle.setAttribute("aria-checked", String(darkThemeEnabled));

		try {
			window.localStorage.setItem(storageKey, mode);
			window.localStorage.removeItem(legacyStorageKey);
		} catch (error) {
			// The mode still works when storage is unavailable.
		}
	};

	const closePanel = ({ returnFocus = false } = {}) => {
		panel.hidden = true;
		trigger.setAttribute("aria-expanded", "false");
		trigger.setAttribute("aria-label", "Open accessibility options");

		if (returnFocus) {
			trigger.focus();
		}
	};

	let storedMode = "default";

	try {
		const savedMode = window.localStorage.getItem(storageKey);

		if (["default", "readability", "dark"].includes(savedMode)) {
			storedMode = savedMode;
		} else if (window.localStorage.getItem(legacyStorageKey) === "true") {
			storedMode = "readability";
		}
	} catch (error) {
		// Use the default when storage is unavailable.
	}

	setDisplayMode(storedMode);

	trigger.addEventListener("click", () => {
		const isOpen = trigger.getAttribute("aria-expanded") === "true";

		if (isOpen) {
			closePanel();
			return;
		}

		panel.hidden = false;
		trigger.setAttribute("aria-expanded", "true");
		trigger.setAttribute("aria-label", "Close accessibility options");
		readabilityToggle.focus();
	});

	readabilityToggle.addEventListener("click", () => {
		setDisplayMode(readabilityToggle.getAttribute("aria-checked") === "true" ? "default" : "readability");
	});

	darkThemeToggle.addEventListener("click", () => {
		setDisplayMode(darkThemeToggle.getAttribute("aria-checked") === "true" ? "default" : "dark");
	});

	document.addEventListener("click", (event) => {
		if (!menu.contains(event.target)) {
			closePanel();
		}
	});

	document.addEventListener("keydown", (event) => {
		if (event.key === "Escape" && !panel.hidden) {
			closePanel({ returnFocus: true });
		}
	});
})();
