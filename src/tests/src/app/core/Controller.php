<?php
defined('EBAZAR') or exit;

/**
 * Classe de base des contrôleurs : rendu des vues, redirections, contrôle d'accès.
 */
abstract class Controller
{
    /** Affiche une vue à l'intérieur du gabarit général. */
    protected function vue(string $vue, array $donnees = [], int $code = 200, string $gabarit = 'main'): void
    {
        http_response_code($code);
        if (Session::estConnecte()) {
            // Pages privées : jamais mises en cache (bouton « précédent » après déconnexion)
            header('Cache-Control: no-store, no-cache, must-revalidate, private');
            header('Pragma: no-cache');
        }
        $fichierVue = APP . '/views/' . $vue . '.php';
        extract($donnees, EXTR_SKIP);
        ob_start();
        require $fichierVue;
        $contenu = ob_get_clean();
        $titre = $donnees['titre'] ?? 'e-bazar';
        $pageActive = $donnees['pageActive'] ?? '';
        require APP . '/views/layout/' . $gabarit . '.php';
    }

    protected function rediriger(string $url): never
    {
        header('Location: ' . $url, true, 303);
        exit;
    }

    protected function estPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    /** Vérifie le jeton CSRF de tout formulaire POST. */
    protected function exigerCsrf(): void
    {
        if (!Session::verifierCsrf($_POST['csrf'] ?? null)) {
            Session::flash('erreur', 'Votre session a expiré ou la requête est invalide, veuillez recommencer.');
            $this->rediriger(url());
        }
    }

    protected function exigerPost(): void
    {
        if (!$this->estPost()) {
            $this->erreur(405, 'Méthode non autorisée.');
        }
        $this->exigerCsrf();
    }

    /** Utilisateur connecté requis ; sinon redirection vers la connexion puis reprise. */
    protected function exigerConnexion(): void
    {
        if (!Session::estConnecte()) {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && !Session::sessionTerminee()) {
                $parts = parse_url($_SERVER['REQUEST_URI'] ?? '');
                $chemin = ($parts['path'] ?? base_path()) . (isset($parts['query']) ? '?' . $parts['query'] : '');
                Session::memoriserRedirection($chemin);
                Session::flash('info', 'Connectez-vous pour poursuivre, vous serez ramené ici automatiquement.');
            }
            $this->rediriger(url('auth', 'connexion'));
        }
    }

    /** Membre inscrit requis (l'administrateur ne produit pas de contenu). */
    protected function exigerMembre(): void
    {
        $this->exigerConnexion();
        if (!Session::estMembre()) {
            $this->erreur(403, "L'administrateur ne peut pas effectuer cette opération.");
        }
    }

    protected function exigerAdmin(): void
    {
        $this->exigerConnexion();
        if (!Session::estAdmin()) {
            $this->erreur(403, 'Accès réservé à l’administrateur.');
        }
    }

    protected function idParam(string $nom = 'id'): int
    {
        $id = filter_var($_GET[$nom] ?? $_POST[$nom] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false || $id === null) {
            $this->erreur(404, 'Ressource introuvable.');
        }
        return $id;
    }

    protected function pageParam(): int
    {
        return filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
    }

    protected function erreur(int $code, string $message): never
    {
        $this->vue('erreur/erreur', ['titre' => "Erreur $code", 'code' => $code, 'message' => $message], $code);
        exit;
    }
}
