(() => {
	const button = document.querySelector("[data-back-to-top]");

	if (!button) {
		return;
	}

	let ticking = false;

	const updateVisibility = () => {
		button.classList.toggle("is-visible", window.scrollY > Math.min(480, window.innerHeight * 0.6));
		ticking = false;
	};

	window.addEventListener(
		"scroll",
		() => {
			if (!ticking) {
				window.requestAnimationFrame(updateVisibility);
				ticking = true;
			}
		},
		{ passive: true },
	);

	button.addEventListener("click", () => {
		window.scrollTo({
			top: 0,
			behavior: window.matchMedia("(prefers-reduced-motion: reduce)").matches ? "auto" : "smooth",
		});
	});

	updateVisibility();
})();
