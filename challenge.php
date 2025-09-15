<?php
include "includes/auth.php";
include "includes/db.php";

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$challenge_id = $_GET['id'];

// ambil info challenge
$sql = "SELECT c.id, c.title, c.description, u.username, c.created_at 
        FROM challenges c 
        JOIN users u ON c.user_id = u.id
        WHERE c.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $challenge_id);
$stmt->execute();
$result = $stmt->get_result();
$challenge = $result->fetch_assoc();

if (!$challenge) {
    echo "Challenge tidak ditemukan!";
    exit;
}

$already_joined = false;

// kalau user klik "Ikuti Challenge"
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['apply_challenge'])) {
    $user_id = $_SESSION['user_id'];

    // cek apakah sudah ikut
    $check = $conn->prepare("SELECT * FROM challenge_participants WHERE user_id=? AND challenge_id=?");
    $check->bind_param("ii", $user_id, $challenge_id);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows == 0) {
        $insert = $conn->prepare("INSERT INTO challenge_participants (user_id, challenge_id) VALUES (?, ?)");
        $insert->bind_param("ii", $user_id, $challenge_id);
        $insert->execute();
    }

    $already_joined = true;
} else {
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $check = $conn->prepare("SELECT * FROM challenge_participants WHERE user_id=? AND challenge_id=?");
        $check->bind_param("ii", $user_id, $challenge_id);
        $check->execute();
        $res = $check->get_result();
        if ($res->num_rows > 0) {
            $already_joined = true;
        }
    }
}

// ================= KOMENTAR ==================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_comment'])) {
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $comment = trim($_POST['comment']);
        if ($comment !== "") {
            $stmt = $conn->prepare("INSERT INTO comments (challenge_id, user_id, comment) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $challenge_id, $user_id, $comment);
            $stmt->execute();
        }
    }
}

// ambil komentar
$comments = [];
$stmt = $conn->prepare("SELECT c.comment, c.created_at, u.username 
                        FROM comments c 
                        JOIN users u ON c.user_id = u.id 
                        WHERE c.challenge_id=? 
                        ORDER BY c.created_at DESC");
$stmt->bind_param("i", $challenge_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $comments[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($challenge['title']) ?> - Questly</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * { margin:0; padding:0; box-sizing:border-box; font-family: 'Poppins', sans-serif; }

    body {
      background: linear-gradient(135deg, #a18cd1, #fbc2eb);
      min-height: 100vh;
      padding: 20px;
      color: #333;
    }

    .container {
      max-width: 800px;
      margin: 0 auto;
    }

    /* Header / Navbar */
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      padding: 15px 20px;
      background: rgba(255,255,255,0.9);
      border-radius: 15px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .header a, .header button {
      color: #fff;
      background: #6a5acd;
      padding: 8px 15px;
      border-radius: 8px;
      border: none;
      text-decoration: none;
      font-weight: 500;
      cursor: pointer;
      transition: 0.3s;
    }
    .header a:hover, .header button:hover {
      background: #483d8b;
    }

    h1 {
      margin-bottom: 15px;
      font-size: 2rem;
      color: #fff;
      text-align: center;
      text-shadow: 2px 2px 8px rgba(0,0,0,0.2);
    }

    .card {
      background: #fff;
      padding: 20px;
      border-radius: 15px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      margin-bottom: 20px;
    }

    .btn {
      display: inline-block;
      padding: 10px 20px;
      background: #ff6f61;
      color: #fff;
      border: none;
      border-radius: 10px;
      font-weight: bold;
      cursor: pointer;
      text-decoration: none;
      transition: 0.3s;
    }
    .btn:hover { background: #e65b50; }

    .comment-form textarea {
      width: 100%;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
      margin-bottom: 10px;
      resize: vertical;
      font-size: 1rem;
    }

    .comments ul { list-style: none; margin-top: 10px; }
    .comments li {
      background: #f9f9f9;
      padding: 10px 15px;
      border-radius: 8px;
      margin-bottom: 10px;
    }
    .comments strong { color: #6a5acd; }

    @media (max-width: 600px) {
      h1 { font-size: 1.5rem; }
      .card { padding: 15px; }
      .btn { width: 100%; text-align: center; }
    }
  </style>
</head>
<body>
  <div class="container">

    <!-- Header -->
    <div class="header">
      <?php if (isset($_SESSION['user_id'])): ?>
        <div>
          Hai, <b><?= htmlspecialchars($_SESSION['username']); ?></b>
        </div>
        <form action="logout.php" method="POST" style="display:inline;">
          <button type="submit">Logout</button>
        </form>
      <?php else: ?>
        <div>
          <a href="login.php">Login</a>
          <a href="register.php">Register</a>
        </div>
      <?php endif; ?>
    </div>

    <h1><?= htmlspecialchars($challenge['title']) ?></h1>

    <!-- Card detail challenge -->
    <div class="card">
      <p><?= nl2br(htmlspecialchars($challenge['description'])) ?></p>
      <small>Dibuat oleh: <b><?= htmlspecialchars($challenge['username']) ?></b> | <?= $challenge['created_at'] ?></small>
      <br><br>

      <?php if (isset($_SESSION['user_id'])): ?>
        <?php if ($already_joined): ?>
          <p>Kamu sudah mengikuti challenge ini ✅</p>
          <a href="my_challenges.php" class="btn">Lihat My Challenges</a>
        <?php else: ?>
          <form method="post">
            <button type="submit" name="apply_challenge" class="btn">Ikuti Challenge</button>
          </form>
        <?php endif; ?>
      <?php else: ?>
        <p><a href="login.php">Login</a> untuk mengikuti challenge ini.</p>
      <?php endif; ?>
    </div>

    <!-- Bagian komentar -->
    <div class="card comments">
      <h2>Komentar</h2>
      <?php if (isset($_SESSION['user_id'])): ?>
        <form method="post" class="comment-form">
          <textarea name="comment" rows="3" placeholder="Tulis komentar..."></textarea>
          <button type="submit" name="add_comment" class="btn">Kirim</button>
        </form>
      <?php else: ?>
        <p><a href="login.php">Login</a> untuk berkomentar.</p>
      <?php endif; ?>

      <ul>
        <?php foreach ($comments as $c): ?>
          <li>
            <strong><?= htmlspecialchars($c['username']) ?>:</strong>
            <?= htmlspecialchars($c['comment']) ?><br>
            <em><?= $c['created_at'] ?></em>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <a href="index.php" class="btn">← Kembali</a>
  </div>
</body>
</html>
