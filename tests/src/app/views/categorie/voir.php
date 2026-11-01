<?php defined('EBAZAR') or exit; ?>
<nav class="fil" aria-label="Fil d’Ariane">
    <a href="<?= e(url()) ?>">Accueil</a><?= icone('droite') ?><span><?= e($categorie['nom']) ?></span>
</nav>

<div class="mise-en-page-liste">
    <aside class="carte">
        <ul class="liste-cat-laterale">
            <?php foreach ($categories as $c): ?>
                <li><a href="<?= e(url('categorie', 'voir', ['id' => $c['id']])) ?>" class="<?= (int) $c['id'] === (int) $categorie['id'] ? 'actif' : '' ?>">
                    <?= icone_categorie($c['nom']) ?> <?= e($c['nom']) ?> <span class="compteur-pastille"><?= (int) $c['nb_biens'] ?></span>
                </a></li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <div>
        <div class="titre-page">
            <span class="titre-page-ic"><?= icone_categorie($categorie['nom']) ?></span>
            <div>
                <h1><?= e($categorie['nom']) ?></h1>
                <p><?= e(pluriel($total, 'bien')) ?> en vente<?= $nbPages > 1 ? ' · page ' . $page . ' sur ' . $nbPages : '' ?></p>
            </div>
        </div>

        <?php if (!$annonces): ?>
            <div class="carte vide">
                <div class="vide-ic"><?= icone('paquet') ?></div>
                <h3>Aucun bien dans cette catégorie</h3>
                <p>Revenez bientôt ou déposez votre propre annonce.</p>
                <a class="btn btn-primaire" href="<?= e(url('annonce', 'deposer')) ?>"><?= icone('plus') ?> Déposer une annonce</a>
            </div>
        <?php else: ?>
            <div class="liste-annonces">
                <?php foreach ($annonces as $a) { require APP . '/views/annonce/_ligne.php'; } ?>
            </div>
            <?php $lien = fn(int $p) => url('categorie', 'voir', ['id' => $categorie['id'], 'page' => $p]);
                  require APP . '/views/annonce/_pagination.php'; ?>
        <?php endif; ?>
    </div>
</div>
