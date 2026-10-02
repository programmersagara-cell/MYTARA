<?php
/**
 * Formatting Helpers
 * Date, number, and data formatting utilities
 */

namespace App\Helpers;

class Format
{
    /**
     * Format a date for display
     */
    public static function date(?string $date, string $format = null): string
    {
        if (!$date) return '—';
        $format = $format ?: DISPLAY_DATE_FORMAT;
        return date($format, strtotime($date));
    }

    /**
     * Format a datetime for display
     */
    public static function datetime(?string $datetime, string $format = null): string
    {
        if (!$datetime) return '—';
        $format = $format ?: DISPLAY_DATETIME_FORMAT;
        return date($format, strtotime($datetime));
    }

    /**
     * Format a timestamp as a readable date/time (e.g., "Today, 9:30 AM", "Yesterday, 5:00 PM", "Aug 24, 2026, 9:30 AM")
     */
    public static function relativeTime(?string $datetime): string
    {
        if (!$datetime) return '—';

        $timestamp = strtotime($datetime);
        $now = time();
        $timeStr = date('g:i A', $timestamp);

        // Today
        if (date('Y-m-d', $timestamp) === date('Y-m-d', $now)) {
            return 'Today, ' . $timeStr;
        }

        // Yesterday
        if (date('Y-m-d', $timestamp) === date('Y-m-d', $now - 86400)) {
            return 'Yesterday, ' . $timeStr;
        }

        // Older - show full date and time
        return date('M j, Y', $timestamp) . ', ' . $timeStr;
    }

    /**
     * Format bytes to human-readable size
     */
    public static function fileSize(int $bytes, int $decimals = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = 0;

        while ($bytes >= 1024 && $factor < count($units) - 1) {
            $bytes /= 1024;
            $factor++;
        }

        return round($bytes, $decimals) . ' ' . $units[$factor];
    }

    /**
     * Format a number with commas
     */
    public static function number(mixed $number, int $decimals = 0): string
    {
        return number_format((float) $number, $decimals);
    }

    /**
     * Format percentage
     */
    public static function percentage(mixed $value, int $decimals = 1): string
    {
        return round((float) $value, $decimals) . '%';
    }

    /**
     * Truncate text to a certain length
     */
    public static function truncate(?string $text, int $length = 100, string $suffix = '...'): string
    {
        if (!$text) return '—';
        if (strlen($text) <= $length) return $text;
        
        return substr($text, 0, $length - strlen($suffix)) . $suffix;
    }

    /**
     * Convert a string to slug
     */
    public static function slug(string $text): string
    {
        $text = preg_replace('/[^\w\s-]/', '', $text);
        $text = preg_replace('/[\s_]+/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        return strtolower(trim($text, '-'));
    }

    /**
     * Format asset type for display (e.g., 'pc' -> 'PC', 'laptop' -> 'Laptop')
     */
    public static function assetType(string $type): string
    {
        return match ($type) {
            'pc' => 'PC',
            'laptop' => 'Laptop',
            'switch' => 'Network Switch',
'server' => 'Server',
            'printer' => 'Printer',
            'vm' => 'VM',
            'monitor' => 'Monitor',
            default => ucfirst($type),
        };
    }

    /**
     * Format asset status with HTML badge
     */
    public static function statusBadge(string $status): string
    {
        $colors = [
            'active' => 'success',
            'inactive' => 'secondary',
            'maintenance' => 'warning',
            'retired' => 'danger',
            'lost' => 'dark',
            'reserved' => 'info',
        ];

        $color = $colors[$status] ?? 'secondary';
        $label = ucfirst($status);

        return "<span class=\"badge badge-{$color}\">{$label}</span>";
    }

    /**
     * Format user role with badge
     */
    public static function roleBadge(string $role): string
    {
        $colors = [
            'admin' => 'danger',
            'user' => 'primary',
            'viewer' => 'secondary',
        ];

        $color = $colors[$role] ?? 'secondary';
        $label = ucfirst($role);

        return "<span class=\"badge badge-{$color}\">{$label}</span>";
    }

    /**
     * Format a phone number (placeholder filter)
     */
    public static function phone(string $number): string
    {
        $number = preg_replace('/[^\d]/', '', $number);
        
        if (strlen($number) === 10) {
            return '(' . substr($number, 0, 3) . ') ' . substr($number, 3, 3) . '-' . substr($number, 6);
        }
        
        return $number;
    }

    /**
     * Mask sensitive data (e.g., admin@example.com -> a***@example.com)
     */
    public static function mask(string $value, string $maskChar = '*'): string
    {
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            [$name, $domain] = explode('@', $value);
            $name = substr($name, 0, 1) . str_repeat($maskChar, max(0, strlen($name) - 1));
            return $name . '@' . $domain;
        }

        $length = strlen($value);
        if ($length <= 4) {
            return str_repeat($maskChar, $length);
        }

        return substr($value, 0, 2) . str_repeat($maskChar, $length - 4) . substr($value, -2);
    }
}

