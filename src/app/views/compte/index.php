<?php defined('EBAZAR') or exit;
$u = Session::utilisateur();
$nav = [
    'apercu'   => ['Vue d’ensemble', 'grille', null],
    'annonces' => ['Mes annonces', 'paquet', count($enVente)],
    'ventes'   => ['Mes ventes', 'etiquette', count($vendus)],
    'achats'   => ['Mes achats', 'panier', count($achetes)],
    'securite' => ['Sécurité du compte', 'bouclier', null],
];
$lienOnglet = fn(string $o) => url('compte', 'index', ['onglet' => $o]);
$errMdp = fn(string $c) => isset($erreursMdp[$c]) ? '<p class="erreur-champ">' . icone('alerte') . e($erreursMdp[$c]) . '</p>' : ''; ?>

<div class="tdb">
    <aside class="carte tdb-lateral">
        <div class="tdb-profil">
            <span class="avatar avatar-lg"><?= e(initiale($u['email'])) ?></span>
            <div><strong><?= e($u['email']) ?></strong><small>Membre depuis <?= e(date_fr($membre['date_inscription'])) ?></small></div>
        </div>
        <ul class="tdb-nav">
            <?php foreach ($nav as $cle => [$libelle, $ic, $nb]): ?>
                <li><a href="<?= e($lienOnglet($cle)) ?>" class="<?= $onglet === $cle ? 'actif' : '' ?>" <?= $onglet === $cle ? 'aria-current="page"' : '' ?>>
                    <?= icone($ic) ?> <?= e($libelle) ?>
                    <?php if ($nb !== null): ?><span class="compteur-pastille"><?= $nb ?></span><?php endif; ?>
                </a></li>
            <?php endforeach; ?>
        </ul>
        <div class="tdb-session"><?= icone('cadenas') ?><span>Session sécurisée. Déconnexion automatique après <?= Session::minutesRestantes() ?> min d’inactivité.</span></div>
    </aside>

    <div>
    <?php if ($onglet === 'apercu'): ?>
        <div class="tdb-entete">
            <div><h1>Bonjour 👋</h1><p>Voici l’essentiel de votre activité sur e-bazar.</p></div>
            <a class="btn btn-primaire" href="<?= e(url('annonce', 'deposer')) ?>"><?= icone('plus') ?> Nouvelle annonce</a>
        </div>

        <div class="stats">
            <div class="carte stat">
                <div class="stat-tete">En vente <span class="stat-ic vert"><?= icone('paquet') ?></span></div>
                <div class="stat-valeur"><?= (int) $stats['en_vente'] ?></div>
                <div class="stat-sous">Valeur totale <strong><?= e(montant($stats['valeur_en_vente'])) ?></strong></div>
            </div>
            <div class="carte stat">
                <div class="stat-tete">Biens vendus <span class="stat-ic bleu"><?= icone('etiquette') ?></span></div>
                <div class="stat-valeur"><?= (int) $stats['vendus'] ?></div>
                <div class="stat-sous">Revenus <strong><?= e(montant($stats['revenus'])) ?></strong></div>
            </div>
            <div class="carte stat">
                <div class="stat-tete">Mes achats <span class="stat-ic ambre"><?= icone('panier') ?></span></div>
                <div class="stat-valeur"><?= (int) $stats['achats'] ?></div>
                <div class="stat-sous">Dépensé <strong><?= e(montant($stats['depenses'])) ?></strong></div>
            </div>
            <div class="carte stat">
                <div class="stat-tete">Dernière connexion <span class="stat-ic ardoise"><?= icone('horloge') ?></span></div>
                <div class="stat-valeur"><?= e(date('H:i', strtotime($membre['derniere_connexion'] ?? 'now'))) ?></div>
                <div class="stat-sous"><?= e(date_fr($membre['derniere_connexion'])) ?></div>
            </div>
        </div>

        <div class="grille-2">
            <div class="carte">
                <div class="carte-tete"><h3><?= icone('camion') ?> À livrer</h3><a href="<?= e($lienOnglet('ventes')) ?>">Tout voir</a></div>
                <ul class="liste-actions">
                    <?php foreach (array_slice($vendus, 0, 4) as $a): ?>
                        <li>
                            <?php require APP . '/views/annonce/_mini.php'; ?>
                            <div class="infos"><strong><?= e($a['titre']) ?></strong><small>Acheté par <?= e($a['acheteur_email'] ?? 'compte supprimé') ?> · <?= e(MODES_LIVRAISON[$a['mode_livraison']] ?? '') ?></small></div>
                            <span class="puce puce-ambre">À livrer</span>
                        </li>
                    <?php endforeach; ?>
                    <?php if (!$vendus): ?><li class="vide-petit">Aucune vente à livrer.</li><?php endif; ?>
                </ul>
            </div>
            <div class="carte">
                <div class="carte-tete"><h3><?= icone('panier') ?> En attente de réception</h3><a href="<?= e($lienOnglet('achats')) ?>">Tout voir</a></div>
                <ul class="liste-actions">
                    <?php foreach (array_slice($achetes, 0, 4) as $a): ?>
                        <li>
                            <?php require APP . '/views/annonce/_mini.php'; ?>
                            <div class="infos"><strong><?= e($a['titre']) ?></strong><small>Vendu par <?= e($a['vendeur_email']) ?> · <?= e(prix($a['prix'])) ?></small></div>
                            <span class="puce puce-bleu">En cours</span>
                        </li>
                    <?php endforeach; ?>
                    <?php if (!$achetes): ?><li class="vide-petit">Aucun achat en cours.</li><?php endif; ?>
                </ul>
            </div>
        </div>

    <?php elseif ($onglet === 'annonces'): ?>
        <div class="tdb-entete">
            <div><h1>Mes annonces en vente</h1><p><?= e(pluriel(count($enVente), 'bien')) ?> actuellement en ligne.</p></div>
            <a class="btn btn-primaire" href="<?= e(url('annonce', 'deposer')) ?>"><?= icone('plus') ?> Nouvelle annonce</a>
        </div>
        <div class="carte">
            <?php if (!$enVente): ?>
                <div class="vide"><div class="vide-ic"><?= icone('paquet') ?></div><h3>Aucune annonce en ligne</h3><p>Publiez votre premier objet en 2 minutes.</p>
                    <a class="btn btn-primaire" href="<?= e(url('annonce', 'deposer')) ?>"><?= icone('plus') ?> Déposer une annonce</a></div>
            <?php else: ?>
            <div class="tableau-conteneur"><table class="tableau">
                <thead><tr><th>Bien</th><th>Catégorie</th><th>Publiée</th><th class="num">Prix</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($enVente as $a): ?>
                    <tr>
                        <td><div class="objet"><?php require APP . '/views/annonce/_mini.php'; ?><a href="<?= e(url('annonce', 'voir', ['id' => $a['id']])) ?>"><?= e($a['titre']) ?></a></div></td>
                        <td><span class="puce"><?= e($a['categorie_nom']) ?></span></td>
                        <td class="discret"><?= e(il_y_a($a['date_depot'])) ?></td>
                        <td class="num"><strong><?= e(prix($a['prix'])) ?></strong></td>
                        <td class="actions">
                            <a class="btn btn-secondaire btn-sm" href="<?= e(url('annonce', 'voir', ['id' => $a['id']])) ?>"><?= icone('oeil') ?> Voir</a>
                            <form method="post" action="<?= e(url('annonce', 'supprimer', ['id' => $a['id']])) ?>" data-confirmer="Supprimer « <?= e($a['titre']) ?> » ?">
                                <?= csrf_field() ?><button class="btn btn-danger btn-sm"><?= icone('poubelle') ?> Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
            <?php endif; ?>
        </div>

    <?php elseif ($onglet === 'ventes'): ?>
        <div class="tdb-entete">
            <div><h1>Mes ventes</h1><p>Les biens que vous avez vendus et que vous devez livrer à l’acheteur.</p></div>
        </div>
        <div class="stats">
            <div class="carte stat"><div class="stat-tete">Biens à livrer <span class="stat-ic bleu"><?= icone('camion') ?></span></div><div class="stat-valeur"><?= count($vendus) ?></div></div>
            <div class="carte stat"><div class="stat-tete">Total des ventes <span class="stat-ic vert"><?= icone('euro') ?></span></div><div class="stat-valeur"><?= e(montant($stats['revenus'])) ?></div></div>
        </div>
        <div class="carte">
            <?php if (!$vendus): ?>
                <div class="vide"><div class="vide-ic"><?= icone('etiquette') ?></div><h3>Aucune vente en cours</h3><p>Quand un membre achètera l’un de vos biens, il apparaîtra ici.</p></div>
            <?php else: ?>
            <div class="tableau-conteneur"><table class="tableau">
                <thead><tr><th>Bien</th><th>Acheteur</th><th>Livraison</th><th>Vendu le</th><th>Statut</th><th class="num">Prix</th></tr></thead>
                <tbody>
                <?php foreach ($vendus as $a): ?>
                    <tr>
                        <td><div class="objet"><?php require APP . '/views/annonce/_mini.php'; ?><a href="<?= e(url('annonce', 'voir', ['id' => $a['id']])) ?>"><?= e($a['titre']) ?></a></div></td>
                        <td><?php if ($a['acheteur_email']): ?><a class="lien-mail" href="mailto:<?= e($a['acheteur_email']) ?>"><?= icone('mail') ?><?= e($a['acheteur_email']) ?></a><?php else: ?><span class="discret">compte supprimé</span><?php endif; ?></td>
                        <td><span class="puce"><?= icone($a['mode_livraison'] === 'postale' ? 'camion' : 'main') ?><?= e(MODES_LIVRAISON[$a['mode_livraison']] ?? '') ?></span></td>
                        <td class="discret"><?= e(date_fr($a['date_vente'])) ?></td>
                        <td><span class="puce puce-ambre"><?= icone('horloge') ?><?= $a['mode_livraison'] === 'postale' ? 'À expédier' : 'À remettre' ?></span></td>
                        <td class="num"><strong><?= e(prix($a['prix'])) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot><tr><td colspan="5">Total</td><td class="num"><?= e(montant($stats['revenus'])) ?></td></tr></tfoot>
            </table></div>
            <?php endif; ?>
        </div>
        <p class="aide">Lorsque l’acheteur confirme la réception, la vente est clôturée et retirée de toutes les listes.</p>

    <?php elseif ($onglet === 'achats'): ?>
        <div class="tdb-entete">
            <div><h1>Mes achats</h1><p>Confirmez la réception de vos biens une fois livrés.</p></div>
        </div>
        <div class="carte">
            <?php if (!$achetes): ?>
                <div class="vide"><div class="vide-ic"><?= icone('panier') ?></div><h3>Aucun achat en cours</h3><p>Parcourez les annonces pour trouver votre bonheur.</p>
                    <a class="btn btn-primaire" href="<?= e(url('annonce', 'rechercher')) ?>"><?= icone('recherche') ?> Parcourir les annonces</a></div>
            <?php else: ?>
            <div class="tableau-conteneur"><table class="tableau">
                <thead><tr><th>Bien</th><th>Vendeur</th><th>Livraison</th><th>Acheté le</th><th class="num">Prix</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($achetes as $a): ?>
                    <tr>
                        <td><div class="objet"><?php require APP . '/views/annonce/_mini.php'; ?><a href="<?= e(url('annonce', 'voir', ['id' => $a['id']])) ?>"><?= e($a['titre']) ?></a></div></td>
                        <td><a class="lien-mail" href="mailto:<?= e($a['vendeur_email']) ?>"><?= icone('mail') ?><?= e($a['vendeur_email']) ?></a></td>
                        <td><span class="puce"><?= icone($a['mode_livraison'] === 'postale' ? 'camion' : 'main') ?><?= e(MODES_LIVRAISON[$a['mode_livraison']] ?? '') ?></span></td>
                        <td class="discret"><?= e(date_fr($a['date_vente'])) ?></td>
                        <td class="num"><strong><?= e(prix($a['prix'])) ?></strong></td>
                        <td class="actions">
                            <form method="post" action="<?= e(url('achat', 'confirmerReception', ['id' => $a['id']])) ?>"
                                  data-confirmer="Confirmez-vous avoir bien reçu « <?= e($a['titre']) ?> » ? Cette action clôture la vente.">
                                <?= csrf_field() ?><button class="btn btn-primaire btn-sm"><?= icone('check') ?> J’ai reçu ce bien</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot><tr><td colspan="4">Total dépensé</td><td class="num"><?= e(montant($stats['depenses'])) ?></td><td></td></tr></tfoot>
            </table></div>
            <?php endif; ?>
        </div>

    <?php else: /* securite */ ?>
        <div class="tdb-entete">
            <div><h1>Sécurité du compte</h1><p>Gérez votre mot de passe, vos sessions et consultez l’activité de votre compte.</p></div>
        </div>

        <div class="grille-2">
            <div class="carte">
                <div class="carte-tete"><h3><?= icone('cle') ?> Changer de mot de passe</h3></div>
                <form class="carte-corps" method="post" action="<?= e(url('compte', 'motDePasse')) ?>" data-form-mdp>
                    <?= csrf_field() ?>
                    <div class="champ-groupe">
                        <label class="etiquette" for="actuel">Mot de passe actuel</label>
                        <div class="mdp-boite"><input class="champ<?= isset($erreursMdp['actuel']) ? ' invalide' : '' ?>" type="password" id="actuel" name="actuel" required autocomplete="current-password">
                            <button type="button" class="mdp-oeil" data-oeil aria-label="Afficher le mot de passe"><?= icone('oeil') ?></button></div>
                        <?= $errMdp('actuel') ?>
                    </div>
                    <div class="champ-groupe">
                        <label class="etiquette" for="nouveau">Nouveau mot de passe</label>
                        <div class="mdp-boite"><input class="champ<?= isset($erreursMdp['nouveau']) ? ' invalide' : '' ?>" type="password" id="nouveau" name="nouveau" required minlength="12" maxlength="128" autocomplete="new-password" data-force data-email="<?= e($u['email']) ?>">
                            <button type="button" class="mdp-oeil" data-oeil aria-label="Afficher le mot de passe"><?= icone('oeil') ?></button></div>
                        <?php require APP . '/views/auth/_force.php'; ?>
                        <?= $errMdp('nouveau') ?>
                    </div>
                    <div class="champ-groupe">
                        <label class="etiquette" for="confirmation">Confirmer le nouveau mot de passe</label>
                        <input class="champ<?= isset($erreursMdp['confirmation']) ? ' invalide' : '' ?>" type="password" id="confirmation" name="confirmation" required autocomplete="new-password" data-confirme="nouveau">
                        <?= $errMdp('confirmation') ?>
                    </div>
                    <button class="btn btn-primaire"><?= icone('check') ?> Mettre à jour</button>
                    <p class="aide">Toutes vos autres sessions seront automatiquement déconnectées.</p>
                </form>
            </div>

            <div>
                <div class="carte">
                    <div class="carte-tete"><h3><?= icone('portable') ?> Sessions actives</h3></div>
                    <div class="carte-corps">
                        <div class="encart-secu">
                            <span class="stat-ic"><?= icone('bouclier') ?></span>
                            <div><strong>Cette session</strong>
                                <p>Ouverte <?= e(il_y_a(date('Y-m-d H:i:s', (int) ($_SESSION['debut'] ?? time())))) ?> · IP <span class="ip"><?= e(Journal::ip()) ?></span><br>
                                Expire après <?= Session::minutesRestantes() ?> min d’inactivité (8 h maximum).</p></div>
                        </div>
                        <br>
                        <p class="aide">Vous pensez être resté connecté sur un autre ordinateur ? Fermez toutes les autres sessions d’un clic.</p>
                        <form method="post" action="<?= e(url('compte', 'revoquerSessions')) ?>" data-confirmer="Déconnecter toutes vos autres sessions ?">
                            <?= csrf_field() ?>
                            <button class="btn btn-danger"><?= icone('sortie') ?> Déconnecter les autres appareils</button>
                        </form>
                    </div>
                </div>
                <br>
                <div class="carte">
                    <div class="carte-tete"><h3><?= icone('user') ?> Mon compte</h3></div>
                    <div class="carte-corps">
                        <ul class="liste-cles">
                            <li><span>Adresse e-mail</span><span><?= e($u['email']) ?></span></li>
                            <li><span>Inscription</span><span><?= e(date_fr($membre['date_inscription'])) ?></span></li>
                            <li><span>Dernière connexion</span><span><?= e(date_fr($membre['derniere_connexion'], true)) ?></span></li>
                            <li><span>Protection du mot de passe</span><span><?= str_starts_with($membre['mot_de_passe'], '$argon2id') ? 'Argon2id' : 'bcrypt' ?></span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <br>
        <div class="carte">
            <div class="carte-tete"><h3><?= icone('historique') ?> Activité récente du compte</h3><span class="aide">15 derniers événements</span></div>
            <div class="tableau-conteneur"><table class="tableau">
                <thead><tr><th>Événement</th><th>Détails</th><th>Adresse IP</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($historique as $h): [$lib, $ton] = Journal::LIBELLES[$h['evenement']] ?? [$h['evenement'], 'neutre']; ?>
                    <tr>
                        <td><span class="puce puce-<?= e($ton) ?>"><?= e($lib) ?></span></td>
                        <td class="discret"><?= e($h['details'] ?? '') ?></td>
                        <td><span class="ip"><?= e($h['ip']) ?></span></td>
                        <td class="discret"><?= e(date_fr($h['date_evt'], true)) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$historique): ?><tr><td colspan="4" class="discret">Aucun événement.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </div>
    <?php endif; ?>
    </div>
</div>
