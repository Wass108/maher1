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

<script src="https://cdn.tailwindcss.com"></script>

<div class="min-h-screen bg-gray-50">
    <div class="bg-white border-b border-gray-200 mb-6">
        <div class="px-6 py-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Tableau de bord</h1>
                </div>
                <nav class="flex space-x-2 text-sm">
                    <a href="dashboard.php" class="text-gray-500 hover:text-gray-700">Accueil</a>
                    <span class="text-gray-400">/</span>
                    <span class="text-gray-900 font-medium">Tableau de bord</span>
                </nav>
            </div>
        </div>
    </div>
    <div class="px-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Projets</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo $totalProjects; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fa fa-building text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <a href="projects.php" class="text-sm font-medium text-blue-600 hover:text-blue-800 flex items-center">
                            Voir tous les projets
                            <i class="fa fa-angle-right ml-2"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Projets en Une</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo $totalFeatured; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fa fa-star text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <a href="projects.php?filter=featured" class="text-sm font-medium text-green-600 hover:text-green-800 flex items-center">
                            Gérer la mise en avant
                            <i class="fa fa-angle-right ml-2"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Projets masqués</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo $totalHidden; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i class="fa fa-eye-slash text-orange-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <a href="projects.php?filter=hidden" class="text-sm font-medium text-orange-600 hover:text-orange-800 flex items-center">
                            Voir projets masqués
                            <i class="fa fa-angle-right ml-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm mb-8">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Actions rapides</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <a href="add_project.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-colors duration-200 group">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-blue-200">
                            <i class="fa fa-plus text-blue-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Nouveau projet</p>
                            <p class="text-sm text-gray-600">Ajouter un projet</p>
                        </div>
                    </a>
                    <a href="projects.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-green-300 hover:bg-green-50 transition-colors duration-200 group">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-green-200">
                            <i class="fa fa-folder text-green-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Gérer projets</p>
                            <p class="text-sm text-gray-600">Modifier, supprimer</p>
                        </div>
                    </a>
                    <a href="../index.php" target="_blank" class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-indigo-300 hover:bg-indigo-50 transition-colors duration-200 group">
                        <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-indigo-200">
                            <i class="fa fa-external-link text-indigo-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Voir le site</p>
                            <p class="text-sm text-gray-600">Vue publique</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm mb-8">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Projets récents</h3>
                    </div>
                    <a href="projects.php" class="text-sm font-medium text-blue-600 hover:text-blue-800">Voir tous</a>
                </div>
            </div>
            <div class="overflow-hidden">
                <?php if(!empty($recentProjects)): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Projet</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catégorie</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Année</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach($recentProjects as $project): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($project['title']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-600">
                                                <?php echo htmlspecialchars($project['category']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-600">
                                                <?php echo htmlspecialchars($project['year']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex space-x-2">
                                                <?php if($project['featured'] == 1): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        <i class="fa fa-star mr-1"></i>
                                                        En Une
                                                    </span>
                                                <?php endif; ?>
                                                <?php if($project['hidden'] == 1): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        <i class="fa fa-eye-slash mr-1"></i>
                                                        Masqué
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        <i class="fa fa-eye mr-1"></i>
                                                        Visible
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="edit_project.php?id=<?php echo $project['id']; ?>" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 transition-colors duration-150">
                                                    <i class="fa fa-edit mr-1"></i>
                                                    Éditer
                                                </a>
                                                <a href="manage_images.php?project=<?php echo $project['id']; ?>" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-purple-700 bg-purple-100 hover:bg-purple-200 transition-colors duration-150">
                                                    <i class="fa fa-images mr-1"></i>
                                                    Images
                                                </a>
                                                <a href="../project.php?project=<?php echo urlencode($project['slug']); ?>" target="_blank" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200 transition-colors duration-150">
                                                    <i class="fa fa-eye mr-1"></i>
                                                    Voir
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-8 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                            <i class="fa fa-folder-open text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun projet trouvé</h3>
                        <p class="text-gray-600 mb-4">Commencez par ajouter votre premier projet.</p>
                        <a href="add_project.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <i class="fa fa-plus mr-2"></i>
                            Ajouter un projet
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include "admin_footer.php"; ?>