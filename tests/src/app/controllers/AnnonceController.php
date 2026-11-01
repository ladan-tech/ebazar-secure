<?php
defined('EBAZAR') or exit;

class AnnonceController extends Controller
{
    /** GET ?c=annonce&a=voir&id=N — page détaillée d'un bien. */
    public function voir(): void
    {
        $annonce = (new Annonce())->trouver($this->idParam());
        if (!$annonce) {
            $this->erreur(404, 'Cette annonce n’existe pas ou plus.');
        }
        $uid = Session::id();
        // Un bien vendu n'est plus visible que par son vendeur, son acheteur et l'admin
        $estVendeur = $uid === (int) $annonce['vendeur_id'];
        $estAcheteur = $uid !== null && $uid === (int) $annonce['acheteur_id'];
        if ($annonce['statut'] !== 'en_vente' && !$estVendeur && !$estAcheteur && !Session::estAdmin()) {
            $this->erreur(404, 'Ce bien a déjà été vendu.');
        }
        $this->vue('annonce/voir', [
            'titre'      => $annonce['titre'],
            'annonce'    => $annonce,
            'photos'     => (new Photo())->deLAnnonce((int) $annonce['id']),
            'estVendeur' => $estVendeur,
        ]);
    }

    /** GET ?c=annonce&a=rechercher&q=...&page=P — recherche plein texte dans les biens en vente. */
    public function rechercher(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $q = mb_substr(preg_replace('/\s+/u', ' ', $q), 0, 60);
        $modele = new Annonce();
        $total = mb_strlen($q) === 1 ? 0 : $modele->compterRecherche($q); // vide = toutes les annonces
        $nbPages = max(1, (int) ceil($total / Annonce::PAR_PAGE));
        $page = min($this->pageParam(), $nbPages);
        $this->vue('annonce/recherche', [
            'titre'    => $q !== '' ? "Recherche : $q" : 'Toutes les annonces',
            'q'        => $q,
            'annonces' => $total ? $modele->rechercher($q, $page) : [],
            'total'    => $total,
            'page'     => $page,
            'nbPages'  => $nbPages,
        ]);
    }

    /** GET : formulaire — POST : dépôt d'une annonce (membre uniquement). */
    public function deposer(): void
    {
        $this->exigerMembre();
        $categories = (new Categorie())->toutes();
        $saisie = ['categorie_id' => 0, 'titre' => '', 'description' => '', 'prix' => '',
                   'livraison_postale' => false, 'livraison_main_propre' => false];
        $erreurs = [];

        if ($this->estPost()) {
            // Requête trop volumineuse : PHP vide $_POST, on le signale clairement
            if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
                Session::flash('erreur', 'Envoi trop volumineux (5 photos de 200 Kio maximum).');
                $this->rediriger(url('annonce', 'deposer'));
            }
            $this->exigerCsrf();
            $modele = new Annonce();
            [$saisie, $erreurs] = $modele->valider($_POST, array_map(fn($c) => (int) $c['id'], $categories));
            [$photos, $errPhotos] = Photo::validerEnvoi($_FILES['photos'] ?? null);
            if ($errPhotos) {
                $erreurs['photos'] = implode(' ', $errPhotos);
            }
            if (!$erreurs) {
                try {
                    $id = $modele->creer((int) Session::id(), $saisie, $photos);
                    Session::flash('succes', 'Votre annonce est en ligne.');
                    $this->rediriger(url('annonce', 'voir', ['id' => $id]));
                } catch (RuntimeException $ex) {
                    $erreurs['photos'] = $ex->getMessage();
                }
            }
        }

        $this->vue('annonce/deposer', [
            'titre'      => 'Déposer une annonce',
            'pageActive' => 'deposer',
            'categories' => $categories,
            'saisie'     => $saisie,
            'erreurs'    => $erreurs,
        ]);
    }

    /** POST — le vendeur supprime l'une de ses annonces non vendues. */
    public function supprimer(): void
    {
        $this->exigerMembre();
        $this->exigerPost();
        $modele = new Annonce();
        $annonce = $modele->trouver($this->idParam());
        if (!$annonce || (int) $annonce['vendeur_id'] !== Session::id()) {
            $this->erreur(403, 'Vous ne pouvez supprimer que vos propres annonces.');
        }
        if ($annonce['statut'] !== 'en_vente') {
            Session::flash('erreur', 'Un bien déjà vendu ne peut pas être supprimé.');
        } else {
            $modele->supprimer((int) $annonce['id']);
            Session::flash('succes', 'Annonce supprimée.');
        }
        $this->rediriger(url('compte'));
    }
}
