/**
 * Prevent Material Symbols ligature text flash before the icon font loads.
 */
(function () {
	'use strict';

	function markIconsReady() {
		document.documentElement.classList.add('one-icons-ready');
	}

	if (document.documentElement.classList.contains('one-icons-ready')) {
		return;
	}

	if (document.fonts && document.fonts.ready) {
		Promise.race([
			document.fonts.ready,
			new Promise(function (resolve) {
				setTimeout(resolve, 1800);
			}),
		]).then(markIconsReady).catch(markIconsReady);
		return;
	}

	markIconsReady();
})();
