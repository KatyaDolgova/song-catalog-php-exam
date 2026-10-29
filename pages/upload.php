<?php
session_start();
include_once('./classes.php');
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$categories = SoundManager::getCategories();
if ($_POST['action'] ?? '' === 'upload') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
// Проверка файла
if (isset($_FILES['audio'])) {
    if ($_FILES['audio']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['audio']['tmp_name'];
        $file_size = $_FILES['audio']['size'];

        // Тип и расширение определяем по реальному содержимому файла, а не по данным
        // от клиента ($_FILES['audio']['type'] и имя файла легко подделать).
        $allowed_mimes = [
            'audio/mpeg' => 'mp3',
            'audio/wav' => 'wav',
            'audio/x-wav' => 'wav',
            'audio/vnd.wave' => 'wav',
        ];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detected_mime = finfo_file($finfo, $file_tmp_path);
        finfo_close($finfo);

        if (!isset($allowed_mimes[$detected_mime])) {
            $error = 'Недопустимый тип файла. Разрешены только MP3 и WAV.';
        }
        elseif ($file_size > 10 * 1024 * 1024) {
            $error = 'Файл слишком большой. Максимальный размер 10MB.';
        }
        else {
            // Создание директории для загрузки
            $upload_dir = '../uploads/sounds/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            // Генерация уникального имени файла; расширение берётся из распознанного
            // MIME-типа, а не из имени файла, присланного клиентом.
            $file_extension = $allowed_mimes[$detected_mime];
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename; 
            if (move_uploaded_file($file_tmp_path, $upload_path)) {
                $upload_path_for_db = 'uploads/sounds/' . $new_filename; 
    
                $sound = new Sound(0, $name, $description, $category, $upload_path_for_db, $_SESSION['user_id'], 0); 
                SoundManager::addSound($sound);
                $success = true;
            } else {
                $error = 'Ошибка загрузки файла на диск.';
            }
        }
    } else {
        switch ($_FILES['audio']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error = 'Файл слишком большой. Максимальный размер 10MB.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error = 'Файл был загружен не полностью.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error = 'Пожалуйста, выберите аудиофайл.';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error = 'Ошибка: Временная папка не найдена.';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error = 'Ошибка: Не удалось записать файл на диск.';
                break;
            case UPLOAD_ERR_EXTENSION:
                $error = 'Загрузка файла остановлена расширением PHP.';
                break;
            default:
                $error = 'Неизвестная ошибка при загрузке файла.';
        }
    }
} else {
    $error = 'Пожалуйста, выберите аудиофайл.';
}
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Загрузить звук</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1 class="mb-4">Загрузить звук</h1>
       
        <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
            <div class="container-fluid">
                <a class="navbar-brand" href="../index.php">Каталог</a>
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="upload.php">Загрузить звук</a>
                    <a class="nav-link" href="logout.php">Выйти</a>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a class="nav-link" href="admin.php">Админ-панель</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                Звук успешно загружен и ожидает одобрения администратором.
                <br><br>
                <a href="../index.php" class="btn btn-primary">Вернуться на главную</a>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Название звука</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Описание</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category" class="form-label">Категория</label>
                            <select class="form-control" id="category" name="category" required>
                                <option value="">Выберите категорию</option>
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat['name']) ?>">
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="audio" class="form-label">Аудиофайл</label>
                            <input type="file" class="form-control" id="audio" name="audio" accept="audio/*" required>
                            <div class="form-text">Разрешены форматы MP3, WAV. Максимальный размер 10MB.</div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Загрузить</button>
                        <a href="../index.php" class="btn btn-secondary">Назад</a>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>