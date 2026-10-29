<?php
session_start();
include_once('./classes.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$error = null;
$success = false;

if ($_POST['sound_id'] ?? '' && $_POST['reason'] ?? '') {
    $sound_id = (int)$_POST['sound_id'];
    $reason = trim($_POST['reason']);

    if ($sound_id <= 0 || empty($reason)) {
        $error = 'Пожалуйста, укажите причину жалобы.';
    } else {
        try {
            ComplaintManager::addComplaint($sound_id, $_SESSION['user_id'], $reason);
            $success = true;
        } catch (Exception $e) {
            $error = 'Ошибка при отправке жалобы.';
            error_log("Ошибка добавления жалобы: " . $e->getMessage());
        }
    }
} else {
    $error = 'Неверные данные для жалобы.';
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Жалоба</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3>Жалоба</h3>
                    </div>
                    <div class="card-body text-center">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                Жалоба успешно отправлена!
                            </div>
                        <?php elseif ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <a href="../index.php" class="btn btn-primary">Вернуться на главную</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>