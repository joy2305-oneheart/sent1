(function () {
	'use strict';

	const cfg = typeof onePwaConfig !== 'undefined' ? onePwaConfig : { swUrl: '', i18n: {} };
	const DISMISS_KEY = 'one_pwa_install_dismissed';
	const BANNER_ID = 'one-pwa-install-banner';
	const GUIDE_ID = 'one-pwa-install-guide';

	let deferredPrompt = window.__onePwaDeferredInstall || null;
	let bannerEl = null;
	let guideEl = null;
	let installBtn = null;

	function isMobileViewport() {
		return window.matchMedia('(max-width: 1023px)').matches;
	}

	function isCoarsePointer() {
		return window.matchMedia('(pointer: coarse)').matches;
	}

	function shouldOfferInstall() {
		return isMobileViewport() || isCoarsePointer();
	}

	function isStandalone() {
		return (
			document.documentElement.classList.contains('one-pwa-standalone') ||
			window.matchMedia('(display-mode: standalone)').matches ||
			window.navigator.standalone === true
		);
	}

	function isIOS() {
		return (
			/iPad|iPhone|iPod/.test(navigator.userAgent) ||
			(navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)
		);
	}

	function isAndroid() {
		return /Android/i.test(navigator.userAgent);
	}

	function wasDismissed() {
		try {
			return (
				document.documentElement.classList.contains('one-pwa-install-dismissed') ||
				sessionStorage.getItem(DISMISS_KEY) === '1'
			);
		} catch (err) {
			return false;
		}
	}

	function setDismissed() {
		try {
			sessionStorage.setItem(DISMISS_KEY, '1');
			document.documentElement.classList.add('one-pwa-install-dismissed');
		} catch (err) {
			/* ignore */
		}
	}

	function getDeferredPrompt() {
		return deferredPrompt || window.__onePwaDeferredInstall || null;
	}

	function setDeferredPrompt(event) {
		deferredPrompt = event;
		window.__onePwaDeferredInstall = event;
		updateInstallButton();
	}

	function canNativeInstall() {
		return !!getDeferredPrompt();
	}

	function setBannerVisible(visible) {
		if (!bannerEl) {
			return;
		}
		bannerEl.hidden = !visible;
		document.body.classList.toggle('has-pwa-install-banner', visible);
	}

	function removeBanner() {
		if (!bannerEl) {
			bannerEl = document.getElementById(BANNER_ID);
		}
		if (bannerEl) {
			bannerEl.hidden = true;
		}
		document.body.classList.remove('has-pwa-install-banner');
	}

	function updateInstallButton() {
		if (!installBtn) {
			return;
		}

		if (canNativeInstall()) {
			installBtn.textContent = cfg.i18n.install || 'Install';
			installBtn.classList.add('is-ready');
			installBtn.disabled = false;
			return;
		}

		installBtn.classList.remove('is-ready');
		installBtn.disabled = false;

		if (isIOS()) {
			installBtn.textContent = cfg.i18n.addToHome || 'Add to Home Screen';
			return;
		}

		installBtn.textContent = cfg.i18n.install || 'Install';
	}

	function openGuide(steps) {
		if (!guideEl) {
			guideEl = document.getElementById(GUIDE_ID);
		}
		if (!guideEl) {
			return;
		}

		const list = guideEl.querySelector('[data-one-pwa-guide-steps]');
		if (list) {
			list.innerHTML = '';
			(steps || []).forEach(function (step) {
				const li = document.createElement('li');
				li.textContent = step;
				list.appendChild(li);
			});
		}

		guideEl.hidden = false;
		guideEl.setAttribute('aria-hidden', 'false');
		document.body.classList.add('one-pwa-guide-open');
	}

	function closeGuide() {
		if (!guideEl) {
			guideEl = document.getElementById(GUIDE_ID);
		}
		if (!guideEl) {
			return;
		}
		guideEl.hidden = true;
		guideEl.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('one-pwa-guide-open');
	}

	function bindGuide() {
		guideEl = document.getElementById(GUIDE_ID);
		if (!guideEl) {
			return;
		}

		guideEl.querySelectorAll('[data-one-pwa-guide-close]').forEach(function (node) {
			node.addEventListener('click', closeGuide);
		});
	}

	async function runInstall() {
		const promptEvent = getDeferredPrompt();

		if (promptEvent) {
			installBtn.disabled = true;
			installBtn.textContent = cfg.i18n.installing || 'Installing…';

			try {
				await promptEvent.prompt();
				const choice = await promptEvent.userChoice;
				if (choice && choice.outcome === 'accepted') {
					setDismissed();
					removeBanner();
				}
			} catch (err) {
				/* ignore */
			}

			deferredPrompt = null;
			window.__onePwaDeferredInstall = null;
			updateInstallButton();
			return;
		}

		if (isIOS()) {
			openGuide(cfg.i18n.iosSteps || []);
			return;
		}

		openGuide(cfg.i18n.androidSteps || []);
	}

	function bindBanner(el) {
		bannerEl = el;
		installBtn = el.querySelector('[data-one-pwa-install]');
		const dismissBtn = el.querySelector('[data-one-pwa-dismiss]');

		updateInstallButton();

		if (dismissBtn) {
			dismissBtn.addEventListener('click', function () {
				setDismissed();
				removeBanner();
			});
		}

		if (installBtn) {
			installBtn.addEventListener('click', runInstall);
		}
	}

	function ensureBanner() {
		let el = document.getElementById(BANNER_ID);
		if (!el) {
			el = document.createElement('div');
			el.id = BANNER_ID;
			el.className = 'one-pwa-install';
			el.setAttribute('role', 'region');
			el.setAttribute('aria-label', cfg.i18n.installRegion || 'Install app');
			el.innerHTML =
				'<span class="material-symbols-outlined one-pwa-install__icon" aria-hidden="true">install_mobile</span>' +
				'<p class="one-pwa-install__text">' +
				(cfg.i18n.installLead || 'Install Sent One for quick access from your home screen.') +
				'</p>' +
				'<div class="one-pwa-install__actions">' +
				'<button type="button" class="one-pwa-install__install" data-one-pwa-install>' +
				(cfg.i18n.install || 'Install') +
				'</button>' +
				'<button type="button" class="one-pwa-install__dismiss" data-one-pwa-dismiss aria-label="' +
				(cfg.i18n.dismiss || 'Dismiss') +
				'">' +
				'<span class="material-symbols-outlined" aria-hidden="true">close</span>' +
				'</button>' +
				'</div>';
			document.body.prepend(el);
		}

		bindBanner(el);
		return el;
	}

	function showInstallBanner() {
		if (!shouldOfferInstall() || isStandalone() || wasDismissed()) {
			removeBanner();
			return;
		}

		ensureBanner();
		setBannerVisible(true);
		updateInstallButton();
	}

	async function registerServiceWorker() {
		if (!('serviceWorker' in navigator) || !cfg.swUrl) {
			return false;
		}

		try {
			const registration = await navigator.serviceWorker.register(cfg.swUrl, { scope: '/' });
			window.__onePwaSwReady = true;
			await registration.update().catch(function () {
				return null;
			});
			await navigator.serviceWorker.ready;
			return true;
		} catch (err) {
			return false;
		}
	}

	window.addEventListener('one-pwa-install-ready', function () {
		if (window.__onePwaDeferredInstall) {
			setDeferredPrompt(window.__onePwaDeferredInstall);
			showInstallBanner();
		}
	});

	window.addEventListener('beforeinstallprompt', function (e) {
		e.preventDefault();
		setDeferredPrompt(e);
		showInstallBanner();
	});

	window.addEventListener('appinstalled', function () {
		deferredPrompt = null;
		window.__onePwaDeferredInstall = null;
		removeBanner();
		setDismissed();
		closeGuide();
	});

	function init() {
		if (window.__onePwaDeferredInstall) {
			setDeferredPrompt(window.__onePwaDeferredInstall);
		}

		bindGuide();

		if (isStandalone() || wasDismissed()) {
			removeBanner();
			return;
		}

		registerServiceWorker().then(function () {
			showInstallBanner();
		});

		showInstallBanner();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
