<?php
defined('EBAZAR') or exit;

/**
 * Tableau de bord du membre : vue d'ensemble, annonces en vente, ventes, achats, sécurité.
 */
class CompteController extends Controller
{
    private const ONGLETS = ['apercu', 'annonces', 'ventes', 'achats', 'securite'];

    public function index(): void
    {
        $this->exigerConnexion();
        if (Session::estAdmin()) {
            $this->rediriger(url('admin'));
        }
        $id = (int) Session::id();
        $annonces = new Annonce();
        $onglet = in_array($_GET['onglet'] ?? '', self::ONGLETS, true) ? $_GET['onglet'] : 'apercu';

        $this->vue('compte/index', [
            'titre'      => 'Mon tableau de bord',
            'pageActive' => 'compte',
            'onglet'     => $onglet,
            'stats'      => $annonces->statistiquesMembre($id),
            'enVente'    => $annonces->duVendeur($id, 'en_vente'),
            'vendus'     => $annonces->vendusParVendeur($id),
            'achetes'    => $annonces->acheteesPar($id),
            'membre'     => (new Utilisateur())->trouver($id),
            'historique' => (new Journal())->duMembre($id, 15),
            'erreursMdp' => $_SESSION['erreurs_mdp'] ?? [],
        ]);
        unset($_SESSION['erreurs_mdp']);
    }

    /** POST — changement de mot de passe (l'ancien est exigé). */
    public function motDePasse(): void
    {
        $this->exigerMembre();
        $this->exigerPost();
        $u = Session::utilisateur();
        $modele = new Utilisateur();
        $actuel = (string) ($_POST['actuel'] ?? '');
        $nouveau = (string) ($_POST['nouveau'] ?? '');
        $erreurs = [];

        $etat = (new Journal())->etatTentatives($u['email']);
        if ($etat['bloque']) {
            $erreurs['actuel'] = 'Trop de tentatives, réessayez plus tard.';
        } elseif (!$modele->authentifier($u['email'], $actuel)) {
            Journal::ajouter('connexion_echouee', $u['id'], $u['email'], 'Mot de passe actuel erroné (changement de mot de passe)');
            $erreurs['actuel'] = 'Mot de passe actuel incorrect.';
        }
        if ($regles = Utilisateur::verifierPolitique($nouveau, $u['email'])) {
            $erreurs['nouveau'] = 'Le nouveau mot de passe doit contenir : ' . implode(', ', $regles) . '.';
        } elseif (hash_equals($actuel, $nouveau)) {
            $erreurs['nouveau'] = 'Le nouveau mot de passe doit être différent de l’actuel.';
        } elseif (!hash_equals($nouveau, (string) ($_POST['confirmation'] ?? ''))) {
            $erreurs['confirmation'] = 'Les deux mots de passe ne correspondent pas.';
        }

        if ($erreurs) {
            $_SESSION['erreurs_mdp'] = $erreurs;
        } else {
            // Nouvelle version de session : toutes les AUTRES sessions sont déconnectées
            Session::majVersion($modele->changerMotDePasse((int) $u['id'], $nouveau));
            Journal::ajouter('mdp_modifie', $u['id'], $u['email']);
            Session::flash('succes', 'Mot de passe modifié. Vos autres sessions ont été déconnectées.');
        }
        $this->rediriger(url('compte', 'index', ['onglet' => 'securite']));
    }

    /** POST — « Déconnecter tous mes autres appareils ». */
    public function revoquerSessions(): void
    {
        $this->exigerMembre();
        $this->exigerPost();
        $u = Session::utilisateur();
        Session::majVersion((new Utilisateur())->revoquerSessions((int) $u['id']));
        Journal::ajouter('sessions_revoquees', $u['id'], $u['email']);
        Session::flash('succes', 'Toutes vos autres sessions ont été fermées.');
        $this->rediriger(url('compte', 'index', ['onglet' => 'securite']));
    }
}
