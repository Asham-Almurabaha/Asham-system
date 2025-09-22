{{-- jQuery (مطلوب لـ DataTables) --}}
<script src="{{ asset('assets/js/jquery.js') }}"></script>

{{-- DataTables --}}
<script src="{{ asset('assets/vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/vendor/datatables/js/datatables.js') }}"></script>

{{-- Bootstrap 5 Bundle --}}
<script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

{{-- TinyMCE (اختياري) --}}
<script src="{{ asset('assets/vendor/tinymce/tinymce.min.js') }}"></script>

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
      const flashMessages = document.querySelectorAll('.flash-message-stack .alert');
      flashMessages.forEach(flashMessage => {
        const delayAttribute = flashMessage.getAttribute('data-dismiss-delay');
        let delay = 3000;

        if (delayAttribute !== null) {
          const parsedDelay = Number.parseInt(delayAttribute, 10);

          if (Number.isFinite(parsedDelay) && parsedDelay >= 0) {
            delay = parsedDelay;
          }
        }

        window.setTimeout(() => {
          if (!flashMessage.isConnected) {
            return;
          }

          const removeAlert = () => {
            flashMessage.remove();
          };

          if (flashMessage.classList.contains('fade')) {
            flashMessage.addEventListener('transitionend', removeAlert, { once: true });
            flashMessage.classList.remove('show');
          } else {
            removeAlert();
          }
        }, delay);
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

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('header-search');
    if (!container) {
      return;
    }

    const input = container.querySelector('[data-role="input"]');
    const resultsList = container.querySelector('[data-role="results"]');
    const statusEl = container.querySelector('[data-role="status"]');
    const endpoint = container.dataset.endpoint || '';
    const minLength = Number.parseInt(container.dataset.minLength || '2', 10) || 2;
    const messages = {
      empty: container.dataset.empty || '',
      minLength: container.dataset.minLengthMessage || '',
      loading: container.dataset.loading || '',
      noResults: container.dataset.noResults || '',
      error: container.dataset.error || '',
      open: container.dataset.openLabel || '',
    };
    const direction = (container.dataset.direction || container.getAttribute('dir') || document.documentElement.getAttribute('dir') || 'ltr').toLowerCase();
    const isRtl = direction === 'rtl';
    let activeController = null;
    const collapse = window.bootstrap?.Collapse
      ? window.bootstrap.Collapse.getOrCreateInstance(container, { toggle: false })
      : null;

    if (container.getAttribute('dir') !== direction) {
      container.setAttribute('dir', direction);
    }

    if (resultsList) {
      resultsList.setAttribute('dir', direction);
      resultsList.classList.toggle('text-end', isRtl);
      resultsList.classList.toggle('text-start', !isRtl);
    }

    if (statusEl) {
      statusEl.setAttribute('dir', direction);
      statusEl.classList.toggle('text-end', isRtl);
      statusEl.classList.toggle('text-start', !isRtl);
    }

    const clearResults = () => {
      if (!resultsList) {
        return;
      }
      resultsList.innerHTML = '';
      resultsList.hidden = true;
    };

    const setStatus = (message, options = {}) => {
      if (!statusEl) {
        return;
      }
      const text = message || '';
      statusEl.textContent = text;
      statusEl.classList.toggle('d-none', text.length === 0);
      container.classList.toggle('is-loading', Boolean(options.loading));
    };

    const buildResultItem = (item) => {
      const li = document.createElement('li');
      li.className = 'list-group-item header-search-result-item';

      const link = document.createElement('a');
      link.className = 'header-search-result-link d-flex align-items-start gap-2 text-reset text-decoration-none';
      link.href = item.url || '#';
      link.setAttribute('data-type', item.type || '');
      link.setAttribute('dir', direction);
      link.classList.add(isRtl ? 'text-end' : 'text-start');

      const iconWrapper = document.createElement('div');
      iconWrapper.className = 'header-search-result-icon flex-shrink-0';
      iconWrapper.innerHTML = `<i class="bi ${item.icon || 'bi-search'}"></i>`;

      const content = document.createElement('div');
      content.className = 'header-search-result-content flex-grow-1';
      content.setAttribute('dir', direction);

      const title = document.createElement('div');
      title.className = 'header-search-result-title fw-semibold';
      title.textContent = item.title || '';

      const meta = document.createElement('div');
      meta.className = 'header-search-result-meta';
      meta.setAttribute('dir', direction);

      if (item.type_label) {
        const badge = document.createElement('span');
        badge.className = 'badge rounded-pill bg-primary-subtle text-primary-emphasis header-search-result-badge';
        badge.textContent = item.type_label;
        meta.appendChild(badge);
      }

      if (item.subtitle) {
        const subtitle = document.createElement('small');
        subtitle.className = 'text-muted header-search-result-subtitle';
        subtitle.textContent = item.subtitle;
        meta.appendChild(subtitle);
      }

      if (messages.open) {
        const action = document.createElement('span');
        action.className = 'header-search-result-open text-muted';
        action.setAttribute('dir', direction);
        action.setAttribute('aria-label', messages.open);
        action.setAttribute('title', messages.open);
        action.style.marginInlineStart = 'auto';
        const arrowClass = isRtl ? 'bi-arrow-return-left' : 'bi-arrow-return-right';
        action.innerHTML = `<i class="bi ${arrowClass}" aria-hidden="true"></i><span class="visually-hidden">${messages.open}</span>`;
        meta.appendChild(action);
      }

      content.appendChild(title);
      if (meta.childElementCount > 0) {
        content.appendChild(meta);
      }

      link.appendChild(iconWrapper);
      link.appendChild(content);
      li.appendChild(link);

      return li;
    };

    const renderResults = (items, query) => {
      clearResults();

      if (!Array.isArray(items) || items.length === 0) {
        const template = messages.noResults || '';
        const message = template.includes(':query') ? template.replace(':query', query) : template;
        setStatus(message || messages.empty);
        return;
      }

      if (!resultsList) {
        return;
      }

      const fragment = document.createDocumentFragment();
      items.forEach((item) => {
        fragment.appendChild(buildResultItem(item));
      });

      resultsList.appendChild(fragment);
      resultsList.hidden = false;
      setStatus('');
    };

    const abortActive = () => {
      if (activeController) {
        activeController.abort();
        activeController = null;
      }
    };

    const fetchResults = (query) => {
      if (!endpoint) {
        return;
      }

      abortActive();

      const controller = new AbortController();
      activeController = controller;

      setStatus(messages.loading, { loading: true });

      fetch(`${endpoint}?q=${encodeURIComponent(query)}`, {
        headers: { 'Accept': 'application/json' },
        signal: controller.signal,
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error('Failed to fetch global search results.');
          }
          return response.json();
        })
        .then((data) => {
          if (controller !== activeController) {
            return;
          }

          const items = Array.isArray(data?.results) ? data.results : [];
          renderResults(items, query);
        })
        .catch((error) => {
          if (error.name === 'AbortError') {
            return;
          }
          console.error('Global search error:', error);
          setStatus(messages.error);
        })
        .finally(() => {
          if (controller === activeController) {
            activeController = null;
          }
          container.classList.remove('is-loading');
        });
    };

    const handleInput = (event) => {
      const value = event.target.value.trim();

      if (value.length === 0) {
        abortActive();
        clearResults();
        setStatus(messages.empty);
        return;
      }

      if (value.length < minLength) {
        abortActive();
        clearResults();
        setStatus(messages.minLength);
        return;
      }

      fetchResults(value);
    };

    input?.addEventListener('input', handleInput);

    input?.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        event.preventDefault();
        collapse?.hide();
        return;
      }

      if (event.key === 'Enter') {
        const first = resultsList?.querySelector('a.header-search-result-link');
        if (first) {
          event.preventDefault();
          first.click();
        }
        return;
      }

      if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        const items = Array.from(resultsList?.querySelectorAll('a.header-search-result-link') || []);
        if (!items.length) {
          return;
        }

        event.preventDefault();
        const direction = event.key === 'ArrowDown' ? 1 : -1;
        const currentIndex = items.findIndex((item) => item === document.activeElement);
        const nextIndex = currentIndex === -1
          ? (direction === 1 ? 0 : items.length - 1)
          : (currentIndex + direction + items.length) % items.length;
        items[nextIndex]?.focus();
      }
    });

    resultsList?.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        event.preventDefault();
        collapse?.hide();
        return;
      }

      if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        const items = Array.from(resultsList.querySelectorAll('a.header-search-result-link'));
        if (!items.length) {
          return;
        }

        event.preventDefault();
        const direction = event.key === 'ArrowDown' ? 1 : -1;
        const currentIndex = items.findIndex((item) => item === document.activeElement);
        const nextIndex = currentIndex === -1
          ? (direction === 1 ? 0 : items.length - 1)
          : (currentIndex + direction + items.length) % items.length;
        items[nextIndex]?.focus();
      }
    });

    resultsList?.addEventListener('click', (event) => {
      const link = event.target.closest('a.header-search-result-link');
      if (!link) {
        return;
      }

      collapse?.hide();
    });

    if (collapse) {
      container.addEventListener('shown.bs.collapse', () => {
        window.setTimeout(() => input?.focus(), 50);

        const value = input?.value.trim() || '';
        if (!value.length) {
          setStatus(messages.empty);
        } else if (value.length < minLength) {
          setStatus(messages.minLength);
        } else {
          fetchResults(value);
        }
      });

      container.addEventListener('hidden.bs.collapse', () => {
        abortActive();
        clearResults();
        setStatus(messages.empty);
        if (input) {
          input.value = '';
        }
      });
    } else {
      setStatus(messages.empty);
    }

    setStatus(messages.empty);
  });
</script>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (!window.bootstrap || !window.bootstrap.Dropdown) {
      return;
    }

    const dropdownToggles = document.querySelectorAll('.header-nav [data-bs-toggle="dropdown"]');

    dropdownToggles.forEach(function (toggle) {
      const getDropdown = function () {
        return window.bootstrap.Dropdown.getOrCreateInstance(toggle);
      };

      // Ensure the dropdown instance exists for pointer interaction
      getDropdown();

      toggle.addEventListener('keydown', function (event) {
        if (event.defaultPrevented) {
          return;
        }

        if (event.key === ' ' || event.key === 'Enter') {
          event.preventDefault();
          getDropdown().toggle();
        }
      });
    });
  });
</script>

@yield('js')
@stack('scripts')
