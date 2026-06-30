(function () {
	'use strict';

	const cfg = typeof onePwaConfig !== 'undefined' ? onePwaConfig : { swUrl: '' };

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

	function init() {
		registerServiceWorker();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
