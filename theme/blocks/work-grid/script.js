(() => {
	const ACTIVE_CLASSES = ["border-ink-400", "bg-ink-200", "text-ink"];
	const INACTIVE_CLASSES = ["border-ink-200", "bg-white", "text-ink-600", "hover:border-ink-400"];

	document.querySelectorAll('[data-component="work-grid"]').forEach((grid) => {
		const filterButtons = grid.querySelectorAll('[data-component="work-grid-filters"] button');
		const workCards = grid.querySelectorAll('[data-component="work-card"]');

		filterButtons.forEach((button) => {
			button.addEventListener("click", () => {
				const filter = button.dataset.filter;

				filterButtons.forEach((btn) => {
					const isActive = btn === button;
					btn.classList.toggle("active", isActive);
					btn.classList.remove(...ACTIVE_CLASSES, ...INACTIVE_CLASSES);
					btn.classList.add(...(isActive ? ACTIVE_CLASSES : INACTIVE_CLASSES));
				});

				workCards.forEach((card) => {
					const matches = filter === "all" || card.dataset.filter === filter;
					card.classList.toggle("hidden", !matches);
				});
			});
		});
	});
})();
