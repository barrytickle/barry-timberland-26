(() => {
	const menu = document.querySelector("[data-accessibility-menu]");

	if (!menu) {
		return;
	}

	const trigger = menu.querySelector("[data-accessibility-trigger]");
	const panel = menu.querySelector("[data-accessibility-panel]");
	const readabilityToggle = menu.querySelector("[data-readability-toggle]");
	const storageKey = "barrytickle-readability-mode";

	if (!trigger || !panel || !readabilityToggle) {
		return;
	}

	const setReadabilityMode = (enabled) => {
		document.documentElement.toggleAttribute("data-readability", enabled);
		readabilityToggle.setAttribute("aria-checked", String(enabled));

		try {
			window.localStorage.setItem(storageKey, enabled ? "true" : "false");
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

	let storedPreference = false;

	try {
		storedPreference = window.localStorage.getItem(storageKey) === "true";
	} catch (error) {
		// Use the default when storage is unavailable.
	}

	setReadabilityMode(storedPreference);

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
		setReadabilityMode(readabilityToggle.getAttribute("aria-checked") !== "true");
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
