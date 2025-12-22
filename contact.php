<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// 1. GENERARE CSRF TOKEN (Protectie Securitate)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mesaj_trimis = ''; 


$recaptcha_site_key = '6LdEtTEsAAAAAHqMWZAGJYB-1pKz1uQYOBX0jG5O'; 
$recaptcha_secret_key = '6LdEtTEsAAAAAB-cvxNro1p-m45HIZtYrf7rPpJK';

if (isset($_POST['submit'])) {

    // 2. VERIFICARE CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mesaj_trimis = "<div class='alert alert-danger'>Eroare de securitate (CSRF). Te rog reîncearcă.</div>";
    }
    // 3. VERIFICARE RECAPTCHA 
    else {
        $captcha_valid = false;
        if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
            $verify = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$recaptcha_secret_key.'&response='.$_POST['g-recaptcha-response']);
            $response_data = json_decode($verify);
            if ($response_data->success) {
                $captcha_valid = true;
            }
        }

        if (!$captcha_valid) {
            $mesaj_trimis = "<div class='alert alert-danger'>❌ Te rog bifează căsuța 'Nu sunt robot'.</div>";
        } 
        else {
           
            
            $email_tau = "andreitudorache68@gmail.com"; 
            $parola_aplicatie = "xkie nsin kxaq aeyy"; 

            $nume_expeditor = $_POST['nume'];
            $email_expeditor = $_POST['email'];
            $subiect = $_POST['subiect'];
            $mesaj = $_POST['mesaj'];

            $mail = new PHPMailer(true); 

            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = $email_tau;
                $mail->Password   = $parola_aplicatie;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;

                $mail->setFrom($email_tau, 'Formular Contact Site'); 
                $mail->addAddress($email_tau, 'Admin Revista Online'); 
                $mail->addReplyTo($email_expeditor, $nume_expeditor); 

                $mail->isHTML(true);
                $mail->Subject = "Contact Site: " . htmlspecialchars($subiect);
                
                $mail->Body    = "Ai primit un mesaj nou de pe site:<br><br>" .
                                 "<b>De la:</b> " . htmlspecialchars($nume_expeditor) . "<br>" .
                                 "<b>Email:</b> " . htmlspecialchars($email_expeditor) . "<br><br>" .
                                 "<b>Mesaj:</b><br>" . nl2br(htmlspecialchars($mesaj));
                $mail->AltBody = "De la: $nume_expeditor ($email_expeditor)\n\nMesaj:\n$mesaj";

                $mail->send();
                $mesaj_trimis = "<div class='alert alert-success'>✅ Mesajul tău a fost trimis cu succes!</div>";
            } catch (Exception $e) {
                $mesaj_trimis = "<div class='alert alert-danger'>❌ Mesajul nu a putut fi trimis. Eroare: {$mail->ErrorInfo}</div>";
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
    <title>Contact - Revista Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <div class="container mt-5" style="max-width: 600px;">
        <a href="index.php" class="btn btn-secondary mb-3">← Înapoi la prima pagină</a>
        <h1 class="mb-4">Formular de Contact</h1>
        <p>Ai o întrebare sau un feedback? Trimite-ne un mesaj!</p>

        <form action="contact.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="mb-3">
                <label for="nume" class="form-label">Numele tău</label>
                <input type="text" class="form-control" id="nume" name="nume" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Adresa ta de email</label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="<?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : ''; ?>" required>
            </div>
            <div class="mb-3">
                <label for="subiect" class="form-label">Subiect</label>
                <input type="text" class="form-control" id="subiect" name="subiect" required>
            </div>
            <div class="mb-3">
                <label for="mesaj" class="form-label">Mesajul tău</label>
                <textarea class="form-control" id="mesaj" name="mesaj" rows="5" required></textarea>
            </div>

            <div class="mb-4 d-flex justify-content-center">
                <div class="g-recaptcha" data-sitekey="<?php echo $recaptcha_site_key; ?>"></div>
            </div>

            <button type="submit" name="submit" class="btn btn-primary w-100">Trimite Mesajul</button>
        </form>
        
        <div class="mt-4">
            <?php echo $mesaj_trimis; ?>
        </div>
    </div>
</body>
</html>