<?php
include "../connect/connect.php";
include "admin_header.php";

$message = '';
$error = '';

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
    
    // Valider les données
    if (empty($title) || empty($slug)) {
        $error = "Le titre et le slug sont obligatoires.";
    } else {
        // Vérifier si le slug existe déjà
        $query = "SELECT COUNT(*) as count FROM projects WHERE slug = :slug";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['slug' => $slug]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            $error = "Ce slug est déjà utilisé. Veuillez en choisir un autre.";
        } else {
            // Insérer le projet dans la base de données
            $query = "INSERT INTO projects (title, slug, description, year, category, client, website, btnText, featured, hidden, created_at) 
                      VALUES (:title, :slug, :description, :year, :category, :client, :website, :btnText, :featured, :hidden, NOW())";
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
                'hidden' => $hidden
            ]);
            
            if ($result) {
                $projectId = $pdo->lastInsertId();
                
                // Créer le dossier pour les images du projet
                $projectDir = "../img/projects/" . $slug . "/";
                if (!is_dir($projectDir)) {
                    mkdir($projectDir, 0777, true);
                }
                
                // Rediriger vers la page de gestion des images du projet
                $_SESSION['message'] = "Le projet a été créé avec succès. Vous pouvez maintenant ajouter des images.";
                header("Location: manage_images.php?project=" . $projectId);
                exit;
            } else {
                $error = "Une erreur est survenue lors de l'ajout du projet.";
            }
        }
    }
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Ajouter un projet</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="projects.php">Projets</a></li>
                    <li class="breadcrumb-item active">Ajouter un projet</li>
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
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="title">Titre du projet *</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="slug">Slug *</label>
                                <input type="text" class="form-control" id="slug" name="slug" required>
                                <small class="form-text text-muted">Utilisé pour l'URL du projet. Utilisez uniquement des lettres minuscules, des chiffres et des tirets.</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="category">Catégorie</label>
                                <select class="form-control" id="category" name="category">
                                    <option value="loge">Logements</option>
                                    <option value="hotels">Hotels</option>
                                    <option value="indus">Industries</option>
                                    <option value="bim">BIM</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="year">Année</label>
                                <input type="number" class="form-control" id="year" name="year" min="2000" max="2099">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="5"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="client">Client</label>
                                <input type="text" class="form-control" id="client" name="client">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="website">Site web</label>
                                <input type="url" class="form-control" id="website" name="website">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="btnText">Texte du bouton</label>
                        <input type="text" class="form-control" id="btnText" name="btnText" value="Voir le projet">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="featured" name="featured">
                                <label class="form-check-label" for="featured">Mettre en Une (afficher sur la page d'accueil)</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="hidden" name="hidden">
                                <label class="form-check-label" for="hidden">Masquer le projet (ne pas afficher sur le site)</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                        <a href="projects.php" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
    // Génération automatique du slug à partir du titre
    document.getElementById('title').addEventListener('input', function() {
        var title = this.value;
        var slug = title.toLowerCase()
                        .replace(/[^a-z0-9]+/g, '_')
                        .replace(/_+/g, '_')
                        .replace(/^_|_$/g, '');
        document.getElementById('slug').value = slug;
    });
</script>

<?php include "admin_footer.php"; ?>