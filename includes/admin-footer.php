<?php
/**
 * Slim footer for the admin dashboard only — no marketing footer, no chat widget.
 * Still loads app.js: admin.js depends on its window.api / window.toast helpers.
 */
?>
<div class="toast" data-toast></div>

<script>
    window.CURRENT_USER = <?= json_encode($u ? ['id' => $u['id'], 'email' => $u['email'], 'role' => $u['role']] : null) ?>;
</script>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
