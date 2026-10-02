<?php
/**
 * Department Model
 */

namespace App\Models;

use App\Core\Model;

class Department extends Model
{
    protected static string $table = 'departments';
    protected static array $fillable = ['name', 'code', 'section', 'description'];

    /**
     * Get departments with asset count
     */
    public static function getAllWithCounts(): array
    {
        $db = \App\Core\Database::getInstance();
        return $db->fetchAll("
            SELECT d.*, COUNT(a.id) as asset_count
            FROM departments d
            LEFT JOIN assets a ON d.id = a.department_id
            GROUP BY d.id
            ORDER BY d.name ASC
        ");
    }

    /**
     * Get department options for select dropdowns
     */
    public static function getOptions(): array
    {
        $db = \App\Core\Database::getInstance();
        return $db->fetchAll(
            "SELECT id, name, code FROM departments ORDER BY name ASC"
        );
    }
}

