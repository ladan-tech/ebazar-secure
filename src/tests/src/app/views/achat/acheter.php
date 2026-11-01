<?php defined('EBAZAR') or exit; ?>
<nav class="fil" aria-label="Fil d’Ariane">
    <a href="<?= e(url('annonce', 'voir', ['id' => $annonce['id']])) ?>"><?= icone('gauche') ?> Retour à l’annonce</a>
</nav>
<div class="titre-page">
    <span class="titre-page-ic"><?= icone('panier') ?></span>
    <div><h1>Finaliser votre achat</h1><p>Choisissez le mode de livraison puis confirmez.</p></div>
</div>

<form method="post" action="<?= e(url('achat', 'acheter', ['id' => $annonce['id']])) ?>" class="achat">
    <?= csrf_field() ?>
    <div class="carte">
        <div class="carte-tete"><h2><?= icone('camion') ?> Mode de livraison</h2></div>
        <div class="carte-corps">
            <?php if ($erreur): ?><p class="erreur-champ"><?= icone('alerte') ?><?= e($erreur) ?></p><br><?php endif; ?>
            <div class="choix-grille">
                <?php foreach ($modes as $valeur => $libelle): ?>
                    <label class="choix">
                        <input type="radio" name="mode_livraison" value="<?= e($valeur) ?>" required <?= count($modes) === 1 ? 'checked' : '' ?>>
                        <span class="choix-carte">
                            <span class="choix-ic"><?= icone($valeur === 'postale' ? 'camion' : 'main') ?></span>
                            <span><strong><?= e($libelle) ?></strong><small><?= $valeur === 'postale' ? 'Le vendeur vous expédie le colis' : 'Vous rencontrez le vendeur' ?></small></span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <br>
            <div class="note-secu"><?= icone('info') ?><div>Après confirmation, le bien est retiré de la vente. Vous recevrez l’adresse e-mail du vendeur dans « Mes achats » pour organiser la livraison.</div></div>
        </div>
    </div>

    <aside class="carte">
        <div class="recap-objet">
            <div class="vignette">
                <?php if (!empty($annonce['vignette'])): ?><img src="<?= e(photo_url($annonce['vignette'])) ?>" alt="">
                <?php else: ?><div class="sans-photo"><?= icone('image') ?></div><?php endif; ?>
            </div>
            <div><strong><?= e($annonce['titre']) ?></strong><br><span class="puce puce-vert"><?= e($annonce['categorie_nom']) ?></span></div>
        </div>
        <ul class="recap-lignes">
            <li><span>Prix du bien</span><span><?= e(prix($annonce['prix'])) ?></span></li>
            <li class="total"><span>Total</span><span><?= e(prix($annonce['prix'])) ?></span></li>
        </ul>
        <div class="carte-corps">
            <button type="submit" class="btn btn-degrade btn-lg btn-bloc"><?= icone('check') ?> Confirmer l’achat</button>
            <p class="aide">Le paiement et la livraison ne sont pas gérés par la plateforme (hors périmètre du projet).</p>
        </div>
    </aside>
</form>
