<?php
session_start();
include_once('./classes.php');

if ($_POST['action'] ?? '' === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $captcha_input = $_POST['captcha'] ?? '';
    
    // Проверка CAPTCHA
    if (strtolower($captcha_input) !== strtolower($_SESSION['captcha_code'])) {
        $error = 'Неверный код CAPTCHA';
    } else {
        $user = new User(0, $username, $email, password_hash($password, PASSWORD_DEFAULT), 'user');
        $result = UserManager::register($user);
        
        if ($result === true) {
            $success = true;
        } else {
            $error = $result;
        }
    }

    // Старый код капчи больше не нужен: при перезагрузке страницы картинка captcha.php сама задаст новый.
    unset($_SESSION['captcha_code']);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3>Регистрация</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success">Регистрация успешна! <a href="login.php">Войти</a></div>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="register">
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">Имя пользователя</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">Пароль</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="captcha" class="form-label">CAPTCHA</label>
                                    <div class="mb-2">
                                        <img src="captcha.php" alt="CAPTCHA" id="captchaImage"
                                             style="cursor: pointer;" title="Нажмите, чтобы обновить"
                                             onclick="this.src='captcha.php?' + Date.now()">
                                    </div>
                                    <input type="text" class="form-control" id="captcha" name="captcha" required>
                                    <div class="form-text">Введите код с изображения. Нажмите на картинку, чтобы обновить.</div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">Зарегистрироваться</button>
                            </form>
                            
                            <div class="text-center mt-3">
                                <a href="../index.php">На главную</a> | 
                                <a href="login.php"><u>Уже есть аккаунт? Войти</u></a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>