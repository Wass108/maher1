<?php
include "../connect/connect.php";
include "admin_header.php";

// Récupérer quelques statistiques pour le tableau de bord
// Nombre de projets
$queryProjects = "SELECT COUNT(*) as total FROM projects";
$stmtProjects = $pdo->query($queryProjects);
$totalProjects = $stmtProjects->fetch()['total'];

// Nombre de projets mis en avant (featured)
$queryFeatured = "SELECT COUNT(*) as total FROM projects WHERE featured = 1";
$stmtFeatured = $pdo->query($queryFeatured);
$totalFeatured = $stmtFeatured->fetch()['total'];

// Nombre de projets masqués
$queryHidden = "SELECT COUNT(*) as total FROM projects WHERE hidden = 1";
$stmtHidden = $pdo->query($queryHidden);
$totalHidden = $stmtHidden->fetch()['total'];

// Récupérer les 5 derniers projets
$queryRecentProjects = "SELECT * FROM projects ORDER BY id DESC LIMIT 5";
$stmtRecentProjects = $pdo->query($queryRecentProjects);
$recentProjects = $stmtRecentProjects->fetchAll();
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Tableau de bord</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item active">Tableau de bord</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Statistiques -->
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="me-3">
                                <div class="text-white-75">Total Projets</div>
                                <div class="display-4"><?php echo $totalProjects; ?></div>
                            </div>
                            <i class="fa fa-building fa-2x"></i>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <a class="small text-white stretched-link" href="projects.php">Voir détails</a>
                        <div class="small text-white"><i class="fa fa-angle-right"></i></div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="me-3">
                                <div class="text-white-75">Projets en Une</div>
                                <div class="display-4"><?php echo $totalFeatured; ?></div>
                            </div>
                            <i class="fa fa-star fa-2x"></i>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <a class="small text-white stretched-link" href="projects.php?filter=featured">Voir détails</a>
                        <div class="small text-white"><i class="fa fa-angle-right"></i></div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card bg-warning text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="me-3">
                                <div class="text-white-75">Projets masqués</div>
                                <div class="display-4"><?php echo $totalHidden; ?></div>
                            </div>
                            <i class="fa fa-eye-slash fa-2x"></i>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <a class="small text-white stretched-link" href="projects.php?filter=hidden">Voir détails</a>
                        <div class="small text-white"><i class="fa fa-angle-right"></i></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Projets récents -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fa fa-table me-1"></i>
                Projets récents
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Catégorie</th>
                                <th>Année</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($recentProjects)): ?>
                                <?php foreach($recentProjects as $project): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($project['title']); ?></td>
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
                                            <a href="edit_project.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fa fa-edit"></i> Éditer
                                            </a>
                                            <a href="manage_images.php?project=<?php echo $project['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fa fa-images"></i> Images
                                            </a>
                                            <a href="../project.php?project=<?php echo urlencode($project['slug']); ?>" target="_blank" class="btn btn-sm btn-success">
                                                <i class="fa fa-eye"></i> Voir
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">Aucun projet trouvé</td>
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