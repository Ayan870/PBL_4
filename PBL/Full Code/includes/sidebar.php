<?php
/**
 * Sidebar Include - PROVIA
 * Renders a dynamic sidebar based on the user's role.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['user_role'] ?? '';
$userName = $_SESSION['user_name'] ?? 'User';
$userIdentifier = $_SESSION['user_roll'] ?? $_SESSION['user_email'] ?? 'Unknown';
$initial = strtoupper(substr($userName, 0, 1));

// Define menu items for each role
$menuItems = [
    'student' => [
        ['label' => 'Dashboard', 'icon' => 'bi-grid-1x2-fill', 'url' => 'dashboard.php'],
        ['label' => 'Submit Proposal', 'icon' => 'bi-file-earmark-plus-fill', 'url' => 'submit-proposal.php'],
        ['label' => 'My Projects', 'icon' => 'bi-folder-fill', 'url' => 'my-projects.php'],
        ['label' => 'Feedback', 'icon' => 'bi-hand-thumbs-up-fill', 'url' => 'feedback.php'],
        ['label' => 'Results', 'icon' => 'bi-bar-chart-fill', 'url' => 'results.php'],
    ],
    'supervisor' => [
        ['label' => 'Dashboard', 'icon' => 'bi-grid-1x2-fill', 'url' => 'dashboard.php'],
        ['label' => 'Review Proposals', 'icon' => 'bi-file-earmark-check-fill', 'url' => 'review-proposals.php'],
        ['label' => 'My Students', 'icon' => 'bi-people-fill', 'url' => 'my-students.php'],
        ['label' => 'Mid Evaluation', 'icon' => 'bi-star-fill', 'url' => 'evaluation.php'],
        ['label' => 'Final Results', 'icon' => 'bi-bar-chart-fill', 'url' => 'reports.php'],
    ],
    'pbl_manager' => [
        ['label' => 'Dashboard', 'icon' => 'bi-grid-1x2-fill', 'url' => 'dashboard.php'],
        ['label' => 'Users', 'icon' => 'bi-people-fill', 'url' => 'users.php'],
        ['label' => 'All Proposals', 'icon' => 'bi-file-earmark-text-fill', 'url' => 'proposals.php'],
        ['label' => 'Supervisor Assignment', 'icon' => 'bi-person-badge-fill', 'url' => 'supervisor-assignment.php'],
        ['label' => 'Evaluations', 'icon' => 'bi-star-fill', 'url' => 'evaluations.php'],
        ['label' => 'Analytics', 'icon' => 'bi-bar-chart-fill', 'url' => 'analytics.php'],
    ],
    'evaluator' => [
        ['label' => 'Final Evaluation', 'icon' => 'bi-star-fill', 'url' => 'final-evaluation.php'],
    ]
];

$currentItems = $menuItems[$role] ?? [];
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar d-flex flex-column">
  <div class="p-4 border-bottom border-secondary border-opacity-10">
    <div class="d-flex align-items-center gap-3">
      <img src="../../assets/img/LOGO.png" alt="University Logo" style="height: 40px; width: auto; object-fit: contain;">
      <div>
        <span class="fw-bold text-white fs-6 d-block">PROVIA</span>
        <small class="text-secondary" style="font-size: 0.7rem;"><?php echo ucwords(str_replace('_', ' ', $role)); ?> Panel</small>
      </div>
    </div>
  </div>
  <nav class="flex-grow-1 py-4">
    <ul class="nav flex-column">
      <?php foreach ($currentItems as $item): 
          $isActive = ($currentPage === $item['url']) ? 'active' : '';
      ?>
        <li>
          <a class="nav-link <?php echo $isActive; ?>" href="<?php echo $item['url']; ?>">
            <i class="bi <?php echo $item['icon']; ?> me-3"></i><?php echo $item['label']; ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </nav>
  <div class="p-4 border-top border-secondary border-opacity-10">
    <div class="d-flex align-items-center gap-3 mb-3">
      <div class="rounded-circle text-white d-flex align-items-center justify-content-center shadow-sm" 
           style="width:40px;height:40px;background:#6366f1;font-weight:700;" id="userAvatar">
        <?php echo $initial; ?>
      </div>
      <div class="overflow-hidden">
        <div class="fw-semibold text-white small text-truncate" id="userName"><?php echo htmlspecialchars($userName); ?></div>
        <div class="text-secondary small text-truncate" style="font-size:0.7rem;" id="userIdentifier"><?php echo htmlspecialchars($userIdentifier); ?></div>
      </div>
    </div>
    <button class="btn btn-outline-danger btn-sm w-100 rounded-3" onclick="logout()">
      <i class="bi bi-box-arrow-right me-2"></i>Logout
    </button>
  </div>
</div>

