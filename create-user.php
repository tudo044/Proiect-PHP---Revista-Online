<?php
session_start();
require_once 'model/Database.php';

// --- 1. GENERARE CSRF TOKEN (Protectie) ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mesaj_eroare = '';
$mesaj_succes = '';


$recaptcha_site_key = '6LdEtTEsAAAAAHqMWZAGJYB-1pKz1uQYOBX0jG5O'; 
$recaptcha_secret_key = '6LdEtTEsAAAAAB-cvxNro1p-m45HIZtYrf7rPpJK';

if (isset($_POST['submit'])) {
    
    // --- 2. VERIFICARE CSRF ---
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Eroare de securitate (CSRF)! Cererea a fost blocată.");
    }

    // --- 3. VERIFICARE RECAPTCHA ---
    if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
        // Trimitem cererea la Google pentru verificare
        $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$recaptcha_secret_key.'&response='.$_POST['g-recaptcha-response']);
        $responseData = json_decode($verifyResponse);
        
        if (!$responseData->success) {
            $mesaj_eroare = "❌ Validarea 'Nu sunt robot' a eșuat. Mai încearcă.";
        }
    } else {
        $mesaj_eroare = "❌ Te rog bifează căsuța 'Nu sunt robot'.";
    }

    // Continuam doar daca nu avem erori
    if (empty($mesaj_eroare)) {
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
                
                // Verificam daca mailul exista
                $stmt_check = $pdo->prepare("SELECT id FROM user WHERE email = :email");
                $stmt_check->execute(['email' => $email]);
                
                if ($stmt_check->fetch()) {
                    $mesaj_eroare = "❌ Există deja un cont cu acest email!";
                } else {
                    // Inseram userul nou (Default: cititor)
                    $sql = "INSERT INTO user (name, email, password, rol) VALUES (:username, :email, :password, 'cititor')";
                    $stmt = $pdo->prepare($sql);
                    
                    $stmt->execute([
                        'username' => htmlspecialchars($nume),
                        'email'    => $email,
                        'password' => password_hash($parola, PASSWORD_BCRYPT)
                    ]);
                    
                    $mesaj_succes = "✅ Cont creat cu succes! Te poți <a href='login-user.php'>autentifica acum</a>.";
                }

            } catch (PDOException $e) {
                $mesaj_eroare = "❌ Eroare tehnică: " . $e->getMessage();
            }
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
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body class="bg-light">
    <div class="container mt-5" style="max-width: 500px;">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="mb-4 text-center">Creează Cont Nou</h2>
                
                <form action="create-user.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div class="mb-3">
                        <label class="form-label">Nume utilizator</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Parolă</label>
                        <input type="password" class="form-control" name="password" minlength="8" required>
                        <div class="form-text">Minim 8 caractere.</div>
                    </div>
                    
                    <div class="mb-3 d-flex justify-content-center">
                        <div class="g-recaptcha" data-sitekey="<?php echo $recaptcha_site_key; ?>"></div>
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