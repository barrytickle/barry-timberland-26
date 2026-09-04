const CONSENT_KEY = "bt_cookie_consent_v1";
const ANALYTICS_CONSENT = "analytics";
const ESSENTIAL_CONSENT = "essential";
const HOTJAR_SITE_ID = 613347;

const getPreference = () => {
	try {
		return window.localStorage.getItem(CONSENT_KEY);
	} catch (error) {
		return null;
	}
};

const savePreference = (value) => {
	try {
		window.localStorage.setItem(CONSENT_KEY, value);
	} catch (error) {
		// If storage is unavailable, respect the choice for the current page only.
	}
};

const updateGoogleConsent = (granted) => {
	window.dataLayer = window.dataLayer || [];
	window.gtag = window.gtag || function () {
		window.dataLayer.push(arguments);
	};

	window.gtag("consent", "update", {
		analytics_storage: granted ? "granted" : "denied",
		ad_storage: "denied",
		ad_user_data: "denied",
		ad_personalization: "denied",
	});
};

const loadHotjar = () => {
	if (
		window.__btHotjarLoaded ||
		document.querySelector('script[src*="static.hotjar.com/c/hotjar-613347"]')
	) {
		return;
	}

	window.__btHotjarLoaded = true;

	(function (h, o, t, j, a, r) {
		h.hj = h.hj || function () {
			(h.hj.q = h.hj.q || []).push(arguments);
		};
		h._hjSettings = { hjid: HOTJAR_SITE_ID, hjsv: 6 };
		a = o.getElementsByTagName("head")[0];
		r = o.createElement("script");
		r.async = 1;
		r.src = t + h._hjSettings.hjid + j + h._hjSettings.hjsv;
		a.appendChild(r);
	})(window, document, "https://static.hotjar.com/c/hotjar-", ".js?sv=");
};

const expireCookie = (name, domain = "") => {
	const domainPart = domain ? `; domain=${domain}` : "";
	document.cookie = `${name}=; Max-Age=0; path=/${domainPart}; SameSite=Lax`;
};

const clearAnalyticsCookies = () => {
	const names = document.cookie
		.split(";")
		.map((cookie) => cookie.split("=")[0].trim())
		.filter(
			(name) =>
				name.startsWith("_hj") ||
				name === "_ga" ||
				name.startsWith("_ga_") ||
				name === "_gid" ||
				name.startsWith("_gat")
		);

	const rootDomain = window.location.hostname.replace(/^www\./, "");

	names.forEach((name) => {
		expireCookie(name);
		expireCookie(name, window.location.hostname);
		expireCookie(name, `.${rootDomain}`);
	});
};

const initCookieConsent = () => {
	const banner = document.querySelector("[data-cookie-banner]");
	const acceptButton = document.querySelector("[data-cookie-accept]");
	const rejectButton = document.querySelector("[data-cookie-reject]");
	const settingsButtons = document.querySelectorAll("[data-cookie-settings]");

	if (!banner || !acceptButton || !rejectButton) {
		return;
	}

	const showBanner = (focus = false) => {
		banner.classList.remove("hidden");

		if (focus) {
			window.setTimeout(() => rejectButton.focus(), 0);
		}
	};

	const hideBanner = () => {
		banner.classList.add("hidden");
	};

	const preference = getPreference();

	if (preference === ANALYTICS_CONSENT) {
		loadHotjar();
	} else if (preference !== ESSENTIAL_CONSENT) {
		showBanner();
	}

	acceptButton.addEventListener("click", () => {
		savePreference(ANALYTICS_CONSENT);
		updateGoogleConsent(true);
		loadHotjar();
		hideBanner();
	});

	rejectButton.addEventListener("click", () => {
		const hotjarWasActive =
			getPreference() === ANALYTICS_CONSENT || window.__btHotjarLoaded;

		savePreference(ESSENTIAL_CONSENT);
		updateGoogleConsent(false);
		clearAnalyticsCookies();
		hideBanner();

		if (hotjarWasActive) {
			window.location.reload();
		}
	});

	settingsButtons.forEach((button) => {
		button.addEventListener("click", () => showBanner(true));
	});
};

if (document.readyState === "loading") {
	document.addEventListener("DOMContentLoaded", initCookieConsent);
} else {
	initCookieConsent();
}
