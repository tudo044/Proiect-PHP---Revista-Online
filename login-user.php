<?php

session_start();
require_once 'model/Database.php';

$mesaj_eroare = ''; 


if (isset($_POST['submit'])) {
    
    try {
        $pdo = Database::getInstance()->getConnection();

        $sql = "SELECT * FROM user WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['email' => $_POST['email']]);
        $user = $stmt->fetch();

       
        if ($user && password_verify($_POST['password'], $user['password'])) {
            
          
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_rol'] = $user['rol'];
            $_SESSION['user_email'] = $user['email'];
          
            header("Location: index.php");
            exit; 

        } else {
          
            $mesaj_eroare = "❌ Email sau parolă invalidă.";
        }
    } catch (PDOException $e) {
        $mesaj_eroare = "❌ Eroare la autentificare: " . $e->getMessage();
    }
}


?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autentificare - Revista Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5" style="max-width: 500px;">
        <h1 class="mb-4">Autentificare</h1>
        <form action="login-user.php" method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Parolă</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" name="submit" class="btn btn-primary">Login</button>
        </form>

        <div class="mt-4">
            <?php
           
            if (!empty($mesaj_eroare)) {
                echo "<div class='alert alert-danger'>$mesaj_eroare</div>";
            }
            ?>
        </div>
    </div>
</body>
</html>