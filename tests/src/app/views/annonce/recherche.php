<?php defined('EBAZAR') or exit; ?>
<div class="titre-page">
    <span class="titre-page-ic"><?= icone('recherche') ?></span>
    <div>
        <h1><?= $q !== '' ? 'Résultats pour « ' . e($q) . ' »' : 'Toutes les annonces' ?></h1>
        <p><?= mb_strlen($q) === 1 ? 'Saisissez au moins 2 caractères.' : e(pluriel($total, $q !== '' ? 'résultat' : 'bien')) . ($q === '' ? ' en vente' : '') ?></p>
    </div>
</div>

<form class="heros-recherche bordure" action="<?= e(base_path() . 'index.php') ?>" method="get" role="search">
    <input type="hidden" name="c" value="annonce"><input type="hidden" name="a" value="rechercher">
    <label class="champ-icone"><?= icone('recherche') ?>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Vélo, livre, console…" minlength="2" maxlength="60" aria-label="Rechercher" autofocus>
    </label>
    <button class="btn btn-primaire btn-lg">Rechercher</button>
</form>

<?php if (!$annonces): ?>
    <div class="carte vide">
        <div class="vide-ic"><?= icone('recherche') ?></div>
        <h3><?= $q !== '' ? 'Aucun résultat' : 'Aucun bien en vente' ?></h3>
        <p>Essayez avec d’autres mots-clés ou parcourez les catégories.</p>
        <a class="btn btn-secondaire" href="<?= e(url()) ?>">Voir les catégories</a>
    </div>
<?php else: ?>
    <div class="liste-annonces">
        <?php foreach ($annonces as $a) { require APP . '/views/annonce/_ligne.php'; } ?>
    </div>
    <?php $lien = fn(int $p) => url('annonce', 'rechercher', ['q' => $q, 'page' => $p]);
          require APP . '/views/annonce/_pagination.php'; ?>
<?php endif; ?>
