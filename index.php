<?php
session_start();
include "includes/db.php";

// ambil parameter sort dari query string
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// tentukan query sorting
switch ($sort) {
  case 'oldest':
    $order = "c.created_at ASC";
    break;
  case 'popular':
    $order = "participants DESC";
    break;
  default:
    $order = "c.created_at DESC";
}

// ambil semua challenge dengan jumlah peserta
$sql = "SELECT c.id, c.title, c.created_at, u.username, 
        (SELECT COUNT(*) FROM challenge_participants p WHERE p.challenge_id = c.id) as participants
        FROM challenges c 
        LEFT JOIN users u ON c.user_id = u.id
        ORDER BY $order";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Questly - Community Challenge</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; font-family: 'Poppins', sans-serif; }
    body {
      background: linear-gradient(135deg, #a18cd1, #fbc2eb);
      min-height: 100vh;
      padding: 20px;
      color: #333;
    }
    .container { max-width: 900px; margin: 0 auto; }
    .header {
      display: flex; justify-content: space-between; align-items: center;
      margin-bottom: 25px; padding: 15px 20px;
      background: rgba(255,255,255,0.9); border-radius: 15px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      flex-wrap: wrap;
    }
    .header a, .header button {
      color: #fff; background: #6a5acd;
      padding: 8px 15px; border-radius: 8px;
      border: none; text-decoration: none; font-weight: 500;
      cursor: pointer; transition: 0.3s; margin-left: 5px;
    }
    .header a:hover, .header button:hover { background: #483d8b; }
    h1 {
      text-align: center; margin-bottom: 20px; font-size: 2.5rem;
      color: #fff; text-shadow: 2px 2px 8px rgba(0,0,0,0.2);
    }
    .add-btn {
      display: inline-block; margin-bottom: 20px;
      padding: 10px 20px; background: #ff6f61; color: #fff;
      text-decoration: none; border-radius: 10px; font-weight: bold;
      transition: 0.3s;
    }
    .add-btn:hover { background: #e65b50; }
    .sort-box { margin-bottom: 20px; text-align: right; }
    .sort-box select {
      padding: 8px 12px; border-radius: 8px; border: 1px solid #ccc; cursor: pointer;
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
    @media(max-width:600px){
      h1 { font-size: 1.8rem; }
      .header { flex-direction: column; align-items: flex-start; }
      .sort-box { text-align: left; }
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Header -->
    <div class="header">
      <?php if (isset($_SESSION['user_id'])): ?>
        <div>Hai, <b><?php echo htmlspecialchars($_SESSION['username']); ?></b></div>
        <div>
          <a href="profile.php">Profil</a>
          <a href="add.php">+ Challenge</a>
          <form action="logout.php" method="POST" style="display:inline;">
            <button type="submit">Logout</button>
          </form>
        </div>
      <?php else: ?>
        <div>
          <a href="login.php">Login</a>
          <a href="register.php">Register</a>
        </div>
      <?php endif; ?>
    </div>

    <h1>🔥 Questly</h1>

    <!-- Dropdown sorting -->
    <div class="sort-box">
      <form method="GET" action="">
        <label for="sort">Urutkan:</label>
        <select name="sort" id="sort" onchange="this.form.submit()">
          <option value="newest" <?php if($sort=='newest') echo 'selected'; ?>>Terbaru</option>
          <option value="oldest" <?php if($sort=='oldest') echo 'selected'; ?>>Terlama</option>
          <option value="popular" <?php if($sort=='popular') echo 'selected'; ?>>Peserta Terbanyak</option>
        </select>
      </form>
    </div>

    <!-- List Challenge -->
    <ul>
      <?php while($row = $result->fetch_assoc()): ?>
        <li>
          <a href="challenge.php?id=<?php echo $row['id']; ?>">
            <?php echo htmlspecialchars($row['title']); ?>
          </a>
          <small>
            oleh: <?php echo $row['username'] ? htmlspecialchars($row['username']) : "Anon"; ?> | 
            <?php echo date("d M Y", strtotime($row['created_at'])); ?> | 
            👥 <?php echo $row['participants']; ?> peserta
          </small>
        </li>
      <?php endwhile; ?>
    </ul>
  </div>
</body>
</html>
