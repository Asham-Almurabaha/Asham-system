@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form         = document.getElementById('filtersForm');
    const catSelect    = document.getElementById('party_category');
    const investorWrap = document.getElementById('investorWrap');
    const investorSel  = document.getElementById('investor_id');
    const statusSel    = document.getElementById('status_id');
    const accountType  = document.getElementById('account_type');
    const fromDate     = document.getElementById('from');
    const toDate       = document.getElementById('to');

    if (!form) {
        return;
    }

    // Debounce
    let timer = null;
    function autosubmit() { clearTimeout(timer); timer = setTimeout(() => form.requestSubmit(), 300); }

    // إظهار/إخفاء المستثمر بدون تغيير الشبكة
    function syncInvestorVisibility() {
        const isInv = (catSelect && (catSelect.value === 'investors' || catSelect.value === ''));
        if (!investorWrap || !investorSel) return;
        investorWrap.classList.toggle('invisible', !isInv);
        investorSel.disabled = !isInv;
        if (!isInv) investorSel.value = '';
    }

    // فلترة الحالات حسب الفئة + إخفاء التحويل (type=3)
    const allStatusOptions = statusSel ? Array.from(statusSel.querySelectorAll('option[data-cat]')) : [];
    function filterStatusesByCategory() {
        if (!statusSel) return;
        const cat = catSelect ? catSelect.value : '';
        let keepSelected = false;

        allStatusOptions.forEach(op => {
            const isTransfer = (op.dataset.type === '3');
            const show = (cat === '' ? true : (op.dataset.cat === cat));
            if (isTransfer || !show) {
                op.disabled = true; op.hidden = true; if (op.selected) keepSelected = true;
            } else { op.disabled = false; op.hidden = false; }
        });

        if (keepSelected) statusSel.value = '';
    }

    // تفعيل Tooltips
    if (window.bootstrap && bootstrap.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el, {container: 'body'});
        });
    }

    // ربط الأحداث مع Auto-submit
    [catSelect, investorSel, statusSel, accountType].forEach(el => {
        el && el.addEventListener('change', () => {
            if (el === catSelect) { syncInvestorVisibility(); filterStatusesByCategory(); }
            autosubmit();
        });
    });
    [fromDate, toDate].forEach(el => {
        el && el.addEventListener('change', autosubmit);
        el && el.addEventListener('keyup', (e)=> { if (e.key === 'Enter') autosubmit(); });
    });

    // زر مسح
    const btnClear = document.getElementById('btnClear');
    if (btnClear) {
        btnClear.addEventListener('click', function (e) {
            e.preventDefault();
            [catSelect, investorSel, statusSel, accountType, fromDate, toDate].forEach(el => { if (el) el.value = ''; });
            form.requestSubmit();
        });
    }

    // init
    syncInvestorVisibility();
    filterStatusesByCategory();
});
</script>
@endpush
