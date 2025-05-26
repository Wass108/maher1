<?php
include "../connect/connect.php";
include "admin_header.php";

// Gestion des filtres
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$whereClause = '';

if ($filter === 'featured') {
    $whereClause = "WHERE featured = 1";
} elseif ($filter === 'hidden') {
    $whereClause = "WHERE hidden = 1";
}

// Récupérer la liste des projets
$query = "SELECT * FROM projects $whereClause ORDER BY id DESC";
$stmt = $pdo->query($query);
$projects = $stmt->fetchAll();

// Gestion des messages de feedback
$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Effacer le message après l'avoir affiché
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Gestion des projets</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item active">Projets</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Liste des projets</h3>
                <div class="card-tools">
                    <a href="add_project.php" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Ajouter un projet
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtres -->
                <div class="mb-3">
                    <a href="projects.php" class="btn <?php echo empty($filter) ? 'btn-primary' : 'btn-outline-primary'; ?> me-2">
                        Tous les projets
                    </a>
                    <a href="projects.php?filter=featured" class="btn <?php echo $filter === 'featured' ? 'btn-success' : 'btn-outline-success'; ?> me-2">
                        Projets en Une
                    </a>
                    <a href="projects.php?filter=hidden" class="btn <?php echo $filter === 'hidden' ? 'btn-warning' : 'btn-outline-warning'; ?> me-2">
                        Projets masqués
                    </a>
                </div>
                
                <!-- Tableau des projets -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Titre</th>
                                <th>Slug</th>
                                <th>Catégorie</th>
                                <th>Année</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($projects)): ?>
                                <?php foreach ($projects as $project): ?>
                                    <?php
                                        // Récupérer l'image principale du projet
                                        $projectFolder = "../img/projects/" . $project['slug'] . "/";
                                        $imagesFromFolder = glob($projectFolder . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
                                        $imagePath = !empty($imagesFromFolder) ? $imagesFromFolder[0] : '../img/default.jpg';
                                    ?>
                                    <tr>
                                        <td><?php echo $project['id']; ?></td>
                                        <td>
                                            <?php if (file_exists($imagePath)): ?>
                                                <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="img-thumbnail">
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Pas d'image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($project['title']); ?></td>
                                        <td><?php echo htmlspecialchars($project['slug']); ?></td>
                                        <td><?php echo htmlspecialchars($project['category']); ?></td>
                                        <td><?php echo htmlspecialchars($project['year']); ?></td>
                                        <td>
                                            <?php if($project['featured'] == 1): ?>
                                                <span class="badge bg-success">En Une</span>
                                            <?php endif; ?>
                                            <?php if($project['hidden'] == 1): ?>
                                                <span class="badge bg-danger">Masqué</span>
                                            <?php else: ?>
                                                <span class="badge bg-info">Visible</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="edit_project.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-primary mb-2">
                                                <i class="fa fa-edit"></i> Éditer
                                            </a>
                                            <a href="../project.php?project=<?php echo urlencode($project['slug']); ?>" target="_blank" class="btn btn-sm btn-info mb-2">
                                                <i class="fa fa-eye"></i> Voir
                                            </a>
                                            <a href="manage_images.php?project=<?php echo $project['id']; ?>" class="btn btn-sm btn-success mb-2">
                                                <i class="fa fa-image"></i> Images
                                            </a>
                                            <a href="toggle_featured.php?id=<?php echo $project['id']; ?>" class="btn btn-sm <?php echo $project['featured'] == 1 ? 'btn-warning' : 'btn-outline-warning'; ?> mb-2">
                                                <i class="fa fa-<?php echo $project['featured'] == 1 ? 'star' : 'star-o'; ?>"></i> 
                                                <?php echo $project['featured'] == 1 ? 'Retirer de la Une' : 'Mettre en Une'; ?>
                                            </a>
                                            <a href="toggle_visibility.php?id=<?php echo $project['id']; ?>" class="btn btn-sm <?php echo $project['hidden'] == 1 ? 'btn-secondary' : 'btn-outline-secondary'; ?> mb-2">
                                                <i class="fa fa-<?php echo $project['hidden'] == 1 ? 'eye' : 'eye-slash'; ?>"></i> 
                                                <?php echo $project['hidden'] == 1 ? 'Rendre visible' : 'Masquer'; ?>
                                            </a>
                                            <a href="delete_project.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-danger mb-2" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce projet?');">
                                                <i class="fa fa-trash"></i> Supprimer
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">Aucun projet trouvé</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include "admin_footer.php"; ?>