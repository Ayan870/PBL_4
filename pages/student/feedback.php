<?php
require_once "../../helpers/auth_check.php";
checkRole('student');
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Evaluation Feedback – PROVIA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <link href="../../assets/css/theme-dark-purple.css" rel="stylesheet"/>
  <style>
    body { background: #0f172a; font-family: 'Inter', sans-serif; }
    .sidebar { min-height: 100vh; background: #1e293b; border-right: 1px solid #334155; width: 240px; position: fixed; top: 0; left: 0; }
    .sidebar .nav-link { color: #94a3b8; font-size: 0.9rem; padding: 12px 16px; border-radius: 8px; margin: 4px 12px; transition: all 0.2s; }
    .sidebar .nav-link:hover { background: rgba(79, 70, 229, 0.1); color: #818cf8; }
    .sidebar .nav-link.active { background: #4f46e5; color: #fff; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); }
    .main { margin-left: 240px; padding: 40px; min-height: 100vh; background: radial-gradient(circle at top right, rgba(79, 70, 229, 0.05), transparent); }
    
    .feedback-card { background: #1e293b; border: 1px solid #334155; border-radius: 24px; padding: 32px; margin-bottom: 32px; transition: all 0.3s; position: relative; overflow: hidden; }
    .feedback-card::before { content: ''; position: absolute; top: 0; left: 0; width: 6px; height: 100%; background: #4f46e5; opacity: 0.8; }
    .feedback-card.mid-term::before { background: #6366f1; }
    .feedback-card.final::before { background: #10b981; }
    
    .score-badge { font-size: 1.5rem; font-weight: 800; color: #fff; background: rgba(79, 70, 229, 0.1); padding: 8px 20px; border-radius: 16px; border: 1px solid rgba(79, 70, 229, 0.2); }
    .feedback-meta { font-size: 0.8rem; color: #94a3b8; display: flex; align-items: center; gap: 16px; margin-bottom: 24px; }
    
    .comment-box { background: rgba(15, 23, 42, 0.5); border-radius: 16px; padding: 24px; border: 1px solid rgba(51, 65, 85, 0.5); margin-top: 20px; }
    .recommendation-alert { background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.1); border-radius: 16px; padding: 20px; margin-top: 20px; color: #34d399; }
    
    .status-pill { padding: 6px 16px; border-radius: 100px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
    
    @media(max-width:768px) { .sidebar { display: none; } .main { margin-left: 0; padding: 20px; } }
  </style>
</head>
<body>

<div class="sidebar d-flex flex-column">
  <div class="p-4 border-bottom border-secondary border-opacity-10">
    <div class="d-flex align-items-center gap-3">
      <img src="../../assets/img/LOGO.png" alt="University Logo" style="height: 40px; width: auto; object-fit: contain;">
      <div>
        <span class="fw-bold text-white fs-6 d-block">PROVIA</span>
        <small class="text-secondary" style="font-size: 0.7rem;">Student Panel</small>
      </div>
    </div>
  </div>
  <nav class="flex-grow-1 py-4">
    <ul class="nav flex-column">
      <li><a class="nav-link" href="dashboard.php"><i class="bi bi-grid-1x2-fill me-3"></i>Dashboard</a></li>
      <li><a class="nav-link" href="submit-proposal.php"><i class="bi bi-file-earmark-plus-fill me-3"></i>Submit Proposal</a></li>
      <li><a class="nav-link" href="my-projects.php"><i class="bi bi-folder-fill me-3"></i>My Projects</a></li>
      <li><a class="nav-link active" href="feedback.php"><i class="bi bi-hand-thumbs-up-fill me-3"></i>Feedback</a></li>
      <li><a class="nav-link" href="results.php"><i class="bi bi-bar-chart-fill me-3"></i>Results</a></li>
    </ul>
  </nav>
  <div class="p-4 border-top border-secondary border-opacity-10">
    <div class="d-flex align-items-center gap-3 mb-3">
      <div class="rounded-circle bg-indigo-500 text-white d-flex align-items-center justify-content-center shadow-sm" style="width:40px;height:40px;background:#6366f1;font-weight:700;" id="userAvatar">S</div>
      <div class="overflow-hidden">
        <div class="fw-semibold text-white small text-truncate" id="userName">Student</div>
        <div class="text-secondary small text-truncate" style="font-size:0.7rem;" id="userRoll">Roll No</div>
      </div>
    </div>
    <button class="btn btn-outline-danger btn-sm w-100 rounded-3" onclick="logout()"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
  </div>
</div>

<div class="main">
  <div class="mb-5 d-flex justify-content-between align-items-center">
    <div>
      <h2 class="fw-bold text-white mb-1">Evaluation Journey</h2>
      <p class="text-secondary mb-0">Track your progress through Mid-Term and Final evaluations</p>
    </div>
    <button class="btn btn-outline-primary rounded-pill px-4" onclick="loadFeedback()"><i class="bi bi-arrow-clockwise me-2"></i>Refresh</button>
  </div>

  <div id="feedbackContainer">
    <div class="text-center py-5">
      <div class="spinner-border text-primary mb-3"></div>
      <p class="text-secondary">Retrieving your evaluation history...</p>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/auth.js"></script>
<script src="../../assets/js/app.js?v=1.1"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  requireAuth('student');
  loadFeedback();
});

async function loadFeedback() {
  const container = document.getElementById('feedbackContainer');
  try {
    const res = await fetch('../../api/student/get_feedback.php');
    const data = await res.json();
    
    if (data.success) {
      if (data.feedback.length === 0) {
        container.innerHTML = `
          <div class="text-center py-5 bg-dark bg-opacity-25 rounded-4 border border-secondary border-opacity-10 mt-4">
            <i class="bi bi-emoji-smile fs-1 d-block mb-3 text-secondary opacity-25"></i>
            <h5 class="text-white">No Evaluations Yet</h5>
            <p class="text-secondary small">Your supervisor will provide feedback once your evaluations are complete.</p>
          </div>
        `;
        return;
      }

      container.innerHTML = data.feedback.map(f => {
        const isMid      = f.eval_type === 'Mid-Term Evaluation';
        const isFinal    = f.eval_type === 'Final Evaluation';
        const isProposal = f.eval_type.includes('Proposal Review');
        
        let scoreDisplay = f.tech_score;
        let scoreLabel   = "Marks";
        
        if (isMid) scoreDisplay += `<span class="fs-6 opacity-50 ms-1">/ 5</span>`;
        if (isFinal) scoreDisplay += `<span class="fs-6 opacity-50 ms-1">/ 20</span>`;
        if (isProposal) {
          scoreLabel = "Result";
          const isPass = f.tech_score === 'PASS';
          scoreDisplay = `<span class="${isPass ? 'text-success' : 'text-danger'}">${f.tech_score}</span>`;
        }

        const cardClass = isMid ? 'mid-term' : (isFinal ? 'final' : 'proposal');
        const badgeClass = isMid ? 'bg-primary' : (isFinal ? 'bg-success' : (f.tech_score === 'PASS' ? 'bg-info' : 'bg-danger'));
        
        return `
          <div class="feedback-card ${cardClass} shadow-lg animate__animated animate__fadeInUp">
            <div class="d-flex justify-content-between align-items-start mb-4">
              <div>
                <span class="status-pill mb-2 d-inline-block ${badgeClass} bg-opacity-10 text-${badgeClass.replace('bg-', '')}">
                  <i class="bi ${isMid ? 'bi-star-half' : (isFinal ? 'bi-star-fill' : 'bi-file-earmark-check')} me-2"></i>${f.eval_type}
                </span>
                <h4 class="text-white fw-bold mb-1">${isProposal ? 'Proposal Feedback' : (isMid ? 'Performance Insight' : 'Final Achievement')}</h4>
                <div class="feedback-meta">
                  <span><i class="bi bi-person-circle me-2"></i>${f.supervisor_name || 'N/A'}</span>
                  <span><i class="bi bi-calendar3 me-2"></i>${new Date(f.created_at).toLocaleDateString(undefined, {day:'numeric', month:'long', year:'numeric'})}</span>
                </div>
              </div>
              <div class="text-center">
                <div class="score-badge">${scoreDisplay}</div>
                <div class="text-secondary small mt-2 fw-bold text-uppercase" style="letter-spacing:1px;">${scoreLabel}</div>
              </div>
            </div>

            <div class="comment-box">
              <label class="text-secondary small fw-bold text-uppercase mb-3 d-block" style="letter-spacing:1px;">
                <i class="bi bi-chat-left-text me-2"></i>Supervisor Feedback
              </label>
              <p class="text-white mb-0" style="line-height:1.7; font-size:1rem;">"${f.feedback || 'No comments provided.'}"</p>
            </div>

            ${isProposal ? `
               <div class="recommendation-alert" style="background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.05); color: #94a3b8;">
                <div class="d-flex gap-3 align-items-center">
                   <div class="bg-secondary bg-opacity-10 p-2 rounded-circle">
                    <i class="bi bi-bookmark-fill text-secondary"></i>
                  </div>
                  <div>
                    <strong class="d-block small text-uppercase mb-1">Project Title</strong>
                    <p class="mb-0 small text-white-50">${f.recommendations}</p>
                  </div>
                </div>
              </div>
            ` : (f.recommendations ? `
              <div class="recommendation-alert">
                <div class="d-flex gap-3 align-items-center">
                  <div class="bg-success bg-opacity-10 p-2 rounded-circle">
                    <i class="bi bi-lightning-charge-fill text-success"></i>
                  </div>
                  <div>
                    <strong class="d-block small text-uppercase mb-1">Growth Path & Suggestions</strong>
                    <p class="mb-0 small text-white-50">${f.recommendations}</p>
                  </div>
                </div>
              </div>
            ` : '')}
          </div>
        `;
      }).join('');
    }
  } catch (e) {
    container.innerHTML = `<div class="alert alert-danger">Error connecting to server.</div>`;
  }
}
</script>
<?php include_once "../shared/chat_init.php"; ?>
</body>
</html>


