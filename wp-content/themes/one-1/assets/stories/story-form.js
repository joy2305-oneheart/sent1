/**
 * Story form: post type pills, donation panel, file upload.
 */
function initOneStoryPostType() {
	document.querySelectorAll('[data-one-story-post-type]').forEach((group) => {
		const root = group.closest('[data-one-story-compose-fields]') || group.closest('form') || document;
		const hiddenDonation = group.querySelector('[data-one-story-is-donation-hidden]');
		const donationWrap = root.querySelector('[data-one-story-donation-wrap]');
		const donationSection = root.querySelector('[data-one-story-donation-section]');
		const panel = root.querySelector('[data-one-story-donation-panel]');
		const required = panel ? panel.querySelectorAll('[data-one-donation-required]') : [];

		const sync = () => {
			const selected = group.querySelector('[data-one-story-post-type-input]:checked');
			const isDonation = selected && selected.value === 'donation';

			group.querySelectorAll('.one-story-post-type__pill').forEach((pill) => {
				const input = pill.querySelector('input');
				pill.classList.toggle('is-active', input && input.checked);
			});

			if (hiddenDonation) {
				hiddenDonation.value = isDonation ? '1' : '0';
			}

			[donationWrap, donationSection].forEach((el) => {
				if (!el) {
					return;
				}
				if (isDonation) {
					el.removeAttribute('hidden');
				} else {
					el.setAttribute('hidden', 'hidden');
				}
			});

			required.forEach((el) => {
				if (isDonation) {
					el.setAttribute('required', 'required');
				} else {
					el.removeAttribute('required');
				}
			});
		};

		group.querySelectorAll('[data-one-story-post-type-input]').forEach((input) => {
			input.addEventListener('change', sync);
		});
		sync();
	});
}

function initOneStoryDonationToggle() {
	const toggles = document.querySelectorAll('[data-one-story-donation-toggle]');
	toggles.forEach((toggle) => {
		const root = toggle.closest('[data-one-story-fields]') || document;
		const panel = root.querySelector('[data-one-story-donation-panel]');
		if (!panel) {
			return;
		}
		const required = panel.querySelectorAll('[data-one-donation-required]');
		const sync = () => {
			const on = toggle.checked;
			if (on) {
				panel.removeAttribute('hidden');
			} else {
				panel.setAttribute('hidden', 'hidden');
			}
			required.forEach((el) => {
				if (on) {
					el.setAttribute('required', 'required');
				} else {
					el.removeAttribute('required');
				}
			});
		};
		toggle.addEventListener('change', sync);
		sync();
	});
}

function initOneStoryFileDrop() {
	const zones = document.querySelectorAll('[data-one-story-file-drop]');
	zones.forEach((zone) => {
		const input = zone.querySelector('[data-one-story-file-input]');
		const preview = zone.querySelector('[data-one-story-file-preview]');
		if (!input || !preview) {
			return;
		}

		const defaultHtml = preview.innerHTML;

		const render = (file) => {
			if (!file) {
				zone.classList.remove('has-file');
				preview.innerHTML = defaultHtml;
				return;
			}
			zone.classList.add('has-file');
			if (file.type.startsWith('image/')) {
				const url = URL.createObjectURL(file);
				preview.innerHTML =
					'<img class="one-story-file-drop__preview-img" src="' +
					url +
					'" alt="" /><span class="one-story-file-drop__filename">' +
					file.name +
					'</span>';
			} else {
				preview.innerHTML =
					'<span class="one-story-file-drop__filename">' + file.name + '</span>';
			}
		};

		input.addEventListener('change', () => {
			render(input.files && input.files[0] ? input.files[0] : null);
		});

		['dragenter', 'dragover'].forEach((ev) => {
			zone.addEventListener(ev, (e) => {
				e.preventDefault();
				zone.classList.add('is-dragover');
			});
		});
		['dragleave', 'drop'].forEach((ev) => {
			zone.addEventListener(ev, (e) => {
				e.preventDefault();
				zone.classList.remove('is-dragover');
			});
		});
		zone.addEventListener('drop', (e) => {
			const files = e.dataTransfer && e.dataTransfer.files;
			if (files && files.length) {
				input.files = files;
				render(files[0]);
			}
		});
	});
}

function initOneStoryPlacesAutocomplete() {
	const input = document.querySelector('[data-one-places-autocomplete]');
	if (!input || !window.google || !google.maps || !google.maps.places) {
		return;
	}
	const root = input.closest('form') || document;
	const placeIdField = root.querySelector('[data-one-location-place-id]');
	const cityField = root.querySelector('[data-one-location-city]');
	const regionField = root.querySelector('[data-one-location-region]');
	const autocomplete = new google.maps.places.Autocomplete(input, {
		types: ['(regions)'],
		fields: ['place_id', 'formatted_address', 'address_components', 'name'],
	});
	autocomplete.addListener('place_changed', () => {
		const place = autocomplete.getPlace();
		if (!place) {
			return;
		}
		input.value = place.formatted_address || place.name || input.value;
		if (placeIdField && place.place_id) {
			placeIdField.value = place.place_id;
		}
		let city = '';
		let region = '';
		(place.address_components || []).forEach((comp) => {
			if (comp.types.includes('locality')) {
				city = comp.long_name;
			}
			if (comp.types.includes('administrative_area_level_1')) {
				region = comp.long_name;
			}
			if (!city && comp.types.includes('postal_town')) {
				city = comp.long_name;
			}
		});
		if (cityField) {
			cityField.value = city;
		}
		if (regionField) {
			regionField.value = region;
		}
	});
}

function initOneStoryForm() {
	initOneStoryPostType();
	initOneStoryDonationToggle();
	initOneStoryFileDrop();
	initOneStoryPlacesAutocomplete();
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initOneStoryForm);
} else {
	initOneStoryForm();
}
