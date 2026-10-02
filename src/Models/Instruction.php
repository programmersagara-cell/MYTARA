<?php
/**
 * Instruction Model
 * Manages department instructions/key notes.
 */

namespace App\Models;

use App\Core\Model;

class Instruction extends Model
{
    protected static string $table = 'instructions';
    protected static array $fillable = ['tittle', 'instruction_text'];
    protected static bool $timestamps = true;

    /**
     * Get all instructions ordered by newest first
     */
    public static function allOrdered(): array
    {
        return static::all('created_at', 'DESC');
    }
}

