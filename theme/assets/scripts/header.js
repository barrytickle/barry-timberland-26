(() => {
	const openButton = document.querySelector('[data-component="mobile-menu-open"]');
	const closeButton = document.querySelector('[data-component="mobile-menu-close"]');
	const menu = document.querySelector('[data-component="mobile-menu"]');

	if (!openButton || !closeButton || !menu) {
		return;
	}

	const openMenu = () => {
		menu.classList.remove("hidden");
		openButton.setAttribute("aria-expanded", "true");
		document.body.classList.add("overflow-hidden");
		closeButton.focus();
	};

	const closeMenu = () => {
		menu.classList.add("hidden");
		openButton.setAttribute("aria-expanded", "false");
		document.body.classList.remove("overflow-hidden");
	};

	openButton.addEventListener("click", openMenu);
	closeButton.addEventListener("click", closeMenu);

	document.addEventListener("keydown", (e) => {
		if (e.key === "Escape") {
			closeMenu();
		}
	});

	window.matchMedia("(min-width: 1024px)").addEventListener("change", (e) => {
		if (e.matches) {
			closeMenu();
		}
	});
})();
