<?php
session_start();
require_once 'model/Database.php';

$mesaj_eroare = '';
$mesaj_succes = '';

if (isset($_POST['submit'])) {
    
    $nume = $_POST['username'];
    $email = $_POST['email'];
    $parola = $_POST['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mesaj_eroare = "❌ Adresa de email nu este validă!";
    } 
    elseif (strlen($parola) < 8) {
        $mesaj_eroare = "❌ Parola trebuie să aibă minim 8 caractere!";
    }
    else {
        try {
            $pdo = Database::getInstance()->getConnection();
            
            $stmt_check = $pdo->prepare("SELECT id FROM user WHERE email = :email");
            $stmt_check->execute(['email' => $email]);
            
            if ($stmt_check->fetch()) {
                $mesaj_eroare = "❌ Există deja un cont cu acest email!";
            } else {
                $sql = "INSERT INTO user (name, email, password) 
                        VALUES (:username, :email, :password)";
                
                $stmt = $pdo->prepare($sql);

                $data = [
                    'username' => $nume,
                    'email'    => $email,
                    'password' => password_hash($parola, PASSWORD_BCRYPT)
                ];
                
                $stmt->execute($data);
                $mesaj_succes = "✅ Cont creat cu succes! Te poți <a href='login-user.php'>autentifica acum</a>.";
            }

        } catch (PDOException $e) {
            $mesaj_eroare = "❌ Eroare tehnică: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creare Cont - Revista Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5" style="max-width: 500px;">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="mb-4 text-center">Creează Cont Nou</h2>
                
                <form action="create-user.php" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Nume utilizator</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="nume@exemplu.com" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Parolă</label>
                        <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                        <div class="form-text">Minim 8 caractere.</div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" name="submit" class="btn btn-primary">Creează Cont</button>
                    </div>
                </form>

                <div class="mt-3 text-center">
                    Ai deja cont? <a href="login-user.php">Intră în cont</a>
                </div>

                <div class="mt-4">
                    <?php 
                    if ($mesaj_eroare) echo "<div class='alert alert-danger'>$mesaj_eroare</div>";
                    if ($mesaj_succes) echo "<div class='alert alert-success'>$mesaj_succes</div>";
                    ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>