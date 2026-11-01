<?php
defined('EBAZAR') or exit;

class HomeController extends Controller
{
    /** GET / — catégories avec compteurs + 4 dernières annonces. */
    public function index(): void
    {
        $this->vue('home/index', [
            'titre'      => 'Petites annonces entre particuliers',
            'pageActive' => 'accueil',
            'categories' => (new Categorie())->toutesAvecCompte(),
            'dernieres'  => (new Annonce())->dernieres(4),
            'stats'      => (new Annonce())->statistiquesGlobales(),
            'nbMembres'  => (new Utilisateur())->compterMembres(),
        ]);
    }

    public function introuvable(): void
    {
        $this->erreur(404, 'La page demandée n’existe pas.');
    }

    public function erreurInterne(): void
    {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        $this->vue('erreur/erreur', [
            'titre' => 'Erreur', 'code' => 500,
            'message' => 'Une erreur inattendue est survenue. Elle a été enregistrée, veuillez réessayer.',
        ], 500);
    }
}
