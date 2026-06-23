(function () {
	'use strict';

	const cfg = typeof onePwaConfig !== 'undefined' ? onePwaConfig : { swUrl: '', i18n: {} };
	const DISMISS_KEY = 'one_pwa_install_dismissed';
	const BANNER_ID = 'one-pwa-install-banner';

	let deferredPrompt = null;
	let bannerEl = null;

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

	function showIOSHint() {
		window.alert(
			cfg.i18n.iosHint ||
				'Tap the Share button in Safari, then choose "Add to Home Screen".'
		);
	}

	async function runInstall() {
		if (deferredPrompt) {
			deferredPrompt.prompt();
			try {
				await deferredPrompt.userChoice;
			} catch (err) {
				/* ignore */
			}
			deferredPrompt = null;
			removeBanner();
			setDismissed();
			return;
		}

		if (isIOS()) {
			showIOSHint();
			return;
		}

		window.alert(
			cfg.i18n.manualHint ||
				'Open your browser menu and choose "Install app" or "Add to Home screen".'
		);
	}

	function bindBanner(el) {
		bannerEl = el;
		const dismissBtn = el.querySelector('[data-one-pwa-dismiss]');
		const installBtn = el.querySelector('[data-one-pwa-install]');

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
	}

	if ('serviceWorker' in navigator && cfg.swUrl) {
		window.addEventListener('load', function () {
			navigator.serviceWorker.register(cfg.swUrl, { scope: '/' }).catch(function () {
				/* noop */
			});
		});
	}

	window.addEventListener('beforeinstallprompt', function (e) {
		e.preventDefault();
		deferredPrompt = e;
		showInstallBanner();
	});

	window.addEventListener('appinstalled', function () {
		deferredPrompt = null;
		removeBanner();
		setDismissed();
	});

	function init() {
		if (isStandalone() || wasDismissed()) {
			removeBanner();
			return;
		}
		showInstallBanner();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	window.addEventListener('load', function () {
		window.setTimeout(showInstallBanner, 300);
	});
})();
