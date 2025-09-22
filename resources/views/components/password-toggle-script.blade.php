<script>
(function () {
  'use strict';

  var TOGGLE_ATTR = 'data-password-toggle-target';

  function ready(callback) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', function handler() {
        document.removeEventListener('DOMContentLoaded', handler);
        callback();
      });
    } else {
      callback();
    }
  }

  function updateButtonState(button, input) {
    var isVisible = input.type === 'text';
    button.setAttribute('aria-pressed', isVisible ? 'true' : 'false');

    var showLabel = button.getAttribute('data-show-label') || button.getAttribute('aria-label') || '';
    var hideLabel = button.getAttribute('data-hide-label') || showLabel;
    var label = isVisible ? hideLabel : showLabel;

    if (label) {
      button.setAttribute('aria-label', label);
      button.setAttribute('title', label);
    }

    var icon = button.querySelector('i');
    if (icon) {
      icon.classList.toggle('bi-eye', !isVisible);
      icon.classList.toggle('bi-eye-slash', isVisible);
    }
  }

  function focusInput(input) {
    try {
      input.focus({ preventScroll: true });
      if (typeof input.setSelectionRange === 'function') {
        var end = input.value.length;
        input.setSelectionRange(end, end);
      }
    } catch (err) {
      // Ignore focus errors on unsupported browsers or disabled inputs.
    }
  }

  function toggleVisibility(button, input) {
    var shouldShow = input.type === 'password';

    try {
      input.type = shouldShow ? 'text' : 'password';
    } catch (err) {
      return;
    }

    updateButtonState(button, input);

    if (shouldShow) {
      focusInput(input);
    }
  }

  function resolveInput(button) {
    var targetId = button.getAttribute(TOGGLE_ATTR) || button.getAttribute('aria-controls') || '';
    if (targetId) {
      return document.getElementById(targetId);
    }

    var previous = button.previousElementSibling;
    if (previous && previous.tagName === 'INPUT') {
      return previous;
    }

    return null;
  }

  function initToggle(button) {
    if (button.__passwordToggleInitialised) {
      return;
    }

    var input = resolveInput(button);
    if (!input) {
      return;
    }

    var handleClick = function (event) {
      event.preventDefault();
      toggleVisibility(button, input);
    };

    button.addEventListener('click', handleClick);
    button.addEventListener('keydown', function (event) {
      if (event.key === ' ' || event.key === 'Enter') {
        handleClick(event);
      }
    });

    button.__passwordToggleInitialised = true;
    updateButtonState(button, input);
  }

  function initAll(context) {
    var scope = context || document;
    var buttons = scope.querySelectorAll('[' + TOGGLE_ATTR + ']');
    buttons.forEach ? buttons.forEach(initToggle) : Array.prototype.forEach.call(buttons, initToggle);
  }

  ready(function () {
    initAll();
  });

  window.Asham = window.Asham || {};
  window.Asham.initPasswordToggles = initAll;
})();
</script>
