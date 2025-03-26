<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du film</title>
    <!-- Bootstrap CSS depuis CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .film-poster {
            max-height: 500px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .film-info {
            background-color: #f8f9fa;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .seance-card {
            transition: transform 0.3s;
        }

        .seance-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>

<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-film me-2"></i>Ciné App
        </a>
    </div>
</nav>

<div class="container py-5">
    <?php
    // Vérifier si l'ID du film est fourni
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        echo '<div class="alert alert-danger" role="alert">';
        echo 'Erreur: Aucun film spécifié.';
        echo '</div>';
        echo '<a href="index.php" class="btn btn-primary">Retour à la liste des films</a>';
    } else {
        $film_id = intval($_GET['id']);

        // URL de l'API Fastify pour le film
        $film_api_url = "http://localhost:3000/api/films/{$film_id}";
        // URL de l'API Fastify pour les séances
        $seances_api_url = "http://localhost:3000/api/films/{$film_id}/seances";

        // Fonction pour récupérer les données de l'API
        function fetchApiData($url) {
            $response = file_get_contents($url);
            if ($response === false) {
                return false;
            }
            return json_decode($response, true);
        }

        // Récupérer les données du film
        $film_data = fetchApiData($film_api_url);
        // Récupérer les données des séances
        $seances_data = fetchApiData($seances_api_url);

        // Vérifier si les requêtes ont réussi
        if ($film_data === false) {
            echo '<div class="alert alert-danger" role="alert">';
            echo 'Erreur: Impossible de se connecter à l\'API.';
            echo '</div>';
            echo '<a href="index.php" class="btn btn-primary">Retour à la liste des films</a>';
        } else if (isset($film_data['data']) && !empty($film_data['data'])) {
            $film = $film_data['data'];

            ?>
            <div class="mb-4">
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour à la liste
                </a>
            </div>

            <div class="row">
                <div class="col-md-4 mb-4">
                    <img src="<?php echo htmlspecialchars($film['affiche']); ?>" alt="<?php echo htmlspecialchars($film['titre']); ?>" class="film-poster img-fluid">
                </div>
                <div class="col-md-8">
                    <div class="film-info">
                        <h1 class="mb-4"><?php echo htmlspecialchars($film['titre']); ?></h1>

                        <div class="mb-3">
                            <span class="badge bg-primary me-2"><?php echo htmlspecialchars($film['genre_nom']); ?></span>
                            <span class="badge bg-secondary"><?php echo date('Y', strtotime($film['date_sortie'])); ?></span>
                        </div>

                        <p class="lead"><?php echo htmlspecialchars($film['description']); ?></p>

                        <hr>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <p><strong><i class="fas fa-user-tie me-2"></i>Réalisateur:</strong> <?php echo htmlspecialchars($film['realisateur']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong><i class="fas fa-clock me-2"></i>Durée:</strong> <?php echo htmlspecialchars($film['duree']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong><i class="fas fa-calendar-alt me-2"></i>Date de sortie:</strong> <?php echo date('d/m/Y', strtotime($film['date_sortie'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            // Les séqnaces
            ?>
            <div class="row mt-5">
                <div class="col-12">
                    <h2 class="mb-4">Séances disponibles</h2>

                    <?php if ($seances_data !== false && isset($seances_data['data']) && !empty($seances_data['data'])): ?>
                        <div class="row">
                            <?php foreach ($seances_data['data'] as $seance): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card seance-card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <?php echo date('H:i', strtotime($seance['heure'])); ?>
                                            </h5>
                                            <h6 class="card-subtitle mb-2 ">
                                                <?php echo date('d/m/Y', strtotime($seance['date'])); ?>
                                            </h6>
                                            <p class="card-text">
                                                Nombre Places <?php echo htmlspecialchars($seance['places_disponibles']); ?>
                                            </p>

                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info" role="alert">
                            Aucune séance disponible pour ce film.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        } else {
            echo '<div class="alert alert-warning" role="alert">';
            echo 'Film non trouvé.';
            echo '</div>';
            echo '<a href="index.php" class="btn btn-primary">Retour à la liste des films</a>';
        }
    }
    ?>
</div>

<footer class="bg-dark text-white py-4 mt-5">
    <div class="container text-center">
        <p>&copy; <?php echo date('Y'); ?> Ciné App - Projet API REST</p>
    </div>
</footer>

<!-- Bootstrap JS Bundle avec Popper depuis CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>