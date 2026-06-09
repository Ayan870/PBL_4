<?php
require_once "../../helpers/auth_check.php";

$role = $_SESSION['user_role'] ?? '';
$navItems = [];
$panelName = 'Message Center';

if ($role === 'student') {
  $panelName = 'Student Panel';
  $navItems = [
    ['../student/dashboard.php', 'bi-grid-1x2-fill', 'Dashboard'],
    ['../student/submit-proposal.php', 'bi-file-earmark-plus-fill', 'Submit Proposal'],
    ['../student/my-projects.php', 'bi-folder-fill', 'My Projects'],
    ['../student/feedback.php', 'bi-hand-thumbs-up-fill', 'Feedback'],
    ['../student/results.php', 'bi-bar-chart-fill', 'Results'],
  ];
} elseif ($role === 'supervisor') {
  $panelName = 'Supervisor Panel';
  $navItems = [
    ['../supervisor/dashboard.php', 'bi-grid-1x2-fill', 'Dashboard'],
    ['../supervisor/review-proposals.php', 'bi-file-earmark-check-fill', 'Review Proposals'],
    ['../supervisor/my-students.php', 'bi-people-fill', 'My Students'],
    ['../supervisor/evaluation.php', 'bi-star-fill', 'Mid Evaluation'],
    ['../supervisor/reports.php', 'bi-bar-chart-fill', 'Final Results'],
  ];
} elseif ($role === 'pbl_manager' || $role === 'manager') {
  $panelName = 'Manager Panel';
  $navItems = [
    ['../manager/dashboard.php', 'bi-grid-1x2-fill', 'Dashboard'],
    ['../manager/users.php', 'bi-people-fill', 'Users'],
    ['../manager/proposals.php', 'bi-file-earmark-text-fill', 'All Proposals'],
    ['../manager/evaluations.php', 'bi-star-fill', 'Evaluations'],
    ['../manager/analytics.php', 'bi-bar-chart-fill', 'Analytics'],
  ];
} elseif ($role === 'evaluator') {
  $panelName = 'Evaluator Panel';
  $navItems = [
    ['../evaluator/final-evaluation.php', 'bi-clipboard-check-fill', 'Final Evaluation'],
  ];
} elseif ($role === 'chairman') {
  $panelName = 'Chairman Panel';
  $navItems = [
    ['../chairman/dashboard.php', 'bi-grid-1x2-fill', 'Dashboard'],
    ['../chairman/manager-assignment.php', 'bi-person-gear', 'Assign Managers'],
    ['../chairman/analytics.php', 'bi-bar-chart-fill', 'Analytics'],
  ];
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Messages – PROVIA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <link href="../../assets/css/theme-dark-purple.css" rel="stylesheet"/>
  <style>
    body{background:#0f172a;}
    .sidebar{min-height:100vh;background:#1e293b;border-right:1px solid #334155;width:240px;position:fixed;top:0;left:0;}
    .sidebar .nav-link{color:#94a3b8;font-size:0.9rem;padding:12px 16px;border-radius:8px;margin:4px 12px;transition:all 0.2s;}
    .sidebar .nav-link:hover{background:rgba(79, 70, 229, 0.1);color:#818cf8;}
    .sidebar .nav-link.active{background:#4f46e5;color:#fff;box-shadow:0 4px 12px rgba(79, 70, 229, 0.3);}
    .main{margin-left:240px;padding:32px;min-height:100vh;}
    .chat-container{height:calc(100vh - 200px);display:flex;gap:24px;}
    .contact-list{width:320px;background:#1e293b;border:1px solid #334155;border-radius:16px;display:flex;flex-direction:column;}
    .chat-window{flex:1;background:#1e293b;border:1px solid #334155;border-radius:16px;display:flex;flex-direction:column;overflow:hidden;}
    .contact-item{padding:16px;border-bottom:1px solid #334155;cursor:pointer;transition:all 0.2s;}
    .contact-item:hover{background:rgba(255,255,255,0.02);}
    .contact-item.active{background:rgba(79, 70, 229, 0.1);border-left:4px solid #4f46e5;}
    .chat-messages{flex:1;padding:24px;overflow-y:auto;display:flex;flex-direction:column;gap:16px;}
    .msg-bubble{max-width:70%;padding:12px 16px;border-radius:16px;font-size:0.9rem;line-height:1.5;}
    .msg-received{background:#334155;color:#e2e8f0;align-self:flex-start;border-bottom-left-radius:4px;}
    .msg-sent{background:#4f46e5;color:#fff;align-self:flex-end;border-bottom-right-radius:4px;}
    @media(max-width:768px){.sidebar{display:none;}.main{margin-left:0;}}
  </style>
</head>
<body>

<div class="sidebar d-flex flex-column">
  <div class="p-4 border-bottom border-secondary border-opacity-10">
    <div class="d-flex align-items-center gap-3">
      <img src="../../assets/img/LOGO.png" alt="University Logo" style="height: 40px; width: auto; object-fit: contain;">
      <div>
        <span class="fw-bold text-white fs-6 d-block">PROVIA</span>
        <small class="text-secondary" style="font-size: 0.7rem;"><?php echo $panelName; ?></small>
      </div>
    </div>
  </div>
  <nav class="flex-grow-1 py-4">
    <ul class="nav flex-column">
      <?php foreach ($navItems as $item): ?>
        <li>
          <a class="nav-link <?php echo ($item[3]??false) ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($item[0]); ?>">
            <i class="bi <?php echo htmlspecialchars($item[1]); ?> me-3"></i><?php echo htmlspecialchars($item[2]); ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </nav>
  <div class="p-4 border-top border-secondary border-opacity-10">
    <div class="d-flex align-items-center gap-3 mb-3">
      <div class="rounded-circle bg-indigo-500 text-white d-flex align-items-center justify-content-center shadow-sm" style="width:40px;height:40px;background:#6366f1;font-weight:700;" id="userAvatar">U</div>
      <div class="overflow-hidden">
        <div class="fw-semibold text-white small text-truncate" id="userName">User</div>
        <div class="text-secondary small text-truncate" style="font-size:0.7rem;" id="userRoll">ID</div>
      </div>
    </div>
    <button class="btn btn-outline-danger btn-sm w-100 rounded-3" onclick="logout()"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
  </div>
</div>

<div class="main">
  <div class="mb-5">
    <h3 class="mb-1 fw-bold text-white">Messages</h3>
    <p class="text-secondary mb-0">Direct communication with project members and supervisors</p>
  </div>

  <div class="alert alert-info border-0 bg-primary bg-opacity-10 text-white small mb-4">
    <i class="bi bi-info-circle me-2"></i> Use the chat bubble at the bottom right to start real-time conversations.
  </div>

  <div class="card shadow-lg">
    <div class="card-body p-5 text-center">
        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 100px; height: 100px;">
            <i class="bi bi-chat-dots text-primary fs-1"></i>
        </div>
        <h4 class="text-white fw-bold">Your Message Center</h4>
        <p class="text-secondary mb-4">Real-time messaging is now active! Use the persistent chat widget in the bottom right corner to communicate with your team and supervisors from any page.</p>
        <button class="btn btn-primary rounded-pill px-5" onclick="document.getElementById('chat-toggle-btn').click()">
            <i class="bi bi-chat-fill me-2"></i> Open Chat Now
        </button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/auth.js"></script>
<script src="../../assets/js/app.js"></script>
<?php include_once "chat_init.php"; ?>
</body>
</html>


