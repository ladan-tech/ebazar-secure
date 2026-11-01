<?php
defined('EBAZAR') or exit;

/**
 * Journal de sécurité : sert à la fois
 *  - à la limitation des tentatives de connexion (anti force brute),
 *  - au journal d'audit consultable par l'administrateur et par chaque membre.
 */
class Journal extends Model
{
    /** Fenêtre et seuils de la limitation des tentatives. */
    public const FENETRE_MIN       = 15;  // minutes
    public const MAX_ECHECS_COMPTE = 5;   // échecs pour un même couple (e-mail, IP)
    public const MAX_ECHECS_IP     = 20;  // échecs toutes adresses confondues pour une IP
    public const MAX_INSCRIPTIONS  = 5;   // inscriptions par IP et par heure

    public const LIBELLES = [
        'connexion_reussie'   => ['Connexion réussie', 'succes'],
        'connexion_echouee'   => ['Échec de connexion', 'alerte'],
        'connexion_bloquee'   => ['Connexion bloquée (trop d’essais)', 'danger'],
        'deconnexion'         => ['Déconnexion', 'neutre'],
        'inscription'         => ['Inscription', 'info'],
        'mdp_modifie'         => ['Mot de passe modifié', 'info'],
        'sessions_revoquees'  => ['Sessions révoquées', 'info'],
        'session_suspecte'    => ['Session suspecte rejetée', 'danger'],
        'admin_suppr_annonce' => ['Annonce supprimée (admin)', 'alerte'],
        'admin_suppr_membre'  => ['Membre supprimé (admin)', 'danger'],
        'admin_categorie'     => ['Catégorie modifiée (admin)', 'neutre'],
        'installation'        => ['Installation', 'neutre'],
    ];

    public static function ip(): string
    {
        // REMOTE_ADDR uniquement : X-Forwarded-For est falsifiable par le client
        return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
    }

    /** Ajout « silencieux » : une panne du journal ne doit pas bloquer l'application. */
    public static function ajouter(string $evenement, ?int $utilisateurId = null, ?string $email = null, ?string $details = null, ?PDO $db = null): void
    {
        try {
            (new self($db))->requete(
                'INSERT INTO journal (utilisateur_id, email, ip, evenement, details) VALUES (?, ?, ?, ?, ?)',
                [$utilisateurId, $email !== null ? mb_substr($email, 0, 190) : null, self::ip(), $evenement,
                 $details !== null ? mb_substr($details, 0, 255) : null]
            );
        } catch (Throwable $e) {
            error_log('[e-bazar] journal : ' . $e->getMessage());
        }
    }

    /**
     * État de la limitation pour (email, IP).
     * Renvoie ['bloque' => bool, 'attente' => secondes, 'restantes' => essais restants].
     */
    public function etatTentatives(string $email): array
    {
        $ip = self::ip();
        $f = self::FENETRE_MIN;
        // Échecs du couple (email, IP) depuis la dernière connexion réussie, dans la fenêtre
        $c = $this->requete(
            "SELECT COUNT(*) AS n, MAX(date_evt) AS dernier
             FROM journal
             WHERE evenement = 'connexion_echouee' AND email = ? AND ip = ?
               AND date_evt > NOW() - INTERVAL $f MINUTE
               AND date_evt > COALESCE((SELECT MAX(j2.date_evt) FROM journal j2
                                        WHERE j2.evenement = 'connexion_reussie' AND j2.email = ? AND j2.ip = ?), '1970-01-01')",
            [$email, $ip, $email, $ip]
        )->fetch();
        // Échecs de l'IP, tous comptes confondus (attaque par dictionnaire sur plusieurs comptes)
        $i = $this->requete(
            "SELECT COUNT(*) AS n, MAX(date_evt) AS dernier FROM journal
             WHERE ip = ? AND evenement = 'connexion_echouee' AND date_evt > NOW() - INTERVAL $f MINUTE",
            [$ip]
        )->fetch();

        $bloque = false;
        $dernier = null;
        if ((int) $c['n'] >= self::MAX_ECHECS_COMPTE) {
            $bloque = true;
            $dernier = $c['dernier'];
        } elseif ((int) $i['n'] >= self::MAX_ECHECS_IP) {
            $bloque = true;
            $dernier = $i['dernier'];
        }
        $attente = 0;
        if ($bloque) {
            $attente = (int) $this->requete(
                "SELECT GREATEST(0, TIMESTAMPDIFF(SECOND, NOW(), ? + INTERVAL $f MINUTE))",
                [$dernier]
            )->fetchColumn();
        }
        return [
            'bloque'    => $bloque,
            'attente'   => $attente,
            'restantes' => max(0, self::MAX_ECHECS_COMPTE - (int) $c['n']),
        ];
    }

    public function inscriptionsRecentesIp(): int
    {
        return (int) $this->requete(
            "SELECT COUNT(*) FROM journal WHERE ip = ? AND evenement = 'inscription' AND date_evt > NOW() - INTERVAL 1 HOUR",
            [self::ip()]
        )->fetchColumn();
    }

    public function duMembre(int $utilisateurId, int $limite = 15): array
    {
        return $this->requete(
            'SELECT evenement, ip, details, date_evt FROM journal WHERE utilisateur_id = ? ORDER BY date_evt DESC, id DESC LIMIT ?',
            [$utilisateurId, $limite]
        )->fetchAll();
    }

    /**
     * Échecs visant ce compte (toutes IP) depuis sa dernière connexion réussie.
     * À appeler AVANT d'enregistrer la nouvelle connexion : permet d'avertir le membre.
     */
    public function echecsDepuisDernierSucces(string $email): int
    {
        return (int) $this->requete(
            "SELECT COUNT(*) FROM journal
             WHERE evenement IN ('connexion_echouee','connexion_bloquee') AND email = ?
               AND date_evt >= COALESCE((SELECT MAX(j2.date_evt) FROM journal j2
                                         WHERE j2.evenement = 'connexion_reussie' AND j2.email = ?), '1970-01-01')",
            [$email, $email]
        )->fetchColumn();
    }

    public function derniers(int $limite = 100, ?string $filtre = null): array
    {
        $where = '';
        $params = [];
        if ($filtre === 'securite') {
            $where = "WHERE evenement IN ('connexion_echouee','connexion_bloquee','session_suspecte')";
        } elseif ($filtre === 'admin') {
            $where = "WHERE evenement LIKE 'admin\\_%'";
        }
        $params[] = $limite;
        return $this->requete(
            "SELECT * FROM journal $where ORDER BY date_evt DESC, id DESC LIMIT ?",
            $params
        )->fetchAll();
    }

    public function statistiques(): array
    {
        return $this->requete(
            "SELECT
               SUM(evenement = 'connexion_echouee' AND date_evt > NOW() - INTERVAL 24 HOUR) AS echecs_24h,
               SUM(evenement = 'connexion_bloquee' AND date_evt > NOW() - INTERVAL 24 HOUR) AS blocages_24h,
               SUM(evenement = 'connexion_reussie' AND date_evt > NOW() - INTERVAL 24 HOUR) AS connexions_24h,
               COUNT(DISTINCT CASE WHEN evenement = 'connexion_echouee' AND date_evt > NOW() - INTERVAL 24 HOUR THEN ip END) AS ips_suspectes
             FROM journal"
        )->fetch() ?: [];
    }
}
