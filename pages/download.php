<?php
session_start();
include_once('./classes.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$sound_id = (int)$_GET['id'];
$sound = SoundManager::getSoundById($sound_id);

if (!$sound || !$sound->isApproved()) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

// Пути считаем от корня проекта (__DIR__ = pages/), а не от текущей рабочей
// директории процесса — иначе результат зависит от того, как настроен веб-сервер.
// Раньше здесь был путь '../pages/uploads/sounds/', который не совпадал с реальной
// папкой '../uploads/sounds/' — проверка не работала и скачивание было сломано.
$project_root = realpath(__DIR__ . '/..');
$allowed_dir = realpath($project_root . '/uploads/sounds/');
$real_path = $allowed_dir ? realpath($project_root . '/' . $sound->getFilePath()) : false;

if (!$real_path || !$allowed_dir || strpos($real_path, $allowed_dir) !== 0) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

if (!file_exists($real_path)) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

// Заголовки для скачивания
$file_extension = strtolower(pathinfo($real_path, PATHINFO_EXTENSION));
$content_types = [
    'mp3' => 'audio/mpeg',
    'wav' => 'audio/wav',
    'ogg' => 'audio/ogg'
];

$content_type = $content_types[$file_extension] ?? 'application/octet-stream';

header('Content-Type: ' . $content_type);
header('Content-Disposition: attachment; filename="' . basename($real_path) . '"');
header('Content-Length: ' . filesize($real_path));

readfile($real_path);
exit;
?>