<?php
class UserModel {
    private $pdo;
    private $lastError = '';

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getLastError() {
        return $this->lastError;
    }

    public function userExists($username, $email) {
        try {
            $stmt = $this->pdo->prepare("SELECT login, email FROM users WHERE login = ? OR email = ?");
            $stmt->execute([$username, $email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("UserModel userExists error: " . $e->getMessage());
            return false;
        }
    }

    public function createUser($username, $email, $password) {
    try {
        if (strlen($username) > 50) {
            $this->lastError = 'Логин слишком длинный (максимум 50 символов)';
            return false;
        }
        
        if (strlen($email) > 100) {
            $this->lastError = 'Email слишком длинный (максимум 100 символов)';
            return false;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        if (!$hashedPassword) {
            $this->lastError = 'Ошибка хеширования пароля';
            return false;
        }

        if (strlen($hashedPassword) > 100) {
            $this->lastError = 'Хеш пароля слишком длинный (максимум 100 символов)';
            return false;
        }

        $stmt = $this->pdo->prepare("INSERT INTO users (login, email, password) VALUES (?, ?, ?)");
        
        $result = $stmt->execute([$username, $email, $hashedPassword]);
        
        if ($result) {
            return true;
        } else {
            $errorInfo = $stmt->errorInfo();
            $this->lastError = $errorInfo[2] ?? 'Неизвестная ошибка базы данных';
        
            error_log("PostgreSQL error: " . print_r($errorInfo, true));
            return false;
        }
        
    } catch (PDOException $e) {
        $this->lastError = $e->getMessage();
        error_log("UserModel createUser PDOException: " . $e->getMessage());
        error_log("SQLSTATE: " . $e->getCode());
        return false;
    } catch (Exception $e) {
        $this->lastError = $e->getMessage();
        error_log("UserModel createUser Exception: " . $e->getMessage());
        return false;
    }
}

    public function findUserForLogin($emailOrLogin) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? OR login = ?");
            $stmt->execute([$emailOrLogin, $emailOrLogin]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("UserModel findUserForLogin error: " . $e->getMessage());
            return false;
        }
    }
}
?>