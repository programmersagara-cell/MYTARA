<?php
/**
 * Settings Controller
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Setting;
use App\Services\AuditService;

class SettingsController extends Controller
{
    private AuditService $auditService;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole('admin');
        $this->auditService = new AuditService();
    }

    /**
     * Show settings page
     */
    public function index(): void
    {
        $settings = Setting::getAllAsArray();
        $this->render('settings/index', [
            'title' => 'System Settings',
            'settings' => $settings,
        ]);
    }

    /**
     * Update settings
     */
    public function update(): void
    {
        // Map form field names to settings table keys.
        // NOTE: str_replace('_', '.', 'notify_expiry_days') would produce
        // 'notify.expiry.days' which is wrong, so use an explicit map.
        $map = [
            'app_name'           => 'app.name',
            'app_timezone'       => 'app.timezone',
            'company_name'       => 'company.name',
            'asset_tag_org'      => 'asset.tag.org',
            'notify_expiry_days' => 'notify.expiry_days',
            'backup_enabled'     => 'backup.enabled',
            'backup_interval'    => 'backup.interval',
        ];

        foreach ($map as $field => $key) {
            $value = $this->request->input($field);
            if ($value === null) {
                continue;
            }

            // Org/site letter is stored normalized (uppercase A-Z, max 4 chars).
            if ($key === 'asset.tag.org') {
                $value = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $value));
                $value = substr($value, 0, 4);
                if ($value === '') {
                    $value = 'O';
                }
            }

            $oldValue = Setting::get($key);
            Setting::set($key, $value);

            // One audit entry per changed key, with old and new value.
            if ((string) $oldValue !== (string) $value) {
                $this->auditService->log('setting_updated', 'setting', null, $key, $key, $oldValue, $value, 'Setting changed');
            }
        }

        $this->redirectWith('/settings', 'Settings updated successfully.', 'success');
    }
}

