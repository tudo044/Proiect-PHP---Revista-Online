<?php
session_start();
require_once 'model/Database.php';

// 1. GENERARE CSRF TOKEN
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Eroare: Știrea nu a fost găsită.");
}

$stire_id = $_GET['id'];
$pdo = Database::getInstance()->getConnection();
$mesaj_comentariu = '';

// --- LOGICA STERGERE COMENTARIU (ADMIN/REPORTER) ---
if (isset($_POST['sterge_comentariu_id'])) {
    if (isset($_SESSION['user_rol']) && ($_SESSION['user_rol'] == 'admin' || $_SESSION['user_rol'] == 'reporter')) {
        try {
            $sql_del = "DELETE FROM comentarii WHERE id = :id_com";
            $stmt_del = $pdo->prepare($sql_del);
            $stmt_del->execute(['id_com' => $_POST['sterge_comentariu_id']]);
            header("Location: stire-detaliu.php?id=$stire_id&msg=sters");
            exit;
        } catch (PDOException $e) {
            $mesaj_comentariu = "<div class='alert alert-danger'>Eroare: " . $e->getMessage() . "</div>";
        }
    }
}

// --- LOGICA ADAUGARE COMENTARIU (CU VERIFICARE CSRF) ---
if (isset($_POST['submit_comentariu'])) {
    // A. Verificare CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Eroare de securitate (CSRF). Reîncarcă pagina.");
    }

    // B. Adaugare Comentariu
    if (isset($_SESSION['user_id'])) {
        try {
            $data_romania = new DateTime("now", new DateTimeZone('Europe/Bucharest'));
            $ora_formatata = $data_romania->format('Y-m-d H:i:s');

            $sql_insert = "INSERT INTO comentarii (continut, id_user, id_stire, data_comentariu) 
                           VALUES (:continut, :id_user, :id_stire, :data_com)";
            $stmt_insert = $pdo->prepare($sql_insert);
            
            $stmt_insert->execute([
                'continut' => $_POST['comentariu_text'], // Nota: XSS e tratat la afisare cu htmlspecialchars
                'id_user' => $_SESSION['user_id'],
                'id_stire' => $stire_id,
                'data_com' => $ora_formatata
            ]);
            
            header("Location: stire-detaliu.php?id=$stire_id&comentariu=succes");
            exit;

        } catch (PDOException $e) {
            $mesaj_comentariu = "<div class='alert alert-danger'>Eroare: " . $e->getMessage() . "</div>";
        }
    } else {
        $mesaj_comentariu = "<div class='alert alert-warning'>Trebuie să fii logat pentru a comenta!</div>";
    }
}

// --- PRELUARE DATE STIRE ---
try {
    $sql_stire = "SELECT stiri.*, categorii.name AS nume_categorie, user.name AS nume_autor 
                  FROM stiri
                  LEFT JOIN categorii ON stiri.id_categorie = categorii.id
                  LEFT JOIN user ON stiri.id_autor = user.id
                  WHERE stiri.id = :id_stire";
    $stmt_stire = $pdo->prepare($sql_stire);
    $stmt_stire->execute(['id_stire' => $stire_id]);
    $stire = $stmt_stire->fetch();
    
    if (!$stire) die("Eroare: Știrea nu există.");

    // Preluare Comentarii
    $sql_com = "SELECT comentarii.*, user.name AS nume_comentator
                FROM comentarii
                JOIN user ON comentarii.id_user = user.id
                WHERE comentarii.id_stire = :id_stire
                ORDER BY comentarii.data_comentariu DESC";     
    $stmt_com = $pdo->prepare($sql_com);
    $stmt_com->execute(['id_stire' => $stire_id]);
    $comentarii = $stmt_com->fetchAll();
    
} catch (PDOException $e) {
    die("Eroare BD: " . $e->getMessage());
}

if (isset($_GET['comentariu']) && $_GET['comentariu'] == 'succes') {
    $mesaj_comentariu = "<div class='alert alert-success'>Comentariu adăugat!</div>";
}
if (isset($_GET['msg']) && $_GET['msg'] == 'sters') {
    $mesaj_comentariu = "<div class='alert alert-warning'>Comentariul a fost șters.</div>";
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($stire['titlu']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .article-img { width: 100%; max-height: 500px; object-fit: cover; border-radius: 8px; margin-bottom: 20px; }
        .article-meta { font-size: 0.9rem; color: #6c757d; margin-bottom: 20px; border-left: 4px solid #dc3545; padding-left: 10px; }
        .article-content { font-size: 1.1rem; line-height: 1.8; color: #212529; }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand text-danger fw-bold" href="index.php">Revista Online</a>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">← Înapoi la Home</a>
        </div>
    </nav>

    <div class="container mb-5" style="max-width: 900px;">
        
        <?php if (!empty($stire['imagine'])): ?>
            <img src="<?php echo htmlspecialchars($stire['imagine']); ?>" class="article-img shadow-sm" alt="Imagine articol">
        <?php endif; ?>

        <h1 class="display-5 fw-bold mb-3"><?php echo htmlspecialchars($stire['titlu']); ?></h1>
        <div class="article-meta">
            <strong>Autor:</strong> <?php echo htmlspecialchars($stire['nume_autor']); ?> | 
            <strong>Categorie:</strong> <?php echo htmlspecialchars($stire['nume_categorie']); ?> | 
            <strong>Data:</strong> <?php echo date("d F Y, H:i", strtotime($stire['data_publicarii'])); ?>
        </div>

        <div class="bg-white p-5 rounded shadow-sm article-content">
            <p class="lead fw-bold"><?php echo htmlspecialchars($stire['short_description']); ?></p>
            <hr>
            <?php echo nl2br(htmlspecialchars($stire['description'])); ?>
        </div>

        <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_rol'] == 'admin' || $_SESSION['user_id'] == $stire['id_autor'])): ?>
            <div class="mt-4 p-3 bg-warning bg-opacity-10 border border-warning rounded d-flex justify-content-between align-items-center">
                <h6 class="text-warning-emphasis mb-0">⚙️ Administrare Articol</h6>
                <div>
                    <a href="editare-stire.php?id=<?php echo $stire['id']; ?>" class="btn btn-warning btn-sm">Modifică</a>
                    <?php if ($_SESSION['user_rol'] == 'admin'): ?>
                        <a href="stergere-stire.php?id=<?php echo $stire['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Sigur vrei să ștergi știrea?')">Șterge</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="mt-5">
            <h3 class="mb-4">Comentarii (<?php echo count($comentarii); ?>)</h3>
            
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <form action="stire-detaliu.php?id=<?php echo $stire_id; ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="mb-2">
                                <textarea class="form-control" name="comentariu_text" rows="3" placeholder="Scrie părerea ta..." required></textarea>
                            </div>
                            <button type="submit" name="submit_comentariu" class="btn btn-primary btn-sm">Postează Comentariul</button>
                        </form>
                    <?php else: ?>
                        <p class="text-muted mb-0">Trebuie să fii <a href="login-user.php">autentificat</a> pentru a comenta.</p>
                    <?php endif; ?>
                    <?php echo $mesaj_comentariu; ?>
                </div>
            </div>

            <?php foreach ($comentarii as $com): ?>
                <div class="card mb-3 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong class="text-primary"><?php echo htmlspecialchars($com['nume_comentator']); ?></strong>
                                <small class="text-muted ms-2"><?php echo date("d.m.Y H:i", strtotime($com['data_comentariu'])); ?></small>
                            </div>
                            
                            <?php if (isset($_SESSION['user_rol']) && ($_SESSION['user_rol'] == 'admin' || $_SESSION['user_rol'] == 'reporter')): ?>
                                <form method="POST" onsubmit="return confirm('Ștergi acest comentariu?');">
                                    <input type="hidden" name="sterge_comentariu_id" value="<?php echo $com['id']; ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2" style="font-size: 0.8rem;">x</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <p class="card-text mt-2"><?php echo nl2br(htmlspecialchars($com['continut'])); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>