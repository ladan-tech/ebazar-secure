<?php
defined('EBAZAR') or exit;

/**
 * Gestion sécurisée de la session.
 *
 *  - cookie HttpOnly + SameSite + Secure (si HTTPS), mode strict (refus des ID inconnus)
 *  - régénération de l'identifiant à la connexion et toutes les 10 minutes
 *  - expiration après 30 min d'inactivité et 8 h au maximum
 *  - empreinte du navigateur (User-Agent) : un cookie volé utilisé ailleurs est rejeté
 *  - « version de session » stockée en base : changer de mot de passe ou cliquer sur
 *    « déconnecter tous les appareils » invalide immédiatement toutes les autres sessions
 *  - jeton CSRF renouvelé à chaque connexion
 */
class Session
{
    public const INACTIVITE_MAX   = 30 * 60;      // 30 minutes
    public const DUREE_MAX        = 8 * 3600;     // 8 heures
    public const REGENERATION     = 10 * 60;      // nouvel identifiant toutes les 10 min

    private static ?string $raisonFin = null;

    public static function demarrer(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.gc_maxlifetime', (string) self::DUREE_MAX);

        session_name('EBAZARSESSID');
        session_set_cookie_params([
            'lifetime' => 0,                       // cookie de session (effacé à la fermeture)
            'path'     => base_path(),
            'httponly' => true,                    // inaccessible au JavaScript (vol par XSS)
            'samesite' => 'Lax',                   // non envoyé par les POST inter-sites (CSRF)
            'secure'   => self::estHttps(),
        ]);
        session_start();
    }

    public static function estHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }

    /**
     * Contrôles effectués à CHAQUE requête pour un utilisateur connecté.
     * $versionEnBase : fonction qui renvoie la version de session actuelle de l'utilisateur
     * (null si le compte n'existe plus).
     */
    public static function verifier(callable $versionEnBase): void
    {
        $u = $_SESSION['utilisateur'] ?? null;
        if (!$u) {
            return;
        }
        $maintenant = time();
        $raison = null;

        if ($maintenant - ($_SESSION['derniere_activite'] ?? 0) > self::INACTIVITE_MAX) {
            $raison = 'Votre session a expiré après 30 minutes d’inactivité.';
        } elseif ($maintenant - ($_SESSION['debut'] ?? 0) > self::DUREE_MAX) {
            $raison = 'Votre session a dépassé la durée maximale, veuillez vous reconnecter.';
        } elseif (!hash_equals($_SESSION['empreinte'] ?? '', self::empreinte())) {
            $raison = 'Session invalide (changement de navigateur détecté).';
            Journal::ajouter('session_suspecte', $u['id'], $u['email'], 'Empreinte navigateur différente');
        } else {
            $version = $versionEnBase((int) $u['id']);
            if ($version === null || $version !== (int) $u['version']) {
                $raison = 'Vous avez été déconnecté (session révoquée ou compte supprimé).';
            }
        }

        if ($raison) {
            self::deconnecter();
            self::demarrer();
            self::flash('info', $raison);
            self::$raisonFin = $raison;
            return;
        }

        $_SESSION['derniere_activite'] = $maintenant;
        if ($maintenant - ($_SESSION['regeneree'] ?? 0) > self::REGENERATION) {
            session_regenerate_id(true);
            $_SESSION['regeneree'] = $maintenant;
        }
    }

    public static function sessionTerminee(): bool
    {
        return self::$raisonFin !== null;
    }

    private static function empreinte(): string
    {
        return hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' . ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
    }

    /* ---------- Utilisateur connecté ---------- */

    public static function connecter(array $utilisateur): void
    {
        $redirection = $_SESSION['apres_connexion'] ?? null;
        session_regenerate_id(true);            // anti-fixation de session
        $_SESSION = [];                          // on repart d'une session vierge
        $t = time();
        $_SESSION['utilisateur'] = [
            'id'      => (int) $utilisateur['id'],
            'email'   => $utilisateur['email'],
            'role'    => $utilisateur['role'],
            'version' => (int) $utilisateur['version_session'],
        ];
        $_SESSION['debut'] = $_SESSION['derniere_activite'] = $_SESSION['regeneree'] = $t;
        $_SESSION['empreinte'] = self::empreinte();
        $_SESSION['csrf'] = bin2hex(random_bytes(32)); // nouveau jeton CSRF
        if ($redirection) {
            $_SESSION['apres_connexion'] = $redirection;
        }
    }

    /** Met à jour la version de session courante (après révocation des autres sessions). */
    public static function majVersion(int $version): void
    {
        if (isset($_SESSION['utilisateur'])) {
            session_regenerate_id(true);
            $_SESSION['utilisateur']['version'] = $version;
        }
    }

    public static function deconnecter(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000, 'path' => $p['path'], 'domain' => $p['domain'],
                'secure' => $p['secure'], 'httponly' => $p['httponly'], 'samesite' => $p['samesite'] ?: 'Lax',
            ]);
        }
        session_destroy();
    }

    public static function utilisateur(): ?array
    {
        return $_SESSION['utilisateur'] ?? null;
    }

    public static function id(): ?int
    {
        return self::utilisateur()['id'] ?? null;
    }

    public static function estConnecte(): bool
    {
        return self::utilisateur() !== null;
    }

    public static function estAdmin(): bool
    {
        return (self::utilisateur()['role'] ?? null) === 'admin';
    }

    public static function estMembre(): bool
    {
        return (self::utilisateur()['role'] ?? null) === 'membre';
    }

    /** Secondes restantes avant expiration pour inactivité (affiché dans l'interface). */
    public static function minutesRestantes(): int
    {
        return (int) ceil(self::INACTIVITE_MAX / 60);
    }

    /* ---------- Messages flash ---------- */

    public static function flash(string $type, string $message): void
    {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function lireFlash(): array
    {
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $messages;
    }

    /* ---------- Reprise d'une opération après connexion ---------- */

    public static function memoriserRedirection(string $url): void
    {
        $_SESSION['apres_connexion'] = $url;
    }

    public static function extraireRedirection(): ?string
    {
        $url = $_SESSION['apres_connexion'] ?? null;
        unset($_SESSION['apres_connexion']);
        // Seule une URL interne est acceptée (pas de redirection ouverte)
        if (is_string($url) && str_starts_with($url, base_path()) && !str_contains($url, '//') && !preg_match('/[\r\n\\\\]/', $url)) {
            return $url;
        }
        return null;
    }

    /* ---------- CSRF ---------- */

    public static function jetonCsrf(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function verifierCsrf(?string $jeton): bool
    {
        return is_string($jeton) && hash_equals(self::jetonCsrf(), $jeton);
    }
}
