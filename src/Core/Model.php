<?php
/**
 * Base Model - Active Record Pattern
 */

namespace App\Core;

use PDO;

abstract class Model
{
    protected static string $table;
    protected static string $primaryKey = 'id';
    protected static array $fillable = [];
    protected static array $casts = [];
    protected static bool $timestamps = true;

    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get the table name
     */
    public static function getTable(): string
    {
        return static::$table ?? strtolower((new \ReflectionClass(static::class))->getShortName()) . 's';
    }

    /**
     * Find by primary key
     */
    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        return $db->fetch(
            "SELECT * FROM " . static::getTable() . " WHERE " . static::$primaryKey . " = ?",
            [$id]
        );
    }

    /**
     * Find by a specific column
     */
    public static function findBy(string $column, mixed $value): ?array
    {
        $db = Database::getInstance();
        return $db->fetch(
            "SELECT * FROM " . static::getTable() . " WHERE {$column} = ?",
            [$value]
        );
    }

    /**
     * Get all records
     */
    public static function all(string $orderBy = null, string $direction = 'ASC'): array
    {
        $db = Database::getInstance();
        $sql = "SELECT * FROM " . static::getTable();
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy} {$direction}";
        }

        return $db->fetchAll($sql);
    }

    /**
     * Get paginated results
     */
    public static function paginate(int $page = 1, int $perPage = 20, string $where = '', array $params = [], string $orderBy = 'created_at', string $direction = 'DESC'): array
    {
        $db = Database::getInstance();
        $table = static::getTable();

        // Count total
        $countSql = "SELECT COUNT(*) as total FROM {$table}";
        if ($where) {
            $countSql .= " WHERE {$where}";
        }
        $total = $db->fetch($countSql, $params)['total'];

        // Calculate offset
        $offset = ($page - 1) * $perPage;
        $totalPages = ceil($total / $perPage);

        // Fetch page
        $dataSql = "SELECT * FROM {$table}";
        if ($where) {
            $dataSql .= " WHERE {$where}";
        }
        $dataSql .= " ORDER BY {$orderBy} {$direction} LIMIT ? OFFSET ?";
        
        $data = $db->fetchAll($dataSql, array_merge($params, [$perPage, $offset]));

        return [
            'data'        => $data,
            'total'       => (int) $total,
            'per_page'    => $perPage,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'has_next'    => $page < $totalPages,
            'has_prev'    => $page > 1,
        ];
    }

    /**
     * Create a new record
     */
    public static function create(array $data): int
    {
        $db = Database::getInstance();
        
        // Filter fillable columns
        if (!empty(static::$fillable)) {
            $data = array_intersect_key($data, array_flip(static::$fillable));
        }

        // Auto timestamps
        if (static::$timestamps) {
            if (!isset($data['created_at'])) {
                $data['created_at'] = date('Y-m-d H:i:s');
            }
            if (!isset($data['updated_at'])) {
                $data['updated_at'] = date('Y-m-d H:i:s');
            }
        }

        return $db->insert(static::getTable(), $data);
    }

    /**
     * Update a record
     */
    public static function update(mixed $id, array $data): int
    {
        $db = Database::getInstance();
        $table = static::getTable();

        // Filter fillable columns
        if (!empty(static::$fillable)) {
            $data = array_intersect_key($data, array_flip(static::$fillable));
        }

        // Auto timestamps
        if (static::$timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        return $db->update($table, $data, static::$primaryKey . ' = ?', [$id]);
    }

    /**
     * Delete a record
     */
    public static function delete(mixed $id): int
    {
        $db = Database::getInstance();
        return $db->delete(static::getTable(), static::$primaryKey . ' = ?', [$id]);
    }

    /**
     * Search records
     */
    public static function search(string $query, array $columns, int $page = 1, int $perPage = 20): array
    {
        $table = static::getTable();
        $conditions = [];
        $params = [];

        foreach ($columns as $column) {
            $conditions[] = "{$column} LIKE ?";
            $params[] = "%{$query}%";
        }

        $where = implode(' OR ', $conditions);

        return static::paginate($page, $perPage, $where, $params);
    }

    /**
     * Count records
     */
    public static function count(string $where = '', array $params = []): int
    {
        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) as total FROM " . static::getTable();
        
        if ($where) {
            $sql .= " WHERE {$where}";
        }

        return (int) $db->fetch($sql, $params)['total'];
    }

    /**
     * Get recent records
     */
    public static function recent(int $limit = 10): array
    {
        $db = Database::getInstance();
        $table = static::getTable();
        
        return $db->fetchAll(
            "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT ?",
            [$limit]
        );
    }

    /**
     * Cast attributes to proper types
     */
    protected static function cast(array $data): array
    {
        foreach (static::$casts as $field => $type) {
            if (isset($data[$field])) {
                $data[$field] = match ($type) {
                    'int', 'integer' => (int) $data[$field],
                    'float', 'double' => (float) $data[$field],
                    'bool', 'boolean' => (bool) $data[$field],
                    'array' => json_decode($data[$field], true) ?? [],
                    'object' => json_decode($data[$field]),
                    'date' => $data[$field],
                    default => $data[$field],
                };
            }
        }
        return $data;
    }
}

