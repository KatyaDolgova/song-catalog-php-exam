<?php
class Database {
    private static $pdo = null;
    
    public static function connect() {
        if (self::$pdo === null) {
            $config = require __DIR__ . '/../config.php';

            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            self::$pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        }
        return self::$pdo;
    }
}

class Sound {
    protected $id;
    protected $name;
    protected $description;
    protected $category;
    protected $file_path;
    protected $uploader_id;
    protected $approved;
    protected $created_at;
    
    public function __construct($id, $name, $description, $category, $file_path, $uploader_id, $approved, $created_at = null) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->category = $category;
        $this->file_path = $file_path;
        $this->uploader_id = $uploader_id;
        $this->approved = $approved;
        $this->created_at = $created_at;
    }
    
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getDescription() { return $this->description; }
    public function getCategory() { return $this->category; }
    public function getFilePath() { return $this->file_path; }
    public function getUploaderId() { return $this->uploader_id; }
    public function isApproved() { return $this->approved; }
    public function getCreatedAt() { return $this->created_at; }
    
    public function getUploaderName() {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$this->uploader_id]);
        $row = $stmt->fetch();
        return $row['username'] ?? 'Неизвестный';
    }
    
}

class User {
    protected $id;
    protected $username;
    protected $email;
    protected $password_hash;
    protected $role;
    protected $status;
    protected $created_at;
    
    public function __construct($id, $username, $email, $password_hash, $role, $status = 'active', $created_at = null) {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->password_hash = $password_hash;
        $this->role = $role;
        $this->status = $status;
        $this->created_at = $created_at;
    }
    
    public function getId() { return $this->id; }
    public function getUsername() { return $this->username; }
    public function getEmail() { return $this->email; }
    public function getPasswordHash() { return $this->password_hash; }
    public function getRole() { return $this->role; }
    public function getStatus() { return $this->status; }
    public function getCreatedAt() { return $this->created_at; }
}

class SoundManager {
    public static function addSound($sound) {
        $pdo = Database::connect();
        
        $sql = "INSERT INTO sounds (name, description, category, file_path, uploader_id, approved) 
                VALUES (:name, :description, :category, :file_path, :uploader_id, :approved)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name' => $sound->getName(),
            ':description' => $sound->getDescription(),
            ':category' => $sound->getCategory(),
            ':file_path' => $sound->getFilePath(),
            ':uploader_id' => $sound->getUploaderId(),
            ':approved' => $sound->isApproved()
        ]);
    }
    
    public static function getSounds($category = null, $search = null, $approved = 1) {
        $pdo = Database::connect();
        
        $sql = "SELECT * FROM sounds WHERE approved = :approved";
        $params = [':approved' => $approved];
        
        if ($category && $category !== '') {
            $sql .= " AND category = :category";
            $params[':category'] = $category;
        }
        
        if ($search && $search !== '') {
            $sql .= " AND (name LIKE :search OR description LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $sounds = [];
        while ($row = $stmt->fetch()) {
            $sounds[] = new Sound(
                $row['id'],
                $row['name'],
                $row['description'],
                $row['category'],
                $row['file_path'],
                $row['uploader_id'],
                $row['approved'],
                $row['created_at']
            );
        }
        
        return $sounds;
    }
    
    public static function getSoundById($id) {
        $pdo = Database::connect();
        
        $sql = "SELECT * FROM sounds WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $row = $stmt->fetch();
        if ($row) {
            return new Sound(
                $row['id'],
                $row['name'],
                $row['description'],
                $row['category'],
                $row['file_path'],
                $row['uploader_id'],
                $row['approved'],
                $row['created_at']
            );
        }
        
        return null;
    }
    
    public static function getPendingSounds() {
        $pdo = Database::connect();
        
        $sql = "SELECT * FROM sounds WHERE approved = 0 ORDER BY created_at DESC";
        $stmt = $pdo->query($sql);
        
        $sounds = [];
        while ($row = $stmt->fetch()) {
            $sounds[] = new Sound(
                $row['id'],
                $row['name'],
                $row['description'],
                $row['category'],
                $row['file_path'],
                $row['uploader_id'],
                $row['approved'],
                $row['created_at']
            );
        }
        
        return $sounds;
    }
    
    public static function approveSound($id) {
        $pdo = Database::connect();
        
        $sql = "UPDATE sounds SET approved = 1 WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
    }
    
    public static function deleteSound($id) {
        $pdo = Database::connect();
        
        // Удаление файла
        $stmt = $pdo->prepare("SELECT file_path FROM sounds WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        
        if ($row && file_exists($row['file_path'])) {
            unlink($row['file_path']);
        }
        
        // Удаление записи
        $sql = "DELETE FROM sounds WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
    }
    
    public static function updateSoundCategory($id, $category) {
        $pdo = Database::connect();
        
        $sql = "UPDATE sounds SET category = :category WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':category' => $category, ':id' => $id]);
    }
    
    public static function getCategories() {
        $pdo = Database::connect();
        
        $sql = "SELECT * FROM categories ORDER BY name";
        $stmt = $pdo->query($sql);
        
        return $stmt->fetchAll();
    }
    
    public static function addCategory($name) {
        $pdo = Database::connect();
        
        $sql = "INSERT INTO categories (name) VALUES (:name)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':name' => $name]);
    }
    
    public static function deleteCategory($id) {
        $pdo = Database::connect();
        
        $sql = "DELETE FROM categories WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
    }
        // Метод для обновления пути к файлу и одобрения звука
    public static function updateSoundFilePathAndApprove($id, $new_file_path) {
        $pdo = Database::connect();
        $sql = "UPDATE sounds SET file_path = :file_path, approved = 1 WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':file_path' => $new_file_path,
            ':id' => $id
        ]);
    }
}

class UserManager {
    public static function register($user) {
        $pdo = Database::connect();
        
        // Проверка уникальности имени пользователя и email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
        $stmt->execute([':username' => $user->getUsername(), ':email' => $user->getEmail()]);
        
        if ($stmt->fetch()) {
            return 'Пользователь с таким именем или email уже существует';
        }
        
        $sql = "INSERT INTO users (username, email, password_hash, role) 
                VALUES (:username, :email, :password_hash, :role)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':username' => $user->getUsername(),
            ':email' => $user->getEmail(),
            ':password_hash' => $user->getPasswordHash(),
            ':role' => $user->getRole()
        ]);
        
        return true;
    }
    
    public static function authenticate($username, $password) {
        $pdo = Database::connect();
        
        $sql = "SELECT * FROM users WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':username' => $username]);
        
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] === 'banned') {
                return 'banned';
            }
            
            return new User(
                $user['id'],
                $user['username'],
                $user['email'],
                $user['password_hash'],
                $user['role'],
                $user['status'],
                $user['created_at']
            );
        }
        
        return null;
    }
    
    public static function getUsers() {
        $pdo = Database::connect();
        
        $sql = "SELECT * FROM users ORDER BY created_at DESC";
        $stmt = $pdo->query($sql);
        
        $users = [];
        while ($row = $stmt->fetch()) {
            $users[] = new User(
                $row['id'],
                $row['username'],
                $row['email'],
                $row['password_hash'],
                $row['role'],
                $row['status'],
                $row['created_at']
            );
        }
        
        return $users;
    }
    
    public static function updateUserStatus($id, $status) {
        $pdo = Database::connect();
        
        $sql = "UPDATE users SET status = :status WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':status' => $status, ':id' => $id]);
    }
    
    public static function deleteUser($id) {
        $pdo = Database::connect();
        
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
    }
}

class ComplaintManager {
    public static function addComplaint($sound_id, $user_id, $reason) {
        $pdo = Database::connect();
        
        $sql = "INSERT INTO complaints (sound_id, user_id, reason) VALUES (:sound_id, :user_id, :reason)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':sound_id' => $sound_id,
            ':user_id' => $user_id,
            ':reason' => $reason
        ]);
    }
    
    public static function getComplaints() {
        $pdo = Database::connect();
        
        $sql = "SELECT c.*, s.name as sound_name, u.username as user_username 
                FROM complaints c
                JOIN sounds s ON c.sound_id = s.id
                JOIN users u ON c.user_id = u.id
                ORDER BY c.created_at DESC";
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    }
}
?>