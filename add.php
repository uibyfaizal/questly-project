<?php
// add.php
include "includes/auth.php";   // asumsi file ini mem-start session dan cek login
include "includes/db.php";     // koneksi $conn

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $duration_select = $_POST['duration_select'] ?? '7';
    $custom_duration = intval($_POST['custom_duration'] ?? 0);

    // Tentukan duration_days final
    if ($duration_select === 'custom') {
        $duration_days = max(1, $custom_duration); // minimal 1 hari
    } else {
        $duration_days = intval($duration_select);
        if ($duration_days <= 0) $duration_days = 7;
    }

    $user_id = intval($_SESSION['user_id']);

    if ($title === '' || $description === '') {
        $error = "Judul dan deskripsi tidak boleh kosong.";
    } else {
        $sql = "INSERT INTO challenges (title, description, user_id, duration_days) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            $error = "Gagal mempersiapkan query (add challenge).";
        } else {
            $stmt->bind_param("ssii", $title, $description, $user_id, $duration_days);
            if ($stmt->execute()) {
                header("Location: index.php");
                exit;
            } else {
                $error = "Gagal menyimpan challenge: " . htmlspecialchars($stmt->error);
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Tambah Challenge - Questly</title>
<style>
  :root { --bg1:#a18cd1; --bg2:#fbc2eb; --accent:#ff6f61; --accent-dark:#e65b50; --card:#fff; }
  *{box-sizing:border-box;font-family:Inter, system-ui, Arial, sans-serif}
  body{margin:0;min-height:100vh;background:linear-gradient(135deg,var(--bg1),var(--bg2));display:flex;align-items:center;justify-content:center;padding:20px}
  .card{width:100%;max-width:520px;background:var(--card);border-radius:14px;padding:22px;box-shadow:0 8px 30px rgba(0,0,0,0.12)}
  h1{margin:0 0 10px;font-size:1.4rem;color:#333}
  label{display:block;margin-top:12px;margin-bottom:6px;color:#444;font-weight:600;font-size:0.95rem}
  input[type=text], textarea, input[type=number], select {
    width:100%;padding:10px;border:1px solid #ddd;border-radius:10px;font-size:0.95rem;
  }
  textarea{min-height:120px;resize:vertical}
  .row{display:flex;gap:10px;align-items:center}
  .actions{display:flex;gap:10px;margin-top:16px;justify-content:space-between;flex-wrap:wrap}
  .btn {padding:10px 16px;border-radius:12px;border:none;background:var(--accent);color:#fff;font-weight:700;cursor:pointer}
  .btn:hover{background:var(--accent-dark)}
  .btn.secondary{background:#6a11cb}
  .note {margin-top:10px;color:#666;font-size:0.9rem}
  .error {background:#ffe6e6;color:#8a1b1b;padding:10px;border-radius:8px;margin-top:10px}
  @media (max-width:480px){
    .actions {flex-direction:column}
  }
</style>
</head>
<body>
  <div class="card">
    <h1>Tambah Challenge Baru</h1>

    <?php if (!empty($error)): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="add.php" id="addForm">
      <label for="title">Judul:</label>
      <input type="text" id="title" name="title" required value="<?= htmlspecialchars($title ?? '') ?>">

      <label for="description">Deskripsi:</label>
      <textarea id="description" name="description" required><?= htmlspecialchars($description ?? '') ?></textarea>

      <label for="duration_select">Durasi target:</label>
      <div class="row">
        <select id="duration_select" name="duration_select">
          <option value="7">7 hari</option>
          <option value="14">14 hari</option>
          <option value="21">21 hari</option>
          <option value="30">30 hari</option>
          <option value="custom">Custom...</option>
        </select>
        <input type="number" id="custom_duration" name="custom_duration" min="1" placeholder="Masukkan angka (hari)" style="display:none;width:140px">
      </div>
      <div class="note">Pilih durasi (mis. 7 hari untuk challenge 1 minggu). Pilih "Custom" untuk memasukkan angka sendiri.</div>

      <div class="actions">
        <button type="submit" class="btn">Simpan</button>
        <a href="index.php" class="btn secondary" style="display:inline-block;text-decoration:none;text-align:center;line-height:28px">⬅ Kembali</a>
      </div>
    </form>
  </div>

<script>
  const sel = document.getElementById('duration_select');
  const custom = document.getElementById('custom_duration');

  function toggleCustom(){
    if (sel.value === 'custom') {
      custom.style.display = 'inline-block';
      custom.focus();
    } else {
      custom.style.display = 'none';
      custom.value = '';
    }
  }
  sel.addEventListener('change', toggleCustom);
  toggleCustom();
</script>
</body>
</html>
