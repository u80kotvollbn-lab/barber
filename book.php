<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/session.php';

if (!$isLoggedIn) {
    $loginUrl = 'register.php?mode=login&next=book';
    if (!empty($_GET['service'])) {
        $loginUrl .= '&service=' . urlencode($_GET['service']);
    }
    header('Location: ' . $loginUrl);
    exit;
}

$message = "";
$messageType = "";
$barbers = pobierzAktywnychPracownikow($pdo, 'barber');
$selectedWorkerId = isset($_POST['worker_id']) ? (int)$_POST['worker_id'] : 0;

$serviceOptions = [];
foreach ($treatments as $svc) {
    $serviceOptions[$svc['key']] = $translations['en']['svc.' . $svc['key'] . '.name'] ?? $svc['key'];
}
$prefilledService = $_POST['service'] ?? '';
if ($prefilledService === '' && !empty($_GET['service']) && isset($serviceOptions[$_GET['service']])) {
    $prefilledService = $serviceOptions[$_GET['service']];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars($_POST['name'] ?? '');
    $email = htmlspecialchars($_POST['email'] ?? '');
    $service = htmlspecialchars($_POST['service'] ?? '');
    $date = htmlspecialchars($_POST['date'] ?? '');
    $time = htmlspecialchars($_POST['time'] ?? '');
    $workerId = (int)($_POST['worker_id'] ?? 0);

    if (!empty($name) && !empty($email) && !empty($service) && !empty($date) && !empty($time) && (count($barbers) === 0 || $workerId > 0)) {
        $bookingData = [
            'worker_id' => $workerId > 0 ? $workerId : null,
            'name'    => $name,
            'email'   => $email,
            'service' => $service,
            'date'    => $date . ' ' . $time . ':00'
        ];

        if (zapiszRezerwacje($pdo, $bookingData)) {
            $message = t('book.alert.success');
            $messageType = "success";
            setcookie('client_name', $name, time() + (86400 * 30), "/");
            setcookie('client_email', $email, time() + (86400 * 30), "/");
            $_COOKIE['client_name'] = $name;
            $_COOKIE['client_email'] = $email;
            if ($isLoggedIn) {
                header('Location: reservations.php?booked=1');
                exit;
            }
        } else {
            $message = t('book.alert.fail');
            $messageType = "error";
        }
    } else {
        $message = t('book.alert.fields');
        $messageType = "error";
    }
}

if ($isLoggedIn) {
    $prefillName = htmlspecialchars($sessionUsername ?? ($_COOKIE['client_name'] ?? ''));
    $prefillEmail = htmlspecialchars($sessionEmail ?? ($_COOKIE['client_email'] ?? ''));
} else {
    $prefillName = isset($_COOKIE['client_name']) ? htmlspecialchars($_COOKIE['client_name']) : '';
    $prefillEmail = isset($_COOKIE['client_email']) ? htmlspecialchars($_COOKIE['client_email']) : '';
}
$cookieName = $prefillName;
$cookieEmail = $prefillEmail;
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('meta.title.book'); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="page-book">

    <div class="d-only">
        <header class="b-header">
            <a href="index.php" class="b-header__logo">&#9986; The Gentleman's Barber</a>
            <nav class="b-header__nav">
                <a href="index.php"><?php echo t('nav.about'); ?></a>
                <a href="book.php" class="active"><?php echo t('nav.book'); ?></a>
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

        <main class="b-content">
            <aside class="b-rail">
                <p class="b-rail__cap"><?php echo t('book.rail.cap'); ?></p>
                <h1 class="b-rail__head"><?php echo t('book.rail.head'); ?></h1>
                <p class="b-rail__body"><?php echo t('book.rail.body'); ?></p>
                <div class="b-rail__photo" aria-label="Barbershop service detail"></div>
                <div class="b-rail__meta">
                    <span class="b-rail__meta-a"><?php echo t('book.rail.meta_a'); ?></span>
                    <span class="b-rail__meta-b"><?php echo t('book.rail.meta_b'); ?></span>
                </div>
            </aside>

            <section class="b-form">
                <p class="b-form__step"><?php echo t('book.form.step'); ?></p>
                <h2 class="b-form__title"><?php echo t('book.form.title'); ?></h2>
                <p class="b-form__desc"><?php echo t('book.form.desc'); ?></p>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                <?php endif; ?>

                <form action="book.php" method="POST" id="bookingForm">
                    <div class="b-field">
                        <label for="name"><?php echo t('book.field.name'); ?></label>
                        <input type="text" id="name" name="name" value="<?php echo $cookieName; ?>" required placeholder="<?php echo t('book.placeholder.name'); ?>" autocomplete="name">
                    </div>

                    <div class="b-field">
                        <label for="email"><?php echo t('book.field.email'); ?></label>
                        <input type="email" id="email" name="email" value="<?php echo $cookieEmail; ?>" required placeholder="<?php echo t('book.placeholder.email'); ?>" autocomplete="email">
                        <small><?php echo t('book.field.email.help'); ?></small>
                    </div>

                    <div class="b-field">
                        <label for="service"><?php echo t('book.field.service'); ?></label>
                        <select id="service" name="service" required>
                            <option value="" disabled <?php echo $prefilledService === '' ? 'selected' : ''; ?>><?php echo t('book.field.service.placeholder'); ?></option>
                            <?php foreach ($treatments as $svc): ?>
                                <?php $optValue = $serviceOptions[$svc['key']]; ?>
                                <option value="<?php echo htmlspecialchars($optValue); ?>" <?php echo $prefilledService === $optValue ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(t('svc.' . $svc['key'] . '.name')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="b-field">
                        <label for="worker_id"><?php echo t('book.field.barber'); ?></label>
                        <select id="worker_id" name="worker_id" <?php echo count($barbers) ? 'required' : ''; ?>>
                            <option value="" disabled <?php echo $selectedWorkerId ? '' : 'selected'; ?>><?php echo t('book.field.barber.placeholder'); ?></option>
                            <?php foreach ($barbers as $b): ?>
                                <option value="<?php echo (int)$b['id']; ?>" <?php echo $selectedWorkerId === (int)$b['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($b['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="b-field">
                        <label for="date"><?php echo t('book.field.date'); ?></label>
                        <input type="date" id="date" name="date" required placeholder="<?php echo t('book.field.date.placeholder'); ?>" min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="b-field">
                        <label for="time"><?php echo t('book.field.time'); ?></label>
                        <select id="time" name="time" required disabled>
                            <option value="" disabled selected><?php echo t('book.field.time.placeholder'); ?></option>
                        </select>
                    </div>

                    <div class="b-field">
                        <label for="signatureCanvas"><?php echo t('book.field.signature'); ?></label>
                        <div class="b-sig">
                            <canvas id="signatureCanvas" width="400" height="150" aria-label="Signature pad"></canvas>
                        </div>
                    </div>

                    <div class="b-actions">
                        <button type="button" id="clearSignature" class="b-clear"><?php echo t('book.action.clear'); ?></button>
                        <button type="submit" class="b-submit" id="submitBtn"><?php echo t('book.action.submit'); ?></button>
                    </div>
                </form>
            </section>
        </main>

        <footer class="b-foot">
            <?php echo t('foot.copyright.long'); ?>
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
                    <a href="book.php" class="is-active"><?php echo t('nav.book'); ?></a>
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
            <p class="m-cap"><?php echo t('book.rail.cap'); ?></p>
            <h1 class="m-h1"><?php echo t('book.rail.head.mobile'); ?></h1>
            <p class="m-body"><?php echo t('book.rail.body.mobile'); ?></p>

            <section class="b-form">
                <p class="b-form__step"><?php echo t('book.form.step'); ?></p>
                <h2 class="b-form__title"><?php echo t('book.form.title'); ?></h2>
                <p class="b-form__desc"><?php echo t('book.form.desc.mobile'); ?></p>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                <?php endif; ?>

                <form action="book.php" method="POST" id="bookingForm">
                    <div class="b-field">
                        <label for="name"><?php echo t('book.field.name'); ?></label>
                        <input type="text" id="name" name="name" value="<?php echo $cookieName; ?>" required placeholder="<?php echo t('book.placeholder.name'); ?>" autocomplete="name">
                    </div>

                    <div class="b-field">
                        <label for="email"><?php echo t('book.field.email'); ?></label>
                        <input type="email" id="email" name="email" value="<?php echo $cookieEmail; ?>" required placeholder="<?php echo t('book.placeholder.email'); ?>" autocomplete="email">
                        <small><?php echo t('book.field.email.help'); ?></small>
                    </div>

                    <div class="b-field">
                        <label for="service"><?php echo t('book.field.service'); ?></label>
                        <select id="service" name="service" required>
                            <option value="" disabled <?php echo $prefilledService === '' ? 'selected' : ''; ?>><?php echo t('book.field.service.placeholder'); ?></option>
                            <?php foreach ($treatments as $svc): ?>
                                <?php $optValue = $serviceOptions[$svc['key']]; ?>
                                <option value="<?php echo htmlspecialchars($optValue); ?>" <?php echo $prefilledService === $optValue ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(t('svc.' . $svc['key'] . '.name')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="b-field">
                        <label for="worker_id_mobile"><?php echo t('book.field.barber'); ?></label>
                        <select id="worker_id_mobile" name="worker_id" <?php echo count($barbers) ? 'required' : ''; ?>>
                            <option value="" disabled <?php echo $selectedWorkerId ? '' : 'selected'; ?>><?php echo t('book.field.barber.placeholder'); ?></option>
                            <?php foreach ($barbers as $b): ?>
                                <option value="<?php echo (int)$b['id']; ?>" <?php echo $selectedWorkerId === (int)$b['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($b['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="b-field">
                        <label for="date"><?php echo t('book.field.date'); ?></label>
                        <input type="date" id="date" name="date" required placeholder="<?php echo t('book.field.date.placeholder'); ?>" min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="b-field">
                        <label for="time"><?php echo t('book.field.time'); ?></label>
                        <select id="time" name="time" required disabled>
                            <option value="" disabled selected><?php echo t('book.field.time.placeholder'); ?></option>
                        </select>
                    </div>

                    <div class="b-field">
                        <label for="signatureCanvas"><?php echo t('book.field.signature'); ?></label>
                        <div class="b-sig">
                            <canvas id="signatureCanvas" width="400" height="150" aria-label="Signature pad"></canvas>
                        </div>
                    </div>

                    <div class="b-actions">
                        <button type="button" id="clearSignature" class="b-clear"><?php echo t('book.action.clear'); ?></button>
                        <button type="submit" class="b-submit" id="submitBtn"><?php echo t('book.action.submit'); ?></button>
                    </div>
                </form>
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
