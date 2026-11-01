/* e-bazar — améliorations progressives.
 * L'application fonctionne entièrement sans JavaScript ; toutes les règles
 * vérifiées ici le sont AUSSI côté serveur (le client n'est jamais digne de confiance).
 * Aucun script en ligne : compatible avec la Content-Security-Policy stricte. */
(function () {
    'use strict';
    var $ = function (s, ctx) { return (ctx || document).querySelector(s); };
    var $$ = function (s, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(s)); };

    /* ---------- Messages : fermeture ---------- */
    $$('.toast-fermer').forEach(function (b) {
        b.addEventListener('click', function () { b.closest('.toast').remove(); });
    });

    /* ---------- Menu utilisateur : fermeture au clic extérieur / Échap ---------- */
    var menu = $('.menu-u');
    if (menu) {
        document.addEventListener('click', function (e) { if (!menu.contains(e.target)) menu.removeAttribute('open'); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') menu.removeAttribute('open'); });
    }

    /* ---------- Confirmation des actions destructrices ---------- */
    $$('form[data-confirmer]').forEach(function (f) {
        f.addEventListener('submit', function (e) { if (!window.confirm(f.dataset.confirmer)) e.preventDefault(); });
    });

    /* ---------- Anti double-soumission ---------- */
    $$('form[method="post"]').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            if (e.defaultPrevented) return;
            var b = f.querySelector('button[type="submit"], button:not([type])');
            if (b) { setTimeout(function () { b.disabled = true; }, 0); }
        });
    });

    /* ---------- Galerie photo ---------- */
    var galerie = $('[data-galerie]');
    if (galerie) {
        var image = $('[data-galerie-image]', galerie);
        var minis = $$('.miniature', galerie);
        var compteur = $('[data-galerie-compteur]', galerie);
        var courant = 0;
        var afficher = function (i) {
            if (!minis.length) return;
            courant = (i + minis.length) % minis.length;
            image.src = minis[courant].dataset.src;
            minis.forEach(function (m, j) { m.classList.toggle('active', j === courant); });
            if (compteur) compteur.textContent = (courant + 1) + ' / ' + minis.length;
        };
        minis.forEach(function (m, i) { m.addEventListener('click', function () { afficher(i); }); });
        var p = $('[data-galerie-prec]', galerie), s = $('[data-galerie-suiv]', galerie);
        if (p) p.addEventListener('click', function () { afficher(courant - 1); });
        if (s) s.addEventListener('click', function () { afficher(courant + 1); });
        document.addEventListener('keydown', function (e) {
            if (e.target.closest('input, textarea')) return;
            if (e.key === 'ArrowLeft') afficher(courant - 1);
            if (e.key === 'ArrowRight') afficher(courant + 1);
        });
    }

    /* ---------- Compteurs de caractères ---------- */
    $$('[data-compteur]').forEach(function (champ) {
        var info = document.createElement('div');
        info.className = 'compteur';
        champ.insertAdjacentElement('afterend', info);
        var maj = function () {
            var n = champ.value.length;
            info.textContent = n + ' / ' + champ.maxLength;
            info.classList.toggle('limite', n > champ.maxLength * 0.9 || (n > 0 && n < champ.minLength));
        };
        champ.addEventListener('input', maj);
        maj();
    });

    /* ---------- Afficher / masquer le mot de passe ---------- */
    $$('[data-oeil]').forEach(function (b) {
        b.addEventListener('click', function () {
            var champ = b.parentNode.querySelector('input');
            var visible = champ.type === 'text';
            champ.type = visible ? 'password' : 'text';
            b.setAttribute('aria-label', visible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
            b.style.color = visible ? '' : '#059669';
        });
    });

    /* ---------- Robustesse du mot de passe (mêmes règles que le serveur) ---------- */
    $$('[data-force]').forEach(function (champ) {
        var groupe = champ.closest('.champ-groupe') || document;
        var barre = $('[data-jauge]', groupe), libelle = $('[data-jauge-libelle]', groupe);
        var regles = $('[data-regles]', groupe);
        var emailFixe = champ.dataset.email || '';
        var sourceEmail = champ.dataset.emailSource ? document.getElementById(champ.dataset.emailSource) : null;
        var niveaux = [
            ['Très faible', '#dc2626'], ['Faible', '#f97316'], ['Moyen', '#d97706'], ['Bon', '#0d9488'], ['Excellent', '#059669']
        ];
        var maj = function () {
            var v = champ.value;
            var email = (sourceEmail ? sourceEmail.value : emailFixe).toLowerCase();
            var local = email.split('@')[0];
            var r = {
                longueur: v.length >= 12,
                minuscule: /\p{Ll}/u.test(v),
                majuscule: /\p{Lu}/u.test(v),
                chiffre: /\d/.test(v),
                special: /[^\p{L}\d]/u.test(v),
                email: v.length > 0 && !(local.length >= 3 && v.toLowerCase().indexOf(local) !== -1)
            };
            var score = 0;
            Object.keys(r).forEach(function (k) {
                if (r[k]) score++;
                var li = regles && regles.querySelector('[data-regle="' + k + '"]');
                if (li) li.classList.toggle('ok', r[k]);
            });
            if (v.length >= 16) score++;
            var niveau = v.length === 0 ? -1 : Math.min(4, Math.max(0, score - 3));
            if (barre) {
                barre.style.width = v.length === 0 ? '0' : ((niveau + 1) * 20) + '%';
                barre.style.background = niveau < 0 ? '' : niveaux[niveau][1];
            }
            if (libelle) {
                libelle.textContent = v.length === 0 ? 'Robustesse du mot de passe' : 'Robustesse : ' + niveaux[niveau][0];
                libelle.style.color = niveau < 0 ? '' : niveaux[niveau][1];
            }
        };
        champ.addEventListener('input', maj);
        if (sourceEmail) sourceEmail.addEventListener('input', maj);
        maj();
    });

    /* ---------- Confirmation de mot de passe ---------- */
    $$('[data-confirme]').forEach(function (c) {
        var cible = document.getElementById(c.dataset.confirme);
        var maj = function () {
            c.setCustomValidity(c.value && cible && c.value !== cible.value ? 'Les mots de passe ne correspondent pas.' : '');
            c.classList.toggle('invalide', !!(c.value && cible && c.value !== cible.value));
        };
        c.addEventListener('input', maj);
        if (cible) cible.addEventListener('input', maj);
    });

    /* ---------- Photos : aperçu et vérification avant envoi ---------- */
    var input = $('#photos');
    if (input) {
        var zone = $('[data-zone-photos]'), apercus = $('[data-apercus]'), info = $('[data-photos-info]');
        ['dragenter', 'dragover'].forEach(function (ev) { zone.addEventListener(ev, function () { zone.classList.add('survol'); }); });
        ['dragleave', 'drop'].forEach(function (ev) { zone.addEventListener(ev, function () { zone.classList.remove('survol'); }); });
        input.addEventListener('change', function () {
            var max = +input.dataset.max, taille = +input.dataset.taille, erreurs = [];
            apercus.innerHTML = '';
            Array.prototype.forEach.call(input.files, function (f, i) {
                var probleme = '';
                if (f.type !== 'image/jpeg') probleme = 'Pas un JPEG';
                else if (f.size > taille) probleme = (Math.round(f.size / 1024)) + ' Kio';
                else if (i >= max) probleme = 'En trop';
                if (probleme) erreurs.push(f.name + ' : ' + probleme);
                var div = document.createElement('div');
                div.className = 'apercu' + (probleme ? ' erreur' : '');
                if (f.type === 'image/jpeg') {
                    var img = document.createElement('img');
                    img.src = URL.createObjectURL(f);
                    img.alt = '';
                    img.onload = function () { URL.revokeObjectURL(img.src); };
                    div.appendChild(img);
                }
                var badge = document.createElement('span');
                badge.className = 'apercu-badge';
                badge.textContent = probleme || (i === 0 ? 'Vignette' : String(i + 1));
                div.appendChild(badge);
                apercus.appendChild(div);
            });
            if (input.files.length > max) erreurs.unshift('Maximum ' + max + ' photos.');
            info.textContent = erreurs.length ? '⚠ ' + erreurs.join(' · ') : input.files.length + ' photo(s) prête(s). La première servira de vignette.';
            info.style.color = erreurs.length ? '#dc2626' : '';
        });
    }
})();
