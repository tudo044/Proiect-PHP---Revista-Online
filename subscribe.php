<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require_once 'model/Database.php';


if (isset($_POST['submit_newsletter']) && isset($_POST['email_newsletter'])) {
    
    $email = $_POST['email_newsletter'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: index.php?subscribe=eroare");
        exit;
    }

    try {
        $pdo = Database::getInstance()->getConnection();

      
        $sql_check = "SELECT * FROM newsletter WHERE email = :email";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute(['email' => $email]);
        
        if ($stmt_check->fetch()) {
            header("Location: index.php?subscribe=exista");
            exit;
        }

       
        $sql_insert = "INSERT INTO newsletter (email) VALUES (:email)";
        $stmt_insert = $pdo->prepare($sql_insert);
        $stmt_insert->execute(['email' => $email]);

      
        
      
        $email_tau = "andreitudorache68@gmail.com"; 
        $parola_aplicatie = "xkie nsin kxaq aeyy"; 
       
        
        $mail = new PHPMailer(true);

        
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $email_tau;
        $mail->Password   = $parola_aplicatie;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';

      
        $mail->setFrom($email_tau, 'Revista Online');
        $mail->addAddress($email); 

        
        $mail->isHTML(true);
        $mail->Subject = 'Confirmare abonare la newsletter';
        $mail->Body    = "Vă mulțumim pentru abonarea la newsletter-ul nostru! <br><br>Veți primi cele mai noi știri de pe site-ul Revista Online.";
        $mail->AltBody = "Vă mulțumim pentru abonarea la newsletter-ul nostru! Veți primi cele mai noi știri de pe site-ul Revista Online.";

        $mail->send();

      
        header("Location: index.php?subscribe=succes");
        exit;

    } catch (Exception $e) {
        
        if (!isset($mail) || !$mail->isError()) {
             header("Location: index.php?subscribe=eroare_bd");
        } else {
           
            error_log("PHPMailer error: " . $e->getMessage());
            header("Location: index.php?subscribe=succes");
            exit;
        }
    }

} else {
    header("Location: index.php");
    exit;
}
?>