<?php
// Inclure les fichiers nécessaires (en utilisant la version buffered du header)
include "../connect/connect.php";
include "admin_header_buffered.php";  // On utilise désormais la version avec buffer

$message = '';
$error = '';

// Vérifier si l'ID du projet est spécifié
if (!isset($_GET['id'])) {
    header("Location: projects.php");
    exit;
}

$projectId = $_GET['id'];

// Récupérer les informations du projet
$query = "SELECT * FROM projects WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $projectId]);
$project = $stmt->fetch();

// Vérifier si le projet existe
if (!$project) {
    header("Location: projects.php");
    exit;
}

// Si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer les données du formulaire
    $title = trim($_POST['title']);
    $slug = trim($_POST['slug']);
    $description = trim($_POST['description']);
    $year = trim($_POST['year']);
    $category = trim($_POST['category']);
    $client = trim($_POST['client']);
    $website = trim($_POST['website']);
    $btnText = trim($_POST['btnText']);
    $featured = isset($_POST['featured']) ? 1 : 0;
    $hidden = isset($_POST['hidden']) ? 1 : 0;
    
    // Logique pour empêcher qu'un projet soit à la fois en une et masqué
    if ($featured == 1 && $hidden == 1) {
        // Si les deux sont cochés, on privilégie "masqué" et on désactive "en une"
        $featured = 0;
        $error = "Un projet ne peut pas être à la fois en une et masqué. Le projet sera masqué mais pas mis en une.";
    }
    
    // Valider les données
    if (empty($title) || empty($slug)) {
        $error .= "Le titre et le slug sont obligatoires.";
    } else {
        // Vérifier si le slug existe déjà (sauf pour le projet actuel)
        $query = "SELECT COUNT(*) as count FROM projects WHERE slug = :slug AND id != :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['slug' => $slug, 'id' => $projectId]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            $error = "Ce slug est déjà utilisé. Veuillez en choisir un autre.";
        } else {
            // Si le slug a changé, renommer le dossier des images
            if ($slug !== $project['slug']) {
                $oldDir = "../img/projects/" . $project['slug'] . "/";
                $newDir = "../img/projects/" . $slug . "/";
                
                if (is_dir($oldDir)) {
                    // Créer le nouveau dossier s'il n'existe pas
                    if (!is_dir($newDir)) {
                        mkdir($newDir, 0777, true);
                    }
                    
                    // Copier tous les fichiers du vieux dossier vers le nouveau
                    $files = glob($oldDir . "*.*");
                    foreach($files as $file) {
                        $fileName = basename($file);
                        copy($file, $newDir . $fileName);
                    }
                    
                    // Supprimer l'ancien dossier et son contenu
                    array_map('unlink', glob($oldDir . "*.*"));
                    rmdir($oldDir);
                }
            }
            
            // Mettre à jour le projet dans la base de données
            $query = "UPDATE projects SET 
                      title = :title, 
                      slug = :slug, 
                      description = :description, 
                      year = :year, 
                      category = :category, 
                      client = :client, 
                      website = :website, 
                      btnText = :btnText, 
                      featured = :featured, 
                      hidden = :hidden, 
                      updated_at = NOW()
                      WHERE id = :id";
                      
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute([
                'title' => $title,
                'slug' => $slug,
                'description' => $description,
                'year' => $year,
                'category' => $category,
                'client' => $client,
                'website' => $website,
                'btnText' => $btnText,
                'featured' => $featured,
                'hidden' => $hidden,
                'id' => $projectId
            ]);
            
            if ($result) {
                // S'assurer que le dossier pour les images existe
                $projectDir = "../img/projects/" . $slug . "/";
                if (!is_dir($projectDir)) {
                    mkdir($projectDir, 0777, true);
                }
                
                $_SESSION['message'] = "Le projet a été mis à jour avec succès.";
                header("Location: projects.php");
                exit;
            } else {
                $error = "Une erreur est survenue lors de la mise à jour du projet.";
            }
        }
    }
}

// À ce stade, toutes les redirections potentielles ont été traitées
// On peut donc vider le buffer pour afficher la page
ob_end_flush();
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Modifier le projet</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="projects.php">Projets</a></li>
                    <li class="breadcrumb-item active">Modifier le projet</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Informations du projet</h3>
                <div class="card-tools">
                    <a href="manage_images.php?project=<?php echo $projectId; ?>" class="btn btn-info">
                        <i class="fa fa-image"></i> Gérer les images
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $projectId); ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="title">Titre du projet *</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($project['title']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="slug">Slug *</label>
                                <input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars($project['slug']); ?>" required>
                                <small class="form-text text-muted">Utilisé pour l'URL du projet. Utilisez uniquement des lettres minuscules, des chiffres et des tirets.</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="category">Catégorie</label>
                                <select class="form-control" id="category" name="category">
                                    <option value="loge" <?php echo $project['category'] === 'loge' ? 'selected' : ''; ?>>Logements</option>
                                    <option value="hotels" <?php echo $project['category'] === 'hotels' ? 'selected' : ''; ?>>Hotels</option>
                                    <option value="indus" <?php echo $project['category'] === 'indus' ? 'selected' : ''; ?>>Industries</option>
                                    <option value="bim" <?php echo $project['category'] === 'bim' ? 'selected' : ''; ?>>BIM</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="year">Année</label>
                                <input type="number" class="form-control" id="year" name="year" min="2000" max="2099" value="<?php echo htmlspecialchars($project['year']); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($project['description']); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="client">Client</label>
                                <input type="text" class="form-control" id="client" name="client" value="<?php echo htmlspecialchars($project['client']); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="website">Site web</label>
                                <input type="url" class="form-control" id="website" name="website" value="<?php echo htmlspecialchars($project['website']); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="btnText">Texte du bouton</label>
                        <input type="text" class="form-control" id="btnText" name="btnText" value="<?php echo htmlspecialchars($project['btnText']); ?>">
                    </div>
                    
                    <!-- Section modifiée pour les cases à cocher -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="featured" name="featured" <?php echo $project['featured'] == 1 ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="featured">Mettre en Une (afficher sur la page d'accueil)</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="hidden" name="hidden" <?php echo $project['hidden'] == 1 ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="hidden">Masquer le projet (ne pas afficher sur le site)</label>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <small>Note: Un projet ne peut pas être à la fois en Une et masqué. Si vous cochez les deux options, l'option "masquer" sera prioritaire.</small>
                    </div>
                    
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                        <a href="projects.php" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
    // Ajouter un script pour gérer l'interaction entre les deux checkboxes
    document.addEventListener('DOMContentLoaded', function() {
        var featuredCheckbox = document.getElementById('featured');
        var hiddenCheckbox = document.getElementById('hidden');
        
        hiddenCheckbox.addEventListener('change', function() {
            if(this.checked) {
                // Si "masqué" est coché, on décoche "en une"
                featuredCheckbox.checked = false;
                featuredCheckbox.disabled = true;
            } else {
                // Si "masqué" est décoché, on permet de cocher "en une"
                featuredCheckbox.disabled = false;
            }
        });
        
        featuredCheckbox.addEventListener('change', function() {
            if(this.checked) {
                // Si "en une" est coché, on décoche "masqué"
                hiddenCheckbox.checked = false;
            }
        });
        
        // Initialiser l'état au chargement de la page
        if(hiddenCheckbox.checked) {
            featuredCheckbox.disabled = true;
        }
    });
</script>

<?php include "admin_footer.php"; ?>