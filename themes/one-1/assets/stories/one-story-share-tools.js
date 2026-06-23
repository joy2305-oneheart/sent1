(function () {
	'use strict';

	const cfg = typeof oneStoryShareTools !== 'undefined' ? oneStoryShareTools : null;
	if (!cfg) {
		return;
	}

	const modal = document.querySelector('[data-one-share-link-modal]');
	const durationSelect = modal && modal.querySelector('[data-one-share-link-duration]');
	const generateBtn = modal && modal.querySelector('[data-one-share-link-generate]');
	const shareBtn = modal && modal.querySelector('[data-one-share-link-share]');
	const resultWrap = modal && modal.querySelector('[data-one-share-link-result]');
	const urlInput = modal && modal.querySelector('[data-one-share-link-url]');
	const expiryEl = modal && modal.querySelector('[data-one-share-link-expiry]');
	const feedbackEl = modal && modal.querySelector('[data-one-share-link-feedback]');
	const copyBtn = modal && modal.querySelector('[data-one-share-link-copy]');

	let activePostId = '';

	function ensureOnBody(el) {
		if (el && el.parentNode !== document.body) {
			document.body.appendChild(el);
		}
	}

	function post(action, data) {
		const form = new FormData();
		form.append('action', action);
		Object.keys(data).forEach((key) => form.append(key, data[key]));
		return fetch(cfg.ajaxUrl, { method: 'POST', body: form, credentials: 'same-origin' }).then((res) => res.json());
	}

	function setFeedback(message, isError) {
		if (!feedbackEl) {
			return;
		}
		feedbackEl.textContent = message || '';
		feedbackEl.hidden = !message;
		feedbackEl.classList.toggle('is-error', !!isError);
	}

	function closeOwnerMenus() {
		document.querySelectorAll('[data-one-story-owner-menu-panel]').forEach((panel) => {
			panel.setAttribute('hidden', 'hidden');
			panel.classList.remove('is-fixed');
			panel.style.top = '';
			panel.style.bottom = '';
			panel.style.left = '';
			panel.style.right = '';
		});
		document.querySelectorAll('[data-one-story-owner-menu-toggle]').forEach((trigger) => {
			trigger.setAttribute('aria-expanded', 'false');
		});
	}

	function closeModal() {
		if (!modal) {
			return;
		}
		modal.setAttribute('hidden', 'hidden');
		modal.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('one-share-link-modal-open');
		activePostId = '';
	}

	function openModal(postId) {
		if (!modal) {
			return;
		}
		ensureOnBody(modal);
		closeOwnerMenus();
		activePostId = String(postId);
		if (resultWrap) {
			resultWrap.hidden = true;
		}
		if (urlInput) {
			urlInput.value = '';
		}
		if (expiryEl) {
			expiryEl.textContent = '';
		}
		setFeedback('');
		if (generateBtn) {
			generateBtn.disabled = false;
			generateBtn.textContent = cfg.createLabel || 'Create link';
		}
		if (shareBtn) {
			shareBtn.disabled = false;
		}
		modal.removeAttribute('hidden');
		modal.setAttribute('aria-hidden', 'false');
		document.body.classList.add('one-share-link-modal-open');
		if (durationSelect) {
			durationSelect.focus();
		}
	}

	async function copyText(text) {
		if (navigator.clipboard && navigator.clipboard.writeText) {
			await navigator.clipboard.writeText(text);
			return;
		}
		const input = document.createElement('textarea');
		input.value = text;
		document.body.appendChild(input);
		input.select();
		document.execCommand('copy');
		document.body.removeChild(input);
	}

	function formatExpiry(expiresAt) {
		if (!expiresAt || !cfg.expiresLabel) {
			return '';
		}
		const ts = Date.parse(String(expiresAt).replace(' ', 'T') + 'Z');
		if (Number.isNaN(ts)) {
			return cfg.expiresLabel;
		}
		return cfg.expiresLabel.replace('%s', new Date(ts).toLocaleString());
	}

	function applyLinkResult(data) {
		if (urlInput) {
			urlInput.value = data.url || '';
		}
		if (expiryEl) {
			expiryEl.textContent = formatExpiry(data.expires_at);
		}
		if (resultWrap) {
			resultWrap.hidden = !data.url;
		}
	}

	async function createLink() {
		if (!activePostId || !generateBtn) {
			return false;
		}
		generateBtn.disabled = true;
		if (shareBtn) {
			shareBtn.disabled = true;
		}
		setFeedback('');
		try {
			const res = await post('one_create_story_share_link', {
				nonce: cfg.shareNonce,
				post_id: activePostId,
				expiry_seconds: durationSelect ? durationSelect.value : '',
			});
			if (!res.success) {
				setFeedback(res.data && res.data.message ? res.data.message : cfg.errorGeneric, true);
				return false;
			}
			applyLinkResult(res.data || {});
			if (res.data && res.data.url) {
				await copyText(res.data.url);
				setFeedback(cfg.copiedLabel || 'Link copied to clipboard.', false);
			}
			generateBtn.textContent = cfg.regenerateLabel || 'Create new link';
			return true;
		} catch (err) {
			setFeedback(cfg.errorGeneric, true);
			return false;
		} finally {
			generateBtn.disabled = false;
			if (shareBtn) {
				shareBtn.disabled = false;
			}
		}
	}

	async function shareLink() {
		if (!activePostId || !shareBtn) {
			return;
		}
		shareBtn.disabled = true;
		setFeedback('');

		try {
			if (!urlInput || !urlInput.value) {
				const created = await createLink();
				if (!created || !urlInput || !urlInput.value) {
					return;
				}
			}

			const url = urlInput.value;
			const title = cfg.shareTitle || 'Shared post';

			if (navigator.share) {
				try {
					await navigator.share({ title: title, url: url });
					setFeedback(cfg.sharedLabel || 'Shared successfully.', false);
					return;
				} catch (err) {
					if (err && err.name === 'AbortError') {
						return;
					}
				}
			}

			await copyText(url);
			setFeedback(cfg.copiedLabel || 'Link copied to clipboard.', false);
		} catch (err) {
			setFeedback(cfg.errorGeneric, true);
		} finally {
			shareBtn.disabled = false;
		}
	}

	if (modal) {
		modal.querySelectorAll('[data-one-share-link-modal-close]').forEach((el) => {
			el.addEventListener('click', closeModal);
		});
		if (generateBtn) {
			generateBtn.addEventListener('click', createLink);
		}
		if (shareBtn) {
			shareBtn.addEventListener('click', shareLink);
		}
		if (copyBtn && urlInput) {
			copyBtn.addEventListener('click', async () => {
				if (!urlInput.value) {
					return;
				}
				try {
					await copyText(urlInput.value);
					setFeedback(cfg.copiedLabel || 'Link copied to clipboard.', false);
				} catch (err) {
					setFeedback(cfg.errorGeneric, true);
				}
			});
		}
		document.addEventListener('keydown', (e) => {
			if (e.key === 'Escape' && modal && !modal.hasAttribute('hidden')) {
				closeModal();
			}
		});
	}

	document.addEventListener('click', (event) => {
		const publicShareBtn = event.target.closest('[data-one-story-public-share]');
		if (publicShareBtn) {
			event.preventDefault();
			event.stopPropagation();
			const postId = publicShareBtn.getAttribute('data-post-id');
			if (!postId) {
				return;
			}
			openModal(postId);
			return;
		}

		const blastBtn = event.target.closest('[data-one-story-notify-friends]');
		if (!blastBtn) {
			return;
		}

		event.preventDefault();
		const postId = blastBtn.getAttribute('data-post-id');
		if (!postId) {
			return;
		}

		const runBlast = async () => {
			blastBtn.disabled = true;
			try {
				const res = await post('one_story_send_blast', {
					nonce: cfg.blastNonce,
					post_id: postId,
					force: blastBtn.hasAttribute('data-force') ? '1' : '',
				});
				const message =
					res.success && res.data.message
						? res.data.message
						: res.data && res.data.message
							? res.data.message
							: cfg.errorGeneric;
				if (window.OneConfirm && typeof window.OneConfirm.ask === 'function') {
					await window.OneConfirm.ask(message, {
						title: res.success ? cfg.blastSentTitle || 'Done' : cfg.errorTitle || 'Error',
						confirmLabel: 'OK',
					});
				} else {
					window.alert(message);
				}
				if (res.success) {
					blastBtn.setAttribute('disabled', 'disabled');
				}
			} catch (err) {
				window.alert(cfg.errorGeneric);
			} finally {
				if (!blastBtn.hasAttribute('disabled')) {
					blastBtn.disabled = false;
				}
			}
		};

		if (window.OneConfirm && typeof window.OneConfirm.ask === 'function') {
			window.OneConfirm.ask(cfg.blastConfirm || 'Notify your friends about this post?', {
				title: cfg.blastConfirmTitle || 'Notify friends',
				confirmLabel: cfg.blastConfirmOk || 'Send',
			}).then((ok) => {
				if (ok) {
					runBlast();
				}
			});
		} else if (window.confirm(cfg.blastConfirm || 'Notify your friends about this post?')) {
			runBlast();
		}
	});
})();
