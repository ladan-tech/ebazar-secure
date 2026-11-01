<?php defined('EBAZAR') or exit; /** Petite vignette. Attend $a. */ ?>
<span class="mini-vignette">
    <?php if (!empty($a['vignette'])): ?>
        <img src="<?= e(photo_url($a['vignette'])) ?>" alt="" loading="lazy">
    <?php else: ?>
        <span class="sans-photo"><?= icone('image') ?></span>
    <?php endif; ?>
</span>
