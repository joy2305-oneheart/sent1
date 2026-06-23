/**
 * Following / Followers drawer and sidebar tabs.
 */
(function () {
	function openDrawer(tab) {
		const drawer = document.querySelector('[data-one-connections-drawer]');
		if (!drawer) {
			return;
		}
		drawer.removeAttribute('hidden');
		drawer.setAttribute('aria-hidden', 'false');
		document.body.classList.add('one-connections-open');
		if (tab) {
			activateDrawerTab(tab);
		}
	}

	function closeDrawer() {
		const drawer = document.querySelector('[data-one-connections-drawer]');
		if (!drawer) {
			return;
		}
		drawer.setAttribute('hidden', 'hidden');
		drawer.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('one-connections-open');
	}

	function activateDrawerTab(tab) {
		const drawer = document.querySelector('[data-one-connections-drawer]');
		if (!drawer) {
			return;
		}
		drawer.querySelectorAll('[data-one-connections-tab]').forEach((btn) => {
			const active = btn.getAttribute('data-one-connections-tab') === tab;
			btn.classList.toggle('is-active', active);
			btn.setAttribute('aria-selected', active ? 'true' : 'false');
		});
		drawer.querySelectorAll('[data-one-connections-panel]').forEach((panel) => {
			const active = panel.getAttribute('data-one-connections-panel') === tab;
			panel.classList.toggle('is-active', active);
			if (active) {
				panel.removeAttribute('hidden');
			} else {
				panel.setAttribute('hidden', 'hidden');
			}
		});
	}

	function bindDrawer() {
		document.querySelectorAll('[data-one-open-connections-drawer]').forEach((btn) => {
			btn.addEventListener('click', () => {
				openDrawer(btn.getAttribute('data-tab') || 'followers');
			});
		});

		document.querySelectorAll('[data-one-connections-drawer-close]').forEach((el) => {
			el.addEventListener('click', closeDrawer);
		});

		document.querySelectorAll('[data-one-connections-drawer] [data-one-connections-tab]').forEach((btn) => {
			btn.addEventListener('click', () => {
				activateDrawerTab(btn.getAttribute('data-one-connections-tab'));
			});
		});

		document.addEventListener('keydown', (e) => {
			if (e.key === 'Escape') {
				closeDrawer();
			}
		});
	}

	function bindSidebarTabs() {
		document.querySelectorAll('[data-one-share-connections]').forEach((widget) => {
			const tabs = widget.querySelectorAll('[data-one-share-connections-tab]');
			const panels = widget.querySelectorAll('[data-one-share-connections-panel]');
			tabs.forEach((tab) => {
				tab.addEventListener('click', () => {
					const key = tab.getAttribute('data-one-share-connections-tab');
					tabs.forEach((t) => t.classList.toggle('is-active', t === tab));
					panels.forEach((panel) => {
						const active = panel.getAttribute('data-one-share-connections-panel') === key;
						if (active) {
							panel.removeAttribute('hidden');
						} else {
							panel.setAttribute('hidden', 'hidden');
						}
					});
				});
			});
		});
	}

	function init() {
		bindDrawer();
		bindSidebarTabs();
		window.OneConnections = { open: openDrawer, close: closeDrawer };
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
