<?php
$isAdminViewer = (($_SESSION['role'] ?? '') === 'admin');
$adminViewOnlyMode = (int)($_SESSION['admin_view_only_mode'] ?? 0) === 1;
?>
<div style="
    text-align:center;
    padding:12px;
    background:#ffffff;
    border-top:1px solid #ddd;
    font-size:13px;
    color:#334155;">
    © 2026 Lorenzana Food Corporation - LORINIMS || Lot 6720 Brgy San Joaquin Sto Tomas Batangas
</div>

<link rel="stylesheet" href="assets/css/notifications.css">
<script src="assets/js/notifications.js" defer></script>
<script src="assets/js/sidebar.js" defer></script>

<?php if ($isAdminViewer && $adminViewOnlyMode): ?>
<script>
(function () {
    var role = <?php echo json_encode($_SESSION['role'] ?? ''); ?>;
    if (role !== 'admin') return;
    // Keep admin acceptance/rejection capability while disabling other write actions.
    var approvalPattern = /(approve|approved|accept|accepted|reject|rejected)/i;

    function textOf(el) {
        return ((el.textContent || '') + ' ' + (el.value || '') + ' ' + (el.getAttribute('aria-label') || '') + ' ' + (el.getAttribute('title') || '')).trim();
    }

    function isApprovalControl(el) {
        return approvalPattern.test(textOf(el));
    }

    function disableMutatingForms() {
        document.querySelectorAll('form').forEach(function (form) {
            var method = (form.getAttribute('method') || 'get').toLowerCase();
            var action = (form.getAttribute('action') || '').toLowerCase();
            var isMutatingForm = method === 'post' || action.indexOf('api/') >= 0;
            if (!isMutatingForm) return;

            var controls = Array.from(form.querySelectorAll('button,input[type="submit"],input[type="button"],a.btn'));
            var hasApproval = controls.some(isApprovalControl);

            if (!hasApproval) {
                form.style.display = 'none';
                return;
            }

            form.querySelectorAll('input,select,textarea').forEach(function (input) {
                if (input.type === 'hidden') return;
                input.disabled = true;
                input.style.background = '#f8fafc';
                input.style.color = '#64748b';
            });

            controls.forEach(function (ctrl) {
                if (hasApproval && isApprovalControl(ctrl)) return;
                ctrl.disabled = true;
                ctrl.style.opacity = '0.55';
                ctrl.style.cursor = 'not-allowed';
                ctrl.setAttribute('title', 'Admin is view-only for non-approval actions');
            });
        });
    }

    function disableMutationLinks() {
        document.querySelectorAll('a,button').forEach(function (el) {
            if (isApprovalControl(el)) return;
            var href = (el.getAttribute('href') || '').toLowerCase();
            var hasMutationUrl = href.indexOf('api/') >= 0 || href.indexOf('delete') >= 0 || href.indexOf('edit') >= 0 || href.indexOf('save') >= 0 || href.indexOf('auto_generate') >= 0;
            if (!hasMutationUrl) return;
            el.addEventListener('click', function (e) {
                e.preventDefault();
            });
            el.style.pointerEvents = 'none';
            el.style.opacity = '0.6';
            el.setAttribute('title', 'Admin is view-only for non-approval actions');
        });
    }

    function hideStandaloneInputs() {
        document.querySelectorAll('input,select,textarea,button').forEach(function (el) {
            if (el.closest('form')) return;
            if (isApprovalControl(el)) return;
            if (el.tagName === 'BUTTON' || el.type === 'button' || el.type === 'submit' || el.type === 'reset') {
                el.style.display = 'none';
            }
            if (el.tagName === 'INPUT' || el.tagName === 'SELECT' || el.tagName === 'TEXTAREA') {
                if ((el.type || '').toLowerCase() !== 'hidden') {
                    el.style.display = 'none';
                }
            }
        });
    }

    function addBadge() {
        if (document.querySelector('.admin-view-badge')) return;
        var badge = document.createElement('div');
        badge.className = 'admin-view-badge';
        badge.textContent = 'Admin view-only mode';
        document.body.appendChild(badge);
    }

    disableMutatingForms();
    disableMutationLinks();
    hideStandaloneInputs();
    addBadge();
})();
</script>
<?php endif; ?>
