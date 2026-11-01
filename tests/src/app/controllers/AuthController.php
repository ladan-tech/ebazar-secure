<?php
defined('EBAZAR') or exit;

class AuthController extends Controller
{
    /** GET : formulaire — POST : création du compte. */
    public function inscription(): void
    {
        if (Session::estConnecte()) {
            $this->rediriger(url());
        }
        $erreurs = [];
        $email = '';
        if ($this->estPost()) {
            $this->exigerCsrf();
            $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
            $mdp = (string) ($_POST['mot_de_passe'] ?? '');
            $mdp2 = (string) ($_POST['confirmation'] ?? '');
            $modele = new Utilisateur();
            $journal = new Journal();

            // Pot de miel : champ invisible que seuls les robots remplissent
            if (!empty($_POST['site_web'])) {
                $this->rediriger(url());
            }
            if ($journal->inscriptionsRecentesIp() >= Journal::MAX_INSCRIPTIONS) {
                $erreurs['email'] = 'Trop de comptes créés depuis votre adresse IP. Réessayez dans une heure.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
                $erreurs['email'] = 'Adresse e-mail invalide.';
            } elseif ($modele->trouverParEmail($email)) {
                $erreurs['email'] = 'Un compte existe déjà avec cette adresse.';
            }
            if ($regles = Utilisateur::verifierPolitique($mdp, $email)) {
                $erreurs['mot_de_passe'] = 'Le mot de passe doit contenir : ' . implode(', ', $regles) . '.';
            } elseif (!hash_equals($mdp, $mdp2)) {
                $erreurs['confirmation'] = 'Les deux mots de passe ne correspondent pas.';
            }
            if (empty($_POST['cgu'])) {
                $erreurs['cgu'] = 'Vous devez accepter les conditions d’utilisation.';
            }

            if (!$erreurs) {
                $id = $modele->creer($email, $mdp);
                Journal::ajouter('inscription', $id, $email);
                Journal::ajouter('connexion_reussie', $id, $email, 'Après inscription');
                $u = $modele->trouver($id);
                $modele->noterConnexion($id);
                Session::connecter($u);
                Session::flash('succes', 'Bienvenue sur e-bazar ! Votre compte a été créé.');
                $this->rediriger(Session::extraireRedirection() ?? url('compte'));
            }
        }
        $this->vue('auth/inscription', ['titre' => 'Créer un compte', 'erreurs' => $erreurs, 'email' => $email], 200, 'auth');
    }

    /** GET : formulaire — POST : authentification (avec limitation des tentatives). */
    public function connexion(): void
    {
        if (Session::estConnecte()) {
            $this->rediriger(url());
        }
        $erreur = null;
        $email = '';
        $journal = new Journal();

        if ($this->estPost()) {
            $this->exigerCsrf();
            $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
            $mdp = (string) ($_POST['mot_de_passe'] ?? '');
            $etat = $journal->etatTentatives($email);

            if ($etat['bloque']) {
                // Compte (pour cette IP) temporairement verrouillé : le mot de passe n'est même pas testé
                Journal::ajouter('connexion_bloquee', null, $email);
                $erreur = 'Trop de tentatives échouées. Pour votre sécurité, réessayez dans '
                        . max(1, (int) ceil($etat['attente'] / 60)) . ' minute(s).';
            } else {
                $u = (new Utilisateur())->authentifier($email, $mdp);
                if ($u) {
                    $echecs = $journal->echecsDepuisDernierSucces($email);
                    Journal::ajouter('connexion_reussie', (int) $u['id'], $email);
                    (new Utilisateur())->noterConnexion((int) $u['id']);
                    Session::connecter($u);
                    Session::flash('succes', 'Bonjour ! Vous êtes connecté.');
                    if ($echecs > 0) {
                        Session::flash('alerte', pluriel($echecs, 'tentative') . ' de connexion échouée(s) sur votre compte depuis votre dernière visite. '
                            . 'Si ce n’était pas vous, changez votre mot de passe.');
                    }
                    $defaut = $u['role'] === 'admin' ? url('admin') : url('compte');
                    $this->rediriger(Session::extraireRedirection() ?? $defaut);
                }
                // Échec : on n'indique jamais si c'est l'e-mail ou le mot de passe qui est faux
                Journal::ajouter('connexion_echouee', null, $email);
                $restantes = $etat['restantes'] - 1;
                $erreur = 'Adresse e-mail ou mot de passe incorrect.';
                if ($restantes <= 0) {
                    $erreur .= ' Connexion bloquée pendant ' . Journal::FENETRE_MIN . ' minutes.';
                } elseif ($restantes <= 3) {
                    $erreur .= ' Encore ' . pluriel($restantes, 'essai') . ' avant blocage temporaire.';
                }
            }
        }
        $this->vue('auth/connexion', ['titre' => 'Connexion', 'erreur' => $erreur, 'email' => $email], 200, 'auth');
    }

    /** POST : fermeture de la session. */
    public function deconnexion(): void
    {
        $this->exigerPost();
        if ($u = Session::utilisateur()) {
            Journal::ajouter('deconnexion', $u['id'], $u['email']);
        }
        Session::deconnecter();
        Session::demarrer();
        Session::flash('info', 'Vous êtes déconnecté. À bientôt !');
        $this->rediriger(url());
    }
}
