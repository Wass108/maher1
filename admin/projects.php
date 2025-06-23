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

<div class="bg-white border-b border-gray-200 mb-6">
    <div class="px-6 py-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Gestion des projets</h1>
                <p class="text-gray-600 mt-1">Gérez vos projets d'architecture et de design</p>
            </div>
            <nav class="flex space-x-2 text-sm">
                <a href="dashboard.php" class="text-gray-500 hover:text-gray-700">Accueil</a>
                <span class="text-gray-400">/</span>
                <span class="text-gray-900 font-medium">Projets</span>
            </nav>
        </div>
    </div>
</div>
<div class="px-6">
    <?php if (!empty($message)): ?>
        <div class="mb-6 p-4 border-l-4 border-green-400 bg-green-50 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700"><?php echo htmlspecialchars($message); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm mb-6">
        <div class="p-6 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Liste des projets</h3>
                    <p class="text-sm text-gray-600 mt-1"><?php echo count($projects); ?> projet(s) trouvé(s)</p>
                </div>
                <a href="add_project.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200">
                    <i class="fas fa-plus mr-2"></i>
                    Ajouter un projet
                </a>
            </div>
        </div>
        <div class="p-6">
            <div class="flex flex-wrap gap-3">
                <a href="projects.php" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md transition-colors duration-200 <?php echo empty($filter) ? 'bg-blue-100 text-blue-800 border-2 border-blue-200' : 'text-gray-600 bg-white border border-gray-300 hover:bg-gray-50'; ?>">
                    <i class="fas fa-list mr-2"></i>
                    Tous les projets
                </a>
                <a href="projects.php?filter=featured" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md transition-colors duration-200 <?php echo $filter === 'featured' ? 'bg-green-100 text-green-800 border-2 border-green-200' : 'text-gray-600 bg-white border border-gray-300 hover:bg-gray-50'; ?>">
                    <i class="fas fa-star mr-2"></i>
                    Projets en Une
                </a>
                <a href="projects.php?filter=hidden" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md transition-colors duration-200 <?php echo $filter === 'hidden' ? 'bg-orange-100 text-orange-800 border-2 border-orange-200' : 'text-gray-600 bg-white border border-gray-300 hover:bg-gray-50'; ?>">
                    <i class="fas fa-eye-slash mr-2"></i>
                    Projets masqués
                </a>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
        <?php if (!empty($projects)): ?>
            <div class="hidden lg:block overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Projet</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Détails</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($projects as $project): ?>
                            <?php
                                $projectFolder = "../img/projects/" . $project['slug'] . "/";
                                $imagesFromFolder = glob($projectFolder . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
                                $imagePath = !empty($imagesFromFolder) ? $imagesFromFolder[0] : '../img/default.jpg';
                            ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-16 w-16">
                                            <?php if (file_exists($imagePath)): ?>
                                                <img class="h-16 w-16 rounded-lg object-cover border border-gray-200" src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($project['title']); ?>">
                                            <?php else: ?>
                                                <div class="h-16 w-16 rounded-lg bg-gray-100 flex items-center justify-center">
                                                    <i class="fas fa-image text-gray-400 text-xl"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($project['title']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                ID: <?php echo $project['id']; ?> • <?php echo htmlspecialchars($project['slug']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($project['category']); ?></div>
                                    <div class="text-sm text-gray-500">Année: <?php echo htmlspecialchars($project['year']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col space-y-2">
                                        <?php if($project['featured'] == 1): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 w-fit">
                                                <i class="fas fa-star mr-1"></i>
                                                En Une
                                            </span>
                                        <?php endif; ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium w-fit <?php echo $project['hidden'] == 1 ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'; ?>">
                                            <i class="fas fa-<?php echo $project['hidden'] == 1 ? 'eye-slash' : 'eye'; ?> mr-1"></i>
                                            <?php echo $project['hidden'] == 1 ? 'Masqué' : 'Visible'; ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="edit_project.php?id=<?php echo $project['id']; ?>" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-blue-700 bg-blue-100 hover:bg-blue-200 transition-colors duration-150">
                                            <i class="fas fa-edit mr-1"></i>
                                            Éditer
                                        </a>
                                        <a href="../project.php?project=<?php echo urlencode($project['slug']); ?>" target="_blank" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-green-700 bg-green-100 hover:bg-green-200 transition-colors duration-150">
                                            <i class="fas fa-eye mr-1"></i>
                                            Voir
                                        </a>
                                        <a href="manage_images.php?project=<?php echo $project['id']; ?>" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-purple-700 bg-purple-100 hover:bg-purple-200 transition-colors duration-150">
                                            <i class="fas fa-images mr-1"></i>
                                            Images
                                        </a>
                                        <a href="toggle_featured.php?id=<?php echo $project['id']; ?>" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded <?php echo $project['featured'] == 1 ? 'text-orange-700 bg-orange-100 hover:bg-orange-200' : 'text-yellow-700 bg-yellow-100 hover:bg-yellow-200'; ?> transition-colors duration-150">
                                            <i class="fas fa-star mr-1"></i>
                                            <?php echo $project['featured'] == 1 ? 'Retirer' : 'Mettre'; ?>
                                        </a>
                                        <a href="toggle_visibility.php?id=<?php echo $project['id']; ?>" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors duration-150">
                                            <i class="fas fa-<?php echo $project['hidden'] == 1 ? 'eye' : 'eye-slash'; ?> mr-1"></i>
                                            <?php echo $project['hidden'] == 1 ? 'Afficher' : 'Masquer'; ?>
                                        </a>
                                        <a href="delete_project.php?id=<?php echo $project['id']; ?>" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-red-700 bg-red-100 hover:bg-red-200 transition-colors duration-150 delete-confirm">
                                            <i class="fas fa-trash mr-1"></i>
                                            Supprimer
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="lg:hidden divide-y divide-gray-200">
                <?php foreach ($projects as $project): ?>
                    <?php
                        $projectFolder = "../img/projects/" . $project['slug'] . "/";
                        $imagesFromFolder = glob($projectFolder . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
                        $imagePath = !empty($imagesFromFolder) ? $imagesFromFolder[0] : '../img/default.jpg';
                    ?>
                    <div class="p-6">
                        <div class="flex items-center space-x-4">
                            <div class="flex-shrink-0">
                                <?php if (file_exists($imagePath)): ?>
                                    <img class="h-16 w-16 rounded-lg object-cover border border-gray-200" src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($project['title']); ?>">
                                <?php else: ?>
                                    <div class="h-16 w-16 rounded-lg bg-gray-100 flex items-center justify-center">
                                        <i class="fas fa-image text-gray-400 text-xl"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    <?php echo htmlspecialchars($project['title']); ?>
                                </p>
                                <p class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($project['category']); ?> • <?php echo htmlspecialchars($project['year']); ?>
                                </p>
                                <div class="flex flex-wrap gap-1 mt-2">
                                    <?php if($project['featured'] == 1): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-star mr-1"></i>
                                            En Une
                                        </span>
                                    <?php endif; ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?php echo $project['hidden'] == 1 ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'; ?>">
                                        <i class="fas fa-<?php echo $project['hidden'] == 1 ? 'eye-slash' : 'eye'; ?> mr-1"></i>
                                        <?php echo $project['hidden'] == 1 ? 'Masqué' : 'Visible'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="flex flex-wrap gap-2">
                                <a href="edit_project.php?id=<?php echo $project['id']; ?>" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 transition-colors duration-150">
                                    <i class="fas fa-edit mr-1"></i>
                                    Éditer
                                </a>
                                <a href="../project.php?project=<?php echo urlencode($project['slug']); ?>" target="_blank" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200 transition-colors duration-150">
                                    <i class="fas fa-eye mr-1"></i>
                                    Voir
                                </a>
                                <a href="manage_images.php?project=<?php echo $project['id']; ?>" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-purple-700 bg-purple-100 hover:bg-purple-200 transition-colors duration-150">
                                    <i class="fas fa-images mr-1"></i>
                                    Images
                                </a>
                            </div>
                            <div class="flex flex-wrap gap-2 mt-2">
                                <a href="toggle_featured.php?id=<?php echo $project['id']; ?>" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md <?php echo $project['featured'] == 1 ? 'text-orange-700 bg-orange-100 hover:bg-orange-200' : 'text-yellow-700 bg-yellow-100 hover:bg-yellow-200'; ?> transition-colors duration-150">
                                    <i class="fas fa-star mr-1"></i>
                                    <?php echo $project['featured'] == 1 ? 'Retirer de la Une' : 'Mettre en Une'; ?>
                                </a>
                                <a href="toggle_visibility.php?id=<?php echo $project['id']; ?>" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors duration-150">
                                    <i class="fas fa-<?php echo $project['hidden'] == 1 ? 'eye' : 'eye-slash'; ?> mr-1"></i>
                                    <?php echo $project['hidden'] == 1 ? 'Rendre visible' : 'Masquer'; ?>
                                </a>
                                <a href="delete_project.php?id=<?php echo $project['id']; ?>" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 transition-colors duration-150 delete-confirm">
                                    <i class="fas fa-trash mr-1"></i>
                                    Supprimer
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="p-12 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-folder-open text-gray-400 text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun projet trouvé</h3>
                <p class="text-gray-600 mb-6">
                    <?php if ($filter): ?>
                        Aucun projet ne correspond au filtre "<?php echo $filter; ?>".
                    <?php else: ?>
                        Commencez par ajouter votre premier projet.
                    <?php endif; ?>
                </p>
                <div class="space-x-3">
                    <a href="add_project.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>
                        Ajouter un projet
                    </a>
                    <?php if ($filter): ?>
                        <a href="projects.php" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Voir tous les projets
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include "admin_footer.php"; ?>