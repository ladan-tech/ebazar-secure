<?php
/**
 * Routeur pour le serveur de développement PHP :   php -S localhost:8000 router.php
 *
 * Le serveur intégré ignore les fichiers .htaccess : ce routeur reproduit les
 * mêmes protections pour que l'environnement de développement soit aussi sûr
 * que la production (Apache).
 */
declare(strict_types=1);

$chemin = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$refuser = static function (): bool {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo "403 Forbidden\n";
    return true;
};

// 1. Traversée de répertoire et fichiers cachés (.htaccess, .git, .env…)
if (str_contains($chemin, '..') || str_contains($chemin, "\0") || preg_match('#/\.#', $chemin)) {
    return $refuser();
}
// 2. Dossiers internes jamais servis directement
if (preg_match('#^/(app|config|sql)(/|$)#i', $chemin)) {
    return $refuser();
}
// 3. Extensions sensibles
if (preg_match('#\.(sql|md|dist|log|ini|sh|bak|swp)$#i', $chemin)) {
    return $refuser();
}
// 4. uploads/ : uniquement des JPEG, jamais de code exécuté
if (preg_match('#^/uploads/#i', $chemin)) {
    if (!preg_match('#^/uploads/[a-f0-9]{32}\.jpg$#', $chemin)) {
        return $refuser();
    }
    header('X-Content-Type-Options: nosniff');
    header("Content-Security-Policy: default-src 'none'");
    return false; // fichier statique servi par le serveur intégré
}
// 5. Ressources statiques publiques
if (preg_match('#^/public/#', $chemin) && is_file(__DIR__ . $chemin)) {
    header('X-Content-Type-Options: nosniff');
    return false;
}
// 6. Tout le reste passe par le contrôleur frontal
if ($chemin !== '/' && $chemin !== '/index.php') {
    $_GET += [];
    if ($chemin !== '/favicon.ico') {
        $_GET['c'] = $_GET['c'] ?? '__introuvable';
    }
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
return true;
