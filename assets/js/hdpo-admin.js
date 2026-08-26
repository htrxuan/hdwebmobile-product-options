(function () {
	'use strict';

	var groupsContainer = document.getElementById('hdpo-groups');
	var addGroupButton = document.getElementById('hdpo-add-group');
	var groupTemplate = document.getElementById('hdpo-group-template');
	var choiceTemplate = document.getElementById('hdpo-choice-template');

	if (!groupsContainer || !addGroupButton || !groupTemplate || !choiceTemplate) {
		return;
	}

	// Seeded high so new rows never collide with server-rendered indices (0, 1, 2, ...).
	// The actual numeric value has no meaning beyond "unique array key" -- PHP re-indexes
	// everything sequentially on save regardless of what keys are submitted.
	var counter = Date.now();

	function nextId() {
		counter += 1;
		return String(counter);
	}

	function toggleGroupFields(groupRow) {
		var type = groupRow.querySelector('.hdpo-type-select').value;
		var priceField = groupRow.querySelector('.hdpo-price-field');
		var choicesField = groupRow.querySelector('.hdpo-choices-field');

		if ('select' === type) {
			priceField.setAttribute('hidden', 'hidden');
			choicesField.removeAttribute('hidden');
		} else {
			priceField.removeAttribute('hidden');
			choicesField.setAttribute('hidden', 'hidden');
		}
	}

	addGroupButton.addEventListener('click', function () {
		var html = groupTemplate.innerHTML.split('__INDEX__').join(nextId());
		var wrapper = document.createElement('div');
		wrapper.innerHTML = html.trim();
		groupsContainer.appendChild(wrapper.firstElementChild);
	});

	groupsContainer.addEventListener('click', function (event) {
		if (event.target.classList.contains('hdpo-remove-group')) {
			event.preventDefault();
			var groupRow = event.target.closest('.hdpo-group-row');
			if (groupRow) {
				groupRow.remove();
			}
			return;
		}

		if (event.target.classList.contains('hdpo-remove-choice')) {
			event.preventDefault();
			var choiceRow = event.target.closest('.hdpo-choice-row');
			if (choiceRow) {
				choiceRow.remove();
			}
			return;
		}

		if (event.target.classList.contains('hdpo-add-choice')) {
			event.preventDefault();
			var groupRowEl = event.target.closest('.hdpo-group-row');
			var choicesEl = groupRowEl ? groupRowEl.querySelector('.hdpo-choices') : null;
			if (!choicesEl) {
				return;
			}
			var groupIndex = choicesEl.getAttribute('data-group-index');
			var html = choiceTemplate.innerHTML
				.split('__GROUP__').join(groupIndex)
				.split('__CINDEX__').join(nextId());
			var wrapper = document.createElement('div');
			wrapper.innerHTML = html.trim();
			choicesEl.appendChild(wrapper.firstElementChild);
		}
	});

	groupsContainer.addEventListener('change', function (event) {
		if (event.target.classList.contains('hdpo-type-select')) {
			var groupRow = event.target.closest('.hdpo-group-row');
			if (groupRow) {
				toggleGroupFields(groupRow);
			}
		}
	});
})();
