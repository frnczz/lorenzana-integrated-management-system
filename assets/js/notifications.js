/**
 * LORINIMS Notification System
 * Loading overlay (with action text below), success and error toasts.
 */
(function () {
    'use strict';

    function ensureContainer() {
        if (!document.getElementById('lorinims-loading-overlay')) {
            var overlay = document.createElement('div');
            overlay.id = 'lorinims-loading-overlay';
            overlay.className = 'lorinims-loading-overlay';
            overlay.innerHTML = '<div class="lorinims-loading-content">' +
                '<div class="lorinims-loading-spinner"></div>' +
                '<p class="lorinims-loading-message" id="lorinims-loading-message">Please wait...</p>' +
                '<p class="lorinims-loading-subtext" id="lorinims-loading-subtext"></p>' +
                '</div>';
            document.body.appendChild(overlay);
        }
        if (!document.getElementById('lorinims-toast-container')) {
            var container = document.createElement('div');
            container.id = 'lorinims-toast-container';
            container.className = 'lorinims-toast-container';
            document.body.appendChild(container);
        }
    }

    window.LorinimsNotify = {
        showLoading: function (message, subtext) {
            ensureContainer();
            var overlay = document.getElementById('lorinims-loading-overlay');
            var msgEl = document.getElementById('lorinims-loading-message');
            var subEl = document.getElementById('lorinims-loading-subtext');
            if (msgEl) msgEl.textContent = message || 'Please wait...';
            if (subEl) {
                subEl.textContent = subtext || '';
                subEl.style.display = subtext ? 'block' : 'none';
            }
            overlay.classList.add('active');
        },

        hideLoading: function () {
            var overlay = document.getElementById('lorinims-loading-overlay');
            if (overlay) overlay.classList.remove('active');
        },

        showSuccess: function (message, duration) {
            ensureContainer();
            duration = duration === undefined ? 5000 : duration;
            var container = document.getElementById('lorinims-toast-container');
            var toast = document.createElement('div');
            toast.className = 'lorinims-toast lorinims-toast-success';
            toast.innerHTML = '<span class="lorinims-toast-icon" aria-hidden="true">✓</span>' +
                '<span class="lorinims-toast-body">' + escapeHtml(message) + '</span>' +
                '<button type="button" class="lorinims-toast-close" aria-label="Close">&times;</button>';
            container.appendChild(toast);
            var close = function () {
                toast.classList.add('hiding');
                setTimeout(function () {
                    if (toast.parentNode) toast.parentNode.removeChild(toast);
                }, 250);
            };
            toast.querySelector('.lorinims-toast-close').addEventListener('click', close);
            if (duration > 0) setTimeout(close, duration);
        },

        showError: function (message, duration) {
            ensureContainer();
            duration = duration === undefined ? 7000 : duration;
            var container = document.getElementById('lorinims-toast-container');
            var toast = document.createElement('div');
            toast.className = 'lorinims-toast lorinims-toast-error';
            toast.innerHTML = '<span class="lorinims-toast-icon" aria-hidden="true">✕</span>' +
                '<span class="lorinims-toast-body">' + escapeHtml(message) + '</span>' +
                '<button type="button" class="lorinims-toast-close" aria-label="Close">&times;</button>';
            container.appendChild(toast);
            var close = function () {
                toast.classList.add('hiding');
                setTimeout(function () {
                    if (toast.parentNode) toast.parentNode.removeChild(toast);
                }, 250);
            };
            toast.querySelector('.lorinims-toast-close').addEventListener('click', close);
            if (duration > 0) setTimeout(close, duration);
        }
    };

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function getFormLoadingMessage(form) {
        var msg = form.getAttribute('data-loading-message');
        if (msg) return msg;
        var btn = form.querySelector('button[type="submit"], input[type="submit"]');
        if (btn) {
            var btnMsg = btn.getAttribute('data-loading-message');
            if (btnMsg) return btnMsg;
            var label = (btn.textContent || btn.value || '').trim();
            if (label) return label.replace(/^(\w)/, function (c) { return c.toUpperCase(); });
        }
        return 'Processing...';
    }

    function getFormLoadingSubtext(form) {
        return form.getAttribute('data-loading-subtext') || '';
    }

    function initFormIntercept() {
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (form.tagName !== 'FORM') return;
            var message = getFormLoadingMessage(form);
            var subtext = getFormLoadingSubtext(form);
            window.LorinimsNotify.showLoading(message, subtext || 'Do not close this window.');
            form.addEventListener('submit', function once() {
                form.removeEventListener('submit', once);
            }, { once: true });
        }, true);
    }

    function showPendingFromPage() {
        document.querySelectorAll('[data-notify][data-message]').forEach(function (el) {
            var type = el.getAttribute('data-notify');
            var message = el.getAttribute('data-message') || '';
            if (type === 'success') window.LorinimsNotify.showSuccess(message);
            else if (type === 'error') window.LorinimsNotify.showError(message);
            if (el.parentNode) el.parentNode.removeChild(el);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initFormIntercept();
            showPendingFromPage();
        });
    } else {
        initFormIntercept();
        showPendingFromPage();
    }
})();
