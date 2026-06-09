<?php
session_start();
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'] ?? '';
    if ($role === 'student') header("Location: pages/student/dashboard.php");
    elseif ($role === 'supervisor') header("Location: pages/supervisor/dashboard.php");
    elseif ($role === 'pbl_manager' || $role === 'manager') header("Location: pages/manager/dashboard.php");
    elseif ($role === 'evaluator') header("Location: pages/evaluator/final-evaluation.php");
    elseif ($role === 'chairman') header("Location: pages/chairman/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PROVIA – Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <link href="assets/css/theme-dark-purple.css" rel="stylesheet"/>
  <style>
    body { 
      background-color: #0f172a; 
      min-height: 100vh;
    }
    body::before {
      content: "";
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: url('assets/img/Backgroud pick.png') no-repeat center center;
      background-size: cover;
      transform: scale(1.08); /* Scales up slightly to hide the watermark */
      opacity: 0.25;
      z-index: -1;
    }
    .login-card { max-width: 500px; margin: 80px auto; position: relative; z-index: 1; }
    .brand-title { color: #6366f1; font-weight: 700; }
    .role-btn.active { background: #4f46e5; color: #fff; border-color: #4f46e5; }
    .btn-login { background: #4f46e5; border-color: #4f46e5; }
    .btn-login:hover { background: #4338ca; border-color: #4338ca; }
    .card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; }
    .role-btn { font-size: 0.8rem; padding: 8px 10px; flex: 1 1 auto; border-radius: 8px !important; }
  </style>
</head>
<body>

<div class="login-card px-3">
  <div class="text-center mb-4">
    <img src="assets/img/LOGO.png" alt="University Logo" style="height: 100px; width: auto; margin-bottom: 15px;">
    <h3 class="brand-title">PROVIA</h3>
  </div>

  <div class="card shadow-lg">
    <div class="card-body p-4">
      <h5 class="card-title mb-1 text-white">Sign In</h5>
      <p class="text-secondary small mb-4">Login to your account</p>

      <div class="d-flex flex-wrap gap-2 mb-4" role="group">
        <button class="btn btn-outline-primary role-btn active" onclick="selectRole('student', this)">Student</button>
        <button class="btn btn-outline-primary role-btn" onclick="selectRole('supervisor', this)">Supervisor</button>
        <button class="btn btn-outline-primary role-btn" onclick="selectRole('manager', this)">PBL Manager</button>
        <button class="btn btn-outline-primary role-btn" onclick="selectRole('evaluator', this)">Evaluator</button>
        <button class="btn btn-outline-primary role-btn" onclick="selectRole('chairman', this)">Chairman</button>
      </div>

      <form onsubmit="handleLogin(event)">
        <div class="mb-3" id="loginRollGroup">
          <label class="form-label text-secondary small fw-bold">Roll Number (Students)</label>
          <div class="input-group">
            <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary"><i class="bi bi-person-badge"></i></span>
            <input type="text" id="loginRollNumber" class="form-control bg-dark border-secondary border-opacity-25 text-white" placeholder="SU74-BSCSM-F24-005"/>
          </div>
          <small class="form-text text-secondary" style="font-size: 0.7rem;">(e.g. SU74-BSCSM-F24-005)</small>
        </div>
        <div class="mb-3 d-none" id="loginEmailGroup">
          <label class="form-label text-secondary small fw-bold">Email Address</label>
          <div class="input-group">
            <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary"><i class="bi bi-envelope"></i></span>
            <input type="email" id="loginEmail" class="form-control bg-dark border-secondary border-opacity-25 text-white" placeholder="you@university.edu" />
          </div>
        </div>
        <div class="mb-4">
          <label class="form-label text-secondary small fw-bold">Password</label>
          <div class="input-group">
            <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary"><i class="bi bi-lock"></i></span>
            <input type="password" id="loginPassword" class="form-control bg-dark border-secondary border-opacity-25 text-white" placeholder="Enter password" />
          </div>
        </div>
        <div class="mb-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="rememberMe"/>
            <label class="form-check-label text-secondary small" for="rememberMe">Remember me</label>
          </div>
        </div>
        <button type="submit" class="btn btn-login btn-primary w-100 py-2 fw-bold shadow-sm" id="loginBtn">
          <span id="loginBtnText">Sign In</span>
          <span id="loginSpinner" class="spinner-border spinner-border-sm d-none ms-1"></span>
        </button>
      </form>

      <hr class="my-4 border-secondary border-opacity-10"/>
      <p class="text-center small text-secondary mb-0">
        Don't have an account? <a href="pages/shared/register.php" class="text-primary text-decoration-none fw-medium">Register</a>
      </p>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/auth.js?v=<?php echo filemtime('assets/js/auth.js'); ?>"></script>
</body>
</html>

