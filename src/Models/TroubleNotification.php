<?php
/**
 * TroubleNotification Model
 * Internal ticketing notifications for users.
 */

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class TroubleNotification extends Model
{
    protected static string $table = 'trouble_notifications';
    protected static array $fillable = [
        'user_id', 'username_ref', 'concern_id', 'ticket_number', 'department', 'type', 'message', 'read_at',
    ];
    protected static bool $timestamps = false;

    /**
     * Create an internal notification to a user (by user_id or username_ref).
     */
    public static function notify(int | string $userId, ?string $usernameRef, ?int $concernId, ?string $ticketNumber, string $department, string $type, string $message): int
    {
        $db = Database::getInstance();

        // Ensure username_ref lookup if only user_id provided
        if (!$usernameRef) {
            $u = $db->fetch("SELECT username FROM users WHERE id = ?", [$userId]);
            $usernameRef = $u['username'] ?? null;
        }

        return $db->insert('trouble_notifications', [
            'user_id' => $userId,
            'username_ref' => $usernameRef,
            'concern_id' => $concernId,
            'ticket_number' => $ticketNumber,
            'department' => $department,
            'type' => $type,
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s'),
            'read_at' => null,
        ]);
    }

    /**
     * Get unread notifications for a user.
     */
    public static function unreadForUser(int $userId): array
    {
        return static::allForUser($userId, true);
    }

    /**
     * Get all notifications for a user (optionally only unread).
     */
    public static function allForUser(int $userId, bool $unreadOnly = false): array
    {
        $db = Database::getInstance();
        $where = "user_id = ?";
        $params = [$userId];
        if ($unreadOnly) {
            $where .= " AND read_at IS NULL";
        }
        return $db->fetchAll(
            "SELECT * FROM trouble_notifications WHERE {$where} ORDER BY created_at DESC LIMIT 50",
            $params
        );
    }

    /**
     * Count unread notifications for a user.
     */
    public static function countUnread(int $userId): int
    {
        return static::count("user_id = ? AND read_at IS NULL", [$userId]);
    }

    /**
     * Mark a notification as read.
     */
    public static function markRead(int $notificationId): void
    {
        static::update($notificationId, ['read_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Mark all notifications for a user as read.
     */
    public static function markAllRead(int $userId): void
    {
        $db = Database::getInstance();
        $db->update('trouble_notifications', ['read_at' => date('Y-m-d H:i:s')], 'user_id = ? AND read_at IS NULL', [$userId]);
    }
}

