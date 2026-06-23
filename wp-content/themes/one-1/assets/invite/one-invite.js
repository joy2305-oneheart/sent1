(function () {
	const cfg = typeof oneInviteConfig !== 'undefined' ? oneInviteConfig : null;
	if (!cfg) {
		return;
	}

	const form = document.querySelector('[data-one-invite-form]');
	const feedback = document.querySelector('[data-one-invite-feedback]');
	const submitBtn = document.querySelector('[data-one-invite-submit]');
	const copyBtn = document.querySelector('[data-one-invite-copy]');
	const linkInput = document.getElementById('one-invite-link');

	if (form) {
		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			if (!submitBtn) {
				return;
			}
			const emailInput = form.querySelector('#one-invite-email');
			const email = emailInput ? emailInput.value.trim() : '';

			submitBtn.disabled = true;
			submitBtn.textContent = cfg.i18n.sending;
			if (feedback) {
				feedback.setAttribute('hidden', 'hidden');
			}

			const body = new FormData();
			body.append('action', 'one1_send_invite');
			body.append('nonce', cfg.nonce);
			body.append('email', email);

			try {
				const res = await fetch(cfg.ajaxUrl, { method: 'POST', body, credentials: 'same-origin' });
				const json = await res.json();
				if (feedback) {
					feedback.removeAttribute('hidden');
					feedback.classList.remove('is-success', 'is-error');
					if (json.success) {
						feedback.classList.add('is-success');
						feedback.textContent = json.data.message || cfg.i18n.send;
						if (emailInput) {
							emailInput.value = '';
						}
						window.setTimeout(() => window.location.reload(), 900);
					} else {
						feedback.classList.add('is-error');
						feedback.textContent = (json.data && json.data.message) || cfg.i18n.error;
					}
				}
			} catch (err) {
				if (feedback) {
					feedback.removeAttribute('hidden');
					feedback.classList.add('is-error');
					feedback.textContent = cfg.i18n.error;
				}
			} finally {
				submitBtn.disabled = false;
				submitBtn.textContent = cfg.i18n.send;
			}
		});
	}

	if (copyBtn && linkInput) {
		copyBtn.addEventListener('click', async () => {
			try {
				await navigator.clipboard.writeText(linkInput.value);
				const original = copyBtn.innerHTML;
				copyBtn.textContent = cfg.i18n.copied;
				setTimeout(() => {
					copyBtn.innerHTML = original;
				}, 2000);
			} catch (e) {
				linkInput.select();
				try {
					document.execCommand('copy');
				} catch (err) {
					window.alert(cfg.i18n.copyFail);
				}
			}
		});
	}

	const highlight = document.querySelector('.one-invite-inbox__item.is-highlighted');
	if (highlight) {
		highlight.scrollIntoView({ behavior: 'smooth', block: 'center' });
	}

	function closeSentMenus(except) {
		document.querySelectorAll('[data-one-sent-invite-menu-panel]').forEach((panel) => {
			if (except && panel === except) {
				return;
			}
			panel.setAttribute('hidden', 'hidden');
			const menu = panel.closest('[data-one-sent-invite-menu]');
			const trigger = menu && menu.querySelector('[data-one-sent-invite-menu-toggle]');
			if (trigger) {
				trigger.setAttribute('aria-expanded', 'false');
			}
		});
	}

	document.querySelectorAll('[data-one-sent-invite-menu-toggle]').forEach((trigger) => {
		trigger.addEventListener('click', (e) => {
			e.stopPropagation();
			const menu = trigger.closest('[data-one-sent-invite-menu]');
			const panel = menu && menu.querySelector('[data-one-sent-invite-menu-panel]');
			if (!panel) {
				return;
			}
			const open = panel.hasAttribute('hidden');
			closeSentMenus();
			if (open) {
				panel.removeAttribute('hidden');
				trigger.setAttribute('aria-expanded', 'true');
			}
		});
	});

	document.addEventListener('click', (e) => {
		if (!e.target.closest('[data-one-sent-invite-menu]')) {
			closeSentMenus();
		}
	});

	async function postInviteAction(action, invitationId) {
		const body = new FormData();
		body.append('action', action);
		body.append('nonce', cfg.sentNonce);
		body.append('invitation_id', String(invitationId));
		const res = await fetch(cfg.ajaxUrl, { method: 'POST', body, credentials: 'same-origin' });
		return res.json();
	}

	async function confirmAction(message, options) {
		if (window.OneConfirm && typeof window.OneConfirm.ask === 'function') {
			return window.OneConfirm.ask(message, options || {});
		}
		return window.confirm(message);
	}

	document.addEventListener('click', async (e) => {
		const resendBtn = e.target.closest('[data-one-sent-invite-resend]');
		if (resendBtn) {
			e.preventDefault();
			const invitationId = resendBtn.getAttribute('data-invitation-id');
			if (!invitationId) {
				return;
			}
			closeSentMenus();
			resendBtn.disabled = true;
			try {
				const json = await postInviteAction('one1_resend_invite', invitationId);
				if (json.success) {
					window.alert(json.data.message || cfg.i18n.resent);
				} else {
					window.alert((json.data && json.data.message) || cfg.i18n.error);
				}
			} catch (err) {
				window.alert(cfg.i18n.error);
			} finally {
				resendBtn.disabled = false;
			}
			return;
		}

		const removeBtn = e.target.closest('[data-one-sent-invite-remove]');
		if (!removeBtn) {
			return;
		}

		e.preventDefault();
		const invitationId = removeBtn.getAttribute('data-invitation-id');
		if (!invitationId) {
			return;
		}
		closeSentMenus();
		const ok = await confirmAction(cfg.i18n.removeConfirm, {
			title: cfg.i18n.removeTitle,
			confirmLabel: cfg.i18n.removeOk,
			danger: true,
		});
		if (!ok) {
			return;
		}
		removeBtn.disabled = true;
		try {
			const json = await postInviteAction('one1_remove_invite', invitationId);
			if (json.success) {
				const row = document.querySelector('[data-one-sent-invite-row="' + invitationId + '"]');
				if (row) {
					row.remove();
				} else {
					window.location.reload();
				}
			} else {
				window.alert((json.data && json.data.message) || cfg.i18n.error);
			}
		} catch (err) {
			window.alert(cfg.i18n.error);
		} finally {
			removeBtn.disabled = false;
		}
	});
})();
