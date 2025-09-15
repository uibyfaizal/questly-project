<?php
// profile.php
session_start();
include "includes/db.php";

// Pastikan user login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);

// Ambil data user (aman dengan pengecekan prepare/get_result)
$user = [
    'username' => '-',
    'email' => '-',
    'created_at' => null
];

$sql = "SELECT username, email, created_at FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
if ($stmt !== false) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            $user = $row;
        }
    }
    $stmt->close();
}

// jumlah challenge yang dibuat
$created_count = 0;
$sql_created = "SELECT COUNT(*) as total FROM challenges WHERE user_id = ?";
$stmt2 = $conn->prepare($sql_created);
if ($stmt2 !== false) {
    $stmt2->bind_param("i", $user_id);
    if ($stmt2->execute()) {
        $res2 = $stmt2->get_result();
        if ($res2 && $r2 = $res2->fetch_assoc()) {
            $created_count = intval($r2['total']);
        }
    }
    $stmt2->close();
}

// jumlah challenge yang diikuti
$joined_count = 0;
$sql_joined = "SELECT COUNT(*) as total FROM challenge_participants WHERE user_id = ?";
$stmt3 = $conn->prepare($sql_joined);
if ($stmt3 !== false) {
    $stmt3->bind_param("i", $user_id);
    if ($stmt3->execute()) {
        $res3 = $stmt3->get_result();
        if ($res3 && $r3 = $res3->fetch_assoc()) {
            $joined_count = intval($r3['total']);
        }
    }
    $stmt3->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Profil Saya - Questly</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <style>
    * { margin:0; padding:0; box-sizing:border-box; font-family: 'Poppins', system-ui, Arial, sans-serif; }
    body {
      background: linear-gradient(135deg, #89f7fe, #66a6ff);
      min-height: 100vh;
      padding: 20px;
      color: #333;
    }
    .container { max-width: 700px; margin: 0 auto; }
    .card {
      background: #fff; padding: 25px; border-radius: 15px;
      box-shadow: 0 6px 14px rgba(0,0,0,0.1);
    }
    h1 {
      text-align: center; margin-bottom: 20px; color: #444;
    }
    .info { margin-bottom: 12px; font-size: 1.05rem; display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;}
    .info .label { color:#666; font-weight:600; min-width:140px; }
    .info .value { color:#222; font-weight:700; }
    .summary { display:flex; gap:12px; margin-top:12px; flex-wrap:wrap; }
    .pill {
      background: linear-gradient(90deg,#6a5acd,#8a6be6);
      color:#fff; padding:10px 14px; border-radius:12px; font-weight:700;
      box-shadow: 0 4px 12px rgba(106,90,205,0.18);
      min-width:120px; text-align:center;
    }
    .actions {
      text-align: center; margin-top: 20px;
    }
    .actions a, .actions form button {
      background: #6a5acd; color: #fff;
      padding: 10px 18px; border-radius: 8px;
      text-decoration: none; margin: 6px; display:inline-block; border: none;
      transition: 0.3s; cursor: pointer;
    }
    .actions a:hover, .actions form button:hover { background: #483d8b; }
    @media(max-width:600px){
      .info { font-size: 0.98rem; }
      h1 { font-size: 1.6rem; }
      .pill { min-width:100px; padding:8px 10px; font-size:0.95rem; }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <h1>👤 Profil Saya</h1>

      <div class="info">
        <div class="label">Username</div>
        <div class="value"><?= htmlspecialchars($user['username'] ?? '-') ?></div>
      </div>

      <div class="info">
        <div class="label">Email</div>
        <div class="value"><?= htmlspecialchars($user['email'] ?? '-') ?></div>
      </div>

      <div class="info">
        <div class="label">Bergabung</div>
        <div class="value">
          <?php
            if (!empty($user['created_at'])) {
                echo htmlspecialchars(date("d M Y", strtotime($user['created_at'])));
            } else {
                echo '-';
            }
          ?>
        </div>
      </div>

      <div class="summary" aria-hidden="false" style="margin-top:18px;">
        <div class="pill">Dibuat: <?= $created_count ?></div>
        <div class="pill">Diikuti: <?= $joined_count ?></div>
      </div>

      <div class="actions">
        <a href="index.php">⬅ Kembali</a>
        <a href="edit_profile.php">✏ Edit Profil</a>
        <!-- contoh logout form -->
        <form action="logout.php" method="POST" style="display:inline;">
          <button type="submit">Logout</button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
