<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/session.php';

$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? ($sessionEmail ?? ''));
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    $wizyta = sprawdzMozliwoscOpinii($pdo, $email);

    if ($wizyta) {
        if ($rating >= 1 && $rating <= 5) {
            if (zapiszOpinie($pdo, $wizyta['id'], $rating, $comment)) {
                $message = t('rev.alert.success');
                $messageType = "success";
            } else {
                $message = t('rev.alert.error');
                $messageType = "error";
            }
        } else {
            $message = t('rev.alert.rating_range');
            $messageType = "error";
        }
    } else {
        $message = t('rev.alert.no_eligible_visit');
        $messageType = "error";
    }
}

$opinie = pobierzWszystkieOpinie($pdo);
$sumaOcen = 0;
$iloscOcen = count($opinie);
foreach ($opinie as $opinia) { $sumaOcen += $opinia['rating']; }
$sredniaOcena = $iloscOcen > 0 ? round($sumaOcen / $iloscOcen, 2) : 4.9;

$eligibleVisit = $isLoggedIn && $sessionEmail ? sprawdzMozliwoscOpinii($pdo, $sessionEmail) : false;
$canReview = (bool)$eligibleVisit;
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('meta.title.reviews'); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="page-reviews">

    <div class="d-only">
        <header class="b-header">
            <a href="index.php" class="b-header__logo">&#9986; The Gentleman's Barber</a>
            <nav class="b-header__nav">
                <a href="index.php"><?php echo t('nav.about'); ?></a>
                <a href="book.php"><?php echo t('nav.book'); ?></a>
                <a href="treatments.php"><?php echo t('nav.treatments'); ?></a>
                <a href="reviews.php" class="active"><?php echo t('nav.reviews'); ?></a>
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

        <section class="r-content">
            <div class="r-hero">
                <p class="r-hero__eyebrow"><?php echo t('rev.hero.eyebrow'); ?></p>
                <p class="r-hero__lead"><?php echo t('rev.hero.lead'); ?></p>
                <h1 class="r-hero__title"><?php echo t('rev.hero.title'); ?></h1>
                <p class="r-hero__metric"><?php echo $sredniaOcena; ?> <?php echo t('rev.hero.metric_suffix'); ?></p>
            </div>

            <div class="r-headline">
                <p class="r-headline__label"><?php echo t('rev.headline.label'); ?></p>
                <h2 class="r-headline__display"><?php echo t('rev.headline.display'); ?></h2>
            </div>

            <div class="r-grid">
                <aside class="r-rail-l">
                    <p class="r-rail-l__label"><?php echo t('rev.rail_l.label'); ?></p>
                    <p class="r-rail-l__copy"><?php echo t('rev.rail_l.copy'); ?></p>
                    <div class="r-rating-wrap">
                        <canvas id="ratingCanvas" width="300" height="300" data-average="<?php echo $sredniaOcena; ?>" aria-label="Average rating chart"></canvas>
                        <div id="canvasLegend"><?php echo t('rev.canvas.legend_prefix'); ?> <?php echo $sredniaOcena; ?> <?php echo t('rev.canvas.legend_suffix'); ?></div>
                    </div>
                </aside>

                <section class="r-center">
                    <p class="r-quote"><?php echo t('rev.quote'); ?></p>
                    <div class="r-pair">
                        <article class="r-rev">
                            <p class="r-rev__kicker"><?php echo t('rev.testimonial1.kicker'); ?></p>
                            <p class="r-rev__quote"><?php echo t('rev.testimonial1.quote'); ?></p>
                            <p class="r-rev__author">Marek Nowak</p>
                        </article>
                        <article class="r-rev">
                            <p class="r-rev__kicker"><?php echo t('rev.testimonial2.kicker'); ?></p>
                            <p class="r-rev__quote"><?php echo t('rev.testimonial2.quote'); ?></p>
                            <p class="r-rev__author">Piotr Wisniewski</p>
                        </article>
                    </div>

                    <section class="r-form">
                        <p class="r-form__cap"><?php echo t('rev.form.cap'); ?></p>
                        <h3 class="r-form__title"><?php echo t('rev.form.title'); ?></h3>

                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                        <?php endif; ?>

                        <?php if (!$isLoggedIn): ?>
                            <p class="b-form__desc"><a href="register.php?mode=login"><?php echo t('rev.form.signin_link'); ?></a> <?php echo t('rev.form.signin_prompt_after'); ?></p>
                        <?php elseif (!$canReview): ?>
                            <p class="b-form__desc"><?php echo t('rev.form.no_eligible'); ?></p>
                        <?php else: ?>
                            <form action="reviews.php" method="POST" id="reviewForm">
                                <div class="b-field"><label for="reviewEmail"><?php echo t('rev.field.email'); ?></label>
                                    <input type="email" id="reviewEmail" name="email" value="<?php echo htmlspecialchars($sessionEmail); ?>" required readonly placeholder="<?php echo t('book.placeholder.email'); ?>" autocomplete="email">
                                </div>
                                <div class="b-field"><label><?php echo t('rev.field.visit'); ?></label>
                                    <input type="text" value="<?php echo htmlspecialchars($eligibleVisit['service_type'] . ' — ' . formatujDateNaPolski($eligibleVisit['visit_date'])); ?>" readonly>
                                </div>
                                <div class="b-field"><label for="rating"><?php echo t('rev.field.rating'); ?></label>
                                    <select id="rating" name="rating" required>
                                        <option value="5"><?php echo t('rev.rating.5'); ?></option>
                                        <option value="4"><?php echo t('rev.rating.4'); ?></option>
                                        <option value="3"><?php echo t('rev.rating.3'); ?></option>
                                        <option value="2"><?php echo t('rev.rating.2'); ?></option>
                                        <option value="1"><?php echo t('rev.rating.1'); ?></option>
                                    </select>
                                </div>
                                <div class="b-field"><label for="comment"><?php echo t('rev.field.comment'); ?></label>
                                    <textarea id="comment" name="comment" rows="4" required placeholder="<?php echo t('rev.field.comment.placeholder'); ?>"></textarea>
                                </div>
                                <div class="r-form__actions">
                                    <button type="submit" class="b-submit"><?php echo t('rev.action.submit'); ?></button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </section>

                    <?php if ($iloscOcen > 0): ?>
                    <section class="r-list">
                        <h3><?php echo t('rev.list.title'); ?></h3>
                        <div class="r-list-grid">
                            <?php foreach ($opinie as $opinia): ?>
                                <article class="r-card">
                                    <p class="r-card__cap"><?php echo (int)$opinia['rating']; ?> <?php echo t('rev.card.stars_suffix'); ?> / <?php echo formatujDateNaPolski($opinia['visit_date']); ?></p>
                                    <p class="r-card__quote">"<?php echo htmlspecialchars($opinia['comment']); ?>"</p>
                                    <div class="r-card__foot">
                                        <span class="r-card__name"><?php echo htmlspecialchars($opinia['client_name']); ?></span>
                                        <span class="r-card__stars"><?php echo str_repeat("&#9733;", (int)$opinia['rating']); ?></span>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>
                </section>

                <aside class="r-rail-r">
                    <div class="r-rail-r__photo" aria-label="Barbershop chair detail"></div>
                    <p class="r-rail-r__stat1"><?php echo t('rev.rail_r.stat1'); ?></p>
                    <p class="r-rail-r__stat2"><?php echo t('rev.rail_r.stat2'); ?></p>
                    <a href="book.php" class="r-rail-r__cta"><?php echo t('rev.rail_r.cta'); ?></a>
                </aside>
            </div>
        </section>

        <footer class="r-foot">
            <p class="r-foot__left"><?php echo t('rev.foot.left'); ?></p>
            <p class="r-foot__right"><?php echo t('rev.foot.right'); ?></p>
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
                    <a href="treatments.php"><?php echo t('nav.treatments'); ?></a>
                    <a href="reviews.php" class="is-active"><?php echo t('nav.reviews'); ?></a>
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
            <img src="assets/images/reviews-hero.jpg" alt="Barbershop chair detail">
            <p class="m-cap"><?php echo t('rev.hero.eyebrow'); ?></p>
            <h1 class="m-h1"><?php echo t('rev.hero.title'); ?></h1>
            <p class="m-body"><?php echo $sredniaOcena; ?> <?php echo t('rev.hero.metric_suffix'); ?></p>

            <div class="r-rating-wrap">
                <canvas id="ratingCanvas" width="300" height="300" data-average="<?php echo $sredniaOcena; ?>" aria-label="Average rating chart"></canvas>
                <div id="canvasLegend"><?php echo t('rev.canvas.legend_prefix'); ?> <?php echo $sredniaOcena; ?> <?php echo t('rev.canvas.legend_suffix'); ?></div>
            </div>

            <section class="r-form">
                <p class="r-form__cap"><?php echo t('rev.form.cap'); ?></p>
                <h3 class="r-form__title"><?php echo t('rev.form.title'); ?></h3>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                <?php endif; ?>

                <?php if (!$isLoggedIn): ?>
                    <p class="b-form__desc"><a href="register.php?mode=login"><?php echo t('rev.form.signin_link'); ?></a> <?php echo t('rev.form.signin_prompt_after'); ?></p>
                <?php elseif (!$canReview): ?>
                    <p class="b-form__desc"><?php echo t('rev.form.no_eligible'); ?></p>
                <?php else: ?>
                    <form action="reviews.php" method="POST" id="reviewForm">
                        <div class="b-field"><label for="reviewEmail"><?php echo t('rev.field.email'); ?></label>
                            <input type="email" id="reviewEmail" name="email" value="<?php echo htmlspecialchars($sessionEmail); ?>" required readonly placeholder="<?php echo t('book.placeholder.email'); ?>" autocomplete="email">
                        </div>
                        <div class="b-field"><label><?php echo t('rev.field.visit'); ?></label>
                            <input type="text" value="<?php echo htmlspecialchars($eligibleVisit['service_type'] . ' — ' . formatujDateNaPolski($eligibleVisit['visit_date'])); ?>" readonly>
                        </div>
                        <div class="b-field"><label for="rating"><?php echo t('rev.field.rating'); ?></label>
                            <select id="rating" name="rating" required>
                                <option value="5"><?php echo t('rev.rating.5'); ?></option>
                                <option value="4"><?php echo t('rev.rating.4'); ?></option>
                                <option value="3"><?php echo t('rev.rating.3'); ?></option>
                                <option value="2"><?php echo t('rev.rating.2'); ?></option>
                                <option value="1"><?php echo t('rev.rating.1'); ?></option>
                            </select>
                        </div>
                        <div class="b-field"><label for="comment"><?php echo t('rev.field.comment'); ?></label>
                            <textarea id="comment" name="comment" rows="4" required placeholder="<?php echo t('rev.field.comment.placeholder'); ?>"></textarea>
                        </div>
                        <div class="r-form__actions">
                            <button type="submit" class="b-submit"><?php echo t('rev.action.submit'); ?></button>
                        </div>
                    </form>
                <?php endif; ?>
            </section>

            <?php if ($iloscOcen > 0): ?>
            <section class="r-list">
                <h3><?php echo t('rev.list.title'); ?></h3>
                <div class="r-list-grid">
                    <?php foreach ($opinie as $opinia): ?>
                        <article class="r-card">
                            <p class="r-card__cap"><?php echo (int)$opinia['rating']; ?> <?php echo t('rev.card.stars_suffix'); ?> / <?php echo formatujDateNaPolski($opinia['visit_date']); ?></p>
                            <p class="r-card__quote">"<?php echo htmlspecialchars($opinia['comment']); ?>"</p>
                            <div class="r-card__foot">
                                <span class="r-card__name"><?php echo htmlspecialchars($opinia['client_name']); ?></span>
                                <span class="r-card__stars"><?php echo str_repeat("&#9733;", (int)$opinia['rating']); ?></span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </main>

        <footer class="r-foot">
            <p class="r-foot__left"><?php echo t('rev.foot.left'); ?></p>
            <p class="r-foot__right"><?php echo t('rev.foot.right.mobile'); ?></p>
        </footer>
    </div>

</div>
<script src="js/main.js"></script>
</body>
</html>
