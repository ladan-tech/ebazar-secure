<?php defined('EBAZAR') or exit; ?>
<div class="page-erreur">
    <div class="page-erreur-code"><?= (int) $code ?></div>
    <h1><?= match ((int) $code) { 403 => 'Accès refusé', 404 => 'Page introuvable', 405 => 'Méthode non autorisée', default => 'Oups, une erreur' } ?></h1>
    <p><?= e($message) ?></p>
    <a class="btn btn-primaire btn-lg" href="<?= e(url()) ?>"><?= icone('accueil') ?> Retour à l’accueil</a>
</div>
