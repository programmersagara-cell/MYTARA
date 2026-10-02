<?php $layout = 'layouts/main'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">System Settings</h1>
        <p class="page-subtitle">Configure application settings</p>
    </div>
    <div class="page-actions">
    </div>
</div>

<div class="row">
    <div class="col-8">
        <div class="card">
            <div class="card-header">
                <h3>General Settings</h3>
            </div>
            <div class="card-body">
<form method="POST" action="<?= url('/settings/update') ?>" class="form">
                    <?= \App\Helpers\Security::csrfField() ?>

                    <div class="form-section">
                        <h4>Application</h4>
                        <div class="form-row">
                            <div class="form-group col-6">
                                <label class="form-label">Application Name</label>
                                <input type="text" name="app_name" class="form-input" 
                                       value="<?= htmlspecialchars($settings['app.name'] ?? APP_NAME) ?>">
                            </div>
                            <div class="form-group col-6">
                                <label class="form-label">Timezone</label>
                                <select name="app_timezone" class="form-input">
                                    <?php
                                    $timezones = ['Asia/Manila', 'Asia/Singapore', 'Asia/Tokyo', 'Asia/Hong_Kong', 'America/New_York', 'America/Chicago', 'America/Los_Angeles', 'Europe/London', 'Europe/Berlin', 'Australia/Sydney'];
                                    $current = $settings['app.timezone'] ?? 'Asia/Manila';
                                    foreach ($timezones as $tz):
                                    ?>
                                    <option value="<?= $tz ?>" <?= $current === $tz ? 'selected' : '' ?>><?= $tz ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Company Name</label>
                            <input type="text" name="company_name" class="form-input" 
                                   value="<?= htmlspecialchars($settings['company.name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Asset Tag Org Letter</label>
                            <input type="text" name="asset_tag_org" class="form-input" style="max-width:120px;"
                                   value="<?= htmlspecialchars($settings['asset.tag.org'] ?? 'O') ?>"
                                   maxlength="4" pattern="[A-Za-z]{1,4}" title="One to four letters, e.g. O">
                            <small class="text-muted">Org/site letter used in the asset tag format ORG-YY-DEPT-NNNN (e.g. O-26-IT-4821).</small>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4>Notifications & Backup</h4>
                        <div class="form-row">
                            <div class="form-group col-4">
                                <label class="form-label">Warranty Expiry Notice (days)</label>
                                <input type="number" name="notify_expiry_days" class="form-input" 
                                       value="<?= htmlspecialchars($settings['notify.expiry_days'] ?? '30') ?>" min="1" max="365">
                            </div>
                            <div class="form-group col-4">
                                <label class="form-label">Auto Backup</label>
                                <select name="backup_enabled" class="form-input">
                                    <option value="true" <?= ($settings['backup.enabled'] ?? 'true') === 'true' ? 'selected' : '' ?>>Enabled</option>
                                    <option value="false" <?= ($settings['backup.enabled'] ?? 'true') === 'false' ? 'selected' : '' ?>>Disabled</option>
                                </select>
                            </div>
                            <div class="form-group col-4">
                                <label class="form-label">Backup Interval</label>
                                <select name="backup_interval" class="form-input">
                                    <option value="daily" <?= ($settings['backup.interval'] ?? 'daily') === 'daily' ? 'selected' : '' ?>>Daily</option>
                                    <option value="weekly" <?= ($settings['backup.interval'] ?? 'daily') === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                                    <option value="monthly" <?= ($settings['backup.interval'] ?? 'daily') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-4">
        <div class="card">
            <div class="card-header">
                <h3>System Info</h3>
            </div>
            <div class="card-body">
                <div class="info-list">
                    <div class="info-item">
                        <label>Application</label>
                        <span><?= APP_NAME ?> v<?= APP_VERSION ?></span>
                    </div>
                    <div class="info-item">
                        <label>PHP Version</label>
                        <span><?= phpversion() ?></span>
                    </div>
                    <div class="info-item">
                        <label>Database</label>
                        <span><?php
                            $db = \App\Core\Database::getInstance();
                            $stmt = $db->query("SELECT VERSION() as version");
                            echo $stmt->fetch()['version'];
                        ?></span>
                    </div>
                    <div class="info-item">
                        <label>Server</label>
                        <span><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3>Database Backup</h3>
            </div>
            <div class="card-body">
                <p class="text-muted small">Create a manual backup of the database.</p>
                <a href="<?= url('/backup.php') ?>" class="btn btn-secondary btn-block" onclick="return confirm('Create a database backup?')">
                    <i class="fas fa-database"></i> Create Backup
                </a>
            </div>
        </div>
    </div>
</div>
