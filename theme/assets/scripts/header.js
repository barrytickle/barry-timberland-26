(() => {
	const navbar = document.querySelector('[data-component="navbar"]');
	const navbarShell = document.querySelector('[data-component="navbar-shell"]');

	if (navbar && navbarShell) {
		let ticking = false;

		const measureNavbar = () => {
			navbarShell.style.height = `${navbar.offsetHeight}px`;
		};

		const updateNavbar = () => {
			navbar.classList.toggle("is-fixed", window.scrollY > navbarShell.offsetTop);
			ticking = false;
		};

		const requestNavbarUpdate = () => {
			if (!ticking) {
				window.requestAnimationFrame(updateNavbar);
				ticking = true;
			}
		};

		measureNavbar();
		updateNavbar();
		window.addEventListener("scroll", requestNavbarUpdate, { passive: true });
		window.addEventListener("resize", () => {
			measureNavbar();
			requestNavbarUpdate();
		});
	}

	const dropdowns = Array.from(document.querySelectorAll("[data-nav-dropdown]"));
	const hoverQuery = window.matchMedia("(hover: hover) and (pointer: fine)");

	const closeDropdown = (dropdown, returnFocus = false) => {
		const button = dropdown.querySelector("[data-nav-dropdown-button]");
		const panel = dropdown.querySelector("[data-nav-dropdown-menu]");
		if (!button || !panel) return;
		button.setAttribute("aria-expanded", "false");
		panel.classList.add("hidden");
		button.querySelector("[data-nav-chevron]")?.classList.remove("rotate-180");
		if (returnFocus) button.focus();
	};

	const openDropdown = (dropdown, focusFirst = false) => {
		dropdowns.forEach((other) => {
			if (other !== dropdown) closeDropdown(other);
		});
		const button = dropdown.querySelector("[data-nav-dropdown-button]");
		const panel = dropdown.querySelector("[data-nav-dropdown-menu]");
		if (!button || !panel) return;
		button.setAttribute("aria-expanded", "true");
		panel.classList.remove("hidden");
		button.querySelector("[data-nav-chevron]")?.classList.add("rotate-180");
		if (focusFirst) panel.querySelector("a")?.focus();
	};

	dropdowns.forEach((dropdown) => {
		const button = dropdown.querySelector("[data-nav-dropdown-button]");
		const panel = dropdown.querySelector("[data-nav-dropdown-menu]");
		let openedByHover = false;
		if (!button || !panel) return;

		button.addEventListener("click", (event) => {
			if (openedByHover || (button.getAttribute("aria-expanded") === "true" && hoverQuery.matches && event.detail > 0)) {
				openedByHover = false;
				return;
			}
			button.getAttribute("aria-expanded") === "true" ? closeDropdown(dropdown) : openDropdown(dropdown);
		});
		button.addEventListener("keydown", (event) => {
			if (event.key === "ArrowDown") {
				event.preventDefault();
				openDropdown(dropdown, true);
			} else if (event.key === "Escape" && button.getAttribute("aria-expanded") === "true") {
				event.preventDefault();
				closeDropdown(dropdown, true);
			}
		});
		panel.addEventListener("keydown", (event) => {
			if (event.key === "Escape") {
				event.preventDefault();
				closeDropdown(dropdown, true);
			}
		});
		dropdown.addEventListener("mouseenter", () => {
			if (hoverQuery.matches) {
				openedByHover = true;
				openDropdown(dropdown);
			}
		});
		dropdown.addEventListener("mouseleave", () => {
			if (hoverQuery.matches) {
				openedByHover = false;
				closeDropdown(dropdown);
			}
		});
		dropdown.addEventListener("focusout", (event) => {
			if (!dropdown.contains(event.relatedTarget)) closeDropdown(dropdown);
		});
	});

	document.addEventListener("click", (event) => {
		dropdowns.forEach((dropdown) => {
			if (!dropdown.contains(event.target)) closeDropdown(dropdown);
		});
	});

	const openButton = document.querySelector('[data-component="mobile-menu-open"]');
	const closeButton = document.querySelector('[data-component="mobile-menu-close"]');
	const menu = document.querySelector('[data-component="mobile-menu"]');
	const mobileSubmenus = Array.from(document.querySelectorAll("[data-mobile-submenu]"));

	if (!openButton || !closeButton || !menu) {
		return;
	}

	const openMenu = () => {
		menu.classList.remove("hidden");
		openButton.setAttribute("aria-expanded", "true");
		document.body.classList.add("overflow-hidden");
		closeButton.focus();
	};

	const closeMenu = (returnFocus = true) => {
		menu.classList.add("hidden");
		openButton.setAttribute("aria-expanded", "false");
		document.body.classList.remove("overflow-hidden");
		mobileSubmenus.forEach((submenu) => {
			const button = submenu.querySelector("[data-mobile-submenu-button]");
			const panel = submenu.querySelector("[data-mobile-submenu-panel]");
			button?.setAttribute("aria-expanded", "false");
			panel?.classList.add("hidden");
			button?.querySelector("[data-mobile-chevron]")?.classList.remove("rotate-180");
		});
		if (returnFocus) openButton.focus();
	};

	mobileSubmenus.forEach((submenu) => {
		const button = submenu.querySelector("[data-mobile-submenu-button]");
		const panel = submenu.querySelector("[data-mobile-submenu-panel]");
		if (!button || !panel) return;
		button.addEventListener("click", () => {
			const willOpen = button.getAttribute("aria-expanded") !== "true";
			button.setAttribute("aria-expanded", String(willOpen));
			panel.classList.toggle("hidden", !willOpen);
			button.querySelector("[data-mobile-chevron]")?.classList.toggle("rotate-180", willOpen);
		});
	});

	openButton.addEventListener("click", openMenu);
	closeButton.addEventListener("click", () => closeMenu());
	menu.querySelectorAll("a[href]").forEach((link) => link.addEventListener("click", () => closeMenu(false)));

	document.addEventListener("keydown", (e) => {
		if (e.key === "Escape" && !menu.classList.contains("hidden")) {
			closeMenu();
		}
		if (e.key === "Tab" && !menu.classList.contains("hidden")) {
			const focusable = Array.from(menu.querySelectorAll('a[href], button:not([disabled]), input:not([disabled])'));
			const first = focusable[0];
			const last = focusable[focusable.length - 1];
			if (e.shiftKey && document.activeElement === first) {
				e.preventDefault();
				last.focus();
			} else if (!e.shiftKey && document.activeElement === last) {
				e.preventDefault();
				first.focus();
			}
		}
	});

	window.matchMedia("(min-width: 1024px)").addEventListener("change", (e) => {
		if (e.matches) {
			closeMenu(false);
		}
	});
})();
