/**
 * Connection disconnect and report actions for the theme drawer.
 */
(function () {
	'use strict';

	const cfg = typeof sinConnections !== 'undefined' ? sinConnections : null;

	function post(action, data) {
		const form = new FormData();
		form.append('action', action);
		form.append('nonce', cfg.nonce);
		Object.keys(data).forEach(function (key) {
			form.append(key, data[key]);
		});
		return fetch(cfg.ajaxUrl, { method: 'POST', body: form, credentials: 'same-origin' }).then(function (res) {
			return res.json();
		});
	}

	function updateDrawerCounts(data) {
		if (!data) {
			return;
		}
		const drawer = document.querySelector('[data-one-connections-drawer]');
		if (!drawer) {
			return;
		}
		const followersCount = drawer.querySelector('[data-one-connections-tab="followers"] .one-connections-tabs__count');
		const followingCount = drawer.querySelector('[data-one-connections-tab="following"] .one-connections-tabs__count');
		if (followersCount) {
			followersCount.textContent = String(data.followers);
		}
		if (followingCount) {
			followingCount.textContent = String(data.following);
		}
	function updateProfileStats(data) {
		if (!data) {
			return;
		}
		document.querySelectorAll('[data-one-open-connections-drawer][data-tab="followers"] strong').forEach(function (el) {
			el.textContent = String(data.followers);
		});
		document.querySelectorAll('[data-one-open-connections-drawer][data-tab="following"] strong').forEach(function (el) {
			el.textContent = String(data.following);
		});
	}

	function removeListItem(item) {
		const list = item.closest('.one-connections-list');
		const panel = item.closest('[data-one-connections-panel]');
		item.remove();
		if (list && !list.querySelector('.one-connections-list__item')) {
			const empty = document.createElement('p');
			empty.className = 'one-connections-list__empty';
			empty.textContent =
				panel && panel.getAttribute('data-one-connections-panel') === 'followers'
					? 'No followers yet.'
					: 'You are not following anyone yet.';
			list.replaceWith(empty);
		}
	}

	function closeReportDialog() {
		const dlg = document.querySelector('[data-sin-report-dialog]');
		if (dlg) {
			dlg.remove();
		}
	}

	function openReportDialog(targetId) {
		closeReportDialog();
		if (!cfg) {
			return;
		}

		const dlg = document.createElement('div');
		dlg.className = 'sin-report-dialog';
		dlg.setAttribute('data-sin-report-dialog', '1');
		dlg.innerHTML =
			'<div class="sin-report-dialog__backdrop" data-sin-report-close></div>' +
			'<div class="sin-report-dialog__panel" role="dialog" aria-modal="true">' +
			'<h3 class="sin-report-dialog__title">' +
			cfg.i18n.reportReason +
			'</h3>' +
			'<select class="sin-report-dialog__select" data-sin-report-reason required>' +
			Object.keys(cfg.reasons)
				.map(function (key) {
					return '<option value="' + key + '">' + cfg.reasons[key] + '</option>';
				})
				.join('') +
			'</select>' +
			'<textarea class="sin-report-dialog__details" data-sin-report-details rows="3" placeholder="' +
			cfg.i18n.reportDetails +
			'"></textarea>' +
			'<div class="sin-report-dialog__actions">' +
			'<button type="button" class="sin-report-dialog__btn sin-report-dialog__btn--ghost" data-sin-report-close>' +
			cfg.i18n.cancel +
			'</button>' +
			'<button type="button" class="sin-report-dialog__btn sin-report-dialog__btn--primary" data-sin-report-submit>' +
			cfg.i18n.submitReport +
			'</button>' +
			'</div>' +
			'</div>';

		document.body.appendChild(dlg);

		dlg.querySelectorAll('[data-sin-report-close]').forEach(function (el) {
			el.addEventListener('click', closeReportDialog);
		});

		dlg.querySelector('[data-sin-report-submit]').addEventListener('click', function () {
			const reason = dlg.querySelector('[data-sin-report-reason]').value;
			const details = dlg.querySelector('[data-sin-report-details]').value;
			post('sin_report_user', { target_id: targetId, reason: reason, details: details })
				.then(function (json) {
					if (json.success) {
						closeReportDialog();
						window.alert(cfg.i18n.reportSuccess);
					} else {
						window.alert((json.data && json.data.message) || cfg.i18n.error);
					}
				})
				.catch(function () {
					window.alert(cfg.i18n.error);
				});
		});
	}

	function handleDisconnect(btn) {
		if (!cfg || !window.confirm(cfg.i18n.disconnectConfirm)) {
			return;
		}
		const targetId = btn.getAttribute('data-sin-disconnect');
		const item = btn.closest('.one-connections-list__item');

		btn.disabled = true;
		post('sin_disconnect_user', { target_id: targetId })
			.then(function (json) {
				if (json.success) {
					if (item) {
						removeListItem(item);
					}
					document.querySelectorAll('.one-connections-list__item').forEach(function (row) {
						const disconnect = row.querySelector('[data-sin-disconnect="' + targetId + '"]');
						if (disconnect) {
							removeListItem(row);
						}
					});
					updateDrawerCounts(json.data);
				} else {
					window.alert((json.data && json.data.message) || cfg.i18n.error);
					btn.disabled = false;
				}
			})
			.catch(function () {
				window.alert(cfg.i18n.error);
				btn.disabled = false;
			});
	}

	function bindActions() {
		if (!cfg) {
			return;
		}

		document.addEventListener('click', function (e) {
			const disconnectBtn = e.target.closest('[data-sin-disconnect]');
			if (disconnectBtn) {
				e.preventDefault();
				handleDisconnect(disconnectBtn);
				return;
			}
			const reportBtn = e.target.closest('[data-sin-report]');
			if (reportBtn) {
				e.preventDefault();
				openReportDialog(reportBtn.getAttribute('data-sin-report'));
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', bindActions);
	} else {
		bindActions();
	}
})();
