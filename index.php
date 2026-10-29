<?php
session_start();
include_once('pages/classes.php');

$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$approved = 1; // Только одобренные звуки для пользователей

$sounds = SoundManager::getSounds($category, $search, $approved);
$categories = SoundManager::getCategories();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Каталог Звуков</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sound-card {
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        .search-form {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1 class="mb-4">Каталог Звуков</h1>
        
        <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">Каталог</a>
                <div class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a class="nav-link" href="pages/upload.php">Загрузить звук</a>
                        <a class="nav-link" href="pages/logout.php">Выйти</a>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a class="nav-link" href="pages/admin.php">Админ-панель</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a class="nav-link" href="pages/login.php">Вход</a>
                        <a class="nav-link" href="pages/register.php">Регистрация</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
        
        <div class="search-form">
            <form method="GET">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="category" class="form-label">Категория</label>
                        <select name="category" id="category" class="form-select">
                            <option value="">Все категории</option>
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['name']) ?>" 
                                        <?= $category === $cat['name'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="search" class="form-label">Поиск</label>
                        <input type="text" name="search" id="search" class="form-control" 
                               placeholder="Поиск звуков..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Поиск</button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="row">
            <?php if (!empty($sounds)): ?>
                <?php foreach ($sounds as $sound): ?>
                    <div class="col-md-4">
                        <div class="sound-card">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($sound->getName()) ?></h5>
                                <p class="card-text">
                                    <small class="text-muted">Категория: <?= htmlspecialchars($sound->getCategory()) ?></small>
                                </p>
                                <p class="card-text"><?= htmlspecialchars($sound->getDescription()) ?></p>
                                
                                                                <audio controls class="w-100 mb-2">
                                    <source src="<?= htmlspecialchars($sound->getFilePath()) ?>" type="audio/mpeg">
                                    Ваш браузер не поддерживает аудио.
                                </audio>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="pages/download.php?id=<?= $sound->getId() ?>" class="btn btn-success btn-sm">
                                        Скачать
                                    </a>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" 
                                            data-bs-target="#complaintModal" 
                                            data-sound-id="<?= $sound->getId() ?>">
                                        Пожаловаться
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="text-center">
                        <p class="lead">Звуки не найдены</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal fade" id="complaintModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Пожаловаться на звук</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                                <form method="POST" action="pages/complaint.php" id="complaintForm">
                    <div class="modal-body">
                        <input type="hidden" id="complaintSoundId" name="sound_id" value="">
                        <div class="mb-3">
                            <label for="complaintReason" class="form-label">Причина жалобы</label>
                            <textarea class="form-control" id="complaintReason" name="reason" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Отправить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
        // Установка ID звука в модальное окно и обработка отправки формы
        document.querySelectorAll('[data-bs-target="#complaintModal"]').forEach(button => {
            button.addEventListener('click', function() {
                const soundId = this.getAttribute('data-sound-id');
                document.getElementById('complaintSoundId').value = soundId;
            });
        });
        document.getElementById('complaintForm').addEventListener('submit', function(e) {
        });
    </script>
</body>
</html>