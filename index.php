<?php
session_start();
require_once 'model/Database.php';

try {
    $pdo = Database::getInstance()->getConnection();
    
    
    // Numaram cate stiri sunt in fiecare categorie
    $sql_chart = "SELECT categorii.name as nume_cat, COUNT(stiri.id) as numar 
                  FROM stiri 
                  JOIN categorii ON stiri.id_categorie = categorii.id 
                  GROUP BY stiri.id_categorie";
    $stmt_chart = $pdo->query($sql_chart);
    $chart_data = $stmt_chart->fetchAll(PDO::FETCH_ASSOC);

    // Pregatim datele pentru Javascript
    $labels = [];
    $data_values = [];
    foreach ($chart_data as $row) {
        $labels[] = $row['nume_cat'];
        $data_values[] = $row['numar'];
    }

    // 2. LOGICA PENTRU STIRI (EXISTENT - Pastrat)
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
       
        .news-card { transition: transform 0.2s; height: 100%; border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .news-card:hover { transform: translateY(-5px); }
        .news-img { height: 200px; object-fit: cover; }
        .category-badge { position: absolute; top: 10px; right: 10px; background: #dc3545; color: white; padding: 5px 10px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
        .navbar-brand { font-weight: bold; font-size: 1.5rem; color: #dc3545 !important; }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php">📰 Revista Online</a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <div class="ms-auto d-flex align-items-center gap-2">
                    <?php if (isset($_SESSION['user_name'])): ?>
                        <span class="text-muted me-2 d-none d-lg-inline">Salut, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></span>
                        
                        <?php if($_SESSION['user_rol'] == 'admin'): ?>
                            <a href="export-excel.php" class="btn btn-success btn-sm">📊 Export Excel</a>
                        <?php endif; ?>

                        <?php if ($_SESSION['user_rol'] == 'reporter' || $_SESSION['user_rol'] == 'admin'): ?>
                            <a href="creare-stire.php" class="btn btn-warning btn-sm text-white">Scrie Știre</a>
                        <?php endif; ?>
                        
                        <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
                    
                    <?php else: ?>
                        <a href="login-user.php" class="btn btn-primary btn-sm">Login</a>
                        <a href="create-user.php" class="btn btn-info btn-sm text-white">Creare Cont</a>
                    <?php endif; ?>
                    
                    <a href="contact.php" class="btn btn-light btn-sm border">Contact</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            
            <div class="col-lg-8">
                <h2 class="mb-4 border-bottom pb-2">Ultimele Noutăți</h2>
                
                <div class="row">
                    <?php if (empty($stiri)): ?>
                        <p class="text-muted">Nu există știri publicate încă.</p>
                    <?php else: ?>
                        <?php foreach ($stiri as $stire): ?>
                            <div class="col-md-6 mb-4">
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
                                        <p class="card-text flex-grow-1"><?php echo substr(htmlspecialchars($stire['short_description']), 0, 80) . '...'; ?></p>
                                        <a href="stire-detaliu.php?id=<?php echo $stire['id']; ?>" class="btn btn-outline-primary btn-sm mt-auto w-100">Citește tot</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                
                <div class="card shadow-sm mb-4 border-0">
                    <div class="card-header bg-white fw-bold border-bottom">📊 Statistici Subiecte</div>
                    <div class="card-body">
                        <canvas id="myChart"></canvas>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-danger text-white fw-bold">🌍 Știri HotNews.ro (RSS)</div>
                    <ul class="list-group list-group-flush">
                        <?php
                        // SCHIMBAM SURSA: HotNews este mai prietenos cu serverele gratuite
                        $rss_url = "https://www.hotnews.ro/rss";
                        
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $rss_url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        // Ne prefacem ca suntem un browser real
                        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
                        
                        $response = curl_exec($ch);
                        curl_close($ch);
                        
                        if ($response) {
                            // HotNews are uneori spatii goale la inceput, le curatam
                            $response = trim($response);
                            $rss = @simplexml_load_string($response);
                            
                            if($rss) {
                                $limit = 5; 
                                $count = 0;
                                foreach ($rss->channel->item as $item) {
                                    if($count >= $limit) break;
                                    echo '<li class="list-group-item">';
                                    // HotNews pune titlul in CDATA, il curatam
                                    $titlu = (string)$item->title;
                                    echo '<a href="'. $item->link .'" target="_blank" class="text-decoration-none text-dark fw-bold" style="font-size:0.9rem;">' . $titlu . '</a>';
                                    echo '<div class="text-muted mt-1" style="font-size:0.75rem;">' . date("d M H:i", strtotime($item->pubDate)) . '</div>';
                                    echo '</li>';
                                    $count++;
                                }
                            } else {
                                echo '<li class="list-group-item text-warning">Formatul RSS invalid (Serverul blocheaza XML-ul).</li>';
                            }
                        } else {
                            echo '<li class="list-group-item text-danger">Conexiunea externă e blocată complet de InfinityFree.</li>';
                        }
                        ?>
                    </ul>
                </div>
        </div> <div class="bg-white p-5 rounded shadow-sm mt-5 mb-5 text-center">
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

    <script>
        const ctx = document.getElementById('myChart');
        // Verificam daca avem date in PHP inainte sa desenam
        <?php if(!empty($labels)): ?>
        new Chart(ctx, {
            type: 'doughnut', 
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Articole',
                    data: <?php echo json_encode($data_values); ?>,
                    backgroundColor: [
                        '#dc3545', 
                        '#0d6efd', 
                        '#ffc107', 
                        '#198754', 
                        '#6f42c1'  
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
        <?php endif; ?>
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>