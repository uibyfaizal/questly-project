<?php
// checkup.php
include "includes/auth.php"; // pastikan session & user login
include "includes/db.php";

if (!isset($_GET['id'])) {
    header("Location: my_challenges.php");
    exit;
}

$challenge_id = intval($_GET['id']);
$user_id = intval($_SESSION['user_id']);

// Ambil info challenge
$sql = "SELECT id, title, description, duration_days FROM challenges WHERE id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) { die("Query error (challenge select)."); }
$stmt->bind_param("i", $challenge_id);
$stmt->execute();
$challenge = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$challenge) {
    echo "Challenge tidak ditemukan.";
    exit;
}

// Ambil / buat participant (challenge_participants)
$sql = "SELECT * FROM challenge_participants WHERE user_id = ? AND challenge_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) { die("Query error (participant select)."); }
$stmt->bind_param("ii", $user_id, $challenge_id);
$stmt->execute();
$participant = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$participant) {
    // auto-join agar user bisa langsung checkup (opsional)
    $ins = $conn->prepare("INSERT INTO challenge_participants (user_id, challenge_id, joined_at) VALUES (?, ?, NOW())");
    if ($ins === false) { die("Gagal prepare auto-join."); }
    $ins->bind_param("ii", $user_id, $challenge_id);
    $ins->execute();
    $ins->close();

    // ambil kembali participant
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $challenge_id);
    $stmt->execute();
    $participant = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Ambil semua checkups user untuk challenge ini (urut asc)
$sql = "SELECT id, day_number, status, note, created_at FROM checkups WHERE user_id = ? AND challenge_id = ? ORDER BY day_number ASC, created_at ASC";
$stmt = $conn->prepare($sql);
if ($stmt === false) { die("Query error (checkups select)."); }
$stmt->bind_param("ii", $user_id, $challenge_id);
$stmt->execute();
$res = $stmt->get_result();
$checkups = [];
$checkups_by_day = [];
while ($r = $res->fetch_assoc()) {
    $checkups[] = $r;
    $checkups_by_day[intval($r['day_number'])] = $r; // last wins for same day_number
}
$stmt->close();

// Hitung indeks hari berdasarkan joined_at
$joined_at = $participant['joined_at'] ?? date('Y-m-d H:i:s'); // fallback jika null
$startDate = new DateTime(date('Y-m-d', strtotime($joined_at))); // normalize ke tanggal
$todayDate = new DateTime(date('Y-m-d'));
$interval = $startDate->diff($todayDate);
$days_passed = (int)$interval->days;
$today_index = $days_passed + 1; // hari ke-1 adalah tanggal joined_at

$duration_days = max(1, intval($challenge['duration_days'] ?? 7));

// Handle POST actions: check (mark today's done) or remove_last
$action_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'check') {
        // only allow check for today_index within range
        if ($today_index >=1 && $today_index <= $duration_days) {
            $day_num = $today_index;
            // cek apakah sudah ada entry untuk this day
            $c = $conn->prepare("SELECT id FROM checkups WHERE user_id = ? AND challenge_id = ? AND day_number = ?");
            $c->bind_param("iii", $user_id, $challenge_id, $day_num);
            $c->execute();
            $g = $c->get_result();
            $exists = $g->fetch_assoc();
            $c->close();

            if (!$exists) {
                $status = 'done';
                $note = trim($_POST['note'] ?? '');
                $ins = $conn->prepare("INSERT INTO checkups (user_id, challenge_id, day_number, status, note, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                if ($ins) {
                    $ins->bind_param("iiiss", $user_id, $challenge_id, $day_num, $status, $note);
                    if ($ins->execute()) {
                        $action_msg = "✅ Hari ke-$day_num disimpan sebagai DONE.";
                        // update local array
                        $checkups_by_day[$day_num] = ['day_number'=>$day_num,'status'=>$status,'note'=>$note,'created_at'=>date('Y-m-d H:i:s')];
                    } else {
                        $action_msg = "Gagal menyimpan checkup.";
                    }
                    $ins->close();
                } else {
                    $action_msg = "Gagal mempersiapkan query insert checkup.";
                }
            } else {
                $action_msg = "Sudah ada catatan untuk hari ini.";
            }
        } else {
            $action_msg = "Tidak bisa check hari ini (di luar rentang durasi challenge).";
        }
    } elseif ($action === 'remove_last') {
        // Hapus last checkup (berdasarkan day_number DESC, created_at DESC)
        $q = $conn->prepare("SELECT id, day_number FROM checkups WHERE user_id = ? AND challenge_id = ? ORDER BY day_number DESC, created_at DESC LIMIT 1");
        $q->bind_param("ii", $user_id, $challenge_id);
        $q->execute();
        $last = $q->get_result()->fetch_assoc();
        $q->close();
        if ($last) {
            $del = $conn->prepare("DELETE FROM checkups WHERE id = ?");
            if ($del) {
                $del->bind_param("i", $last['id']);
                if ($del->execute()) {
                    $action_msg = "🗑️ Menghapus catatan hari ke-{$last['day_number']}.";
                    unset($checkups_by_day[intval($last['day_number'])]);
                } else {
                    $action_msg = "Gagal menghapus checkup.";
                }
                $del->close();
            } else {
                $action_msg = "Gagal mempersiapkan query delete.";
            }
        } else {
            $action_msg = "Belum ada checkup untuk dihapus.";
        }
    }
}

// Prepare display data for each day
$boxes = [];
for ($i = 1; $i <= $duration_days; $i++) {
    // compute date for day i: startDate + (i-1) days
    $dt = (clone $startDate)->modify('+'.($i-1).' days');
    $day_date_str = $dt->format('Y-m-d');

    if (isset($checkups_by_day[$i])) {
        $status = $checkups_by_day[$i]['status'];
        $note = $checkups_by_day[$i]['note'] ?? '';
        $created_at = $checkups_by_day[$i]['created_at'] ?? '';
    } else {
        // if the day is in the past relative to today => treat as miss for display (but not inserted)
        if ($dt < $todayDate) {
            $status = 'miss';
            $note = '';
            $created_at = '';
        } else {
            $status = 'pending';
            $note = '';
            $created_at = '';
        }
    }

    $boxes[] = [
        'day' => $i,
        'date' => $day_date_str,
        'status' => $status,
        'note' => $note,
        'created_at' => $created_at
    ];
}

// find if there's any checkup at all (for showing undo button)
$has_checkups = count($checkups_by_day) > 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Checkup - <?= htmlspecialchars($challenge['title']) ?></title>
<style>
  :root{--bg1:#f6d365;--bg2:#fda085;--card:#fff;--green:#2ecc71;--red:#e74c3c;--muted:#cfd8dc}
  *{box-sizing:border-box;font-family:Inter, system-ui, Arial, sans-serif}
  body{margin:0;min-height:100vh;background:linear-gradient(135deg,var(--bg1),var(--bg2));padding:20px;color:#222}
  .wrap{max-width:920px;margin:0 auto}
  .card{background:var(--card);border-radius:14px;padding:20px;box-shadow:0 8px 30px rgba(0,0,0,0.12)}
  header{display:flex;justify-content:space-between;align-items:center;gap:10px}
  h1{margin:0;font-size:1.2rem;color:#333}
  p.sub{margin:6px 0;color:#555}
  .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(72px,1fr));gap:12px;margin-top:16px}
  .box{background:var(--muted);border-radius:10px;padding:12px;text-align:center;color:#fff;min-height:72px;display:flex;flex-direction:column;justify-content:center}
  .box.pending{background:#b0bec5;color:#37474f}
  .box.done{background:var(--green)}
  .box.miss{background:var(--red)}
  .box .day{font-weight:700;font-size:1rem}
  .box .date{font-size:0.78rem;opacity:0.95}
  .actions{display:flex;gap:12px;margin-top:18px;flex-wrap:wrap}
  button.btn{padding:10px 14px;border-radius:10px;border:none;cursor:pointer;font-weight:700}
  .btn-check{background:#2e7d32;color:#fff}
  .btn-remove{background:#c62828;color:#fff}
  .btn-disabled{opacity:0.6;cursor:not-allowed}
  .note-area{margin-top:12px}
  textarea.note{width:100%;min-height:70px;padding:10px;border-radius:10px;border:1px solid #ddd}
  .list{margin-top:18px}
  .list li{background:#fafafa;padding:10px;border-radius:8px;margin-bottom:8px;box-shadow:0 2px 6px rgba(0,0,0,0.04)}
  .msg{margin-top:10px;padding:10px;border-radius:8px;background:#eef9f0;color:#065b09}
  .small{font-size:0.85rem;color:#444}
  @media (max-width:600px){
    .grid{grid-template-columns:repeat(4,1fr)}
  }
  @media (max-width:420px){
    .grid{grid-template-columns:repeat(3,1fr)}
  }
</style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <header>
        <div>
          <h1>Checkup untuk: <?= htmlspecialchars($challenge['title']) ?></h1>
          <p class="sub">Durasi: <?= $duration_days ?> hari • Joined: <?= htmlspecialchars(date('d M Y', strtotime($participant['joined_at']))) ?></p>
        </div>
        <div class="small">
          Hari ini: <?= htmlspecialchars(date('d M Y')) ?><br>
          Hari ke: <?= ($today_index>=1 && $today_index <= $duration_days) ? $today_index : '-' ?>
        </div>
      </header>

      <?php if ($action_msg): ?>
        <div class="msg"><?= htmlspecialchars($action_msg) ?></div>
      <?php endif; ?>

      <!-- Grid kotak -->
      <div class="grid" aria-label="Progress boxes">
        <?php foreach ($boxes as $b): 
            $cls = $b['status']; // done / miss / pending
        ?>
          <div class="box <?= $cls ?>">
            <div class="day">Hari <?= $b['day'] ?></div>
            <div class="date"><?= date('d M', strtotime($b['date'])) ?></div>
            <?php if ($b['status'] === 'done'): ?>
              <div style="margin-top:6px;font-weight:700">✅</div>
            <?php elseif ($b['status'] === 'miss'): ?>
              <div style="margin-top:6px;font-weight:700">❌</div>
            <?php else: ?>
              <div style="margin-top:6px;font-weight:700">—</div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Actions: check today's box / remove last -->
      <div class="actions">
        <?php
          $can_check = ($today_index >=1 && $today_index <= $duration_days) && !isset($checkups_by_day[$today_index]) && (new DateTime(date('Y-m-d')) >= (new DateTime(date('Y-m-d', strtotime($participant['joined_at'])))));
        ?>
        <form method="POST" style="display:inline;">
          <input type="hidden" name="action" value="check">
          <input type="hidden" name="note" id="note_input" value="">
          <button type="submit" class="btn btn-check <?= $can_check ? '' : 'btn-disabled' ?>" <?= $can_check? '' : 'disabled' ?>>
            ✅ Check Hari Ini
          </button>
        </form>

        <form method="POST" onsubmit="return confirm('Yakin ingin menghapus catatan terakhir?');" style="display:inline;">
          <input type="hidden" name="action" value="remove_last">
          <button type="submit" class="btn btn-remove" <?= $has_checkups ? '' : 'disabled' ?>>🗑️ Undo Last</button>
        </form>

        <div style="flex:1"></div>
        <a href="my_challenges.php" class="small" style="color:#555;text-decoration:none">← Kembali ke My Challenges</a>
      </div>

      <!-- Optional note before checking -->
      <div class="note-area">
        <label class="small">Catatan singkat (opsional):</label>
        <textarea class="note" id="note" placeholder="Tulis catatan singkat untuk hari ini (opsional)"></textarea>
      </div>

      <!-- Show history -->
      <div class="list">
        <h3 style="margin-top:14px;margin-bottom:8px">Riwayat Checkup</h3>
        <?php if (count($checkups) === 0): ?>
          <p class="small">Belum ada riwayat.</p>
        <?php else: ?>
          <ul style="padding-left:0;list-style:none;margin:0">
            <?php foreach ($checkups as $c): ?>
              <li>
                <div style="display:flex;justify-content:space-between;align-items:center">
                  <div>
                    <strong>Hari <?= intval($c['day_number']) ?></strong>
                    <div class="small"><?= htmlspecialchars($c['status']) ?> • <?= htmlspecialchars($c['created_at']) ?></div>
                    <?php if (!empty($c['note'])): ?>
                      <div class="small" style="margin-top:6px">Note: <?= htmlspecialchars($c['note']) ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>

<script>
  // Broadcast note content to hidden input before submitting 'check' form
  const noteArea = document.getElementById('note');
  const noteInput = document.getElementById('note_input');

  // Find 'check' form submit button and intercept to attach note
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
      const action = form.querySelector('input[name="action"]')?.value;
      if (action === 'check') {
        // Put note content into hidden input
        noteInput.value = noteArea.value.trim();
      }
    });
  });
</script>
</body>
</html>
