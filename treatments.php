<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/session.php';

$currencySymbol = $lang === 'pl' ? 'zł' : 'PLN';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('meta.title.treatments'); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="page-treatments">

    <div class="d-only">
        <header class="b-header">
            <a href="index.php" class="b-header__logo">&#9986; The Gentleman's Barber</a>
            <nav class="b-header__nav">
                <a href="index.php"><?php echo t('nav.about'); ?></a>
                <a href="book.php"><?php echo t('nav.book'); ?></a>
                <a href="treatments.php" class="active"><?php echo t('nav.treatments'); ?></a>
                <a href="reviews.php"><?php echo t('nav.reviews'); ?></a>
                <?php if ($isLoggedIn): ?>
                    <a href="reservations.php"><?php echo t('nav.reservations'); ?></a>
                    <a href="logout.php"><?php echo t('nav.signout'); ?></a>
                <?php else: ?>
                    <a href="register.php"><?php echo t('nav.register'); ?></a>
                <?php endif; ?>
                <span class="lang-switch">
                    <a href="?lang=en" class="<?php echo $lang === 'en' ? 'is-active' : ''; ?>"><?php echo t('lang.en'); ?></a>
                    <span class="lang-switch__sep">/</span>
                    <a href="?lang=pl" class="<?php echo $lang === 'pl' ? 'is-active' : ''; ?>"><?php echo t('lang.pl'); ?></a>
                    <span class="lang-switch__sep">/</span>
                    <a href="?lang=de" class="<?php echo $lang === 'de' ? 'is-active' : ''; ?>"><?php echo t('lang.de'); ?></a>
                    <span class="lang-switch__sep">/</span>
                    <a href="?lang=uk" class="<?php echo $lang === 'uk' ? 'is-active' : ''; ?>"><?php echo t('lang.uk'); ?></a>
                </span>
            </nav>
        </header>

        <main class="b-content">
            <aside class="b-rail">
                <p class="b-rail__cap"><?php echo t('treat.eyebrow'); ?></p>
                <h1 class="b-rail__head"><?php echo t('treat.title'); ?></h1>
                <p class="b-rail__body"><?php echo t('treat.lead'); ?></p>
                <div class="b-rail__photo" aria-label="Barbershop service detail"></div>
                <p class="t-note"><?php echo t('treat.note'); ?></p>
            </aside>

            <section class="t-list">
                <?php foreach ($treatments as $svc): ?>
                    <article class="t-card">
                        <div class="t-card__head">
                            <h2 class="t-card__name"><?php echo t('svc.' . $svc['key'] . '.name'); ?></h2>
                            <span class="t-card__price"><?php echo (int)$svc['price'] . ' ' . $currencySymbol; ?></span>
                        </div>
                        <p class="t-card__desc"><?php echo t('svc.' . $svc['key'] . '.desc'); ?></p>
                        <div class="t-card__foot">
                            <span class="t-card__meta"><?php echo t('treat.duration'); ?>: <?php echo (int)$svc['duration'] . ' ' . t('treat.minutes'); ?></span>
                            <a class="t-card__cta" href="book.php?service=<?php echo urlencode($svc['key']); ?>"><?php echo t('treat.cta'); ?></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        </main>

        <footer class="b-foot">
            <?php echo t('foot.copyright'); ?>
        </footer>
    </div>

    <div class="m-only">
        <header class="m-header">
            <div class="m-header__bar">
                <a href="index.php" class="m-header__logo">&#9986; The Gentleman's Barber</a>
                <button class="m-header__btn" type="button" data-mobile-menu-open aria-expanded="false" aria-controls="mobileMenu">☰</button>
            </div>
            <div class="m-menu" id="mobileMenu" data-mobile-menu role="dialog" aria-modal="true" aria-label="<?php echo t('nav.menu'); ?>">
                <div class="m-menu__top">
                    <span class="m-header__logo"><?php echo t('nav.menu'); ?></span>
                    <button class="m-menu__close" type="button" data-mobile-menu-close>✕</button>
                </div>
                <nav class="m-menu__nav">
                    <a href="index.php"><?php echo t('nav.about'); ?></a>
                    <a href="book.php"><?php echo t('nav.book'); ?></a>
                    <a href="treatments.php" class="is-active"><?php echo t('nav.treatments'); ?></a>
                    <a href="reviews.php"><?php echo t('nav.reviews'); ?></a>
                    <?php if ($isLoggedIn): ?>
                        <a href="reservations.php"><?php echo t('nav.reservations'); ?></a>
                        <a href="logout.php"><?php echo t('nav.signout'); ?></a>
                    <?php else: ?>
                        <a href="register.php?mode=register"><?php echo t('nav.register'); ?></a>
                    <?php endif; ?>
                    <span class="lang-switch lang-switch--mobile">
                        <a href="?lang=en" class="<?php echo $lang === 'en' ? 'is-active' : ''; ?>"><?php echo t('lang.en'); ?></a>
                        <span class="lang-switch__sep">/</span>
                        <a href="?lang=pl" class="<?php echo $lang === 'pl' ? 'is-active' : ''; ?>"><?php echo t('lang.pl'); ?></a>
                        <span class="lang-switch__sep">/</span>
                        <a href="?lang=de" class="<?php echo $lang === 'de' ? 'is-active' : ''; ?>"><?php echo t('lang.de'); ?></a>
                        <span class="lang-switch__sep">/</span>
                        <a href="?lang=uk" class="<?php echo $lang === 'uk' ? 'is-active' : ''; ?>"><?php echo t('lang.uk'); ?></a>
                    </span>
                </nav>
            </div>
        </header>

        <main class="m-container m-stack">
            <p class="m-cap"><?php echo t('treat.eyebrow'); ?></p>
            <h1 class="m-h1"><?php echo t('treat.title'); ?></h1>
            <p class="m-body"><?php echo t('treat.lead'); ?></p>

            <section class="t-list">
                <?php foreach ($treatments as $svc): ?>
                    <article class="t-card">
                        <div class="t-card__head">
                            <h2 class="t-card__name"><?php echo t('svc.' . $svc['key'] . '.name'); ?></h2>
                            <span class="t-card__price"><?php echo (int)$svc['price'] . ' ' . $currencySymbol; ?></span>
                        </div>
                        <p class="t-card__desc"><?php echo t('svc.' . $svc['key'] . '.desc'); ?></p>
                        <div class="t-card__foot">
                            <span class="t-card__meta"><?php echo t('treat.duration'); ?>: <?php echo (int)$svc['duration'] . ' ' . t('treat.minutes'); ?></span>
                            <a class="t-card__cta" href="book.php?service=<?php echo urlencode($svc['key']); ?>"><?php echo t('treat.cta'); ?></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>

            <p class="t-note"><?php echo t('treat.note'); ?></p>
        </main>

        <footer class="b-foot">
            <?php echo t('foot.copyright'); ?>
        </footer>
    </div>

</div>
<script src="js/main.js"></script>
</body>
</html>
