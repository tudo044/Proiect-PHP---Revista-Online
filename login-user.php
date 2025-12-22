<?php
session_start();
require_once 'model/Database.php';

// 1. GENERARE CSRF TOKEN
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mesaj_eroare = '';


$recaptcha_site_key = '6LdEtTEsAAAAAHqMWZAGJYB-1pKz1uQYOBX0jG5O'; 
$recaptcha_secret_key = '6LdEtTEsAAAAAB-cvxNro1p-m45HIZtYrf7rPpJK';

if (isset($_POST['submit'])) {
    
    // 2. VERIFICARE CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Eroare de securitate (CSRF).");
    }

    // 3. VERIFICARE RECAPTCHA
    $captcha_valid = false;
    if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
        $verify = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$recaptcha_secret_key.'&response='.$_POST['g-recaptcha-response']);
        $response_data = json_decode($verify);
        if ($response_data->success) {
            $captcha_valid = true;
        }
    }

    if (!$captcha_valid) {
        $mesaj_eroare = "❌ Te rog bifează căsuța 'Nu sunt robot'.";
    } else {
        // Logica normala de Login
        $email = $_POST['email'];
        $pass  = $_POST['password'];

        try {
            $pdo = Database::getInstance()->getConnection();
            $stmt = $pdo->prepare("SELECT * FROM user WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($pass, $user['password'])) {
                // Login reusit
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_rol'] = $user['rol'];
                
                header("Location: index.php");
                exit;
            } else {
                $mesaj_eroare = "❌ Email sau parolă incorectă.";
            }
        } catch (PDOException $e) {
            $mesaj_eroare = "Eroare: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Autentificare - Revista Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body class="bg-light">
    <div class="container mt-5" style="max-width: 400px;">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h3 class="mb-4 text-center">Autentificare</h3>
                
                <?php if ($mesaj_eroare) echo "<div class='alert alert-danger'>$mesaj_eroare</div>"; ?>

                <form action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Parolă</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="mb-3 d-flex justify-content-center">
                        <div class="g-recaptcha" data-sitekey="<?php echo $recaptcha_site_key; ?>"></div>
                    </div>

                    <button type="submit" name="submit" class="btn btn-primary w-100">Intră în cont</button>
                </form>
                
                <div class="text-center mt-3">
                    <a href="create-user.php">Nu ai cont? Înregistrează-te</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>