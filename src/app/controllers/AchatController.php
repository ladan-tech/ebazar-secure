<?php
defined('EBAZAR') or exit;

class AchatController extends Controller
{
    /**
     * GET ?c=achat&a=acheter&id=N  — choix du mode de livraison + confirmation.
     * POST (mode_livraison, csrf)  — enregistrement de l'achat.
     * Un anonyme est redirigé vers la connexion puis revient ici automatiquement.
     */
    public function acheter(): void
    {
        $this->exigerMembre();
        $modele = new Annonce();
        $annonce = $modele->trouver($this->idParam());

        if (!$annonce || $annonce['statut'] !== 'en_vente') {
            Session::flash('erreur', 'Ce bien n’est plus disponible.');
            $this->rediriger(url());
        }
        if ((int) $annonce['vendeur_id'] === Session::id()) {
            Session::flash('erreur', 'Vous ne pouvez pas acheter votre propre bien.');
            $this->rediriger(url('annonce', 'voir', ['id' => $annonce['id']]));
        }

        $modes = [];
        if ($annonce['livraison_postale']) {
            $modes['postale'] = MODES_LIVRAISON['postale'];
        }
        if ($annonce['livraison_main_propre']) {
            $modes['main_propre'] = MODES_LIVRAISON['main_propre'];
        }

        $erreur = null;
        if ($this->estPost()) {
            $this->exigerCsrf();
            $mode = (string) ($_POST['mode_livraison'] ?? '');
            if (!isset($modes[$mode])) {
                $erreur = 'Veuillez choisir un mode de livraison proposé par le vendeur.';
            } elseif ($modele->acheter((int) $annonce['id'], (int) Session::id(), $mode)) {
                Session::flash('succes', 'Achat confirmé ! Retrouvez-le dans « Mes achats ».');
                $this->rediriger(url('compte'));
            } else {
                Session::flash('erreur', 'Ce bien vient d’être vendu à quelqu’un d’autre.');
                $this->rediriger(url());
            }
        }

        $this->vue('achat/acheter', [
            'titre'   => 'Acheter : ' . $annonce['titre'],
            'annonce' => $annonce,
            'modes'   => $modes,
            'erreur'  => $erreur,
        ]);
    }

    /** POST — l'acheteur confirme la réception : le bien disparaît de toutes les listes. */
    public function confirmerReception(): void
    {
        $this->exigerMembre();
        $this->exigerPost();
        $modele = new Annonce();
        $annonce = $modele->trouver($this->idParam());
        if (!$annonce || $annonce['statut'] !== 'vendue' || (int) $annonce['acheteur_id'] !== Session::id()) {
            $this->erreur(403, 'Opération impossible.');
        }
        $modele->supprimer((int) $annonce['id']);
        Session::flash('succes', 'Réception confirmée. Merci !');
        $this->rediriger(url('compte'));
    }
}
