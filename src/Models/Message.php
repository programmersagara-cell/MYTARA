<?php
/**
 * Message Model - handles user-to-user private messages
 */

namespace App\Models;

use App\Core\Model;

class Message extends Model
{
    protected static string $table = 'messages';
    protected static bool $timestamps = false;
    protected static array $fillable = [
        'sender_id', 'receiver_id', 'message', 'is_read'
    ];

    /**
     * Send a message from one user to another
     */
    public static function send(int $senderId, int $receiverId, string $message): int
    {
        if ($senderId === $receiverId) {
            throw new \InvalidArgumentException('Cannot send a message to yourself.');
        }

        return self::create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'message' => $message,
            'is_read' => 0,
        ]);
    }

    /**
     * Get the full conversation between two users (oldest first)
     */
    public static function conversation(int $userA, int $userB, int $sinceId = 0): array
    {
        $db = self::getDb();

        $sql = "SELECT m.*, u.full_name as sender_name, u.avatar as sender_avatar
                FROM messages m
                JOIN users u ON u.id = m.sender_id
                WHERE ((m.sender_id = ? AND m.receiver_id = ?)
                   OR (m.sender_id = ? AND m.receiver_id = ?))";

        $params = [$userA, $userB, $userB, $userA];

        if ($sinceId > 0) {
            $sql .= " AND m.id > ?";
            $params[] = $sinceId;
        }

        $sql .= " ORDER BY m.id ASC";

        return $db->fetchAll($sql, $params);
    }

/**
     * Get the list of conversations for a user (each other user with last message & unread count)
     * Shows ALL admin users (for operators) or ALL users (for admins), with conversation data if available
     *
     * @param string|null $viewerRole Role of the requesting user. Operators may only see admin accounts.
     */
    public static function conversations(int $userId, ?string $viewerRole = null): array
    {
        $db = self::getDb();

        $roleFilter = '';
        $params = [];
        if ($viewerRole === 'user') {
            $roleFilter = " AND u.role = 'admin'";
        }

        $baseParams = [$userId, $userId, $userId, $userId, $userId, $userId];

        return $db->fetchAll("
            SELECT 
                u.id AS user_id,
                u.full_name,
                u.username,
                u.avatar,
                u.role,
                u.is_active,
                u.last_seen,
                (
                    SELECT m.message 
                    FROM messages m 
                    WHERE (m.sender_id = u.id AND m.receiver_id = ?) 
                       OR (m.sender_id = ? AND m.receiver_id = u.id)
                    ORDER BY m.id DESC 
                    LIMIT 1
                ) AS last_message,
                (
                    SELECT m.created_at 
                    FROM messages m 
                    WHERE (m.sender_id = u.id AND m.receiver_id = ?) 
                       OR (m.sender_id = ? AND m.receiver_id = u.id)
                    ORDER BY m.id DESC 
                    LIMIT 1
                ) AS last_message_time,
                (
                    SELECT COUNT(*) 
                    FROM messages m 
                    WHERE m.sender_id = u.id 
                      AND m.receiver_id = ? 
                      AND m.is_read = 0
                ) AS unread_count
            FROM users u
            WHERE u.id != ?{$roleFilter}
            ORDER BY 
                u.is_active DESC,
                last_message_time DESC,
                u.full_name ASC
        ", array_merge($baseParams, $params));
    }

    /**
     * Get all active users for starting a new conversation
     * Operators may only see admin accounts.
     *
     * @param string|null $viewerRole Role of the requesting user.
     */
    public static function getChatUsers(int $excludeUserId, string $query = '', ?string $viewerRole = null): array
    {
        $db = self::getDb();
        $sql = "SELECT id, full_name, username, avatar, role
                FROM users
                WHERE id != ? AND is_active = 1";
        $params = [$excludeUserId];

if ($viewerRole === 'user') {
            $sql .= " AND role = 'admin'";
        }

        if ($query !== '') {
            $sql .= " AND (full_name LIKE ? OR username LIKE ?)";
            $search = "%{$query}%";
            $params[] = $search;
            $params[] = $search;
        }

        $sql .= " ORDER BY full_name ASC LIMIT 50";

        return $db->fetchAll($sql, $params);
    }

    /**
     * Count total unread messages for a user
     */
    public static function unreadCount(int $userId): int
    {
        $db = self::getDb();
        $result = $db->fetch(
            "SELECT COUNT(*) AS total FROM messages WHERE receiver_id = ? AND is_read = 0",
            [$userId]
        );
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Mark all messages from a sender to a receiver as read
     */
    public static function markAsRead(int $senderId, int $receiverId): int
    {
        $db = self::getDb();
        return $db->update(
            'messages',
            ['is_read' => 1],
            'sender_id = ? AND receiver_id = ? AND is_read = 0',
            [$senderId, $receiverId]
        );
    }

    private static function getDb(): \App\Core\Database
    {
        return \App\Core\Database::getInstance();
    }
}

