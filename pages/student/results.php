<?php
require_once "../../helpers/auth_check.php";
checkRole('student');
require_once "../../config/db.php";
$pageTitle = "My Results – PROVIA";
require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";

$student_id = $_SESSION['user_id'];

// Get student's group and final evaluation
$query = "
    SELECT g.name as group_name, fe.marks_out_of_20, fe.feedback, fe.evaluation_date, u.name as evaluator_name,
           s.number as semester_number, s.session, s.year, ps.title as subject_title
    FROM group_members gm
    JOIN `groups` g ON gm.group_id = g.id
    JOIN pbl_subjects ps ON g.pbl_subject_id = ps.id
    JOIN classes c ON g.class_id = c.id
    JOIN semesters s ON c.semester_id = s.id
    LEFT JOIN final_evaluations fe ON g.id = fe.group_id
    LEFT JOIN users u ON fe.evaluator_id = u.id
    WHERE gm.student_id = ? AND gm.invite_status = 'accepted'
    LIMIT 1
";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$has_result = $result && $result['marks_out_of_20'] !== null;
$score_20 = $result['marks_out_of_20'] ?? 0;
$percentage = $score_20 * 5;
$grade = 'F';
if ($percentage >= 80) $grade = 'A';
elseif ($percentage >= 70) $grade = 'B';
elseif ($percentage >= 60) $grade = 'C';
elseif ($percentage >= 50) $grade = 'D';

?>




<div class="main">
  <div class="mb-5">
    <h3 class="mb-1 fw-bold text-white">Project Results</h3>
    <p class="text-secondary mb-0">Review your final evaluation scores and feedback</p>
  </div>

  <?php if ($has_result): ?>
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="card shadow-lg h-100">
          <div class="card-header text-center">
            <span class="fw-bold text-white">Final Score</span>
          </div>
          <div class="card-body p-5 text-center d-flex flex-column justify-content-center">
            <div class="result-badge mb-4">
              <span class="fs-1 fw-bold text-white"><?php echo $score_20; ?></span>
              <span class="text-secondary small">/ 20</span>
            </div>
            <h4 class="fw-bold text-indigo-400 mb-1">Grade: <?php echo $grade; ?></h4>
            <p class="text-secondary small"><?php echo $percentage; ?>% Aggregate Score</p>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="card shadow-lg h-100">
          <div class="card-header">
            <span class="fw-bold text-white">Evaluation Details</span>
          </div>
          <div class="card-body p-4">
            <div class="mb-4">
              <label class="text-secondary small fw-bold text-uppercase mb-2 d-block">Project Title</label>
              <h5 class="text-white fw-bold"><?php echo htmlspecialchars($result['subject_title']); ?></h5>
              <small class="text-secondary"><?php echo htmlspecialchars($result['group_name']); ?></small>
            </div>
            
            <div class="row g-3 mb-4">
              <div class="col-sm-6">
                <label class="text-secondary small fw-bold text-uppercase mb-1 d-block">Semester</label>
                <div class="text-white"><?php echo htmlspecialchars($result['session'] . " " . $result['year']); ?></div>
              </div>
              <div class="col-sm-6">
                <label class="text-secondary small fw-bold text-uppercase mb-1 d-block">Evaluated By</label>
                <div class="text-white"><?php echo htmlspecialchars($result['evaluator_name']); ?> (External)</div>
              </div>
            </div>

            <div class="p-4 bg-dark bg-opacity-50 rounded-3 border border-secondary border-opacity-10">
              <label class="text-secondary small fw-bold text-uppercase mb-2 d-block">Evaluator Feedback</label>
              <p class="text-white mb-0 italic" style="line-height: 1.6;">"<?php echo nl2br(htmlspecialchars($result['feedback'])); ?>"</p>
            </div>

            <div class="mt-4 text-end">
              <small class="text-secondary">Evaluation Date: <?php echo date('M d, Y', strtotime($result['evaluation_date'])); ?></small>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="card shadow-lg">
      <div class="card-body p-5 text-center">
        <div class="bg-secondary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
          <i class="bi bi-clock-history text-secondary fs-1"></i>
        </div>
        <h4 class="text-white fw-bold">Evaluation Pending</h4>
        <p class="text-secondary mx-auto" style="max-width: 400px;">Your project final evaluation has not been conducted or uploaded yet. Please stay tuned for updates from your PBL Manager.</p>
        <a href="dashboard.php" class="btn btn-primary rounded-pill px-4 mt-3">Return to Dashboard</a>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require_once "../../includes/footer.php"; ?>
</body>
</html>

