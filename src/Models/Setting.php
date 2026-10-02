<?php
/**
 * Settings Model
 */

namespace App\Models;

use App\Core\Model;

class Setting extends Model
{
    protected static string $table = 'settings';
    protected static array $fillable = ['setting_key', 'setting_value'];
    protected static bool $timestamps = false;

    /**
     * Get a setting value by key
     */
    public static function get(string $key, mixed $default = null): ?string
    {
        $db = \App\Core\Database::getInstance();
        $result = $db->fetch(
            "SELECT setting_value FROM settings WHERE setting_key = ?",
            [$key]
        );
        return $result['setting_value'] ?? $default;
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, string $value): void
    {
        $db = \App\Core\Database::getInstance();
        $existing = $db->fetch(
            "SELECT id FROM settings WHERE setting_key = ?",
            [$key]
        );

        if ($existing) {
            $db->update('settings', ['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$existing['id']]);
        } else {
            $db->insert('settings', [
                'setting_key' => $key,
                'setting_value' => $value,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Get all settings as key-value pairs
     */
    public static function getAllAsArray(): array
    {
        $db = \App\Core\Database::getInstance();
        $rows = $db->fetchAll("SELECT setting_key, setting_value FROM settings");
        
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
    }

    /**
     * Delete a setting
     */
    public static function deleteKey(string $key): int
    {
        $db = \App\Core\Database::getInstance();
        return $db->delete('settings', 'setting_key = ?', [$key]);
    }
}

