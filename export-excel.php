<?php
session_start();
require_once 'model/Database.php';

// Verificam daca e admin
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    die("⛔ Acces Interzis! Doar administratorii pot exporta date.");
}

// Configurare Header pentru a forta descarcarea ca fisier Excel (.xls)
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=Raport_Complet_Stiri_" . date('Y-m-d_H-i') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Selectam absolut TOATE informatiile relevante
    $sql = "SELECT stiri.id, 
                   stiri.titlu, 
                   stiri.short_description, 
                   stiri.description, 
                   stiri.data_publicarii, 
                   categorii.name as categorie, 
                   user.name as autor 
            FROM stiri 
            LEFT JOIN categorii ON stiri.id_categorie = categorii.id
            LEFT JOIN user ON stiri.id_autor = user.id
            ORDER BY stiri.data_publicarii DESC";
            
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Incepem tabelul HTML (Excel il va interpreta ca celule)
    echo "<meta charset='UTF-8'>";
    echo "<table border='1'>";
    
    // HEADER-UL TABELULUI
    echo "<tr style='background-color:#4CAF50; color:white; font-weight:bold;'>
            <th>ID</th>
            <th>Titlu Știre</th>
            <th>Categorie</th>
            <th>Autor</th>
            <th>Data Publicării</th>
            <th>Rezumat (Short)</th>
            <th>Conținut Complet (Full Text)</th>
          </tr>";

    
    foreach ($data as $row) {
        // Functie helper pentru a curata textul de HTML si a-l formata pt Excel
        $titlu_clean = mb_convert_encoding($row['titlu'], 'UTF-16LE', 'UTF-8');
        
        // La descriere scoatem tag-urile HTML (<p>, <br>) ca sa arate bine in celula
        $short_desc_clean = strip_tags($row['short_description']); 
        $full_desc_clean = strip_tags($row['description']);

        echo "<tr valign='top'>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['titlu'] . "</td>";
        echo "<td>" . $row['categorie'] . "</td>";
        echo "<td>" . $row['autor'] . "</td>";
        echo "<td>" . $row['data_publicarii'] . "</td>";
        
        // Celulele cu text mult
        echo "<td style='width:300px;'>" . $short_desc_clean . "</td>";
        echo "<td style='width:600px;'>" . $full_desc_clean . "</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "Eroare la export: " . $e->getMessage();
}
?>