<?php

session_start();
require_once 'model/Database.php';


if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Eroare: ID-ul știrii lipsește.");
}

$stire_id = $_GET['id'];
$pdo = Database::getInstance()->getConnection();

try {
   
    if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] != 'admin') {
        die("Acces interzis! Nu aveți permisiunea să ștergeți această știre.");
    }

 
    $sql = "DELETE FROM stiri WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $stire_id]);

    
    header("Location: index.php?status=sters_succes");
    exit;

} catch (PDOException $e) {
    die("Eroare la ștergerea știrii: " . $e->getMessage());
}

?>

