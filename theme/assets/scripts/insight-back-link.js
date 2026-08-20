(() => {
	const backLink = document.querySelector("[data-insight-back-link]");
	const dialog = document.querySelector("[data-insight-back-dialog]");
	const mobileQuery = window.matchMedia("(max-width: 639px)");

	if (!backLink || !dialog || typeof dialog.showModal !== "function") {
		return;
	}

	backLink.addEventListener("click", (event) => {
		if (!mobileQuery.matches) {
			return;
		}

		event.preventDefault();
		dialog.showModal();
	});

	dialog.addEventListener("click", (event) => {
		if (event.target === dialog) {
			dialog.close();
		}
	});

	dialog.addEventListener("close", () => backLink.focus());
})();
