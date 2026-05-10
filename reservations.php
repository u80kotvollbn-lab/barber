<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/session.php';

if (!$isLoggedIn || !$sessionEmail) {
    header('Location: register.php?mode=login');
    exit;
}

$message = "";
$messageType = "";

if (isset($_GET['booked']) && $_GET['booked'] === '1') {
    $message = t('res.alert.booked');
    $messageType = "success";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'cancel') {
        $appointmentId = (int)($_POST['appointment_id'] ?? 0);
        if ($appointmentId <= 0) {
            $message = t('res.alert.invalid');
            $messageType = "error";
        } else {
            if (anulujRezerwacje($pdo, $appointmentId, $sessionEmail)) {
                $message = t('res.alert.cancelled');
                $messageType = "success";
            } else {
                $message = t('res.alert.cancel_fail');
                $messageType = "error";
            }
        }
    }
}

$rezerwacje = pobierzRezerwacjePoEmail($pdo, $sessionEmail);
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('meta.title.reservations'); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="page-reservations">

    <div class="d-only">
        <header class="b-header">
            <a href="index.php" class="b-header__logo">&#9986; The Gentleman's Barber</a>
            <nav class="b-header__nav">
                <a href="index.php"><?php echo t('nav.about'); ?></a>
                <a href="book.php"><?php echo t('nav.book'); ?></a>
                <a href="treatments.php"><?php echo t('nav.treatments'); ?></a>
                <a href="reviews.php"><?php echo t('nav.reviews'); ?></a>
                <a href="reservations.php" class="active"><?php echo t('nav.reservations'); ?></a>
                <a href="logout.php"><?php echo t('nav.signout'); ?></a>
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
                <p class="b-rail__cap"><?php echo t('res.rail.cap'); ?></p>
                <h1 class="b-rail__head"><?php echo t('res.rail.head'); ?></h1>
                <p class="b-rail__body"><?php echo t('res.rail.body'); ?> <strong><?php echo htmlspecialchars($sessionEmail); ?></strong>.</p>
                <div class="b-rail__photo" aria-label="Barbershop service detail"></div>
            </aside>

            <section class="b-form">
                <p class="b-form__step"><?php echo t('res.form.step'); ?></p>
                <h2 class="b-form__title"><?php echo t('res.form.title'); ?></h2>
                <p class="b-form__desc"><?php echo t('res.form.desc'); ?></p>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                <?php endif; ?>

                <?php if (count($rezerwacje) === 0): ?>
                    <p class="b-form__desc"><?php echo t('res.empty'); ?></p>
                <?php else: ?>
                    <div class="m-stack">
                        <?php foreach ($rezerwacje as $r): ?>
                            <?php $isFuture = strtotime($r['visit_date']) > time(); ?>
                            <div class="b-field r-item">
                                <label><?php echo htmlspecialchars($r['service_type']); ?></label>
                                <div class="r-item__row">
                                    <div class="r-item__info">
                                        <div class="r-item__date"><?php echo htmlspecialchars(formatujDateNaPolski($r['visit_date'])); ?></div>
                                        <div class="r-item__meta"><?php echo htmlspecialchars($r['client_name']); ?></div>
                                        <?php if (!empty($r['worker_name'])): ?>
                                            <div class="r-item__meta"><?php echo htmlspecialchars($r['worker_name']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isFuture): ?>
                                        <form method="POST" action="reservations.php">
                                            <input type="hidden" name="action" value="cancel">
                                            <input type="hidden" name="appointment_id" value="<?php echo (int)$r['id']; ?>">
                                            <button type="submit" class="b-clear"><?php echo t('res.action.cancel'); ?></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
                    <a href="treatments.php"><?php echo t('nav.treatments'); ?></a>
                    <a href="reviews.php"><?php echo t('nav.reviews'); ?></a>
                    <a href="reservations.php" class="is-active"><?php echo t('nav.reservations'); ?></a>
                    <a href="logout.php"><?php echo t('nav.signout'); ?></a>
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
            <p class="m-cap"><?php echo t('res.rail.cap'); ?></p>
            <h1 class="m-h1"><?php echo t('res.rail.head.mobile'); ?></h1>
            <p class="m-body"><?php echo t('res.rail.body'); ?> <?php echo htmlspecialchars($sessionEmail); ?>.</p>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <section class="b-form">
                <p class="b-form__step"><?php echo t('res.form.step'); ?></p>
                <h2 class="b-form__title"><?php echo t('res.form.title'); ?></h2>
                <p class="b-form__desc"><?php echo t('res.form.desc'); ?></p>

                <?php if (count($rezerwacje) === 0): ?>
                    <p class="b-form__desc"><?php echo t('res.empty'); ?></p>
                <?php else: ?>
                    <div class="m-stack">
                        <?php foreach ($rezerwacje as $r): ?>
                            <?php $isFuture = strtotime($r['visit_date']) > time(); ?>
                            <div class="b-field r-item">
                                <label><?php echo htmlspecialchars($r['service_type']); ?></label>
                                <div class="r-item__row">
                                    <div class="r-item__info">
                                        <div class="r-item__date"><?php echo htmlspecialchars(formatujDateNaPolski($r['visit_date'])); ?></div>
                                        <div class="r-item__meta"><?php echo htmlspecialchars($r['client_name']); ?></div>
                                        <?php if (!empty($r['worker_name'])): ?>
                                            <div class="r-item__meta"><?php echo htmlspecialchars($r['worker_name']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isFuture): ?>
                                        <form method="POST" action="reservations.php">
                                            <input type="hidden" name="action" value="cancel">
                                            <input type="hidden" name="appointment_id" value="<?php echo (int)$r['id']; ?>">
                                            <button type="submit" class="b-clear"><?php echo t('res.action.cancel'); ?></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>

        <footer class="b-foot">
            <?php echo t('foot.copyright'); ?>
        </footer>
    </div>

</div>
<script src="js/main.js"></script>
</body>
</html>
