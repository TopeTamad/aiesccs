<?php
date_default_timezone_set('Asia/Manila');
require_once __DIR__ . '/../includes/db.php';
// Date inputs: support single date or a date range (from / to)
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$from = isset($_GET['from']) ? $_GET['from'] : '';
$to = isset($_GET['to']) ? $_GET['to'] : '';
$gender_filter = isset($_GET['gender']) ? $_GET['gender'] : '';

// validate date helper
function valid_date($d){
  if (!$d) return false;
  $dt = DateTime::createFromFormat('Y-m-d', $d);
  return $dt && $dt->format('Y-m-d') === $d;
}

// build base query and dynamic WHERE clauses
$base = "SELECT a.student_id, s.section, s.year_level, s.pc_number, s.gender FROM attendance a JOIN students s ON a.student_id = s.student_id";
$wheres = [];

// Build a date-only clause for use inside subqueries (so we pick subject at the time in/out within the same date/range)
$date_clause = '';

// if not showing all, apply date filter: prefer valid from/to range, else fallback to single date
if (!(isset($_GET['show_all']) && $_GET['show_all'] == '1')) {
  if (valid_date($from) && valid_date($to)) {
    // ensure from <= to
    if ($from > $to) { $tmp = $from; $from = $to; $to = $tmp; }
    $date_clause = "DATE(a2.scan_time) BETWEEN '" . $from . "' AND '" . $to . "'";
    $wheres[] = "DATE(a.scan_time) BETWEEN '" . $from . "' AND '" . $to . "'";
  } else {
    // fallback to single date (already validated format may vary)
    if (valid_date($date)) {
      $date_clause = "DATE(a2.scan_time)='" . $date . "'";
      $wheres[] = "DATE(a.scan_time)='" . $date . "'";
    }
  }
}

// gender filter
if ($gender_filter) {
  $g = $conn->real_escape_string($gender_filter);
  $wheres[] = "s.gender = '" . $g . "'";
}

// Build where SQL
if (count($wheres)) {
  $where_sql = ' WHERE ' . implode(' AND ', $wheres);
} else {
  $where_sql = '';
}

// We'll return one row per student: Time In (MIN) and Time Out (MAX) within the filters.
// For subjects we pick the subject that corresponds to the time_in and time_out respectively (first/last scan in the filtered range).
$group_base = "SELECT a.student_id, s.section, s.year_level, s.pc_number, s.gender, MIN(a.scan_time) AS time_in, MAX(a.scan_time) AS time_out";

// We'll later add subject_in and subject_out via correlated subqueries when building the final query string

// Count distinct students for pagination
$count_query = "SELECT COUNT(DISTINCT a.student_id) AS c FROM attendance a JOIN students s ON a.student_id = s.student_id LEFT JOIN subjects sub ON a.subject_id = sub.id" . $where_sql;

// --- Pagination setup ---
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 25;
if ($per_page < 1) $per_page = 25;
if ($per_page > 30) $per_page = 30; // cap at 30 per your request

$count_res = $conn->query($count_query);
$total_rows = 0;
if ($count_res) {
  $r = $count_res->fetch_assoc();
  $total_rows = intval($r['c']);
}

$total_pages = max(1, (int) ceil($total_rows / $per_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $per_page;

// Build final grouped query with correlated subqueries to fetch subject at time_in/time_out
// Instead of grouping by student, build subject-aware IN/OUT pairs so the All Attendance
// view matches the behavior of includes/universal_recent_attendance.php.

// Helper: build paired records by matching a scan to the next scan with the same subject for that student
function build_pairs_by_subject_all($rows) {
  $by_student = [];
  foreach ($rows as $r) {
    $sid = $r['student_id'];
    if (!isset($by_student[$sid])) $by_student[$sid] = [];
    $by_student[$sid][] = $r;
  }

  $pairs = [];
  foreach ($by_student as $sid => $list) {
    // chronological order (oldest first)
    usort($list, function($a, $b){ return strtotime($a['scan_time']) <=> strtotime($b['scan_time']); });
    $n = count($list);
    $used = array_fill(0, $n, false);
    for ($i = 0; $i < $n; $i++) {
      if ($used[$i]) continue;
      $in = $list[$i];
      $used[$i] = true;
      $out = null;
      // find the next unused scan with the same subject
      for ($j = $i + 1; $j < $n; $j++) {
        if ($used[$j]) continue;
        if ($list[$j]['subject_id'] == $in['subject_id']) {
          $out = $list[$j];
          $used[$j] = true;
          break;
        }
      }
      $pairs[] = [
        'student_id' => $sid,
        'name' => $in['name'] ?? null,
        'section' => $in['section'] ?? null,
        'year_level' => $in['year_level'] ?? null,
        'pc_number' => $in['pc_number'] ?? null,
        'gender' => $in['gender'] ?? null,
        'time_in' => $in['scan_time'],
        'time_out' => $out ? $out['scan_time'] : null,
        'subject_in' => $in['subject_name'] ?? null,
        'subject_out' => $out ? $out['subject_name'] : null,
      ];
    }
  }
  // sort by time_in desc
  usort($pairs, function($a, $b){ return strtotime($b['time_in']) <=> strtotime($a['time_in']); });
  return $pairs;
}

// Fetch attendance rows matching the current filters (use a full fetch to build accurate pairs)
// Note: using full fetch for a filtered date range; if no date range is provided this may be large.
// We keep the same joins to retrieve student and subject metadata.
 $fetch_sql = "SELECT a.*, s.name, s.section, s.year_level, s.pc_number, s.gender, sub.subject_name, sub.id AS subject_id FROM attendance a
  LEFT JOIN students s ON s.student_id = a.student_id
  LEFT JOIN subjects sub ON sub.id = a.subject_id " . $where_sql . " ORDER BY a.student_id, a.scan_time ASC";

 $fres = $conn->query($fetch_sql);
 $rows = [];
 if ($fres) { while ($r = $fres->fetch_assoc()) $rows[] = $r; }

 // Build all pairs then paginate
 $all_pairs = build_pairs_by_subject_all($rows);
 $total_pairs = count($all_pairs);
 $total_pages = max(1, (int) ceil($total_pairs / $per_page));
 if ($page > $total_pages) $page = $total_pages;
 $offset = ($page - 1) * $per_page;
 $pairs_page = array_slice($all_pairs, $offset, $per_page);

 // For rendering reuse $all variable name to keep template logic similar (it expects a result->fetch_assoc loop)
 $all = new class($pairs_page) {
  private $data;
  private $idx = 0;
  public $num_rows = 0;
  public function __construct($d) { $this->data = array_values($d); $this->num_rows = count($this->data); }
  public function fetch_assoc() { if ($this->idx < count($this->data)) return $this->data[$this->idx++]; return false; }
  public function num_rows() { return $this->num_rows; }
 };

// (Pagination already set above and $all executed)
?>
<?php if (!isset($_GET['show_all'])): ?>
  <form method="get" style="display:flex;justify-content:center;align-items:center;margin-bottom:18px;">
    <div style="background:#fff;padding:12px 24px;border-radius:12px;box-shadow:0 2px 12px rgba(99,102,241,0.06);display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
      <label for="from" style="font-weight:600;color:#222;display:flex;align-items:center;gap:6px;">
        <span style="color:#6366f1;"><i class="fas fa-calendar-alt"></i></span> From:
      </label>
      <input type="date" id="from" name="from" value="<?= htmlspecialchars($from) ?>" style="padding:8px 14px;border-radius:6px;border:1px solid #ccc;font-size:1rem;">

      <label for="to" style="font-weight:600;color:#222;display:flex;align-items:center;gap:6px;margin-left:6px;">
        <span style="color:#6366f1;"><i class="fas fa-calendar-alt"></i></span> To:
      </label>
      <input type="date" id="to" name="to" value="<?= htmlspecialchars($to) ?>" style="padding:8px 14px;border-radius:6px;border:1px solid #ccc;font-size:1rem;">

      <label for="gender" style="font-weight:600;color:#222;display:flex;align-items:center;gap:6px;margin-left:6px;">
        <span style="color:#6366f1;"><i class="fas fa-venus-mars"></i></span> Gender:
      </label>
      <select id="gender" name="gender" style="padding:8px 14px;border-radius:6px;border:1px solid #ccc;font-size:1rem;">
        <option value="">All</option>
        <option value="Male" <?= $gender_filter === 'Male' ? 'selected' : '' ?>>Male</option>
        <option value="Female" <?= $gender_filter === 'Female' ? 'selected' : '' ?>>Female</option>
      </select>

      <button type="submit" style="background:#1976d2;color:#fff;font-weight:600;padding:8px 18px;border-radius:6px;border:none;font-size:1rem;cursor:pointer;transition:background 0.2s;margin-left:8px;">Filter</button>
      <a href="?show_all=1" style="background:#6b7280;color:#fff;font-weight:600;padding:8px 14px;border-radius:6px;text-decoration:none;margin-left:6px;">Show All</a>
    </div>
  </form>
  
  <div style="text-align:center; margin-bottom: 18px;">
  </div>
<?php endif; ?>

<!-- Export and Print Buttons -->
<div style="display:flex;justify-content:center;align-items:center;margin-bottom:18px;gap:12px;">
  <?php
    // build export/print base params
    $params = [];
    if ($gender_filter) $params['gender'] = $gender_filter;
    if (!empty($from)) $params['from'] = $from;
    if (!empty($to)) $params['to'] = $to;
    if (!empty($date) && empty($from) && empty($to)) $params['date'] = $date;
    if (isset($_GET['show_all']) && $_GET['show_all'] == '1') $params['show_all'] = '1';
    $query_str = http_build_query($params);
  ?>
  <a href="includes/export_excel.php?<?= $query_str ?>" style="background:#28a745;color:#fff;font-weight:600;padding:10px 20px;border-radius:8px;text-decoration:none;display:flex;align-items:center;gap:8px;transition:background 0.2s;">
    <i class="fas fa-file-excel"></i> Export to Excel
  </a>
  <a href="includes/print_report.php?<?= $query_str ?>" target="_blank" style="background:#dc3545;color:#fff;font-weight:600;padding:10px 20px;border-radius:8px;text-decoration:none;display:flex;align-items:center;gap:8px;transition:background 0.2s;">
    <i class="fas fa-print"></i> Print Report
  </a>
</div>
<!-- Realtime Recent Attendance Panel (removed UI) -->
<!-- Pagination controls removed as requested -->
<div style="display: flex; justify-content: center; margin-top: 32px;">
  <div style="background: #fff; box-shadow: 0 4px 24px rgba(0,0,0,0.08); border-radius: 18px; padding: 32px; max-width: 1100px; width: 100%;">
    <div style="overflow-x: auto;">
      <table style="width: 100%; border-collapse: collapse; font-size: 1.08rem;">
        <thead>
            <tr style="background: #6366f1; color: #fff;">
            <th style="padding: 12px 10px; font-weight: bold;">Student ID</th>
            <th style="padding: 12px 10px; font-weight: bold;">Section</th>
            <th style="padding: 12px 10px; font-weight: bold;">Year</th>
            <th style="padding: 12px 10px; font-weight: bold;">Gender</th>
            <th style="padding: 12px 10px; font-weight: bold;">PC Number</th>
            <th style="padding: 12px 10px; font-weight: bold;">Subjects</th>
            <th style="padding: 12px 10px; font-weight: bold;">Time In</th>
            <th style="padding: 12px 10px; font-weight: bold;">Time Out</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($all && $all->num_rows > 0): $i = 0; while ($row = $all->fetch_assoc()): ?>
            <tr style="background: <?= $i % 2 === 0 ? '#f3f4f6' : '#fff' ?>; transition: background 0.2s;" onmouseover="this.style.background='#e0e7ff'" onmouseout="this.style.background='<?= $i % 2 === 0 ? '#f3f4f6' : '#fff' ?>'">
              <td style="padding: 10px 8px; border-bottom: 1px solid #e5e7eb; text-align: center;">
                <?= htmlspecialchars($row['student_id']) ?>
              </td>
              <td style="padding: 10px 8px; border-bottom: 1px solid #e5e7eb; text-align: center;">
                <?= htmlspecialchars($row['section']) ?>
              </td>
              <td style="padding: 10px 8px; border-bottom: 1px solid #e5e7eb; text-align: center;">
                <?= $row['year_level'] ?>
              </td>
              <td style="padding: 10px 8px; border-bottom: 1px solid #e5e7eb; text-align: center;">
                <?= htmlspecialchars($row['gender'] ?? '-') ?>
              </td>
              <td style="padding: 10px 8px; border-bottom: 1px solid #e5e7eb; text-align: center;">
                <?= htmlspecialchars($row['pc_number'] ?? '-') ?>
              </td>
              <td style="padding: 10px 8px; border-bottom: 1px solid #e5e7eb; text-align: center;">
                <?= htmlspecialchars($row['subject_out'] ?? $row['subject_in'] ?? '-') ?>
              </td>
              <td style="padding: 10px 8px; border-bottom: 1px solid #e5e7eb; text-align: center;">
                <?= $row['time_in'] ? date('Y-m-d h:i:s A', strtotime($row['time_in'])) : '-' ?>
              </td>
              <td style="padding: 10px 8px; border-bottom: 1px solid #e5e7eb; text-align: center;">
                <?= $row['time_out'] ? date('Y-m-d h:i:s A', strtotime($row['time_out'])) : '-' ?>
              </td>
            </tr>
          <?php $i++; endwhile; else: ?>
            <tr><td colspan="8" style="text-align:center; padding: 18px; color: #888;">No attendance records found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div> 
<script>
  (function(){
    var container = document.getElementById('all-recent-attendance-table');
    async function refresh(){
      try{
        var res = await fetch('includes/universal_recent_attendance.php?type=all&limit=15&_=' + Date.now());
        if(res.ok){
          var html = await res.text();
          if(container) container.innerHTML = html;
        }
      }catch(err){
        console.error('Failed to fetch recent attendance', err);
      }
    }
    refresh();
    setInterval(refresh, 3000);
  })();
</script>