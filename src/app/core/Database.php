<?php
defined('EBAZAR') or exit;

/**
 * Connexion PDO unique (singleton) construite à partir de config/config.php.
 */
class Database
{
    private static ?PDO $pdo = null;

    public static function estConfiguree(): bool
    {
        return is_file(CONFIG_FILE);
    }

    public static function config(): array
    {
        return require CONFIG_FILE;
    }

    public static function connexion(): PDO
    {
        if (self::$pdo === null) {
            $c = self::config();
            self::$pdo = self::creer($c['db_host'], (int) $c['db_port'], $c['db_name'], $c['db_user'], $c['db_pass']);
        }
        return self::$pdo;
    }

    public static function creer(string $hote, int $port, string $base, string $user, string $mdp): PDO
    {
        $dsn = "mysql:host=$hote;port=$port;dbname=$base;charset=utf8mb4";
        return new PDO($dsn, $user, $mdp, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // vraies requêtes préparées
        ]);
    }
}
