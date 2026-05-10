<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/session.php';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('meta.title.home'); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="page-home">

    <div class="d-only">
        <header class="b-header">
            <a href="index.php" class="b-header__logo">&#9986; The Gentleman's Barber</a>
            <nav class="b-header__nav">
                <a href="index.php" class="active"><?php echo t('nav.about'); ?></a>
                <a href="book.php"><?php echo t('nav.book'); ?></a>
                <a href="treatments.php"><?php echo t('nav.treatments'); ?></a>
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

        <section class="h-hero-wrap">
            <div class="h-hero">
                <p class="h-hero__label"><?php echo t('home.hero.label'); ?></p>
                <p class="h-hero__kicker"><?php echo t('home.hero.kicker'); ?></p>
                <p class="h-hero__sub"><?php echo t('home.hero.sub'); ?></p>
                <div class="h-hero__actions">
                    <a href="book.php" class="h-hero__cta"><?php echo t('home.hero.cta'); ?></a>
                    <span class="h-hero__meta"><?php echo t('home.hero.meta'); ?></span>
                </div>
                <h1 class="h-hero__title"><?php echo t('home.hero.title'); ?></h1>
            </div>
        </section>

        <section class="h-editorial">
            <div class="h-grid">
                <p class="h-grid__label"><?php echo t('home.editorial.label'); ?></p>
                <p class="h-grid__body"><?php echo t('home.editorial.body'); ?></p>
                <a href="book.php" class="h-grid__link"><?php echo t('home.editorial.link'); ?></a>
            </div>

            <h2 class="h-monumental"><?php echo t('home.monumental'); ?></h2>

            <div class="h-services">
                <article class="h-svc">
                    <p class="h-svc__cap"><?php echo t('home.svc1.cap'); ?></p>
                    <p class="h-svc__body"><?php echo t('home.svc1.body'); ?></p>
                </article>
                <article class="h-svc">
                    <p class="h-svc__cap"><?php echo t('home.svc2.cap'); ?></p>
                    <p class="h-svc__body"><?php echo t('home.svc2.body'); ?></p>
                </article>
                <article class="h-svc">
                    <p class="h-svc__cap"><?php echo t('home.svc3.cap'); ?></p>
                    <p class="h-svc__body"><?php echo t('home.svc3.body'); ?></p>
                </article>
            </div>

            <div class="h-proof">
                <div class="h-proof__photo" aria-label="Barbershop interior"></div>
                <p class="h-proof__quote"><?php echo t('home.proof.quote'); ?></p>
                <div class="h-proof__booking">
                    <p class="cap"><?php echo t('home.proof.cap'); ?></p>
                    <p class="body"><?php echo t('home.proof.body'); ?></p>
                    <p class="meta"><?php echo t('home.proof.meta'); ?></p>
                    <a href="book.php" class="btn"><?php echo t('home.proof.cta'); ?></a>
                </div>
            </div>
        </section>

        <footer class="h-foot">
            <p class="h-foot__left"><?php echo t('home.foot.left'); ?></p>
            <div class="h-foot__right">
                <a href="book.php" class="h-foot__btn"><?php echo t('home.foot.cta'); ?></a>
                <span class="h-foot__note"><?php echo t('home.foot.note'); ?></span>
            </div>
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
                    <a href="index.php" class="is-active"><?php echo t('nav.about'); ?></a>
                    <a href="book.php"><?php echo t('nav.book'); ?></a>
                    <a href="treatments.php"><?php echo t('nav.treatments'); ?></a>
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
            <img src="assets/images/home-hero.jpg" alt="Barbershop interior">
            <p class="m-cap"><?php echo t('home.hero.label'); ?></p>
            <h1 class="m-h1"><?php echo t('home.hero.title.mobile'); ?></h1>
            <p class="m-body"><?php echo t('home.hero.kicker'); ?></p>
            <a href="book.php" class="h-hero__cta"><?php echo t('home.hero.cta'); ?></a>

            <p class="m-cap"><?php echo t('home.services.label'); ?></p>
            <div class="m-stack">
                <div>
                    <p class="m-cap"><?php echo t('home.svc1.cap'); ?></p>
                    <p class="m-body"><?php echo t('home.svc1.body'); ?></p>
                </div>
                <div>
                    <p class="m-cap"><?php echo t('home.svc2.cap'); ?></p>
                    <p class="m-body"><?php echo t('home.svc2.body'); ?></p>
                </div>
                <div>
                    <p class="m-cap"><?php echo t('home.svc3.cap'); ?></p>
                    <p class="m-body"><?php echo t('home.svc3.body'); ?></p>
                </div>
            </div>

            <p class="m-cap"><?php echo t('home.hours.label'); ?></p>
            <p class="m-body"><?php echo t('home.hours.value'); ?></p>
        </main>

        <footer class="b-foot">
            <?php echo t('foot.copyright'); ?>
        </footer>
    </div>

</div>
<script src="js/main.js"></script>
</body>
</html>
