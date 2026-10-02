<?php
/**
 * User Model
 */

namespace App\Models;

use App\Core\Model;
use App\Helpers\Security;

class User extends Model
{
    protected static string $table = 'users';
protected static array $fillable = [
        'username', 'email', 'password', 'full_name', 'role', 'department', 'avatar', 'is_active', 'last_login'
    ];

    /**
     * Find user by username or email for login
     */
    public static function findByLogin(string $login): ?array
    {
        $db = self::getDb();
        return $db->fetch(
            "SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1",
            [$login, $login]
        );
    }

    /**
     * Attempt to authenticate a user
     */
    public static function authenticate(string $login, string $password): ?array
    {
        $user = self::findByLogin($login);

        if (!$user || !$user['is_active']) {
            return null;
        }

        if (!Security::verifyPassword($password, $user['password'])) {
            return null;
        }

        // Update last login
        self::update($user['id'], ['last_login' => date('Y-m-d H:i:s')]);

        // Remove password from returned data
        unset($user['password']);
        
        return $user;
    }

    /**
     * Get all users with asset count
     */
    public static function getAllWithCounts(): array
    {
        $db = self::getDb();
        return $db->fetchAll("
            SELECT u.*, 
                   COUNT(a.id) as asset_count,
                   SUM(CASE WHEN a.status = 'active' THEN 1 ELSE 0 END) as active_assets
            FROM users u
            LEFT JOIN assets a ON u.id = a.assigned_to
            GROUP BY u.id
            ORDER BY u.full_name ASC
        ");
    }

    /**
     * Search users
     */
    public static function search(string $query, array $columns = [], int $page = 1, int $perPage = 20): array
    {
        $db = self::getDb();
        $searchTerm = "%{$query}%";
        
        return $db->fetchAll(
            "SELECT * FROM users 
             WHERE username LIKE ? 
             OR email LIKE ? 
             OR full_name LIKE ?
             ORDER BY full_name ASC
             LIMIT ? OFFSET ?",
            [$searchTerm, $searchTerm, $searchTerm, $perPage, ($page - 1) * $perPage]
        );
    }

    /**
     * Get user statistics
     */
    public static function getStats(): array
    {
        $db = self::getDb();
        
        $total = $db->fetch("SELECT COUNT(*) as count FROM users")['count'];
        $active = $db->fetch("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'];
        $admins = $db->fetch("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")['count'];
        
        return [
            'total' => (int) $total,
            'active' => (int) $active,
            'admins' => (int) $admins,
        ];
    }

    private static function getDb(): \App\Core\Database
    {
        return \App\Core\Database::getInstance();
    }
}

