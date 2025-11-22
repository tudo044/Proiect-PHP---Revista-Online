<?php
session_start();
require_once 'model/Database.php';


if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Eroare: ID-ul știrii lipsește.");
}

$stire_id = $_GET['id'];
$pdo = Database::getInstance()->getConnection();
$mesaj_succes = '';
$mesaj_eroare = '';


try {
   
    $sql_stire = "SELECT * FROM stiri WHERE id = :id";
    $stmt_stire = $pdo->prepare($sql_stire);
    $stmt_stire->execute(['id' => $stire_id]);
    $stire = $stmt_stire->fetch();

    if (!$stire) {
        die("Știrea nu a fost găsită.");
    }

   
    if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] != 'admin' && $_SESSION['user_id'] != $stire['id_autor'])) {
        die("Acces interzis! Nu aveți permisiunea să modificați această știre.");
    }

   
    $sql_categorii = "SELECT * FROM categorii";
    $stmt_categorii = $pdo->query($sql_categorii);
    $categorii = $stmt_categorii->fetchAll();

} catch (PDOException $e) {
    die("Eroare la încărcarea datelor: ". $e->getMessage());
}


if (isset($_POST['submit'])) {
    try {
        $sql_update = "UPDATE stiri SET 
                        titlu = :titlu, 
                        short_description = :short_desc, 
                        description = :descriere, 
                        id_categorie = :id_cat
                       WHERE id = :id_stire";
        
        $stmt_update = $pdo->prepare($sql_update);

        $data = [
            'titlu' => $_POST['titlu'],
            'short_desc' => $_POST['short_description'],
            'descriere' => $_POST['description'],
            'id_cat' => $_POST['categorie_id'],
            'id_stire' => $stire_id
        ];
        
        $stmt_update->execute($data);
        $mesaj_succes = "✅ Știre modificată cu succes!";
        
       
        $stmt_stire->execute(['id' => $stire_id]);
        $stire = $stmt_stire->fetch();

    } catch (PDOException $e) {
        $mesaj_eroare = "❌ Modificarea a eșuat: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificare Știre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5" style="max-width: 800px;">
        <a href="stire-detaliu.php?id=<?php echo $stire_id; ?>" class="btn btn-secondary mb-3">← Înapoi la știre</a>
        <h1 class="mb-4">Modificare Știre</h1>

        <form action="editare-stire.php?id=<?php echo $stire_id; ?>" method="POST">
            <div class="mb-3">
                <label for="titlu" class="form-label">Titlu Știre</label>
                <input type="text" class="form-control" id="titlu" name="titlu" 
                       value="<?php echo htmlspecialchars($stire['titlu']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="short_description" class="form-label">Descriere Scurtă (Sumar)</label>
                <textarea class="form-control" id="short_description" name="short_description" rows="3"><?php echo htmlspecialchars($stire['short_description']); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Conținut Articol</label>
                <textarea class="form-control" id="description" name="description" rows="10" required><?php echo htmlspecialchars($stire['description']); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="categorie_id" class="form-label">Categorie</label>
                <select class="form-select" id="categorie_id" name="categorie_id" required>
                    <option value="" disabled>Alege o categorie...</option>
                    <?php
                    foreach ($categorii as $cat) {
                
                        $selected = ($cat['id'] == $stire['id_categorie']) ? 'selected' : '';
                        echo "<option value=\"{$cat['id']}\" $selected>" . htmlspecialchars($cat['name']) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <button type="submit" name="submit" class="btn btn-primary">Salvează Modificările</button>
        </form>

        <div class="mt-4">
            <?php if ($mesaj_succes): ?>
                <div class="alert alert-success"><?php echo $mesaj_succes; ?></div>
            <?php endif; ?>
            <?php if ($mesaj_eroare): ?>
                <div class="alert alert-danger"><?php echo $mesaj_eroare; ?></div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>   