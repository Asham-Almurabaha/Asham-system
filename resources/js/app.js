import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const scheduleFlashMessageDismissal = () => {
    if (typeof document === 'undefined') {
        return;
    }

    const flashAlerts = document.querySelectorAll('.flash-message-stack .alert');

    flashAlerts.forEach((alert) => {
        const delayAttribute = alert.getAttribute('data-dismiss-delay');
        let delay = 3000;

        if (delayAttribute !== null) {
            const parsedDelay = Number.parseInt(delayAttribute, 10);

            if (Number.isFinite(parsedDelay) && parsedDelay >= 0) {
                delay = parsedDelay;
            }
        }

        window.setTimeout(() => {
            if (!alert.isConnected) {
                return;
            }

            const removeAlert = () => {
                alert.remove();
            };

            if (alert.classList.contains('fade')) {
                alert.addEventListener('transitionend', removeAlert, { once: true });
                alert.classList.remove('show');
            } else {
                removeAlert();
            }
        }, delay);
    });
};

const initializeSearchableSelects = () => {
    if (typeof document === 'undefined') {
        return;
    }

    const wrappers = document.querySelectorAll('[data-searchable-select-wrapper]');

    const normalizeValue = (value) => value.trim().toLowerCase();

    wrappers.forEach((wrapper) => {
        const select = wrapper.querySelector('[data-searchable-select]');

        if (!select || select.dataset.searchableSelectInitialized === 'true') {
            return;
        }

        select.dataset.searchableSelectInitialized = 'true';

        const options = Array.from(select.options);
        const placeholder = select.dataset.searchablePlaceholder || '';
        const baseIdentifier = select.id || select.name || `searchable_select_${Math.random().toString(36).slice(2)}`;

        const comboboxWrapper = document.createElement('div');
        comboboxWrapper.classList.add('searchable-select', 'position-relative', 'dropdown');

        const input = document.createElement('input');
        input.type = 'text';
        input.autocomplete = 'off';
        input.classList.add('form-control', 'searchable-select__input');
        input.placeholder = placeholder;
        input.id = `${baseIdentifier}_combobox`;
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-expanded', 'false');
        input.setAttribute('aria-haspopup', 'listbox');
        input.setAttribute('aria-autocomplete', 'list');

        if (select.classList.contains('is-invalid')) {
            input.classList.add('is-invalid');
        }

        if (select.classList.contains('is-valid')) {
            input.classList.add('is-valid');
        }

        if (select.required) {
            input.setAttribute('aria-required', 'true');
        }

        if (select.disabled) {
            input.disabled = true;
        }

        const describedBy = select.getAttribute('aria-describedby');

        if (describedBy) {
            input.setAttribute('aria-describedby', describedBy);
        }

        if (select.id) {
            const associatedLabel = document.querySelector(`label[for="${select.id}"]`);

            if (associatedLabel) {
                associatedLabel.setAttribute('for', input.id);

                if (!associatedLabel.id) {
                    associatedLabel.id = `${baseIdentifier}_label`;
                }

                input.setAttribute('aria-labelledby', associatedLabel.id);
            }
        }

        const dropdown = document.createElement('div');
        const dropdownId = `${baseIdentifier}_listbox`;
        dropdown.classList.add('dropdown-menu', 'w-100', 'searchable-select__menu');
        dropdown.setAttribute('role', 'listbox');
        dropdown.id = dropdownId;
        input.setAttribute('aria-controls', dropdownId);

        const emptyState = document.createElement('div');
        emptyState.classList.add('dropdown-item', 'text-muted', 'searchable-select__empty-state', 'd-none');
        emptyState.textContent = 'لا توجد نتائج مطابقة';
        dropdown.appendChild(emptyState);

        const optionItems = options.map((option, index) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.classList.add('dropdown-item');
            item.dataset.value = option.value;
            item.dataset.index = String(index);
            item.textContent = option.text.trim();
            item.tabIndex = -1;
            item.id = `${baseIdentifier}_option_${index}`;
            item.disabled = option.disabled;
            dropdown.appendChild(item);

            return {
                element: item,
                option,
                normalizedText: normalizeValue(option.text),
            };
        });

        const updateInputValue = () => {
            const selectedOption = select.options[select.selectedIndex];

            if (selectedOption && selectedOption.value !== '') {
                input.value = selectedOption.text.trim();
            } else {
                input.value = '';
            }
        };

        const highlightSelection = () => {
            const currentValue = select.value;

            optionItems.forEach(({ element, option }) => {
                const isSelected = option.value === currentValue && currentValue !== '';

                element.classList.toggle('fw-semibold', isSelected);

                if (isSelected) {
                    element.setAttribute('aria-selected', 'true');
                } else {
                    element.removeAttribute('aria-selected');
                }
            });
        };

        const closeDropdown = () => {
            dropdown.classList.remove('show');
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
            optionItems.forEach(({ element }) => element.classList.remove('active'));
            highlightedIndex = -1;
        };

        let highlightedIndex = -1;

        const focusItemAt = (targetIndex) => {
            const visibleItems = optionItems.filter(({ element }) => !element.classList.contains('d-none') && !element.disabled);

            if (visibleItems.length === 0) {
                highlightedIndex = -1;
                return;
            }

            const clampedIndex = ((targetIndex % visibleItems.length) + visibleItems.length) % visibleItems.length;
            const { element } = visibleItems[clampedIndex];

            optionItems.forEach(({ element: el }) => {
                el.classList.toggle('active', el === element);
            });

            highlightedIndex = clampedIndex;
            element.focus();
            input.setAttribute('aria-activedescendant', element.id);
        };

        const openDropdown = (options = { focusSelected: false }) => {
            if (!dropdown.classList.contains('show')) {
                dropdown.classList.add('show');
            }

            input.setAttribute('aria-expanded', 'true');

            if (options.focusSelected) {
                const currentValue = select.value;
                const selectedItemIndex = optionItems.findIndex(({ option }) => option.value === currentValue);

                if (selectedItemIndex >= 0) {
                    focusItemAt(selectedItemIndex);
                }
            }
        };

        const applyFilter = () => {
            const query = normalizeValue(input.value);
            let hasVisible = false;

            optionItems.forEach(({ element, normalizedText }) => {
                const isMatch = query === '' || normalizedText.includes(query);
                element.classList.toggle('d-none', !isMatch);

                if (isMatch) {
                    hasVisible = true;
                }
            });

            emptyState.classList.toggle('d-none', hasVisible);

            if (dropdown.classList.contains('show')) {
                if (!hasVisible) {
                    highlightedIndex = -1;
                    input.removeAttribute('aria-activedescendant');
                    optionItems.forEach(({ element }) => element.classList.remove('active'));
                } else {
                    const nextIndex = highlightedIndex < 0 ? 0 : highlightedIndex;
                    focusItemAt(nextIndex);
                }
            }
        };

        const selectValue = (value) => {
            const matchingOption = options.find((option) => option.value === value);

            if (!matchingOption) {
                select.value = '';
            } else {
                select.value = matchingOption.value;
            }

            select.dispatchEvent(new Event('change', { bubbles: true }));
            updateInputValue();
            highlightSelection();
        };

        optionItems.forEach(({ element }) => {
            element.addEventListener('mousedown', (event) => {
                event.preventDefault();
            });

            element.addEventListener('click', () => {
                if (element.disabled) {
                    return;
                }

                selectValue(element.dataset.value);
                closeDropdown();
                input.focus();
            });
        });

        input.addEventListener('focus', () => {
            openDropdown({ focusSelected: true });
            applyFilter();
        });

        input.addEventListener('input', () => {
            openDropdown();
            applyFilter();
        });

        input.addEventListener('keydown', (event) => {
            switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                openDropdown();
                applyFilter();
                focusItemAt(highlightedIndex + 1);
                break;
            case 'ArrowUp':
                event.preventDefault();
                openDropdown();
                applyFilter();
                focusItemAt(highlightedIndex - 1);
                break;
            case 'Enter':
                if (dropdown.classList.contains('show')) {
                    event.preventDefault();
                    const visibleItems = optionItems.filter(({ element }) => !element.classList.contains('d-none') && !element.disabled);

                    if (visibleItems.length > 0) {
                        const { element } = visibleItems[Math.max(0, highlightedIndex)];
                        selectValue(element.dataset.value);
                    }

                    closeDropdown();
                }
                break;
            case 'Escape':
                event.preventDefault();
                closeDropdown();
                break;
            default:
                break;
            }
        });

        const handleBlur = () => {
            window.setTimeout(() => {
                if (!wrapper.contains(document.activeElement)) {
                    closeDropdown();
                    updateInputValue();
                    highlightSelection();
                }
            }, 150);
        };

        input.addEventListener('blur', handleBlur);
        optionItems.forEach(({ element }) => {
            element.addEventListener('blur', handleBlur);
        });

        select.addEventListener('change', () => {
            updateInputValue();
            highlightSelection();
        });

        const form = select.form;

        if (form) {
            form.addEventListener('reset', () => {
                window.setTimeout(() => {
                    updateInputValue();
                    highlightSelection();
                    applyFilter();
                });
            });
        }

        document.addEventListener('mousedown', (event) => {
            if (!wrapper.contains(event.target)) {
                closeDropdown();
            }
        });

        select.classList.add('d-none');
        select.setAttribute('aria-hidden', 'true');
        select.tabIndex = -1;

        comboboxWrapper.appendChild(input);
        comboboxWrapper.appendChild(dropdown);
        comboboxWrapper.appendChild(select);

        wrapper.appendChild(comboboxWrapper);

        updateInputValue();
        highlightSelection();
        applyFilter();
    });
};

const initializeAutocompleteInputs = () => {
    if (typeof document === 'undefined') {
        return;
    }

    const inputs = document.querySelectorAll('[data-autocomplete-input]');

    inputs.forEach((input) => {
        if (input.dataset.autocompleteInitialized === 'true') {
            return;
        }

        const hiddenInputId = input.dataset.autocompleteHiddenInput;
        const optionsListId = input.dataset.autocompleteOptions || input.getAttribute('list');
        const hiddenInput = hiddenInputId ? document.getElementById(hiddenInputId) : null;
        const datalist = optionsListId ? document.getElementById(optionsListId) : null;

        if (!hiddenInput || !datalist) {
            return;
        }

        input.dataset.autocompleteInitialized = 'true';

        const allowEmpty = input.dataset.autocompleteAllowEmpty === 'true';
        const options = Array.from(datalist.options);
        const normalizeValue = (value) => value.trim().toLowerCase();
        const baseIdentifier = input.id || input.name || `autocomplete_input_${Math.random().toString(36).slice(2)}`;

        const originalParent = input.parentElement;
        const wrapper = document.createElement('div');
        wrapper.classList.add('autocomplete-input', 'position-relative', 'dropdown');

        if (originalParent) {
            originalParent.insertBefore(wrapper, input);
        } else {
            document.body.appendChild(wrapper);
        }

        wrapper.appendChild(input);

        input.removeAttribute('list');
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-expanded', 'false');
        input.setAttribute('aria-haspopup', 'listbox');
        input.setAttribute('aria-autocomplete', 'list');

        if (!input.id) {
            input.id = `${baseIdentifier}_input`;
        }

        const dropdown = document.createElement('div');
        const dropdownId = `${baseIdentifier}_listbox`;
        dropdown.classList.add('dropdown-menu', 'w-100', 'autocomplete-input__menu');
        dropdown.setAttribute('role', 'listbox');
        dropdown.id = dropdownId;
        input.setAttribute('aria-controls', dropdownId);

        const emptyState = document.createElement('div');
        emptyState.classList.add('dropdown-item', 'text-muted', 'autocomplete-input__empty-state', 'd-none');
        emptyState.textContent = 'لا توجد نتائج مطابقة';
        dropdown.appendChild(emptyState);

        let highlightedIndex = -1;

        const optionItems = options.map((option, index) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.classList.add('dropdown-item');
            item.dataset.index = String(index);
            const optionId = option.dataset.id ?? option.getAttribute('data-id') ?? '';
            item.dataset.id = optionId;
            item.dataset.value = option.value;
            item.id = `${baseIdentifier}_option_${index}`;
            item.textContent = option.value;
            item.tabIndex = -1;
            item.disabled = option.disabled;
            dropdown.appendChild(item);

            const optionItem = {
                element: item,
                option,
                normalizedValue: normalizeValue(option.value),
                id: optionId,
            };

            item.addEventListener('mousedown', (event) => {
                event.preventDefault();
            });

            item.addEventListener('click', () => {
                if (item.disabled) {
                    return;
                }

                selectOption(optionItem);
                closeDropdown();
                input.focus();
            });

            return optionItem;
        });

        const markInvalid = () => {
            if (!input.classList.contains('is-invalid')) {
                input.dataset.autocompleteInvalidAdded = 'true';
                input.classList.add('is-invalid');
            }
        };

        const clearInvalid = () => {
            if (input.dataset.autocompleteInvalidAdded === 'true') {
                input.classList.remove('is-invalid');
                delete input.dataset.autocompleteInvalidAdded;
            }
        };

        const highlightSelection = () => {
            const currentId = hiddenInput.value;

            optionItems.forEach(({ element, id }) => {
                const isSelected = currentId !== '' ? id === currentId : allowEmpty && id === '' && currentId === '';

                element.classList.toggle('fw-semibold', isSelected);

                if (isSelected) {
                    element.setAttribute('aria-selected', 'true');
                } else {
                    element.removeAttribute('aria-selected');
                }
            });
        };

        const closeDropdown = () => {
            dropdown.classList.remove('show');
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
            optionItems.forEach(({ element }) => element.classList.remove('active'));
            highlightedIndex = -1;
        };

        const focusItemAt = (targetIndex) => {
            const visibleItems = optionItems.filter(({ element }) => !element.classList.contains('d-none') && !element.disabled);

            if (visibleItems.length === 0) {
                highlightedIndex = -1;
                input.removeAttribute('aria-activedescendant');
                return;
            }

            const clampedIndex = ((targetIndex % visibleItems.length) + visibleItems.length) % visibleItems.length;
            const { element } = visibleItems[clampedIndex];

            optionItems.forEach(({ element: el }) => {
                el.classList.toggle('active', el === element);
            });

            element.focus();
            highlightedIndex = clampedIndex;
            input.setAttribute('aria-activedescendant', element.id);
        };

        const openDropdown = (options = { focusSelected: false }) => {
            if (!dropdown.classList.contains('show')) {
                dropdown.classList.add('show');
            }

            input.setAttribute('aria-expanded', 'true');

            if (options.focusSelected) {
                const currentId = hiddenInput.value;
                const selectedIndex = optionItems.findIndex(({ id }) => id === currentId);

                if (selectedIndex >= 0) {
                    focusItemAt(selectedIndex);
                }
            }
        };

        const applyFilter = () => {
            const query = normalizeValue(input.value);
            let hasVisible = false;

            optionItems.forEach(({ element, normalizedValue }) => {
                const isMatch = query === '' || normalizedValue.includes(query);
                element.classList.toggle('d-none', !isMatch);

                if (isMatch) {
                    hasVisible = true;
                }
            });

            emptyState.classList.toggle('d-none', hasVisible);

            if (dropdown.classList.contains('show')) {
                if (!hasVisible) {
                    highlightedIndex = -1;
                    input.removeAttribute('aria-activedescendant');
                    optionItems.forEach(({ element }) => element.classList.remove('active'));
                } else {
                    const nextIndex = highlightedIndex < 0 ? 0 : highlightedIndex;
                    focusItemAt(nextIndex);
                }
            }
        };

        const findMatchByValue = (value) => {
            const normalizedValue = normalizeValue(value);

            if (normalizedValue === '') {
                return undefined;
            }

            return optionItems.find((item) => item.normalizedValue === normalizedValue && (allowEmpty || item.id !== ''));
        };

        const findOptionById = (id) => optionItems.find((item) => item.id === id);

        const selectOption = (optionItem) => {
            input.value = optionItem.option.value;
            hiddenInput.value = optionItem.id;
            clearInvalid();
            highlightSelection();
        };

        const syncHiddenValue = () => {
            const match = findMatchByValue(input.value);

            if (match) {
                hiddenInput.value = match.id;
                clearInvalid();
            } else {
                hiddenInput.value = '';

                if (input.value.trim() === '') {
                    clearInvalid();
                } else {
                    markInvalid();
                }
            }

            highlightSelection();
        };

        input.addEventListener('focus', () => {
            openDropdown({ focusSelected: true });
            applyFilter();
        });

        input.addEventListener('input', () => {
            openDropdown();
            applyFilter();
            syncHiddenValue();
        });

        input.addEventListener('keydown', (event) => {
            switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                openDropdown();
                applyFilter();
                focusItemAt(highlightedIndex + 1);
                break;
            case 'ArrowUp':
                event.preventDefault();
                openDropdown();
                applyFilter();
                focusItemAt(highlightedIndex - 1);
                break;
            case 'Enter':
                if (dropdown.classList.contains('show')) {
                    event.preventDefault();
                    const visibleItems = optionItems.filter(({ element }) => !element.classList.contains('d-none') && !element.disabled);

                    if (visibleItems.length > 0) {
                        const { element } = visibleItems[Math.max(0, highlightedIndex)];
                        const optionItem = optionItems.find(({ element: el }) => el === element);

                        if (optionItem) {
                            selectOption(optionItem);
                        }
                    }

                    closeDropdown();
                }
                break;
            case 'Escape':
                event.preventDefault();
                closeDropdown();
                break;
            default:
                break;
            }
        });

        const handleBlur = () => {
            window.setTimeout(() => {
                if (!wrapper.contains(document.activeElement)) {
                    closeDropdown();
                    syncHiddenValue();
                }
            }, 150);
        };

        input.addEventListener('blur', handleBlur);
        optionItems.forEach(({ element }) => {
            element.addEventListener('blur', handleBlur);
        });

        const form = input.form;

        if (form) {
            form.addEventListener('reset', () => {
                window.setTimeout(() => {
                    if (hiddenInput.value) {
                        const matchingOption = findOptionById(hiddenInput.value);

                        if (matchingOption) {
                            input.value = matchingOption.option.value;
                        } else {
                            input.value = '';
                        }
                    } else {
                        input.value = '';
                    }

                    clearInvalid();
                    highlightSelection();
                    applyFilter();
                });
            });
        }

        document.addEventListener('mousedown', (event) => {
            if (!wrapper.contains(event.target)) {
                closeDropdown();
            }
        });

        wrapper.appendChild(dropdown);

        if (hiddenInput.value && !input.value) {
            const matchingOption = findOptionById(hiddenInput.value);

            if (matchingOption) {
                input.value = matchingOption.option.value;
            }
        }

        syncHiddenValue();
        applyFilter();
        highlightSelection();
    });
};

const initializeDocumentFeatures = () => {
    scheduleFlashMessageDismissal();
    initializeSearchableSelects();
    initializeAutocompleteInputs();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeDocumentFeatures);
} else {
    initializeDocumentFeatures();
}
