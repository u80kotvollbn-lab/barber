<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/session.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$message = "";
$messageType = "";

$mode = $_GET['mode'] ?? 'register';
if (!in_array($mode, ['register', 'login'], true)) {
    $mode = 'register';
}

$postAuthRedirect = 'book.php';
if (!empty($_GET['service'])) {
    $postAuthRedirect = 'book.php?service=' . urlencode($_GET['service']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? ($mode === 'login' ? 'login' : 'register');

    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $mode = 'login';

        if (empty($email) || empty($password)) {
            $message = t('reg.alert.empty'); $messageType = "error";
        } else {
            $user = znajdzUzytkownikaPoEmail($pdo, $email);
            if (!$user || !password_verify($password, $user['password_hash'])) {
                $message = t('reg.alert.invalid'); $messageType = "error";
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $isLoggedIn = true;
                $message = t('reg.alert.success_login'); $messageType = "success";
                    // Auto-login already set session above, redirect to booking page
                    header('Location: ' . $postAuthRedirect);
                    exit;
            }
        }
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $mode = 'register';

        if (!empty($username) && !empty($email) && !empty($password) && !empty($passwordConfirm)) {
            if ($password !== $passwordConfirm) {
                $message = t('reg.alert.password_mismatch'); $messageType = "error";
            } elseif (strlen($password) < 6) {
                $message = t('reg.alert.password_short'); $messageType = "error";
            } else {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email OR username = :username");
                $stmt->execute(['email' => $email, 'username' => $username]);
                if ($stmt->fetchColumn() > 0) {
                    $message = t('reg.alert.user_exists'); $messageType = "error";
                } else {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    try {
                        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)");
                        $stmt->execute(['username' => $username, 'email' => $email, 'password_hash' => $passwordHash]);
                        $message = t('reg.alert.success_register'); $messageType = "success";
                    // Auto-login after successful registration
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    $isLoggedIn = true;
                    // Redirect to booking page
                    header('Location: ' . $postAuthRedirect);
                    exit;
                    } catch (\PDOException $e) {
                        $message = t('reg.alert.error_register'); $messageType = "error";
                    }
                }
            }
        } else {
            $message = t('reg.alert.empty'); $messageType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('meta.title.register'); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="page-register">

    <div class="d-only">
        <header class="b-header">
            <a href="index.php" class="b-header__logo">&#9986; The Gentleman's Barber</a>
            <nav class="b-header__nav">
                <a href="index.php"><?php echo t('nav.about'); ?></a>
                <a href="book.php"><?php echo t('nav.book'); ?></a>
                <a href="treatments.php"><?php echo t('nav.treatments'); ?></a>
                <a href="reviews.php"><?php echo t('nav.reviews'); ?></a>
                <?php if ($isLoggedIn): ?>
                    <a href="reservations.php"><?php echo t('nav.reservations'); ?></a>
                    <a href="logout.php"><?php echo t('nav.signout'); ?></a>
                <?php else: ?>
                    <a href="register.php" class="active"><?php echo t('nav.register'); ?></a>
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

        <main class="g-main">
            <div class="g-hero">
                <p class="g-hero__eyebrow"><?php echo t('reg.hero.eyebrow'); ?></p>
                <p class="g-hero__quote"><?php echo t('reg.hero.quote'); ?></p>
                <h1 class="g-hero__title"><?php echo t('reg.hero.title'); ?></h1>
            </div>

            <section class="g-side">
                <p class="g-side__kicker"><?php echo t('reg.side.kicker'); ?></p>
                <h2 class="g-side__title"><?php echo t('reg.side.title'); ?></h2>
                <p class="g-side__copy"><?php echo t('reg.side.copy'); ?></p>

                <form action="register.php" method="POST" id="registerForm" class="g-form">
                    <div class="g-auth-switch">
                        <a class="g-auth-switch__link <?php echo $mode === 'register' ? 'is-active' : ''; ?>" href="register.php?mode=register"><?php echo t('reg.tab.register'); ?></a>
                        <a class="g-auth-switch__link <?php echo $mode === 'login' ? 'is-active' : ''; ?>" href="register.php?mode=login"><?php echo t('reg.tab.login'); ?></a>
                    </div>

                    <?php if ($mode === 'login'): ?>
                        <p class="g-form__cap"><?php echo t('reg.login.cap'); ?></p>
                        <h3 class="g-form__title"><?php echo t('reg.login.title'); ?></h3>
                    <?php else: ?>
                        <p class="g-form__cap"><?php echo t('reg.create.cap'); ?></p>
                        <h3 class="g-form__title"><?php echo t('reg.create.title'); ?></h3>
                    <?php endif; ?>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <?php if ($isLoggedIn): ?>
                        <p class="g-form__login"><?php echo t('reg.signedin.text'); ?> <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>.</p>
                        <div class="g-auth-actions">
                            <a href="book.php"><?php echo t('reg.signedin.continue'); ?></a>
                            <a href="logout.php"><?php echo t('reg.signedin.signout'); ?></a>
                        </div>
                    <?php elseif ($mode === 'login'): ?>
                        <input type="hidden" name="action" value="login">
                        <input type="email" class="g-input" id="login_email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required placeholder="<?php echo t('reg.placeholder.email'); ?>" autocomplete="email">
                        <input type="password" class="g-input" id="login_password" name="password" required placeholder="<?php echo t('reg.placeholder.password'); ?>" autocomplete="current-password">
                        <button type="submit" class="g-submit"><?php echo t('reg.action.signin'); ?></button>
                        <p class="g-form__login"><?php echo t('reg.create.prompt'); ?> <a href="register.php?mode=register"><?php echo t('reg.create.link'); ?></a></p>
                    <?php else: ?>
                        <input type="hidden" name="action" value="register">
                        <input type="text" class="g-input" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required placeholder="<?php echo t('reg.placeholder.username'); ?>" autocomplete="username">
                        <input type="email" class="g-input" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required placeholder="<?php echo t('reg.placeholder.email'); ?>" autocomplete="email">
                        <input type="password" class="g-input" id="password" name="password" required placeholder="<?php echo t('reg.placeholder.password'); ?>" autocomplete="new-password">
                        <input type="password" class="g-input" id="password_confirm" name="password_confirm" required placeholder="<?php echo t('reg.placeholder.password_confirm'); ?>" autocomplete="new-password">

                        <label class="g-consent">
                            <input type="checkbox" checked>
                            <span><?php echo t('reg.consent'); ?></span>
                        </label>

                        <button type="submit" class="g-submit"><?php echo t('reg.action.create'); ?></button>
                        <p class="g-form__login"><?php echo t('reg.signin.prompt'); ?> <a href="register.php?mode=login"><?php echo t('reg.signin.link'); ?></a></p>
                    <?php endif; ?>
                </form>
            </section>
        </main>

        <footer class="g-foot">
            <p><?php echo t('reg.foot.left'); ?></p>
            <p><?php echo t('reg.foot.copyright'); ?></p>
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
                    <?php if ($isLoggedIn): ?>
                        <a href="reservations.php"><?php echo t('nav.reservations'); ?></a>
                        <a href="logout.php"><?php echo t('nav.signout'); ?></a>
                    <?php else: ?>
                        <a href="register.php?mode=register" class="is-active"><?php echo t('nav.register'); ?></a>
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
            <div class="g-hero">
                <p class="g-hero__eyebrow"><?php echo t('reg.hero.eyebrow'); ?></p>
                <h1 class="g-hero__title"><?php echo t('reg.hero.title'); ?></h1>
            </div>

            <section class="g-side">
                <p class="m-cap"><?php echo t('reg.side.kicker'); ?></p>
                <h2 class="m-h2"><?php echo t('reg.side.title'); ?></h2>
                <p class="m-body"><?php echo t('reg.side.copy'); ?></p>

                <form action="register.php" method="POST" id="registerForm" class="g-form">
                    <div class="g-auth-switch">
                        <a class="g-auth-switch__link <?php echo $mode === 'register' ? 'is-active' : ''; ?>" href="register.php?mode=register"><?php echo t('reg.tab.register'); ?></a>
                        <a class="g-auth-switch__link <?php echo $mode === 'login' ? 'is-active' : ''; ?>" href="register.php?mode=login"><?php echo t('reg.tab.login'); ?></a>
                    </div>

                    <?php if ($mode === 'login'): ?>
                        <p class="g-form__cap"><?php echo t('reg.login.cap'); ?></p>
                        <h3 class="g-form__title"><?php echo t('reg.login.title'); ?></h3>
                    <?php else: ?>
                        <p class="g-form__cap"><?php echo t('reg.create.cap'); ?></p>
                        <h3 class="g-form__title"><?php echo t('reg.create.title'); ?></h3>
                    <?php endif; ?>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <?php if ($isLoggedIn): ?>
                        <p class="g-form__login"><?php echo t('reg.signedin.text'); ?> <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>.</p>
                        <div class="g-auth-actions">
                            <a href="book.php"><?php echo t('reg.signedin.continue'); ?></a>
                            <a href="logout.php"><?php echo t('reg.signedin.signout'); ?></a>
                        </div>
                    <?php elseif ($mode === 'login'): ?>
                        <input type="hidden" name="action" value="login">
                        <input type="email" class="g-input" id="login_email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required placeholder="<?php echo t('reg.placeholder.email'); ?>" autocomplete="email">
                        <input type="password" class="g-input" id="login_password" name="password" required placeholder="<?php echo t('reg.placeholder.password'); ?>" autocomplete="current-password">
                        <button type="submit" class="g-submit"><?php echo t('reg.action.signin'); ?></button>
                        <p class="g-form__login"><?php echo t('reg.create.prompt'); ?> <a href="register.php?mode=register"><?php echo t('reg.create.link'); ?></a></p>
                    <?php else: ?>
                        <input type="hidden" name="action" value="register">
                        <input type="text" class="g-input" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required placeholder="<?php echo t('reg.placeholder.username'); ?>" autocomplete="username">
                        <input type="email" class="g-input" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required placeholder="<?php echo t('reg.placeholder.email'); ?>" autocomplete="email">
                        <input type="password" class="g-input" id="password" name="password" required placeholder="<?php echo t('reg.placeholder.password'); ?>" autocomplete="new-password">
                        <input type="password" class="g-input" id="password_confirm" name="password_confirm" required placeholder="<?php echo t('reg.placeholder.password_confirm'); ?>" autocomplete="new-password">

                        <label class="g-consent">
                            <input type="checkbox" checked>
                            <span><?php echo t('reg.consent'); ?></span>
                        </label>

                        <button type="submit" class="g-submit"><?php echo t('reg.action.create'); ?></button>
                        <p class="g-form__login"><?php echo t('reg.signin.prompt'); ?> <a href="register.php?mode=login"><?php echo t('reg.signin.link'); ?></a></p>
                    <?php endif; ?>
                </form>
            </section>
        </main>

        <footer class="g-foot">
            <p><?php echo t('reg.foot.copyright'); ?></p>
        </footer>
    </div>

</div>
<script src="js/main.js"></script>
</body>
</html>
