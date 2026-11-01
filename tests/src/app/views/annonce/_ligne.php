<?php defined('EBAZAR') or exit;
/** Vue partielle : une annonce sur une ligne (vignette, titre, prix, lien). Attend $a. */ ?>
<a class="ligne" href="<?= e(url('annonce', 'voir', ['id' => $a['id']])) ?>">
    <div class="vignette">
        <?php if (!empty($a['vignette'])): ?>
            <img src="<?= e(photo_url($a['vignette'])) ?>" alt="" loading="lazy">
        <?php else: ?>
            <div class="sans-photo"><?= icone('image') ?></div>
        <?php endif; ?>
    </div>
    <div>
        <span class="ligne-titre"><?= e($a['titre']) ?></span>
        <div class="ligne-meta">
            <span class="puce puce-vert"><?= icone('etiquette') ?><?= e($a['categorie_nom']) ?></span>
            <?php if (!empty($a['livraison_postale'])): ?><span class="puce"><?= icone('camion') ?>Envoi postal</span><?php endif; ?>
            <?php if (!empty($a['livraison_main_propre'])): ?><span class="puce"><?= icone('main') ?>Main propre</span><?php endif; ?>
            <span class="ligne-date"><?= e(il_y_a($a['date_depot'])) ?></span>
        </div>
    </div>
    <div class="ligne-droite">
        <span class="prix<?= (float) $a['prix'] == 0 ? ' prix-gratuit' : '' ?>"><?= e(prix($a['prix'])) ?></span>
        <span class="ligne-voir">Voir l’annonce <?= icone('fleche') ?></span>
    </div>
</a>
