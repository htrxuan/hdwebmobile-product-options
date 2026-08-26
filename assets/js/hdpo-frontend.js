(function () {
	'use strict';

	var container = document.querySelector('.hdpo-options');
	if (!container || typeof window.hdpoParams === 'undefined') {
		return;
	}

	var params = window.hdpoParams;
	// wp_localize_script() casts every scalar value to a string (see WP_Scripts::localize()),
	// so numeric params arrive here as strings and must be parsed before doing arithmetic.
	var basePrice = parseFloat(params.basePrice) || 0;
	var decimals = parseInt(params.decimals, 10) || 0;

	function formatMoney(amount) {
		var fixed = amount.toFixed(decimals);
		var parts = fixed.split('.');
		var intPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, params.thousandSep);
		var formatted = parts.length > 1 ? intPart + params.decimalSep + parts[1] : intPart;

		return params.priceFormat
			.replace('%1$s', params.pricePrefix)
			.replace('%2$s', formatted);
	}

	function groupContribution(group) {
		var checkbox = group.querySelector('input[type="checkbox"]');
		if (checkbox) {
			return checkbox.checked ? parseFloat(checkbox.getAttribute('data-hdpo-price')) || 0 : 0;
		}

		var textInput = group.querySelector('input[type="text"]');
		if (textInput) {
			return textInput.value.trim() !== '' ? parseFloat(textInput.getAttribute('data-hdpo-price')) || 0 : 0;
		}

		var select = group.querySelector('select');
		if (select && select.selectedIndex > -1) {
			var selectedOption = select.options[select.selectedIndex];
			if (selectedOption && selectedOption.value !== '') {
				return parseFloat(selectedOption.getAttribute('data-hdpo-price')) || 0;
			}
		}

		return 0;
	}

	function updatePreview() {
		var groups = container.querySelectorAll('.hdpo-option-group');
		var sum = 0;

		groups.forEach(function (group) {
			sum += groupContribution(group);
		});

		var preview = container.querySelector('.hdpo-price-preview');
		if (!preview) {
			preview = document.createElement('div');
			preview.className = 'hdpo-price-preview';
			container.appendChild(preview);
		}

		if (sum > 0) {
			preview.textContent = params.previewLabel + ' ' + formatMoney(basePrice + sum);
			preview.hidden = false;
		} else {
			preview.hidden = true;
		}
	}

	container.addEventListener('input', updatePreview);
	container.addEventListener('change', updatePreview);

	updatePreview();
})();
