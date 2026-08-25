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
