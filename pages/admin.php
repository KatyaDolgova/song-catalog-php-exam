<?php
session_start();
include_once('./classes.php');

// Проверка прав администратора
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

if ($_POST['action'] ?? '') {
    switch ($_POST['action']) {
        case 'approve_sound':
            $id = (int)$_POST['id'];
            $sound_to_approve = SoundManager::getSoundById($id);
            if ($sound_to_approve) {
                $current_file_path = $sound_to_approve->getFilePath();
                if (preg_match('/^uploads\/sounds\//', $current_file_path)) {
                     SoundManager::updateSoundFilePathAndApprove($id, $current_file_path);
                } else {
                     error_log("Unexpected file path format for sound ID $id during approval: $current_file_path");
                }
            } else {
                 error_log("Sound ID $id not found for approval.");
            }
            break;
        case 'delete_sound':
            $id = (int)$_POST['id'];
            SoundManager::deleteSound($id);
            break;
        case 'update_category':
            $id = (int)$_POST['id'];
            $category = $_POST['category'];
            SoundManager::updateSoundCategory($id, $category);
            break;
        case 'add_category':
            $name = trim($_POST['category_name']);
            if ($name) {
                SoundManager::addCategory($name);
            }
            break;
        case 'delete_category':
            $id = (int)$_POST['category_id'];
            SoundManager::deleteCategory($id);
            break;
        case 'update_user':
            $id = (int)$_POST['user_id'];
            $status = $_POST['status'];
            UserManager::updateUserStatus($id, $status);
            break;
        case 'delete_user':
            $id = (int)$_POST['user_id'];
            UserManager::deleteUser($id);
            break;
    }
}

$pending_sounds = SoundManager::getPendingSounds();
$categories = SoundManager::getCategories();
$users = UserManager::getUsers();
$complaints = ComplaintManager::getComplaints();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1 class="mb-4">Админ-панель</h1>
    
        <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
            <div class="container-fluid">
                <a class="navbar-brand" href="../index.php">Каталог</a>
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="upload.php">Загрузить звук</a>
                    <a class="nav-link" href="logout.php">Выйти</a>
                    <a class="nav-link" href="admin.php">Админ-панель</a>
                </div>
            </div>
        </nav>
        
        <div class="card mb-4">
            <div class="card-header">
                <h3>Ожидание одобрения</h3>
            </div>
            <div class="card-body">
                <?php if (empty($pending_sounds)): ?>
                    <p>Нет звуков на одобрении</p>
                <?php else: ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Название</th>
                                <th>Описание</th>
                                <th>Категория</th>
                                <th>Автор</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_sounds as $sound): ?>
                                <tr>
                                    <td><?= htmlspecialchars($sound->getName()) ?></td>
                                    <td><?= htmlspecialchars($sound->getDescription()) ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="update_category">
                                            <input type="hidden" name="id" value="<?= $sound->getId() ?>">
                                            <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?= htmlspecialchars($cat['name']) ?>" 
                                                        <?= $sound->getCategory() === $cat['name'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($cat['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    </td>
                                    <td><?= htmlspecialchars($sound->getUploaderName()) ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="approve_sound">
                                            <input type="hidden" name="id" value="<?= $sound->getId() ?>">
                                            <button type="submit" class="btn btn-success btn-sm">Одобрить</button>
                                        </form>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Удалить звук?');">
                                            <input type="hidden" name="action" value="delete_sound">
                                            <input type="hidden" name="id" value="<?= $sound->getId() ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Удалить</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h3>Управление категориями</h3>
            </div>
            <div class="card-body">
                <form method="POST" class="mb-3">
                    <input type="hidden" name="action" value="add_category">
                    <div class="row">
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="category_name" placeholder="Название категории" required>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100">Добавить</button>
                        </div>
                    </div>
                </form>
                
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Категория</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><?= htmlspecialchars($cat['name']) ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Удалить категорию?');">
                                        <input type="hidden" name="action" value="delete_category">
                                        <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Удалить</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
      
        <div class="card mb-4">
            <div class="card-header">
                <h3>Управление пользователями</h3>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Имя</th>
                            <th>Email</th>
                            <th>Роль</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user->getUsername()) ?></td>
                                <td><?= htmlspecialchars($user->getEmail()) ?></td>
                                <td><?= htmlspecialchars($user->getRole()) ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_user">
                                        <input type="hidden" name="user_id" value="<?= $user->getId() ?>">
                                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="active" <?= $user->getStatus() === 'active' ? 'selected' : '' ?>>Активен</option>
                                            <option value="banned" <?= $user->getStatus() === 'banned' ? 'selected' : '' ?>>Заблокирован</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Удалить пользователя?');">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?= $user->getId() ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Удалить</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Жалобы</h3>
            </div>
            <div class="card-body">
                <?php if (empty($complaints)): ?>
                    <p>Нет жалоб</p>
                <?php else: ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Звук</th>
                                <th>Пользователь</th>
                                <th>Причина</th>
                                <th>Дата</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($complaints as $complaint): ?>
                                <tr>
                                    <td><?= htmlspecialchars($complaint['sound_name']) ?></td>
                                    <td><?= htmlspecialchars($complaint['user_username']) ?></td>
                                    <td><?= htmlspecialchars($complaint['reason']) ?></td>
                                    <td><?= $complaint['created_at'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>