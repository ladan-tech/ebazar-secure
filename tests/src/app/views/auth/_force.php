<?php defined('EBAZAR') or exit; /** Jauge de robustesse + règles (mises à jour en JavaScript). */ ?>
<div class="jauge" aria-hidden="true"><div class="jauge-barre" data-jauge></div></div>
<div class="jauge-libelle" data-jauge-libelle>Robustesse du mot de passe</div>
<ul class="regles" data-regles>
    <li data-regle="longueur">12 caractères minimum</li>
    <li data-regle="minuscule">Une minuscule</li>
    <li data-regle="majuscule">Une majuscule</li>
    <li data-regle="chiffre">Un chiffre</li>
    <li data-regle="special">Un caractère spécial</li>
    <li data-regle="email">Différent de votre e-mail</li>
</ul>
