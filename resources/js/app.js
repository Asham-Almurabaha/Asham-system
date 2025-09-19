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

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scheduleFlashMessageDismissal);
} else {
    scheduleFlashMessageDismissal();
}
