<?php
defined('EBAZAR') or exit;

class CategorieController extends Controller
{
    /** GET ?c=categorie&a=voir&id=N&page=P — liste paginée (10 par page). */
    public function voir(): void
    {
        $id = $this->idParam();
        $categorie = (new Categorie())->trouver($id);
        if (!$categorie) {
            $this->erreur(404, 'Catégorie introuvable.');
        }
        $modele = new Annonce();
        $total = $modele->compterEnVenteParCategorie($id);
        $nbPages = max(1, (int) ceil($total / Annonce::PAR_PAGE));
        $page = min($this->pageParam(), $nbPages);

        $this->vue('categorie/voir', [
            'titre'     => $categorie['nom'],
            'categorie' => $categorie,
            'annonces'  => $modele->enVenteParCategorie($id, $page),
            'page'      => $page,
            'nbPages'   => $nbPages,
            'total'     => $total,
            'categories'=> (new Categorie())->toutesAvecCompte(),
        ]);
    }
}
