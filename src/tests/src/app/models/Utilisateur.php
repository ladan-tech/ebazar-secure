<?php
defined('EBAZAR') or exit;

class Utilisateur extends Model
{
    /** Mots de passe trop courants refusés d'office (extrait des listes de fuites). */
    private const MDP_INTERDITS = [
        'motdepasse', 'password', 'azerty', 'qwerty', '123456', 'bonjour', 'soleil',
        'iloveyou', 'welcome', 'ebazar', 'abc123', 'loulou', 'doudou', 'marseille', 'football',
    ];

    /** Algorithme de hachage : Argon2id si disponible, sinon bcrypt. */
    public static function algo(): string|int|null
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    }

    public static function hacher(string $mdp): string
    {
        return password_hash($mdp, self::algo());
    }

    /**
     * Politique de mot de passe (recommandations ANSSI / CNIL) :
     * 12 caractères minimum, minuscule, majuscule, chiffre, caractère spécial,
     * ne contient pas l'e-mail, pas un mot de passe courant.
     * Renvoie la liste des règles non respectées.
     */
    public static function verifierPolitique(string $mdp, string $email = ''): array
    {
        $erreurs = [];
        if (mb_strlen($mdp) < 12) {
            $erreurs[] = 'au moins 12 caractères';
        }
        if (mb_strlen($mdp) > 128) {
            $erreurs[] = '128 caractères maximum';
        }
        if (!preg_match('/\p{Ll}/u', $mdp)) {
            $erreurs[] = 'une lettre minuscule';
        }
        if (!preg_match('/\p{Lu}/u', $mdp)) {
            $erreurs[] = 'une lettre majuscule';
        }
        if (!preg_match('/\d/', $mdp)) {
            $erreurs[] = 'un chiffre';
        }
        if (!preg_match('/[^\p{L}\d]/u', $mdp)) {
            $erreurs[] = 'un caractère spécial';
        }
        $bas = mb_strtolower($mdp);
        $local = mb_strtolower(strstr($email, '@', true) ?: '');
        if ($local !== '' && mb_strlen($local) >= 3 && str_contains($bas, $local)) {
            $erreurs[] = 'ne pas contenir votre adresse e-mail';
        }
        foreach (self::MDP_INTERDITS as $interdit) {
            if (str_contains($bas, $interdit)) {
                $erreurs[] = 'ne pas contenir un mot trop courant (« ' . $interdit . ' »)';
                break;
            }
        }
        return $erreurs;
    }

    public function trouverParEmail(string $email): ?array
    {
        return $this->requete('SELECT * FROM utilisateur WHERE email = ?', [$email])->fetch() ?: null;
    }

    public function trouver(int $id): ?array
    {
        return $this->requete('SELECT * FROM utilisateur WHERE id = ?', [$id])->fetch() ?: null;
    }

    public function versionSession(int $id): ?int
    {
        $v = $this->requete('SELECT version_session FROM utilisateur WHERE id = ?', [$id])->fetchColumn();
        return $v === false ? null : (int) $v;
    }

    public function creer(string $email, string $motDePasse, string $role = 'membre'): int
    {
        $this->requete(
            'INSERT INTO utilisateur (email, mot_de_passe, role) VALUES (?, ?, ?)',
            [$email, self::hacher($motDePasse), $role]
        );
        return (int) $this->db->lastInsertId();
    }

    /** Vérifie les identifiants ; renvoie l'utilisateur ou null. */
    public function authentifier(string $email, string $motDePasse): ?array
    {
        $u = $this->trouverParEmail($email);
        if ($u && password_verify($motDePasse, $u['mot_de_passe'])) {
            if (password_needs_rehash($u['mot_de_passe'], self::algo())) {
                $this->requete('UPDATE utilisateur SET mot_de_passe = ? WHERE id = ?', [self::hacher($motDePasse), (int) $u['id']]);
            }
            return $u;
        }
        // Compte inexistant : on calcule quand même un hachage pour que le temps de
        // réponse ne révèle pas si l'adresse est inscrite (anti-énumération).
        if (!$u) {
            password_verify($motDePasse, defined('PASSWORD_ARGON2ID')
                ? '$argon2id$v=19$m=65536,t=4,p=1$WEEzMWo5NjZxbkR3MGhOQw$JobBZ2fL5LJODsZzzEAE2oxjW8aL4bLyKp/sWtH+vXU'
                : '$2y$10$8f.39YUBeGGA3KBS4acIROqY06NwITJlvAgOZ3tNcPDFtPss7sAZq');
        }
        return null;
    }

    public function noterConnexion(int $id): void
    {
        $this->requete('UPDATE utilisateur SET derniere_connexion = NOW() WHERE id = ?', [$id]);
    }

    public function changerMotDePasse(int $id, string $nouveau): int
    {
        $this->requete(
            'UPDATE utilisateur SET mot_de_passe = ?, version_session = version_session + 1 WHERE id = ?',
            [self::hacher($nouveau), $id]
        );
        return (int) $this->versionSession($id);
    }

    /** Invalide toutes les sessions ouvertes du compte ; renvoie la nouvelle version. */
    public function revoquerSessions(int $id): int
    {
        $this->requete('UPDATE utilisateur SET version_session = version_session + 1 WHERE id = ?', [$id]);
        return (int) $this->versionSession($id);
    }

    /** Liste des membres (hors administrateur) avec quelques indicateurs. */
    public function membres(): array
    {
        return $this->requete(
            "SELECT u.id, u.email, u.date_inscription, u.derniere_connexion,
                    (SELECT COUNT(*) FROM annonce a WHERE a.vendeur_id = u.id) AS nb_annonces,
                    (SELECT COUNT(*) FROM annonce a WHERE a.acheteur_id = u.id) AS nb_achats
             FROM utilisateur u WHERE u.role = 'membre' ORDER BY u.date_inscription DESC"
        )->fetchAll();
    }

    public function compterMembres(): int
    {
        return (int) $this->requete("SELECT COUNT(*) FROM utilisateur WHERE role = 'membre'")->fetchColumn();
    }

    /** Supprime un membre et toutes ses annonces (photos comprises). */
    public function supprimer(int $id): void
    {
        $annonces = new Annonce($this->db);
        $photos = new Photo($this->db);
        $fichiers = [];
        $this->db->beginTransaction();
        try {
            foreach ($annonces->idsDuVendeur($id) as $idAnnonce) {
                $fichiers = array_merge($fichiers, $photos->fichiersDe($idAnnonce));
            }
            // ON DELETE CASCADE supprime les annonces et leurs photos en base
            $this->requete("DELETE FROM utilisateur WHERE id = ? AND role = 'membre'", [$id]);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
        Photo::effacerFichiers($fichiers); // fichiers effacés seulement après validation
    }
}
