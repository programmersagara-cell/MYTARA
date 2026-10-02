<?php
/**
 * TicketSequence Model
 * Manages daily ticket number sequencing: <DEPT>-YYYYMMDD-###.
 */

namespace App\Models;

use App\Core\Database;

class TicketSequence
{
    /**
     * Generate the next ticket number for a department.
     * Uses a transaction + row lock to guarantee uniqueness.
     */
    public static function nextTicketNumber(string $department): string
    {
        $db = Database::getInstance();
        $deptCode = self::departmentCode($department);
        $dateKey = date('Ymd');

        $db->getConnection()->beginTransaction();

        try {
            $row = $db->fetch(
                "SELECT next_seq FROM ticket_sequences WHERE date_key = ? AND department = ? FOR UPDATE",
                [$dateKey, $deptCode]
            );

            if ($row) {
                $nextSeq = (int) $row['next_seq'];
                $db->update('ticket_sequences', ['next_seq' => $nextSeq + 1], 'date_key = ? AND department = ?', [$dateKey, $deptCode]);
            } else {
                $nextSeq = 1;
                $existing = $db->fetch(
                    "SELECT COUNT(*) as c FROM ticket_sequences WHERE date_key = ?",
                    [$dateKey]
                );
                if (!$existing || (int) $existing['c'] === 0) {
                    // Create a fresh row for this date/dept
                    $db->insert('ticket_sequences', [
                        'date_key' => $dateKey,
                        'department' => $deptCode,
                        'next_seq' => 2,
                    ]);
                } else {
                    $db->insert('ticket_sequences', [
                        'date_key' => $dateKey,
                        'department' => $deptCode,
                        'next_seq' => 2,
                    ]);
                }
            }

            $db->getConnection()->commit();

            return sprintf('%s-%s-%03d', $deptCode, $dateKey, $nextSeq);
        } catch (\Throwable $e) {
            if ($db->getConnection()->inTransaction()) {
                $db->getConnection()->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Convert a department name to a short code (up to 10 chars, alphanumeric).
     */
    private static function departmentCode(string $department): string
    {
        $code = strtoupper(trim($department));
        // If already looks like a short code (<= 8 alphanumeric), keep as-is
        $clean = preg_replace('/[^A-Z0-9]/', '', $code);
        if ($clean === '') {
            return 'TICKET';
        }
        return substr($clean, 0, 10);
    }
}

