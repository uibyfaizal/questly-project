<?php
session_start();
include "includes/db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: index.php");
            exit;
        } else {
            $error = "⚠️ Password salah!";
        }
    } else {
        $error = "⚠️ User tidak ditemukan!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Questly</title>
    <style>
        body {
      background: linear-gradient(135deg, #a18cd1, #fbc2eb);
      min-height: 100vh;
      padding: 20px;
      color: #333;
    }
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .card {
            background: #ffffffcc;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            width: 350px;
            text-align: center;
            backdrop-filter: blur(10px);
        }
        h1 {
            margin-bottom: 20px;
            color: #8B5CF6; /* ungu konsisten */
        }
        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 10px;
            border: 1px solid #ddd;
            font-size: 14px;
        }
        input:focus {
            outline: none;
            border-color: #EC4899; /* pink konsisten */
            box-shadow: 0 0 6px #EC4899;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #8B5CF6; /* ungu utama */
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background: #7C3AED; /* ungu lebih gelap */
        }
        .link {
            display: block;
            margin-top: 15px;
            font-size: 14px;
            color: #EC4899; /* pink */
            text-decoration: none;
        }
        .link:hover {
            text-decoration: underline;
        }
        .error {
            background: #FEE2E2;
            color: #DC2626;
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>Login</h1>
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Masuk</button>
        </form>
        <a class="link" href="register.php">Belum punya akun? Daftar</a>
    </div>
</div>
</body>
</html>
