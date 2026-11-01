<?php
defined('EBAZAR') or exit;

class AdminController extends Controller
{
    private const ONGLETS = ['apercu', 'annonces', 'membres', 'categories', 'journal'];

    public function __construct()
    {
        $this->exigerAdmin(); // toutes les actions de ce contrôleur sont réservées
    }

    /** GET — tableau de bord administrateur. */
    public function index(): void
    {
        $onglet = in_array($_GET['onglet'] ?? '', self::ONGLETS, true) ? $_GET['onglet'] : 'apercu';
        $filtre = in_array($_GET['filtre'] ?? '', ['securite', 'admin'], true) ? $_GET['filtre'] : null;
        $journal = new Journal();
        $this->vue('admin/index', [
            'titre'        => 'Administration',
            'pageActive'   => 'admin',
            'onglet'       => $onglet,
            'filtre'       => $filtre,
            'categories'   => (new Categorie())->toutesAvecCompte(),
            'annonces'     => (new Annonce())->toutes(),
            'utilisateurs' => (new Utilisateur())->membres(),
            'statsAnnonces'=> (new Annonce())->statistiquesGlobales(),
            'statsSecu'    => $journal->statistiques(),
            'evenements'   => $journal->derniers($onglet === 'journal' ? 200 : 8, $onglet === 'journal' ? $filtre : 'securite'),
        ]);
    }

    private function retour(string $onglet): never
    {
        $this->rediriger(url('admin', 'index', ['onglet' => $onglet]));
    }

    public function supprimerAnnonce(): void
    {
        $this->exigerPost();
        $modele = new Annonce();
        if ($a = $modele->trouver($id = $this->idParam())) {
            $modele->supprimer($id);
            Journal::ajouter('admin_suppr_annonce', Session::id(), Session::utilisateur()['email'], "#$id « {$a['titre']} » de {$a['vendeur_email']}");
            Session::flash('succes', 'Annonce supprimée.');
        }
        $this->retour('annonces');
    }

    public function supprimerUtilisateur(): void
    {
        $this->exigerPost();
        $modele = new Utilisateur();
        $u = $modele->trouver($id = $this->idParam());
        if ($u && $u['role'] === 'membre') {
            $modele->supprimer($id);
            Journal::ajouter('admin_suppr_membre', Session::id(), Session::utilisateur()['email'], "Membre {$u['email']} et ses annonces");
            Session::flash('succes', 'Le membre ' . $u['email'] . ' et ses annonces ont été supprimés.');
        }
        $this->retour('membres');
    }

    public function ajouterCategorie(): void
    {
        $this->exigerPost();
        $modele = new Categorie();
        $nom = trim((string) ($_POST['nom'] ?? ''));
        if ($err = $modele->validerNom($nom)) {
            Session::flash('erreur', $err);
        } else {
            $modele->creer($nom);
            Journal::ajouter('admin_categorie', Session::id(), Session::utilisateur()['email'], "Ajout « $nom »");
            Session::flash('succes', "Catégorie « $nom » ajoutée.");
        }
        $this->retour('categories');
    }

    public function renommerCategorie(): void
    {
        $this->exigerPost();
        $modele = new Categorie();
        $id = $this->idParam();
        $nom = trim((string) ($_POST['nom'] ?? ''));
        $cat = $modele->trouver($id);
        if (!$cat) {
            Session::flash('erreur', 'Catégorie introuvable.');
        } elseif ($err = $modele->validerNom($nom, $id)) {
            Session::flash('erreur', $err);
        } else {
            $modele->renommer($id, $nom);
            Journal::ajouter('admin_categorie', Session::id(), Session::utilisateur()['email'], "« {$cat['nom']} » renommée en « $nom »");
            Session::flash('succes', "Catégorie renommée en « $nom ».");
        }
        $this->retour('categories');
    }
}
