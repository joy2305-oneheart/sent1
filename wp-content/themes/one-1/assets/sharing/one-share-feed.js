(function () {
	'use strict';

	var cfg = window.oneShareFeed || {};
	var ajaxUrl = cfg.ajaxUrl || '';
	var nonce = cfg.nonce || '';
	var i18n = cfg.i18n || {};

	function setSupportState(button, supported, count) {
		button.dataset.supported = supported ? '1' : '0';
		button.setAttribute('aria-pressed', supported ? 'true' : 'false');
		button.classList.toggle('is-active', supported);

		var countEl = button.querySelector('[data-one-support-count]');
		if (countEl) {
			countEl.textContent = String(count);
		}
	}

	function toggleSupport(button) {
		if (button.disabled || button.classList.contains('is-loading')) {
			return;
		}

		var postId = button.dataset.postId;
		if (!postId || !ajaxUrl) {
			return;
		}

		button.classList.add('is-loading');

		var body = new URLSearchParams();
		body.set('action', 'one_story_toggle_support');
		body.set('nonce', nonce);
		body.set('post_id', postId);

		fetch(ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
			},
			body: body.toString(),
		})
			.then(function (response) {
				return response.json();
			})
			.then(function (payload) {
				if (!payload || !payload.success) {
					throw new Error((payload && payload.data && payload.data.message) || i18n.error || 'Error');
				}
				setSupportState(button, !!payload.data.supported, payload.data.count);
			})
			.catch(function () {
				window.alert(i18n.error || 'Something went wrong. Please try again.');
			})
			.finally(function () {
				button.classList.remove('is-loading');
			});
	}

	function copyUpi(button) {
		var upi = button.dataset.oneCopyUpi;
		if (!upi) {
			return;
		}

		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(upi).then(function () {
				window.alert(i18n.upiCopied || 'UPI ID copied to clipboard.');
			});
			return;
		}

		var input = document.createElement('textarea');
		input.value = upi;
		input.setAttribute('readonly', '');
		input.style.position = 'absolute';
		input.style.left = '-9999px';
		document.body.appendChild(input);
		input.select();
		document.execCommand('copy');
		document.body.removeChild(input);
		window.alert(i18n.upiCopied || 'UPI ID copied to clipboard.');
	}

	document.addEventListener('click', function (event) {
		var supportBtn = event.target.closest('[data-one-story-support]');
		if (supportBtn) {
			event.preventDefault();
			toggleSupport(supportBtn);
			return;
		}

		var upiBtn = event.target.closest('[data-one-copy-upi]');
		if (upiBtn) {
			event.preventDefault();
			copyUpi(upiBtn);
		}
	});
})();
