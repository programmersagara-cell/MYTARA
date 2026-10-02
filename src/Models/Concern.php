<?php
/**
 * Concern (IT Ticket) Model
 * Represents an IT concern/ticket in the unified system.
 */

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Concern extends Model
{
    protected static string $table = 'concerns';
    protected static string $primaryKey = 'id';
protected static array $fillable = [
        'user_id', 'username_ref', 'ticket_number', 'sender_name', 'department', 'department_id',
        'description', 'image_path', 'status', 'priority', 'submitted_at',
        'accepted_at', 'accepted_by', 'accepted_by_id', 'repaired_by_id',
        'ip_address',
        'repaired_date', 'repaired_by', 'canceled_reason', 'canceled_date', 'canceled_by', 'remarks',
        'backed_up_at', 'backed_up_status', 'backed_up_remarks_hash',
        'backed_up_canceled_reason_hash', 'backed_up_repaired_by_hash',
    ];
    protected static bool $timestamps = true;

    /**
     * Get a concern by ticket number
     */
    public static function findByTicketNumber(string $ticketNumber): ?array
    {
        return static::findBy('ticket_number', $ticketNumber);
    }

    /**
     * Get active concerns for a user (operator/ticketing user)
     */
    public static function activeForUser(int $userId, ?string $usernameRef = null): array
    {
        $db = Database::getInstance();
        if ($usernameRef !== null && $usernameRef !== '') {
            return $db->fetchAll(
                "SELECT * FROM concerns WHERE (user_id = ? OR username_ref = ?) AND status IN ('active', 'accepted') ORDER BY submitted_at DESC",
                [$userId, $usernameRef]
            );
        }
        return $db->fetchAll(
            "SELECT * FROM concerns WHERE user_id = ? AND status IN ('active', 'accepted') ORDER BY submitted_at DESC",
            [$userId]
        );
    }

    /**
     * Get all concerns (any status) submitted by a user, paginated,
     * with optional status filter — used for the user's own ticket history.
     */
    public static function historyForUser(int $userId, string $status = '', int $page = 1, int $perPage = 10, ?string $usernameRef = null): array
    {
        // Match by user_id, or by username_ref for legacy tickets where user_id may be missing
        if ($usernameRef !== null && $usernameRef !== '') {
            $where = '(user_id = ? OR username_ref = ?)';
            $params = [$userId, $usernameRef];
        } else {
            $where = 'user_id = ?';
            $params = [$userId];
        }

        if ($status !== '' && in_array($status, ['active', 'accepted', 'repaired', 'canceled'], true)) {
            $where .= ' AND status = ?';
            $params[] = $status;
        }

        return static::paginate($page, $perPage, $where, $params, 'submitted_at', 'DESC');
    }

    /**
     * Get all active concerns across departments (admin)
     */
    public static function allActive(int $page = 1, int $perPage = 20, ?string $department = null): array
    {
        $db = Database::getInstance();
        $where = "status IN ('active', 'accepted')";
        $params = [];

        if ($department && $department !== '') {
            $where .= " AND department = ?";
            $params[] = $department;
        }

        return static::paginate($page, $perPage, $where, $params, 'submitted_at', 'DESC');
    }

    /**
     * Get departments that have active concerns
     */
    public static function activeDepartments(): array
    {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT DISTINCT department FROM concerns WHERE status = 'active' AND department != '' ORDER BY department"
        );
    }

    /**
     * Get all departments referenced by concerns (for filters)
     */
    public static function allDepartments(): array
    {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT DISTINCT department FROM concerns WHERE department != '' ORDER BY department"
        );
    }

    /**
     * Search concerns across ticket_number, sender_name, description, remarks
     */
    public static function searchConcerns(
        string $query = '',
        string $department = '',
        string $status = '',
        string $period = '',
        string $orderBy = 'submitted_at',
        string $direction = 'DESC',
        int $page = 1,
        int $perPage = 10
    ): array {
        $db = Database::getInstance();
        $where = '1=1';
        $params = [];

        if ($query !== '') {
            $where .= " AND (ticket_number LIKE ? OR sender_name LIKE ? OR description LIKE ? OR remarks LIKE ?)";
            $q = "%{$query}%";
            $params = array_merge($params, [$q, $q, $q, $q]);
        }
        if ($department !== '') {
            $where .= " AND department = ?";
            $params[] = $department;
        }
        if ($status !== '') {
            $where .= " AND status = ?";
            $params[] = $status;
        }
        if ($period !== '') {
            $where .= " AND submitted_at >= ?";
            switch ($period) {
                case 'daily':
                    $params[] = date('Y-m-d 00:00:00');
                    break;
                case 'weekly':
                    $params[] = date('Y-m-d 00:00:00', strtotime('-7 days'));
                    break;
                case 'monthly':
                    $params[] = date('Y-m-01 00:00:00');
                    break;
                default:
                    $params[] = date('Y-m-d 00:00:00', strtotime('-30 days'));
            }
        }

        // Whitelist for safe ordering
        $allowedColumns = ['submitted_at', 'sender_name', 'department', 'status', 'ticket_number'];
        $allowedDirections = ['ASC', 'DESC'];
        if (!in_array($orderBy, $allowedColumns, true)) {
            $orderBy = 'submitted_at';
        }
        if (!in_array($direction, $allowedDirections, true)) {
            $direction = 'DESC';
        }

        return static::paginate($page, $perPage, $where, $params, $orderBy, $direction);
    }

/**
     * Count concerns by status
     */
    public static function countByStatus(string $status): int
    {
        return static::count("status = ?", [$status]);
    }

    /**
     * Get active concern counts grouped by IP address.
     * Returns an associative array: ip_address => active_count
     */
    public static function activeCountByIp(): array
    {
        $db = Database::getInstance();
        $rows = $db->fetchAll(
            "SELECT ip_address, COUNT(*) as cnt
             FROM concerns
             WHERE status IN ('active', 'accepted') AND ip_address IS NOT NULL AND ip_address != ''
             GROUP BY ip_address"
        );

        $result = [];
        foreach ($rows as $row) {
            $result[$row['ip_address']] = (int) $row['cnt'];
        }
        return $result;
    }

    /**
     * Repaired concerns today count
     */
    public static function countRepairedToday(): int
    {
        $db = Database::getInstance();
        $row = $db->fetch(
            "SELECT COUNT(*) as c FROM concerns WHERE status = 'repaired' AND repaired_date >= ?",
            [date('Y-m-d 00:00:00')]
        );
        return (int) ($row['c'] ?? 0);
    }

    /**
     * Analytics: concerns grouped by month/year
     */
    public static function analyticsByMonth(int $year): array
    {
        $db = Database::getInstance();
        // Operational analytics exclude cancelled tickets
        return $db->fetchAll(
            "SELECT MONTH(submitted_at) as month, COUNT(*) as count
             FROM concerns
             WHERE YEAR(submitted_at) = ? AND status != 'canceled'
             GROUP BY MONTH(submitted_at)
             ORDER BY month",
            [$year]
        );
    }

    /**
     * Analytics: grouped by department + status (for export)
     */
    public static function analyticsByDepartmentStatus(string $scope, string $status, ?int $year, ?int $month, ?int $week, ?string $date): array
    {
        $db = Database::getInstance();
        $where = '1=1';
        $params = [];

        if ($status !== '' && $status !== 'all') {
            $where .= " AND status = ?";
            $params[] = $status;
        } else {
            // Operational export excludes cancelled tickets unless explicitly
            // requested via the status filter.
            $where .= " AND status != 'canceled'";
        }
        if ($year) {
            $where .= " AND YEAR(submitted_at) = ?";
            $params[] = $year;
        }
        if ($scope === 'monthly' && $month) {
            $where .= " AND MONTH(submitted_at) = ?";
            $params[] = $month;
        }
        if ($scope === 'weekly' && $week) {
            $where .= " AND WEEK(submitted_at) = ?";
            $params[] = $week;
        }
        if ($scope === 'daily' && $date) {
            $where .= " AND DATE(submitted_at) = ?";
            $params[] = $date;
        }

        return $db->fetchAll(
            "SELECT * FROM concerns WHERE {$where} ORDER BY department, submitted_at",
            $params
        );
    }
}

