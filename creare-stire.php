<?php
session_start();


if (!isset($_SESSION['user_rol']) || ($_SESSION['user_rol'] != 'reporter' && $_SESSION['user_rol'] != 'admin')) {
    die("Acces interzis! Nu aveți permisiunea necesară.");
}

require_once 'model/Database.php';
$pdo = Database::getInstance()->getConnection();
$mesaj_succes = '';
$mesaj_eroare = '';


if (isset($_POST['submit'])) {
    try {
        $cale_imagine = null; 

        // 1. LOGICA DE UPLOAD IMAGINE
        if (isset($_FILES['imagine']) && $_FILES['imagine']['error'] == 0) {
            $extensie = pathinfo($_FILES['imagine']['name'], PATHINFO_EXTENSION);
            // Generam un nume unic ca sa nu se suprascrie
            $nume_fisier = uniqid() . "." . $extensie;
            $destinatie = "uploads/" . $nume_fisier;
            
            // Mutam fisierul
            if (move_uploaded_file($_FILES['imagine']['tmp_name'], $destinatie)) {
                $cale_imagine = $destinatie;
            }
        }

        
        $data_romania = new DateTime("now", new DateTimeZone('Europe/Bucharest'));
        $ora_formatata = $data_romania->format('Y-m-d H:i:s');

        // Observa coloana 'imagine' adaugata
        $sql = "INSERT INTO stiri (titlu, short_description, description, id_categorie, id_autor, data_publicarii, imagine) 
                VALUES (:titlu, :short_desc, :descriere, :id_cat, :id_aut, :data_pub, :img)";
        
        $stmt = $pdo->prepare($sql);

        $data = [
            'titlu' => $_POST['titlu'],
            'short_desc' => $_POST['short_description'],
            'descriere' => $_POST['description'],
            'id_cat' => $_POST['categorie_id'],
            'id_aut' => $_SESSION['user_id'],
            'data_pub' => $ora_formatata,
            'img' => $cale_imagine
        ];
        
        $stmt->execute($data);
        $mesaj_succes = "✅ Știre publicată cu succes!";

    } catch (PDOException $e) {
        $mesaj_eroare = "❌ Publicarea a eșuat: " . $e->getMessage();
    }
}

// Preluam categoriile
try {
    $sql_categorii = "SELECT * FROM categorii";
    $stmt_categorii = $pdo->query($sql_categorii);
    $categorii = $stmt_categorii->fetchAll();
} catch (PDOException $e) {
    die("Eroare: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publicare Știre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5" style="max-width: 800px;">
        <h1 class="mb-4">Scrie o Știre Nouă</h1>
        
        <form action="creare-stire.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="titlu" class="form-label">Titlu Știre</label>
                <input type="text" class="form-control" id="titlu" name="titlu" required>
            </div>
            
            <div class="mb-3">
                <label for="imagine" class="form-label">Imagine Principală (Opțional)</label>
                <input type="file" class="form-control" id="imagine" name="imagine" accept="image/*">
                <div class="form-text">Formate acceptate: JPG, PNG, WEBP.</div>
            </div>
            
            <div class="mb-3">
                <label for="short_description" class="form-label">Sumar</label>
                <textarea class="form-control" id="short_description" name="short_description" rows="2"></textarea>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Conținut Articol</label>
                <textarea class="form-control" id="description" name="description" rows="10" required></textarea>
            </div>

            <div class="mb-3">
                <label for="categorie_id" class="form-label">Categorie</label>
                <select class="form-select" id="categorie_id" name="categorie_id" required>
                    <option value="" disabled selected>Alege o categorie...</option>
                    <?php foreach ($categorii as $cat) { echo "<option value=\"{$cat['id']}\">" . htmlspecialchars($cat['name']) . "</option>"; } ?>
                </select>
            </div>

            <button type="submit" name="submit" class="btn btn-primary">Publică Știrea</button>
        </form>
        
        <?php if ($mesaj_succes) echo "<div class='alert alert-success mt-3'>$mesaj_succes</div>"; ?>
        <?php if ($mesaj_eroare) echo "<div class='alert alert-danger mt-3'>$mesaj_eroare</div>"; ?>
    </div>
</body>
</html>