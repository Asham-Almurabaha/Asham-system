(function (window, document) {
    'use strict';

    function noop() {}

    function createGoodsManager(options) {
        if (!options || !options.wrapper || !options.template) {
            return null;
        }

        const section = options.section || null;
        const wrapper = options.wrapper;
        const template = options.template;
        const addButton = options.addButton || null;
        const minRows = Number.isFinite(options.minRows) ? Math.max(1, options.minRows) : 1;

        const isSectionActive = typeof options.isSectionActive === 'function'
            ? options.isSectionActive
            : () => true;

        const isSaleMode = typeof options.isSaleMode === 'function'
            ? options.isSaleMode
            : () => false;

        const fetchAvailability = typeof options.fetchAvailability === 'function'
            ? options.fetchAvailability
            : async () => ({ success: true, available: 0 });

        const prepareNewRow = typeof options.prepareNewRow === 'function'
            ? options.prepareNewRow
            : noop;

        function toggleSection() {
            if (!section) {
                return;
            }
            section.style.display = isSectionActive() ? '' : 'none';
        }

        function clampQuantity(input) {
            if (!input) {
                return;
            }

            const maxAttr = input.getAttribute('max');
            const max = maxAttr ? parseInt(maxAttr, 10) : Infinity;
            let value = parseInt(input.value || '0', 10);

            if (!Number.isFinite(value)) {
                value = 0;
            }

            value = Math.max(0, value);
            if (Number.isFinite(max) && value > max) {
                value = max;
            }

            input.value = value ? String(value) : '';
        }

        function setRowAvailability(row, payload) {
            const badge = row.querySelector('.js-available-badge');
            const qty = row.querySelector('.js-qty-input');

            if (!badge || !qty) {
                return;
            }

            if (!payload || payload.success !== true) {
                const msg = payload && payload.message ? payload.message : 'تعذّر جلب المتاح';
                badge.textContent = 'خطأ: ' + msg;
                badge.className = 'badge bg-danger text-white js-available-badge';
                qty.removeAttribute('max');
                return;
            }

            const raw = Number(payload.available ?? (payload.stock && payload.stock.available));
            const available = Number.isFinite(raw) ? Math.max(0, Math.floor(raw)) : 0;

            badge.textContent = 'المتاح: ' + available.toLocaleString('ar-EG');
            badge.className = 'badge bg-light text-dark js-available-badge';

            if (isSectionActive() && isSaleMode()) {
                qty.setAttribute('max', String(available));
            } else {
                qty.removeAttribute('max');
            }

            clampQuantity(qty);
        }

        async function reloadRow(row) {
            const select = row.querySelector('.js-product-select');
            if (!select) {
                return;
            }

            const badge = row.querySelector('.js-available-badge');
            if (badge) {
                badge.textContent = 'جاري التحميل...';
                badge.className = 'badge bg-secondary text-white js-available-badge';
            }

            const typeId = select.value || '';
            if (!typeId) {
                setRowAvailability(row, { success: true, available: 0 });
                validate();
                return;
            }

            try {
                const payload = await fetchAvailability(typeId);
                setRowAvailability(row, payload);
            } catch (error) {
                setRowAvailability(row, { success: false, message: error.message });
            } finally {
                validate();
            }
        }

        function bindRow(row) {
            if (!row) {
                return;
            }

            const select = row.querySelector('.js-product-select');
            const qty = row.querySelector('.js-qty-input');

            if (select) {
                select.addEventListener('change', () => reloadRow(row));
                if (select.value) {
                    reloadRow(row);
                } else {
                    setRowAvailability(row, { success: true, available: 0 });
                }
            }

            if (qty) {
                const handler = () => {
                    clampQuantity(qty);
                    validate();
                };
                qty.addEventListener('input', handler);
                qty.addEventListener('blur', handler);
            }
        }

        function bindExisting() {
            wrapper.querySelectorAll('.product-row').forEach(bindRow);
        }

        function refreshRows() {
            wrapper.querySelectorAll('.product-row').forEach(row => {
                const select = row.querySelector('.js-product-select');
                if (select && select.value) {
                    reloadRow(row);
                } else {
                    setRowAvailability(row, { success: true, available: 0 });
                }
            });
        }

        function validate() {
            const saleMode = isSectionActive() && isSaleMode();
            let ok = true;

            wrapper.querySelectorAll('.product-row').forEach(row => {
                const select = row.querySelector('.js-product-select');
                const qty = row.querySelector('.js-qty-input');
                if (!select || !qty || !select.value) {
                    return;
                }

                qty.classList.remove('is-invalid');
                qty.setCustomValidity('');

                if (!saleMode) {
                    return;
                }

                const maxAttr = qty.getAttribute('max');
                const max = maxAttr !== null ? parseInt(maxAttr, 10) : null;
                const value = parseInt(qty.value || '0', 10) || 0;
                const effectiveMax = (max === null || Number.isNaN(max)) ? 0 : max;

                if (value > effectiveMax) {
                    ok = false;
                    qty.classList.add('is-invalid');
                    qty.setCustomValidity('الكمية أكبر من المتاح في المخزون.');
                }
            });

            return ok;
        }

        function addRow() {
            if (!template) {
                return;
            }

            const fragment = template.content.cloneNode(true);
            const row = fragment.querySelector('.product-row');
            if (!row) {
                return;
            }

            prepareNewRow(row);
            wrapper.appendChild(fragment);

            const appended = wrapper.querySelector('.product-row:last-child');
            bindRow(appended);
            validate();
        }

        function handleRemove(event) {
            const target = event.target;
            if (!target.classList.contains('js-remove-product')) {
                return;
            }

            const row = target.closest('.product-row');
            if (!row) {
                return;
            }

            if (wrapper.querySelectorAll('.product-row').length <= minRows) {
                return;
            }

            row.remove();
            validate();
        }

        if (addButton) {
            addButton.addEventListener('click', addRow);
        }

        wrapper.addEventListener('click', handleRemove);

        return {
            toggleSection,
            bindExisting,
            refreshRows,
            validate,
            addRow,
            reloadRow,
            setRowAvailability
        };
    }

    window.LedgerGoods = {
        create: createGoodsManager
    };
})(window, document);
