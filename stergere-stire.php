<?php
session_start();
require_once 'model/Database.php';

// 1. Verificare Autentificare
if (!isset($_SESSION['user_id'])) {
    die("⛔ Acces interzis! Trebuie să fii autentificat.");
}

// 2. VERIFICARE ROLURI 
// Doar 'admin' are voie sa stearga stiri. 
if ($_SESSION['user_rol'] !== 'admin') {
    die("⛔ ACCES INTERZIS! Doar administratorii pot șterge știri.");
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        $pdo = Database::getInstance()->getConnection();
        
        // Mai intai stergem comentariile asociate stirii (Foreign Key constraint)
        $stmt_com = $pdo->prepare("DELETE FROM comentarii WHERE id_stire = :id");
        $stmt_com->execute(['id' => $id]);

        // Apoi stergem stirea
        $stmt = $pdo->prepare("DELETE FROM stiri WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        // Redirectionare
        header("Location: index.php");
        exit;
    } catch (PDOException $e) {
        die("Eroare la ștergere: " . $e->getMessage());
    }
} else {
    header("Location: index.php");
    exit;
}
?>