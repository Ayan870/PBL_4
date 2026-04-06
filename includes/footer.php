<?php
// ===== Common HTML Footer =====
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $rootPath ?? '../../' ?>assets/js/app.js"></script>
<?php if (!empty($extraScripts)): foreach ($extraScripts as $s): ?>
<script src="<?= $rootPath ?? '../../' ?>assets/js/<?= $s ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
