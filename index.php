<?php
// Désactivation de l'affichage des erreurs pour l'exemple (mauvaise pratique)
ini_set('display_errors', 1);

// --- Variables Globales (le mal absolu) ---
$host = '127.0.0.1';
$dbname = 'gestion_contacts';
$user = 'root';
$pass = '';
$pdo; // Variable PDO globale
$message_statut = '';
$action_demandee = '';
$contact_id_a_editer = null;
$donnees_formulaire = ['nom' => '', 'prenom' => '', 'telephone' => '', 'email' => ''];

// --- Fonctions longues et "spaghetti" qui mélangent tout ---

function gerer_toutes_les_operations_de_contacts_pdo() {
    global $pdo, $host, $dbname, $user, $pass, $message_statut, $action_demandee, $contact_id_a_editer, $donnees_formulaire;

    // Connexion à la base de données via PDO
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
        // Configuration de PDO pour lever des exceptions en cas d'erreur (bonne pratique ici)
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("La connexion PDO a échoué lamentablement : " . $e->getMessage());
    }

    $action_demandee = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

    if ($action_demandee == 'ajouter') {
        // Utilisation de requêtes préparées PDO (bonne pratique)
        $stmt = $pdo->prepare("INSERT INTO contacts (nom, prenom, telephone, email) VALUES (:nom, :prenom, :telephone, :email)");
        try {
            $stmt->execute([
                ':nom' => $_POST['nom'],
                ':prenom' => $_POST['prenom'],
                ':telephone' => $_POST['telephone'],
                ':email' => $_POST['email']
            ]);
            $message_statut = "Contact ajouté avec succès.";
        } catch (PDOException $e) {
            $message_statut = "Erreur d'ajout : " . $e->getMessage();
        }
    } elseif ($action_demandee == 'supprimer') {
        $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = :id");
        try {
            $stmt->execute([':id' => $_GET['id']]);
            $message_statut = "Contact supprimé.";
        } catch (PDOException $e) {
            $message_statut = "Erreur de suppression : " . $e->getMessage();
        }
    } elseif ($action_demandee == 'editer_form') {
        $contact_id_a_editer = $_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM contacts WHERE id = :id");
        $stmt->execute([':id' => $contact_id_a_editer]);
        if ($contact = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $donnees_formulaire = $contact;
        }
    } elseif ($action_demandee == 'mettre_a_jour') {
        $stmt = $pdo->prepare("UPDATE contacts SET nom = :nom, prenom = :prenom, telephone = :telephone, email = :email WHERE id = :id");
        try {
            $stmt->execute([
                ':id' => $_POST['id'],
                ':nom' => $_POST['nom'],
                ':prenom' => $_POST['prenom'],
                ':telephone' => $_POST['telephone'],
                ':email' => $_POST['email']
            ]);
            $message_statut = "Contact mis à jour.";
        } catch (PDOException $e) {
            $message_statut = "Erreur de mise à jour : " . $e->getMessage();
        }
    }
}

function afficher_liste_contacts_pdo() {
    global $pdo, $message_statut;
    echo "<h2>Liste des Contacts</h2>";

    if ($message_statut) {
        echo "<p style='color: blue;'>$message_statut</p>";
    }

    $stmt = $pdo->query("SELECT * FROM contacts ORDER BY nom ASC");
    $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($resultats) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Téléphone</th><th>Email</th><th>Actions</th></tr>";
        foreach($resultats as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row["id"]). "</td>";
            echo "<td>" . htmlspecialchars($row["nom"]). "</td>";
            echo "<td>" . htmlspecialchars($row["prenom"]). "</td>";
            echo "<td>" . htmlspecialchars($row["telephone"]). "</td>";
            echo "<td>" . htmlspecialchars($row["email"]). "</td>";
            echo "<td><a href='?action=editer_form&id=" . htmlspecialchars($row["id"]) . "'>Éditer</a> | <a href='?action=supprimer&id=" . htmlspecialchars($row["id"]) . "' onclick=\"return confirm('Êtes-vous sûr ?');\">Supprimer</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "Aucun contact trouvé.";
    }
}

// La fonction d'affichage du formulaire reste identique à la précédente car elle ne gère pas la BDD directement.
function afficher_formulaire_contact_pdo() {
    global $action_demandee, $donnees_formulaire, $contact_id_a_editer;
    $action_form = ($action_demandee == 'editer_form') ? 'mettre_a_jour' : 'ajouter';
    $titre_form = ($action_demandee == 'editer_form') ? 'Éditer le Contact' : 'Ajouter un Contact';

    echo "<h2>$titre_form</h2>";
    echo "<form method='POST' action='?action=$action_form'>";
    if ($action_demandee == 'editer_form') {
        echo "<input type='hidden' name='id' value='" . htmlspecialchars($contact_id_a_editer) . "'>";
    }
    echo "Nom: <input type='text' name='nom' value='" . htmlspecialchars($donnees_formulaire['nom']) . "'><br>";
    echo "Prénom: <input type='text' name='prenom' value='" . htmlspecialchars($donnees_formulaire['prenom']) . "'><br>";
    echo "Téléphone: <input type='text' name='telephone' value='" . htmlspecialchars($donnees_formulaire['telephone']) . "'><br>";
    echo "Email: <input type='text' name='email' value='" . htmlspecialchars($donnees_formulaire['email']) . "'><br>";
    echo "<input type='submit' value='Valider'>";
    echo "</form>";

    if ($action_demandee == 'editer_form') {
        echo "<br><a href='index.php'>Annuler l'édition</a>";
    }
}

// --- Point d'entrée principal non structuré ---
?>

<!DOCTYPE html>
<html>
<head>
<title>Gestion de Contacts Spaghettis (PDO)</title>
</head>
<body>

<h1>Application de Gestion de Contacts (PDO mais toujours Spaghettis)</h1>

<?php
// Exécution de toute la logique métier/BDD avant l'affichage
gerer_toutes_les_operations_de_contacts_pdo();

// Affichage des composants
afficher_formulaire_contact_pdo();
afficher_liste_contacts_pdo();

// Fermeture de la connexion (PDO se ferme automatiquement à la fin du script, mais on peut la nullifier)
$pdo = null;
?>

</body>
</html>
