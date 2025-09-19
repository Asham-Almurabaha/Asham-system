import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const DEFAULT_AUTO_DISMISS_DELAY = 3000;

const scheduleFlashMessageDismissal = () => {
    if (typeof document === 'undefined') {
        return;
    }

    const flashAlerts = document.querySelectorAll('.flash-message-stack .alert');

    flashAlerts.forEach((alert) => {
        const delayAttribute = alert.getAttribute('data-dismiss-delay');
        const delay = delayAttribute ? Number.parseInt(delayAttribute, 10) : DEFAULT_AUTO_DISMISS_DELAY;

        if (!Number.isFinite(delay) || delay < 0) {
            return;
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

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scheduleFlashMessageDismissal);
} else {
    scheduleFlashMessageDismissal();
}
