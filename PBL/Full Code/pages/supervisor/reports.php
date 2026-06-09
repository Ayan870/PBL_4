<?php
require_once "../../helpers/auth_check.php";
checkRole('supervisor');
require_once "../../config/db.php";
$pageTitle = "Results – PROVIA";
require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";

$supervisor_id = $_SESSION['user_id'];
$is_mid_evaluator = mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM mid_eval_sessions WHERE evaluator_id = $supervisor_id AND eval_date = CURDATE() AND CURTIME() BETWEEN start_time AND end_time AND status = 'active' LIMIT 1")) > 0;

// Get final evaluations for groups supervised by this supervisor
$query = "
    SELECT g.name as group_name, ps.title as subject_title, fe.marks_out_of_20, fe.feedback, fe.evaluation_date,
           u_eval.name as evaluator_name, s.session, s.year, p.name as program_name
    FROM class_supervisors cs
    JOIN classes c ON cs.class_id = c.id
    JOIN `groups` g ON g.class_id = c.id
    JOIN pbl_subjects ps ON g.pbl_subject_id = ps.id
    JOIN programs p ON ps.program_id = p.id
    JOIN semesters s ON c.semester_id = s.id
    LEFT JOIN final_evaluations fe ON g.id = fe.group_id
    LEFT JOIN users u_eval ON fe.evaluator_id = u_eval.id
    WHERE cs.supervisor_id = ?
    ORDER BY fe.evaluation_date DESC, g.name ASC
";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $supervisor_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$evaluations = [];
$total_score = 0;
$graded_count = 0;

while ($row = mysqli_fetch_assoc($res)) {
    if ($row['marks_out_of_20'] !== null) {
        $row['percentage'] = $row['marks_out_of_20'] * 5;
        $total_score += $row['percentage'];
        $graded_count++;
        
        $grade = 'F';
        if ($row['percentage'] >= 80) $grade = 'A';
        elseif ($row['percentage'] >= 70) $grade = 'B';
        elseif ($row['percentage'] >= 60) $grade = 'C';
        elseif ($row['percentage'] >= 50) $grade = 'D';
        $row['grade'] = $grade;
    }
    $evaluations[] = $row;
}

$avg_score = $graded_count > 0 ? round($total_score / $graded_count, 1) : 0;
?>




<div class="main">
  <div class="d-flex justify-content-between align-items-center mb-5">
    <div>
      <h3 class="mb-1 fw-bold text-white">Final Evaluation Results</h3>
      <p class="text-secondary mb-0">Performance overview of your supervised groups</p>
    </div>
    <button class="btn btn-primary rounded-3 shadow-sm" onclick="window.print()"><i class="bi bi-printer-fill me-2"></i>Print Report</button>
  </div>

  <div class="row g-4 mb-5">
    <div class="col-md-4">
      <div class="stat-card shadow-sm">
        <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-trophy-fill"></i></div>
        <div class="text-secondary small fw-bold text-uppercase mb-1">Class Average</div>
        <div class="fw-bold fs-2 text-white"><?php echo $avg_score; ?>%</div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card shadow-sm">
        <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle-fill"></i></div>
        <div class="text-secondary small fw-bold text-uppercase mb-1">Graded Groups</div>
        <div class="fw-bold fs-2 text-white"><?php echo $graded_count; ?></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card shadow-sm">
        <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-people-fill"></i></div>
        <div class="text-secondary small fw-bold text-uppercase mb-1">Total Groups</div>
        <div class="fw-bold fs-2 text-white"><?php echo count($evaluations); ?></div>
      </div>
    </div>
  </div>

  <div class="card shadow-lg">
    <div class="card-header">
      <span class="fw-bold text-white">Group-wise Performance</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-dark bg-opacity-50">
            <tr>
              <th class="ps-4">Group / Subject</th>
              <th>Program</th>
              <th>Semester</th>
              <th>Score (20)</th>
              <th>Grade</th>
              <th class="pe-4">Feedback</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($evaluations)): ?>
              <tr><td colspan="6" class="text-center py-5 text-secondary">No groups assigned to you yet.</td></tr>
            <?php else: ?>
              <?php foreach ($evaluations as $eval): ?>
                <tr>
                  <td class="ps-4 py-3">
                    <div class="fw-bold text-white"><?php echo htmlspecialchars($eval['group_name']); ?></div>
                    <div class="text-secondary small"><?php echo htmlspecialchars($eval['subject_title']); ?></div>
                  </td>
                  <td><span class="badge bg-primary text-white px-3 rounded-pill shadow-sm" style="font-size: 0.7rem;"><?php echo htmlspecialchars($eval['program_name']); ?></span></td>
                  <td><small class="text-secondary"><?php echo htmlspecialchars($eval['session'] . " " . $eval['year']); ?></small></td>
                  <td>
                    <?php if ($eval['marks_out_of_20'] !== null): ?>
                      <div class="fw-bold text-primary"><?php echo $eval['marks_out_of_20']; ?> <small class="text-secondary">/ 20</small></div>
                      <div class="text-secondary" style="font-size: 0.7rem;"><?php echo $eval['percentage']; ?>%</div>
                    <?php else: ?>
                      <span class="text-secondary small italic">Pending</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (isset($eval['grade'])): ?>
                      <span class="badge bg-<?php echo ($eval['grade'] === 'F' ? 'danger' : 'success'); ?> bg-opacity-10 text-<?php echo ($eval['grade'] === 'F' ? 'danger' : 'success'); ?> border border-<?php echo ($eval['grade'] === 'F' ? 'danger' : 'success'); ?> border-opacity-25 px-3">
                        Grade <?php echo $eval['grade']; ?>
                      </span>
                    <?php else: ?>
                      --
                    <?php endif; ?>
                  </td>
                  <td class="pe-4">
                    <div class="text-secondary small text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($eval['feedback'] ?? ''); ?>">
                      <?php echo $eval['feedback'] ? '"' . htmlspecialchars($eval['feedback']) . '"' : '--'; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once "../../includes/footer.php"; ?>
</body>
</html>

