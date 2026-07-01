/**
 * About page — banner upload/delete and journey editing.
 */
(function () {
	function initAboutEdit() {
		const cfg = typeof oneAboutEdit !== 'undefined' ? oneAboutEdit : null;
		if (!cfg || !cfg.ajaxUrl || !cfg.nonce) {
			return;
		}

		const hero = document.querySelector('[data-one-about-hero]');
		const bannerImg = document.querySelector('[data-one-about-banner-img]');
		const bannerInput = document.querySelector('[data-one-about-banner-input]');
		const bannerRemove = document.querySelector('[data-one-about-banner-remove]');
		const uploadLabel = document.querySelector('[data-one-about-upload-label]');
		const journeyCard = document.querySelector('[data-one-about-journey-card]');
		const journeyDisplay = document.querySelector('[data-one-about-journey-display]');
		const journeyForm = document.querySelector('[data-one-about-journey-form]');
		const journeyInput = document.querySelector('[data-one-about-journey-input]');
		const journeyCount = document.querySelector('[data-one-about-journey-count]');
		const journeyEdit = document.querySelector('[data-one-about-journey-edit]');
		const journeySave = document.querySelector('[data-one-about-journey-save]');
		const journeyCancel = document.querySelector('[data-one-about-journey-cancel]');

		if (!journeyCard && !bannerInput) {
			return;
		}

		let journeyOriginal = journeyInput ? journeyInput.value : '';

		let toastEl = null;
		let toastTimer = null;

		function showToast(message) {
			if (!message) {
				return;
			}
			if (!toastEl) {
				toastEl = document.createElement('div');
				toastEl.className = 'one-about-toast';
				toastEl.setAttribute('role', 'status');
				document.body.appendChild(toastEl);
			}
			toastEl.textContent = message;
			toastEl.classList.add('is-visible');
			clearTimeout(toastTimer);
			toastTimer = setTimeout(function () {
				toastEl.classList.remove('is-visible');
			}, 2600);
		}

		function parseJsonResponse(res) {
			return res.text().then(function (text) {
				let json;
				try {
					json = JSON.parse(text);
				} catch (err) {
					throw new Error(cfg.i18n.error);
				}
				if (!res.ok && json && json.data && json.data.message) {
					throw new Error(json.data.message);
				}
				return json;
			});
		}

		function updateJourneyPlaceholder() {
			if (!journeyDisplay || !journeyInput) {
				return;
			}
			const hasContent = journeyInput.value.trim() !== '';
			journeyDisplay.classList.toggle('is-placeholder', !hasContent);
		}

		function setJourneyEditing(editing) {
			if (!journeyCard || !journeyForm || !journeyDisplay) {
				return;
			}
			journeyCard.classList.toggle('is-editing', editing);
			if (editing) {
				journeyForm.removeAttribute('hidden');
			} else {
				journeyForm.setAttribute('hidden', '');
			}
			if (editing && journeyInput) {
				journeyInput.focus();
			}
		}

		function updateBannerUI(hasCustom) {
			if (bannerRemove) {
				if (hasCustom) {
					bannerRemove.removeAttribute('hidden');
				} else {
					bannerRemove.setAttribute('hidden', '');
				}
			}
			if (uploadLabel) {
				uploadLabel.textContent = hasCustom ? cfg.i18n.changeBanner : cfg.i18n.uploadBanner;
			}
		}

		function setUploadingState(isUploading) {
			if (hero) {
				hero.classList.toggle('is-uploading', isUploading);
			}
		}

		function saveAbout(options) {
			options = options || {};
			const form = new FormData();
			form.append('action', 'one1_update_about');
			form.append('nonce', cfg.nonce);

			if (options.journey !== undefined) {
				form.append('journey', options.journey);
			}
			if (options.removeBanner) {
				form.append('remove_banner', '1');
			}
			if (options.bannerFile) {
				form.append('banner', options.bannerFile);
			}

			if (journeySave) {
				journeySave.disabled = true;
			}
			setUploadingState(true);

			return fetch(cfg.ajaxUrl, { method: 'POST', body: form, credentials: 'same-origin' })
				.then(parseJsonResponse)
				.then(function (json) {
					if (!json.success) {
						throw new Error((json.data && json.data.message) || cfg.i18n.error);
					}
					return json.data;
				})
				.finally(function () {
					if (journeySave) {
						journeySave.disabled = false;
					}
					setUploadingState(false);
				});
		}

		if (journeyInput && journeyCount) {
			journeyInput.addEventListener('input', function () {
				journeyCount.textContent = String(journeyInput.value.length);
			});
		}

		if (journeyEdit) {
			journeyEdit.addEventListener('click', function () {
				setJourneyEditing(true);
			});
		}

		if (journeyCancel) {
			journeyCancel.addEventListener('click', function () {
				if (journeyInput) {
					journeyInput.value = journeyOriginal;
					if (journeyCount) {
						journeyCount.textContent = String(journeyInput.value.length);
					}
				}
				setJourneyEditing(false);
			});
		}

		if (journeySave) {
			journeySave.addEventListener('click', function () {
				const value = journeyInput ? journeyInput.value : '';
				saveAbout({ journey: value })
					.then(function (data) {
						if (journeyDisplay) {
							journeyDisplay.textContent = data.journey_display || data.journey || '';
							journeyDisplay.classList.toggle('is-placeholder', !(data.journey && data.journey.trim()));
						}
						if (journeyInput) {
							journeyInput.value = data.journey || '';
							if (journeyCount) {
								journeyCount.textContent = String(journeyInput.value.length);
							}
							journeyOriginal = journeyInput.value;
						}
						updateJourneyPlaceholder();
						setJourneyEditing(false);
						showToast(cfg.i18n.success);
					})
					.catch(function (err) {
						showToast(err.message || cfg.i18n.error);
					});
			});
		}

		if (bannerInput) {
			bannerInput.addEventListener('change', function () {
				const file = bannerInput.files && bannerInput.files[0];
				if (!file) {
					return;
				}

				const previewUrl = URL.createObjectURL(file);
				if (bannerImg) {
					bannerImg.src = previewUrl;
				}

				saveAbout({ bannerFile: file })
					.then(function (data) {
						if (bannerImg && data.banner_url) {
							bannerImg.src = data.banner_url;
						}
						updateBannerUI(!!data.has_custom_banner);
						showToast(cfg.i18n.bannerChanged);
					})
					.catch(function (err) {
						if (bannerImg) {
							bannerImg.src = cfg.defaultBanner;
						}
						showToast(err.message || cfg.i18n.error);
					})
					.finally(function () {
						bannerInput.value = '';
						URL.revokeObjectURL(previewUrl);
					});
			});
		}

		if (bannerRemove) {
			bannerRemove.addEventListener('click', function () {
				if (!window.confirm(cfg.i18n.confirmRemove)) {
					return;
				}

				saveAbout({ removeBanner: true })
					.then(function () {
						if (bannerImg) {
							bannerImg.src = cfg.defaultBanner;
						}
						updateBannerUI(false);
						showToast(cfg.i18n.bannerRemoved);
					})
					.catch(function (err) {
						showToast(err.message || cfg.i18n.error);
					});
			});
		}

		updateJourneyPlaceholder();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAboutEdit);
	} else {
		initAboutEdit();
	}
})();
