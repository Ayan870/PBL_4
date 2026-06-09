<?php
/**
 * Navbar Include - PROVIA
 * Contains the top header with title and notifications.
 */
$headerTitle = $headerTitle ?? 'Dashboard';
$headerSubtitle = $headerSubtitle ?? '';
$showNotifications = $showNotifications ?? false;
$extraButtons = $extraButtons ?? '';
?>
<div class="d-flex justify-content-between align-items-center mb-5">
  <div>
    <h3 class="mb-1 fw-bold text-white"><?php echo htmlspecialchars($headerTitle); ?></h3>
    <?php if ($headerSubtitle): ?>
      <p class="text-secondary mb-0"><?php echo htmlspecialchars($headerSubtitle); ?></p>
    <?php endif; ?>
  </div>
  <div class="d-flex align-items-center gap-3">
    <?php if ($showNotifications): ?>
      <!-- Notifications Dropdown -->
      <div class="dropdown">
        <button class="btn btn-outline-secondary rounded-pill p-2 border-0 position-relative" type="button" id="notifLink" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-bell-fill fs-5 text-secondary"></i>
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" id="notifBadge" style="font-size: 0.6rem; padding: 0.35em 0.65em;">0</span>
        </button>
        <div class="dropdown-menu dropdown-menu-dark dropdown-menu-end p-0 shadow-lg border-secondary mt-2" style="width: 320px; max-height: 400px; overflow-y: auto;" aria-labelledby="notifLink">
          <div class="p-3 border-bottom border-secondary d-flex justify-content-between align-items-center bg-dark">
            <span class="fw-bold small">Recent Notifications</span>
            <button class="btn btn-link btn-sm p-0 text-secondary text-decoration-none small" onclick="markAllRead()">Clear</button>
          </div>
          <div id="notifList" class="small">
            <div class="p-4 text-center text-secondary">No new notifications</div>
          </div>
        </div>
      </div>
    <?php endif; ?>
    
    <?php echo $extraButtons; ?>
  </div>
</div>

