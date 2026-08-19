(() => {
	const pointerQuery = window.matchMedia("(hover: hover) and (pointer: fine)");
	const motionQuery = window.matchMedia("(prefers-reduced-motion: reduce)");

	if (!pointerQuery.matches || motionQuery.matches) {
		return;
	}

	const cursor = document.createElement("div");
	cursor.className = "custom-cursor";
	cursor.setAttribute("aria-hidden", "true");
	document.body.append(cursor);
	document.documentElement.classList.add("has-custom-cursor");

	const interactiveSelector = [
		"a",
		"button",
		"summary",
		"select",
		"label[for]",
		"[role='button']",
		"[role='link']",
		"[role='switch']",
		"[tabindex]:not([tabindex='-1'])",
	].join(",");

	let currentX = window.innerWidth / 2;
	let currentY = window.innerHeight / 2;
	let targetX = currentX;
	let targetY = currentY;
	let frame = null;

	const render = () => {
		currentX += (targetX - currentX) * 0.22;
		currentY += (targetY - currentY) * 0.22;
		cursor.style.transform = `translate3d(${currentX}px, ${currentY}px, 0) translate(-50%, -50%)`;

		if (Math.abs(targetX - currentX) > 0.1 || Math.abs(targetY - currentY) > 0.1) {
			frame = window.requestAnimationFrame(render);
		} else {
			frame = null;
		}
	};

	const requestRender = () => {
		if (!frame) {
			frame = window.requestAnimationFrame(render);
		}
	};

	document.addEventListener("mousemove", (event) => {
		targetX = event.clientX;
		targetY = event.clientY;
		const isInteractive = event.target instanceof Element && event.target.closest(interactiveSelector);
		cursor.classList.toggle("is-interactive", Boolean(isInteractive));
		cursor.classList.add("is-visible");
		requestRender();
	});

	document.addEventListener("mousedown", () => cursor.classList.add("is-pressed"));
	document.addEventListener("mouseup", () => cursor.classList.remove("is-pressed"));
	document.addEventListener("mouseleave", () => cursor.classList.remove("is-visible"));
	document.addEventListener("mouseenter", () => cursor.classList.add("is-visible"));

	window.addEventListener("blur", () => {
		cursor.classList.remove("is-visible", "is-pressed");
	});
})();
