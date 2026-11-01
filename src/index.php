<?php
/**
 * e-bazar — Contrôleur frontal (point d'entrée unique de l'application).
 *
 * Toutes les requêtes passent par ce fichier :
 *     index.php?c=<controleur>&a=<action>[&param=...]
 */
declare(strict_types=1);

define('EBAZAR', true);
define('ROOT', __DIR__);
define('APP', ROOT . '/app');
define('CONFIG_FILE', ROOT . '/config/config.php');
define('UPLOAD_DIR', ROOT . '/uploads');

// En production, aucune erreur PHP n'est affichée à l'utilisateur (fuite d'informations) :
// elles sont uniquement journalisées.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
ini_set('expose_php', '0');
header_remove('X-Powered-By');

require APP . '/core/helpers.php';
require APP . '/core/Session.php';
require APP . '/core/Database.php';
require APP . '/core/Model.php';
require APP . '/core/Controller.php';
require APP . '/core/App.php';

spl_autoload_register(function (string $classe): void {
    if (!preg_match('/^[A-Za-z]+$/', $classe)) {
        return;
    }
    foreach (['models', 'controllers'] as $dossier) {
        $fichier = APP . "/$dossier/$classe.php";
        if (is_file($fichier)) {
            require $fichier;
            return;
        }
    }
});

envoyer_entetes_securite();
Session::demarrer();
(new App())->executer();
