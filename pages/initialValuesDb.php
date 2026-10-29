<?php

$config = require __DIR__ . '/../config.php';

try {
    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4", $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Очистка таблиц перед заполнением (в правильном порядке)
    $pdo->exec("DELETE FROM complaints");
    $pdo->exec("DELETE FROM sounds");
    $pdo->exec("DELETE FROM categories");
    $pdo->exec("DELETE FROM users WHERE username != 'admin'");
    
    // Добавление тестовых категорий
    $categories = [
        'огонь', 'город', 'автомобили', 'аплодисменты', 'животные', 
        'музыка', 'природа', 'машинные звуки', 'речь', 'удары'
    ];
    
    $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
    foreach ($categories as $category) {
        $stmt->execute([$category]);
    }
    
    // Создание тестовых пользователей
    $users = [
        ['username' => 'user1', 'email' => 'user1@example.com', 'password' => 'password123', 'role' => 'user'],
        ['username' => 'user2', 'email' => 'user2@example.com', 'password' => 'password123', 'role' => 'user'],
        ['username' => 'moderator', 'email' => 'moderator@example.com', 'password' => 'mod123', 'role' => 'admin']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
    foreach ($users as $user) {
        $stmt->execute([
            $user['username'],
            $user['email'],
            password_hash($user['password'], PASSWORD_DEFAULT),
            $user['role']
        ]);
    }
    
    // Получение ID только что созданных пользователей
    $stmt = $pdo->query("SELECT id, username FROM users WHERE username IN ('user1', 'user2', 'moderator')");
    $user_ids = [];
    while ($row = $stmt->fetch()) {
        $user_ids[$row['username']] = $row['id'];
    }
    
    // Добавление тестовых звуков (одобренные)
    $approved_sounds = [
        ['name' => 'Звук костра', 'description' => 'Трещание дров в костре', 'category' => 'огонь', 'file_path' => 'uploads/sounds/fire_crackling.mp3', 'uploader_username' => 'user1', 'approved' => 1],
        ['name' => 'Шум города', 'description' => 'Фоновый шум городской улицы', 'category' => 'город', 'file_path' => 'uploads/sounds/city_noise.mp3', 'uploader_username' => 'user1', 'approved' => 1],
        ['name' => 'Звук автомобиля', 'description' => 'Запуск двигателя автомобиля', 'category' => 'автомобили', 'file_path' => 'uploads/sounds/car_engine.mp3', 'uploader_username' => 'moderator', 'approved' => 1],
        ['name' => 'Аплодисменты', 'description' => 'Аплодисменты толпы', 'category' => 'аплодисменты', 'file_path' => 'uploads/sounds/applause.mp3', 'uploader_username' => 'user1', 'approved' => 1],
        ['name' => 'Лай собаки', 'description' => 'Громкий лай собаки', 'category' => 'животные', 'file_path' => 'uploads/sounds/dog_barking.mp3', 'uploader_username' => 'moderator', 'approved' => 1]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO sounds (name, description, category, file_path, uploader_id, approved) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($approved_sounds as $sound) {
        $stmt->execute([
            $sound['name'],
            $sound['description'],
            $sound['category'],
            $sound['file_path'],
            $user_ids[$sound['uploader_username']],
            $sound['approved']
        ]);
    }
    
    // Получение ID только что созданных звуков
    $stmt = $pdo->query("SELECT id, name FROM sounds WHERE uploader_id IN (" . implode(',', array_values($user_ids)) . ")");
    $sound_ids = [];
    while ($row = $stmt->fetch()) {
        $sound_ids[$row['name']] = $row['id'];
    }
    
    // Добавление тестовых звуков (ожидающие одобрения)
    $pending_sounds = [
        ['name' => 'Новый звук', 'description' => 'Новый звук для одобрения', 'category' => 'музыка', 'file_path' => 'uploads/sounds/new_sound.mp3', 'uploader_username' => 'user1', 'approved' => 0],
        ['name' => 'Звук природы', 'description' => 'Пение птиц в лесу', 'category' => 'природа', 'file_path' => 'uploads/sounds/birds_singing.mp3', 'uploader_username' => 'moderator', 'approved' => 0]
    ];
    
    foreach ($pending_sounds as $sound) {
        $stmt->execute([
            $sound['name'],
            $sound['description'],
            $sound['category'],
            $sound['file_path'],
            $user_ids[$sound['uploader_username']],
            $sound['approved']
        ]);
    }
    
    // Обновление массива ID звуков
    $stmt = $pdo->query("SELECT id, name FROM sounds WHERE uploader_id IN (" . implode(',', array_values($user_ids)) . ")");
    $sound_ids = [];
    while ($row = $stmt->fetch()) {
        $sound_ids[$row['name']] = $row['id'];
    }
    
    // Добавление тестовых жалоб (только на существующие звуки)
    $complaints = [
        ['sound_name' => 'Звук костра', 'user_username' => 'user1', 'reason' => 'Нарушение авторских прав'],
        ['sound_name' => 'Шум города', 'user_username' => 'moderator', 'reason' => 'Неприемлемый контент']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO complaints (sound_id, user_id, reason) VALUES (?, ?, ?)");
    foreach ($complaints as $complaint) {
        $stmt->execute([
            $sound_ids[$complaint['sound_name']],
            $user_ids[$complaint['user_username']],
            $complaint['reason']
        ]);
    }
    
    echo "База данных успешно заполнена тестовыми данными!";
    
} catch(PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>