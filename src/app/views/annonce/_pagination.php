<?php defined('EBAZAR') or exit;
/** Pagination. Attend $page, $nbPages et $lien (fonction int => URL). */
if ($nbPages > 1): ?>
<nav class="pagination" aria-label="Pagination">
    <?php if ($page > 1): ?>
        <a href="<?= e($lien($page - 1)) ?>" aria-label="Page précédente"><?= icone('gauche') ?></a>
    <?php else: ?>
        <span class="inactif"><?= icone('gauche') ?></span>
    <?php endif; ?>
    <?php for ($i = 1; $i <= $nbPages; $i++):
        if ($i !== 1 && $i !== $nbPages && abs($i - $page) > 2) {
            if (abs($i - $page) === 3) { echo '<span class="inactif">…</span>'; }
            continue;
        } ?>
        <?php if ($i === $page): ?>
            <span class="courante" aria-current="page"><?= $i ?></span>
        <?php else: ?>
            <a href="<?= e($lien($i)) ?>"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    <?php if ($page < $nbPages): ?>
        <a href="<?= e($lien($page + 1)) ?>" aria-label="Page suivante"><?= icone('droite') ?></a>
    <?php else: ?>
        <span class="inactif"><?= icone('droite') ?></span>
    <?php endif; ?>
</nav>
<?php endif; ?>
