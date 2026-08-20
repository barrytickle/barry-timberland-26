(() => {
	const browser = document.querySelector("[data-insights-browser]");

	if (!browser) {
		return;
	}

	const controls = browser.querySelector("[data-insights-controls]");
	const search = browser.querySelector("[data-insights-search]");
	const filterButtons = [...browser.querySelectorAll("[data-insights-filter]")];
	const cards = [...browser.querySelectorAll("[data-insight-card]")];
	const status = browser.querySelector("[data-insights-status]");
	const empty = browser.querySelector("[data-insights-empty]");
	const showMore = browser.querySelector("[data-insights-more]");
	const batchSize = 6;
	let activeCategory = "all";
	let visibleLimit = batchSize;

	if (!controls || !search || !cards.length || !status || !empty || !showMore) {
		return;
	}

	const matchesCard = (card) => {
		const matchesCategory = activeCategory === "all" || card.dataset.categories.split(" ").includes(activeCategory);
		const query = search.value.trim().toLocaleLowerCase();
		const matchesSearch = !query || card.textContent.toLocaleLowerCase().includes(query);

		return matchesCategory && matchesSearch;
	};

	const render = () => {
		const matches = cards.filter(matchesCard);

		cards.forEach((card) => {
			const matchIndex = matches.indexOf(card);
			card.hidden = matchIndex === -1 || matchIndex >= visibleLimit;
		});

		filterButtons.forEach((button) => {
			button.setAttribute("aria-pressed", String(button.dataset.insightsFilter === activeCategory));
		});

		const visibleCount = Math.min(matches.length, visibleLimit);
		status.textContent = matches.length === 1
			? "Showing 1 insight"
			: `Showing ${visibleCount} of ${matches.length} insights`;
		empty.hidden = matches.length !== 0;
		showMore.hidden = matches.length <= visibleLimit;
	};

	controls.hidden = false;
	status.hidden = false;

	search.addEventListener("input", () => {
		visibleLimit = batchSize;
		render();
	});

	filterButtons.forEach((button) => {
		button.addEventListener("click", () => {
			activeCategory = button.dataset.insightsFilter;
			visibleLimit = batchSize;
			render();
		});
	});

	showMore.addEventListener("click", () => {
		const previousLimit = visibleLimit;
		visibleLimit += batchSize;
		render();

		const revealedCards = cards.filter(matchesCard).slice(previousLimit, visibleLimit);
		const firstLink = revealedCards[0]?.querySelector("a");
		firstLink?.focus();
	});

	render();
})();
