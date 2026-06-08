<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class UserModel
{
    public function ensureProfileColumns(): void
    {
        try { Database::connection()->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL"); } catch (\Throwable $e) {}
        try { Database::connection()->exec("ALTER TABLE users ADD COLUMN cover VARCHAR(255) DEFAULT NULL"); } catch (\Throwable $e) {}
        try { Database::connection()->exec("ALTER TABLE users ADD COLUMN bio TEXT DEFAULT NULL"); } catch (\Throwable $e) {}
        $this->ensureEmailVerifyColumns();
        $this->ensureSiteLimitColumn();
    }

    public function ensureSiteLimitColumn(): void
    {
        try { Database::connection()->exec("ALTER TABLE users ADD COLUMN site_limit INT UNSIGNED NOT NULL DEFAULT 0"); } catch (\Throwable $e) {}
        try { Database::connection()->exec("ALTER TABLE users ADD COLUMN claybbs_site_limit INT UNSIGNED NOT NULL DEFAULT 0"); } catch (\Throwable $e) {}
        try { Database::connection()->exec("ALTER TABLE users ADD COLUMN cutot_site_limit INT UNSIGNED NOT NULL DEFAULT 0"); } catch (\Throwable $e) {}
        try { Database::connection()->exec("UPDATE users SET site_limit=0 WHERE site_limit IS NULL"); } catch (\Throwable $e) {}
        try { Database::connection()->exec("UPDATE users SET claybbs_site_limit=site_limit WHERE claybbs_site_limit=0 AND site_limit>0"); } catch (\Throwable $e) {}
        try { Database::connection()->exec("UPDATE users SET cutot_site_limit=0 WHERE cutot_site_limit IS NULL"); } catch (\Throwable $e) {}
    }

    public function ensureEmailVerifyColumns(): void
    {
        try { Database::connection()->exec("ALTER TABLE users ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 1"); } catch (\Throwable $e) {}
        try { Database::connection()->exec("ALTER TABLE users ADD COLUMN email_verify_token VARCHAR(64) DEFAULT NULL"); } catch (\Throwable $e) {}
        try { Database::connection()->exec("ALTER TABLE users ADD COLUMN email_verify_expires_at DATETIME DEFAULT NULL"); } catch (\Throwable $e) {}
    }

    public function ensurePasswordResetColumns(): void
    {
        try { Database::connection()->exec("ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(64) DEFAULT NULL"); } catch (\Throwable $e) {}
        try { Database::connection()->exec("ALTER TABLE users ADD COLUMN password_reset_expires_at DATETIME DEFAULT NULL"); } catch (\Throwable $e) {}
    }

    public function find(int $id): ?array
    {
        $this->ensureDeveloperColumns();
        $stmt = Database::connection()->prepare("SELECT * FROM users WHERE id=:id LIMIT 1");
        $stmt->execute([':id'=>$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateProfile(int $id, array $data): void
    {
        $this->ensureProfileColumns();
        Database::connection()->prepare("UPDATE users SET name=:name, bio=:bio, avatar=:avatar, cover=:cover WHERE id=:id")
            ->execute([':name'=>$data['name'], ':bio'=>$data['bio'] ?? '', ':avatar'=>$data['avatar'] ?? '', ':cover'=>$data['cover'] ?? '', ':id'=>$id]);
    }

    public function ensureDeveloperColumns(): void
    {
        try { Database::connection()->exec("ALTER TABLE users ADD COLUMN developer_level VARCHAR(30) NOT NULL DEFAULT 'none'"); } catch (\Throwable $e) {}
    }

    public function displayName(array $user): string
    {
        $name = trim((string)($user['name'] ?? ''));
        if ($name !== '') return $name;
        $email = (string)($user['email'] ?? '');
        return $email !== '' ? preg_replace('/@.*$/', '', $email) : ('用户' . (string)($user['id'] ?? ''));
    }

    public static function developerLevelLabel(string $level): string
    {
        return match ($level) {
            'public' => '公益开发者',
            'normal' => '普通开发者',
            'professional' => '专业开发者',
            'official' => '官方开发者',
            default => '非开发者',
        };
    }

    public function create(string $email, string $password, string $name = '', string $role = 'user', bool $emailVerified = true): int
    {
        $this->ensureSiteLimitColumn();
        $this->ensureEmailVerifyColumns();
        $token = $emailVerified ? null : bin2hex(random_bytes(24));
        $expires = $emailVerified ? null : date('Y-m-d H:i:s', time() + 86400);
        $stmt = Database::connection()->prepare(
            "INSERT INTO users (email, password, name, role, status, site_limit, claybbs_site_limit, cutot_site_limit, email_verified, email_verify_token, email_verify_expires_at) VALUES (:email, :password, :name, :role, 'active', 0, 0, 0, :email_verified, :token, :expires)"
        );
        $stmt->execute([
            ':email' => $email,
            ':password' => password_hash($password, PASSWORD_DEFAULT),
            ':name' => $name,
            ':role' => $role,
            ':email_verified' => $emailVerified ? 1 : 0,
            ':token' => $token,
            ':expires' => $expires,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function findByVerifyToken(string $token): ?array
    {
        $this->ensureEmailVerifyColumns();
        $stmt = Database::connection()->prepare("SELECT * FROM users WHERE email_verify_token=:token LIMIT 1");
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function markEmailVerified(int $id): void
    {
        $this->ensureEmailVerifyColumns();
        Database::connection()->prepare("UPDATE users SET email_verified=1,email_verify_token=NULL,email_verify_expires_at=NULL WHERE id=:id")->execute([':id' => $id]);
    }

    public function refreshVerifyToken(int $id): array
    {
        $this->ensureEmailVerifyColumns();
        $token = bin2hex(random_bytes(24));
        $expires = date('Y-m-d H:i:s', time() + 86400);
        Database::connection()->prepare("UPDATE users SET email_verify_token=:token,email_verify_expires_at=:expires WHERE id=:id")
            ->execute([':token' => $token, ':expires' => $expires, ':id' => $id]);
        return ['token' => $token, 'expires_at' => $expires];
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare("SELECT * FROM users WHERE email=:email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function setPasswordResetToken(string $email): ?array
    {
        $this->ensurePasswordResetColumns();
        $user = $this->findByEmail($email);
        if (!$user) {
            return null;
        }
        $token = bin2hex(random_bytes(24));
        $expires = date('Y-m-d H:i:s', time() + 1800); // 30 minutes
        Database::connection()->prepare(
            "UPDATE users SET password_reset_token=:token, password_reset_expires_at=:expires WHERE id=:id"
        )->execute([':token' => $token, ':expires' => $expires, ':id' => (int)$user['id']]);
        return ['token' => $token, 'expires_at' => $expires];
    }

    public function findByResetToken(string $token): ?array
    {
        $this->ensurePasswordResetColumns();
        $stmt = Database::connection()->prepare(
            "SELECT * FROM users WHERE password_reset_token=:token AND password_reset_expires_at IS NOT NULL AND password_reset_expires_at>=:now LIMIT 1"
        );
        $stmt->execute([':token' => $token, ':now' => date('Y-m-d H:i:s')]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function resetPassword(int $id, string $newPassword): void
    {
        $this->ensurePasswordResetColumns();
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        Database::connection()->prepare(
            "UPDATE users SET password=:password, password_reset_token=NULL, password_reset_expires_at=NULL WHERE id=:id"
        )->execute([':password' => $hash, ':id' => $id]);
    }
}
