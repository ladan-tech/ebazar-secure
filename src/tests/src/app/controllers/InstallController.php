<?php
defined('EBAZAR') or exit;

/**
 * Assistant d'installation : saisie des paramètres de la base, création des
 * tables, du compte administrateur unique et des catégories par défaut, puis
 * écriture de config/config.php. Inaccessible une fois l'application installée.
 */
class InstallController extends Controller
{
    private const CATEGORIES_DEFAUT = ['Informatique', 'Maison', 'Livres', 'Vêtements', 'Loisirs', 'Véhicules'];

    public function index(): void
    {
        if (Database::estConfiguree()) {
            $this->erreur(403, 'L’application est déjà installée. Supprimez config/config.php pour la réinstaller.');
        }

        $p = [
            'db_host' => 'localhost', 'db_port' => '3306', 'db_name' => '', 'db_user' => '', 'db_pass' => '',
            'admin_email' => 'admin@ebazar.local', 'admin_pass' => '',
        ];
        $erreurs = [];
        $configManuelle = null;

        if ($this->estPost()) {
            $this->exigerCsrf();
            foreach ($p as $k => $_) {
                $p[$k] = trim((string) ($_POST[$k] ?? ''));
            }
            if ($p['db_host'] === '' || $p['db_name'] === '' || $p['db_user'] === '') {
                $erreurs[] = 'Serveur, nom de base et utilisateur sont obligatoires.';
            }
            if (!ctype_digit($p['db_port'])) {
                $erreurs[] = 'Port invalide.';
            }
            if (!filter_var($p['admin_email'], FILTER_VALIDATE_EMAIL)) {
                $erreurs[] = 'E-mail administrateur invalide.';
            }
            if ($regles = Utilisateur::verifierPolitique($p['admin_pass'], $p['admin_email'])) {
                $erreurs[] = 'Le mot de passe administrateur doit contenir : ' . implode(', ', $regles) . '.';
            }
            if (!is_writable(UPLOAD_DIR)) {
                $erreurs[] = 'Le dossier uploads/ n’est pas accessible en écriture par le serveur web (voir docs/DEPLOYMENT.md).';
            }

            if (!$erreurs) {
                try {
                    $pdo = Database::creer($p['db_host'], (int) $p['db_port'], $p['db_name'], $p['db_user'], $p['db_pass']);
                    $this->initialiserBase($pdo, $p['admin_email'], $p['admin_pass']);
                    $contenu = $this->genererConfig($p);
                    if (@file_put_contents(CONFIG_FILE, $contenu, LOCK_EX) === false) {
                        // Dossier config/ non inscriptible : on affiche le fichier à créer à la main
                        $configManuelle = $contenu;
                    } else {
                        @chmod(CONFIG_FILE, 0640);
                        Session::flash('succes', 'Installation terminée ! Connectez-vous avec le compte administrateur.');
                        $this->rediriger(url('auth', 'connexion'));
                    }
                } catch (PDOException $ex) {
                    $erreurs[] = 'Connexion ou initialisation impossible : ' . $ex->getMessage();
                }
            }
        }

        $this->vue('install/index', [
            'titre'          => 'Installation',
            'p'              => $p,
            'erreurs'        => $erreurs,
            'configManuelle' => $configManuelle,
        ], 200, 'auth');
    }

    private function initialiserBase(PDO $pdo, string $email, string $mdp): void
    {
        $sql = file_get_contents(ROOT . '/sql/schema.sql');
        // Retire les commentaires puis exécute instruction par instruction
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $instruction) {
            $pdo->exec($instruction);
        }
        (new Utilisateur($pdo))->creer($email, $mdp, 'admin');
        Journal::ajouter('installation', null, $email, 'Base initialisée', $pdo);
        $cat = new Categorie($pdo);
        foreach (self::CATEGORIES_DEFAUT as $nom) {
            $cat->creer($nom);
        }
    }

    private function genererConfig(array $p): string
    {
        $config = [
            'db_host' => $p['db_host'],
            'db_port' => (int) $p['db_port'],
            'db_name' => $p['db_name'],
            'db_user' => $p['db_user'],
            'db_pass' => $p['db_pass'],
        ];
        return "<?php\n// Fichier généré par l'installateur e-bazar le " . date('Y-m-d H:i') . "\n"
             . "defined('EBAZAR') or exit;\n\nreturn " . var_export($config, true) . ";\n";
    }
}
