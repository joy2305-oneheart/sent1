(function () {
	'use strict';

	const root = document.querySelector('[data-one-confirm]');
	if (!root) {
		window.OneConfirm = {
			ask: function (message) {
				return Promise.resolve(window.confirm(message));
			},
		};
		return;
	}

	const titleEl = root.querySelector('[data-one-confirm-title]');
	const messageEl = root.querySelector('[data-one-confirm-message]');
	const okBtn = root.querySelector('[data-one-confirm-ok]');
	const cancelBtns = root.querySelectorAll('[data-one-confirm-cancel]');

	let resolver = null;

	function close(result) {
		root.setAttribute('hidden', 'hidden');
		root.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('one-confirm-open');
		if (resolver) {
			const fn = resolver;
			resolver = null;
			fn(result);
		}
	}

	okBtn.addEventListener('click', function () {
		close(true);
	});

	cancelBtns.forEach(function (btn) {
		btn.addEventListener('click', function () {
			close(false);
		});
	});

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && !root.hasAttribute('hidden')) {
			close(false);
		}
	});

	window.OneConfirm = {
		ask: function (message, options) {
			const opts = options || {};
			if (titleEl) {
				titleEl.textContent = opts.title || '';
				titleEl.hidden = !opts.title;
			}
			if (messageEl) {
				messageEl.textContent = message || '';
			}
			if (okBtn) {
				okBtn.hidden = false;
				okBtn.textContent = opts.confirmLabel || 'Confirm';
				okBtn.classList.toggle('one-confirm__btn--danger', !!opts.danger);
				okBtn.classList.toggle('one-confirm__btn--primary', !opts.danger);
			}
			cancelBtns.forEach(function (btn) {
				if (btn.matches('[data-one-confirm-cancel].one-confirm__btn')) {
					btn.hidden = opts.hideCancel === true;
				}
			});
			root.removeAttribute('hidden');
			root.setAttribute('aria-hidden', 'false');
			document.body.classList.add('one-confirm-open');
			okBtn.focus();
			return new Promise(function (resolve) {
				resolver = resolve;
			});
		},
	};
})();
