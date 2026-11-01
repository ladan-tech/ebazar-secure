<?php
defined('EBAZAR') or exit;

/**
 * Routeur : associe le paramètre c (contrôleur) et a (action) à une méthode.
 * Seules les actions déclarées dans la table ci-dessous sont atteignables
 * (liste blanche : impossible d'appeler une méthode interne via l'URL).
 */
class App
{
    private const ROUTES = [
        'home'      => ['HomeController',      ['index']],
        'auth'      => ['AuthController',      ['connexion', 'inscription', 'deconnexion']],
        'categorie' => ['CategorieController', ['voir']],
        'annonce'   => ['AnnonceController',   ['voir', 'deposer', 'supprimer', 'rechercher']],
        'achat'     => ['AchatController',     ['acheter', 'confirmerReception']],
        'compte'    => ['CompteController',    ['index', 'motDePasse', 'revoquerSessions']],
        'admin'     => ['AdminController',     ['index', 'supprimerAnnonce', 'supprimerUtilisateur', 'ajouterCategorie', 'renommerCategorie']],
        'install'   => ['InstallController',   ['index']],
    ];

    public function executer(): void
    {
        $c = (string) ($_GET['c'] ?? 'home');
        $a = (string) ($_GET['a'] ?? 'index');

        try {
            if (!Database::estConfiguree()) {
                // Tant que l'application n'est pas configurée, on force l'installation.
                $c = 'install';
                $a = 'index';
            } else {
                // Contrôles de session à chaque requête (expiration, empreinte, révocation)
                Session::verifier(fn(int $id) => (new Utilisateur())->versionSession($id));
            }

            if (!isset(self::ROUTES[$c]) || !in_array($a, self::ROUTES[$c][1], true)) {
                (new HomeController())->introuvable();
                return;
            }
            $classe = self::ROUTES[$c][0];
            (new $classe())->$a();
        } catch (Throwable $ex) {
            // Message générique pour l'utilisateur, détail uniquement dans le journal du serveur
            error_log('[e-bazar] ' . get_class($ex) . ': ' . $ex->getMessage() . ' @ ' . $ex->getFile() . ':' . $ex->getLine());
            (new HomeController())->erreurInterne();
        }
    }
}
