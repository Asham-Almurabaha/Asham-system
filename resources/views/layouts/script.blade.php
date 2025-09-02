{{-- jQuery (مطلوب لـ DataTables) --}}
<script src="{{ asset('assets/js/jquery.js') }}"></script>

{{-- DataTables --}}
<script src="{{ asset('assets/vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/vendor/datatables/js/datatables.js') }}"></script>

{{-- Bootstrap 5 Bundle --}}
<script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

{{-- TinyMCE (اختياري) --}}
{{-- <script src="{{ asset('assets/vendor/tinymce/tinymce.min.js') }}" defer></script> --}}

{{-- Template Main: enables sidebar toggle and UI helpers --}}
<script src="{{ asset('assets/js/main.js') }}"></script>

<script>
  (function () {
    'use strict';

    // CSRF لـ Ajax
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token && window.$) {
      $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': token }
      });
    }

    // Tooltips + إخفاء الفلاش
    document.addEventListener('DOMContentLoaded', function () {
      // تهيئة Tooltips
      const tooltips = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
      tooltips.forEach(el => {
        try {
          new bootstrap.Tooltip(el, { container: 'body' });
        } catch (e) {
          console.warn('Tooltip initialization failed:', e);
        }
      });

      // إخفاء رسائل الفلاش تلقائياً
      const flashMessages = document.querySelectorAll('[id^="flash-"]');
      flashMessages.forEach(flashMessage => {
        if (flashMessage) {
          setTimeout(() => {
            flashMessage.style.transition = 'opacity .5s ease';
            flashMessage.style.opacity = '0';
            setTimeout(() => {
              if (flashMessage.parentNode) {
                flashMessage.parentNode.removeChild(flashMessage);
              }
            }, 500);
          }, 5000);
        }
      });
    });
  })();
</script>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ar.js"></script>

<script>
  document.addEventListener('DOMContentLoaded', function () {
      const locale = "{{ app()->getLocale() }}";
      const isArabic = locale === 'ar';

      const baseOpts = {
          dateFormat: 'Y-m-d',
          allowInput: true,
          locale: isArabic ? 'ar' : 'default'
      };

      // دالة لتشغيل التاريخ على أي عنصر داخل السياق المحدد
      function initDatePickers(context = document) {
          context.querySelectorAll('.js-date').forEach(function(el) {
              // لو فيه picker قديم، نمسحه قبل ما نعمل جديد
              if (el._flatpickr) {
                  el._flatpickr.destroy();
              }
              flatpickr(el, baseOpts);

              // ضبط اتجاه النص لو عربي
              if (isArabic) {
                  el.setAttribute('dir', 'rtl');
                  el.style.textAlign = 'center';
              }
          });
      }

      // تشغيل على الصفحة كاملة
      initDatePickers();

      // إعادة التشغيل عند فتح أي مودال
      document.querySelectorAll('.modal').forEach(function(modal) {
          modal.addEventListener('shown.bs.modal', function () {
              initDatePickers(modal);
          });
      });
  });
</script>

@yield('js')
@stack('scripts')
