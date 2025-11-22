<?php
session_start();
require_once 'model/Database.php';

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Luam stirile + numele categoriei si autorului
    $sql = "SELECT stiri.*, categorii.name AS nume_categorie, user.name AS nume_autor
            FROM stiri
            LEFT JOIN categorii ON stiri.id_categorie = categorii.id
            LEFT JOIN user ON stiri.id_autor = user.id
            ORDER BY stiri.data_publicarii DESC";
    $stmt = $pdo->query($sql);
    $stiri = $stmt->fetchAll();
} catch (PDOException $e) { die("Eroare: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revista Online - Actualitate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Stiluri custom pentru aspect de stiri */
        .news-card { transition: transform 0.2s; height: 100%; border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .news-card:hover { transform: translateY(-5px); }
        .news-img { height: 200px; object-fit: cover; }
        .category-badge { position: absolute; top: 10px; right: 10px; background: #dc3545; color: white; padding: 5px 10px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
        .navbar-brand { font-weight: bold; font-size: 1.5rem; color: #dc3545 !important; }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-5">
        <div class="container">
            <a class="navbar-brand" href="index.php">📰 Revista Online</a>
            
            <div class="d-flex align-items-center gap-2">
                <?php if (isset($_SESSION['user_name'])): ?>
                    <span class="text-muted me-2 d-none d-md-inline">Salut, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></span>
                    
                    <?php if ($_SESSION['user_rol'] == 'reporter' || $_SESSION['user_rol'] == 'admin'): ?>
                        <a href="creare-stire.php" class="btn btn-success btn-sm">Scrie Știre</a>
                    <?php endif; ?>
                    
                    <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
                
                <?php else: ?>
                    <a href="login-user.php" class="btn btn-primary btn-sm">Login</a>
                    <a href="create-user.php" class="btn btn-info btn-sm text-white">Creare Cont</a>
                <?php endif; ?>
                
                <a href="contact.php" class="btn btn-light btn-sm border">Contact</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2 class="mb-4 border-bottom pb-2">Ultimele Noutăți</h2>
        
        <div class="row">
            <?php if (empty($stiri)): ?>
                <p class="text-muted">Nu există știri publicate încă.</p>
            <?php else: ?>
                <?php foreach ($stiri as $stire): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card news-card">
                            <?php if (!empty($stire['imagine'])): ?>
                                <img src="<?php echo htmlspecialchars($stire['imagine']); ?>" class="card-img-top news-img" alt="Imagine stire">
                            <?php else: ?>
                                <img src="https://placehold.co/600x400?text=Revista+Online" class="card-img-top news-img" alt="Fara imagine">
                            <?php endif; ?>
                            
                            <span class="category-badge"><?php echo htmlspecialchars($stire['nume_categorie']); ?></span>

                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($stire['titlu']); ?></h5>
                                <div class="small text-muted mb-2">
                                    By <?php echo htmlspecialchars($stire['nume_autor']); ?> | 
                                    <?php echo date("d M", strtotime($stire['data_publicarii'])); ?>
                                </div>
                                <p class="card-text flex-grow-1"><?php echo substr(htmlspecialchars($stire['short_description']), 0, 100) . '...'; ?></p>
                                <a href="stire-detaliu.php?id=<?php echo $stire['id']; ?>" class="btn btn-outline-primary btn-sm mt-auto w-100">Citește tot</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="bg-white p-5 rounded shadow-sm mt-5 mb-5 text-center">
            <h3>📩 Abonează-te la Newsletter</h3>
            <p class="text-muted">Fii primul care află noutățile!</p>
            
             <?php if (isset($_SESSION['user_email'])): ?>
                <form action="subscribe.php" method="POST" class="d-inline-block">
                    <input type="hidden" name="email_newsletter" value="<?php echo htmlspecialchars($_SESSION['user_email']); ?>">
                    <button class="btn btn-dark px-4" type="submit" name="submit_newsletter">Abonare rapidă cu <?php echo htmlspecialchars($_SESSION['user_email']); ?></button>
                </form>
            <?php else: ?>
                <form action="subscribe.php" method="POST" class="row justify-content-center g-2">
                    <div class="col-auto"><input type="email" class="form-control" name="email_newsletter" placeholder="Email-ul tău" required></div>
                    <div class="col-auto"><button class="btn btn-dark" type="submit" name="submit_newsletter">Mă Abonez</button></div>
                </form>
            <?php endif; ?>
            
            <?php 
            if (isset($_GET['subscribe'])) {
                if ($_GET['subscribe'] == 'succes') echo "<div class='text-success mt-2'>Te-ai abonat cu succes!</div>";
                if ($_GET['subscribe'] == 'exista') echo "<div class='text-warning mt-2'>Ești deja abonat.</div>";
            }
            ?>
        </div>

    </div>
    
    <footer class="bg-dark text-white text-center py-3 mt-auto">
        <p class="mb-0">&copy; 2025 Revista Online. Toate drepturile rezervate.</p>
    </footer>

</body>
</html>