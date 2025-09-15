<?php
session_start();
include "includes/db.php";

// pastikan user login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ambil daftar challenge yang diikuti user
$sql = "SELECT c.id, c.title, c.description, c.created_at, u.username
        FROM challenge_participants cp
        JOIN challenges c ON cp.challenge_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE cp.user_id = ?
        ORDER BY cp.joined_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Challenges - Questly</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * { margin:0; padding:0; box-sizing:border-box; font-family: 'Poppins', sans-serif; }

    body {
      background: linear-gradient(135deg, #a18cd1, #fbc2eb);
      min-height: 100vh;
      padding: 20px;
      color: #333;
    }

    .container { max-width: 800px; margin: 0 auto; }

    .header {
      display: flex; justify-content: space-between; align-items: center;
      margin-bottom: 25px; padding: 15px 20px;
      background: rgba(255,255,255,0.9); border-radius: 15px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1); flex-wrap: wrap;
    }
    .header a, .header button {
      color: #fff; background: #6a5acd; padding: 8px 15px;
      border-radius: 8px; border: none; text-decoration: none;
      font-weight: 500; cursor: pointer; transition: 0.3s;
    }
    .header a:hover, .header button:hover { background: #483d8b; }

    h1 {
      text-align: center; margin-bottom: 20px;
      font-size: 2.2rem; color: #fff;
      text-shadow: 2px 2px 8px rgba(0,0,0,0.2);
    }

    ul { list-style: none; }
    li {
      background: #fff; padding: 15px 20px; border-radius: 12px;
      margin-bottom: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      transition: 0.3s;
    }
    li:hover { transform: scale(1.02); }
    li a { font-size: 1.2rem; font-weight: bold; color: #6a5acd; text-decoration: none; }
    li a:hover { text-decoration: underline; }
    small { display: block; margin-top: 5px; color: #777; }

    .btn-checkup {
      display: inline-block; margin-top: 10px; padding: 8px 15px;
      background: #ff6f61; color: #fff; text-decoration: none;
      border-radius: 8px; font-weight: bold; transition: 0.3s;
    }
    .btn-checkup:hover { background: #e65b50; }

    @media (max-width: 600px) {
      h1 { font-size: 1.6rem; }
      .header { flex-direction: column; gap: 10px; }
      li { padding: 12px 15px; }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <div>Hai, <b><?php echo htmlspecialchars($_SESSION['username']); ?></b></div>
      <form action="logout.php" method="POST" style="display:inline;">
        <button type="submit">Logout</button>
      </form>
    </div>

    <h1>📌 Challenge yang Kamu Ikuti</h1>

    <?php if ($result->num_rows > 0): ?>
      <ul>
        <?php while($row = $result->fetch_assoc()): ?>
          <li>
            <a href="challenge.php?id=<?php echo $row['id']; ?>">
              <?php echo htmlspecialchars($row['title']); ?>
            </a>
            <small>
              oleh: <?php echo htmlspecialchars($row['username']); ?> | 
              <?php echo date("d M Y", strtotime($row['created_at'])); ?>
            </small>
            <p><?php echo substr(htmlspecialchars($row['description']), 0, 120) . "..."; ?></p>
            <a href="checkup.php?id=<?php echo $row['id']; ?>" class="btn-checkup">Check-up Progress</a>
          </li>
        <?php endwhile; ?>
      </ul>
    <?php else: ?>
      <p style="background:#fff; padding:15px; border-radius:12px; text-align:center;">
        Kamu belum mengikuti challenge apapun. 
        <a href="index.php" style="color:#6a5acd; font-weight:bold;">Cari challenge</a> untuk mulai!
      </p>
    <?php endif; ?>
  </div>
</body>
</html>
