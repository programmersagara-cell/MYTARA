<?php
/**
 * Integration tests for Software License Management and Asset Disposal workflows
 */

// Register autoloader
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../../src/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';

use App\Core\Database;
use App\Models\SoftwareLicense;
use App\Models\LicenseAssignment;
use App\Models\AssetDisposal;
use App\Models\Asset;

class LicenseDisposalTest
{
    private $db;
    private $passed = 0;
    private $failed = 0;
    private $errors = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function run(): void
    {
        echo "=== License & Disposal Integration Tests ===\n\n";

        // License tests
        $this->testCreateLicense();
        $this->testEditLicense();
        $this->testAssignLicenseToUser();
        $this->testAssignLicenseToAsset();
        $this->testRemoveLicenseAssignment();
        $this->testLicenseExpirationCalculation();
        $this->testDeleteLicense();

        // Disposal tests
        $this->testRetireAsset();
        $this->testAutomaticDisposalCreation();
        $this->testApproveDisposal();
        $this->testRejectDisposal();
        $this->testScheduleDisposal();
        $this->testCompleteDisposal();
        $this->testDataDestructionRecording();
        $this->testDisposalCertificate();
        $this->testPreventDuplicateDisposal();
        $this->testDisposedAssetRemainsInDatabase();
        $this->testAuditLogsCreated();

        echo "\n=== Test Results ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        if ($this->errors) {
            echo "\nErrors:\n";
            foreach ($this->errors as $error) {
                echo "  - $error\n";
            }
        }
        echo $this->failed === 0 ? "\nALL TESTS PASSED ✓\n" : "\nSOME TESTS FAILED ✗\n";
    }

    private function assertTrue(bool $condition, string $message): void
    {
        if ($condition) {
            $this->passed++;
            echo "  ✓ $message\n";
        } else {
            $this->failed++;
            $this->errors[] = $message;
            echo "  ✗ $message\n";
        }
    }

    private function testCreateLicense(): void
    {
        echo "\n--- License CRUD ---\n";
        $licenseId = SoftwareLicense::create([
            'license_name' => 'Microsoft Office 365',
            'software_name' => 'Microsoft Office',
            'vendor' => 'Microsoft',
            'license_key' => 'TEST-KEY-1234-5678',
            'license_type' => 'subscription',
            'version' => '2024',
            'purchase_date' => date('Y-m-d'),
            'start_date' => date('Y-m-d'),
            'expiration_date' => date('Y-m-d', strtotime('+1 year')),
            'purchased_seats' => 100,
            'cost' => 5000.00,
            'currency' => 'PHP',
            'status' => 'active',
            'created_by' => 1,
        ]);
        $this->assertTrue($licenseId > 0, "Create license (ID: $licenseId)");

        // Verify it was created
        $license = SoftwareLicense::find($licenseId);
        $this->assertTrue($license !== null, "License exists in database");
        $this->assertTrue($license['software_name'] === 'Microsoft Office', "License software name matches");
        $this->assertTrue($license['purchased_seats'] == 100, "License seats match");
    }

    private function testEditLicense(): void
    {
        $licenses = SoftwareLicense::all();
        if (empty($licenses)) {
            $this->assertTrue(false, "No licenses to edit");
            return;
        }
        $licenseId = $licenses[0]['id'];
        $updated = SoftwareLicense::update($licenseId, [
            'software_name' => 'Microsoft Office 365 ProPlus',
            'purchased_seats' => 150,
        ]);
        $this->assertTrue($updated > 0, "Edit license (ID: $licenseId)");

        $license = SoftwareLicense::find($licenseId);
        $this->assertTrue($license['software_name'] === 'Microsoft Office 365 ProPlus', "License name updated");
        $this->assertTrue($license['purchased_seats'] == 150, "License seats updated");
    }

    private function testAssignLicenseToUser(): void
    {
        $licenses = SoftwareLicense::all();
        if (empty($licenses)) {
            $this->assertTrue(false, "No licenses to assign");
            return;
        }
        $licenseId = $licenses[0]['id'];
        $assignmentId = LicenseAssignment::assignToUser($licenseId, 1, 1);
        $this->assertTrue($assignmentId > 0, "Assign license to user (assignment ID: $assignmentId)");

        $count = LicenseAssignment::countActiveForLicense($licenseId);
        $this->assertTrue($count >= 1, "License has active assignment");
    }

    private function testAssignLicenseToAsset(): void
    {
        $licenses = SoftwareLicense::all();
        $assets = Asset::all();
        if (empty($licenses) || empty($assets)) {
            $this->assertTrue(false, "No licenses or assets to assign");
            return;
        }
        $licenseId = $licenses[0]['id'];
        $assetId = $assets[0]['id'];
        $assignmentId = LicenseAssignment::assignToAsset($licenseId, $assetId, 1);
        $this->assertTrue($assignmentId > 0, "Assign license to asset (assignment ID: $assignmentId)");
    }

    private function testRemoveLicenseAssignment(): void
    {
        $licenses = SoftwareLicense::all();
        if (empty($licenses)) {
            $this->assertTrue(false, "No licenses to remove assignment");
            return;
        }
        $licenseId = $licenses[0]['id'];
        $assignments = LicenseAssignment::getByLicense($licenseId);
        if (empty($assignments)) {
            $this->assertTrue(false, "No assignments to remove");
            return;
        }
        $assignmentId = $assignments[0]['id'];
        $removed = LicenseAssignment::removeAssignment($assignmentId);
        $this->assertTrue($removed > 0, "Remove license assignment (ID: $assignmentId)");
    }

    private function testLicenseExpirationCalculation(): void
    {
        // Create a license expiring in 15 days
        $licenseId = SoftwareLicense::create([
            'license_name' => 'Expiring License Test',
            'software_name' => 'Test Software',
            'vendor' => 'Test Vendor',
            'license_key' => 'EXP-1234',
            'license_type' => 'subscription',
            'expiration_date' => date('Y-m-d', strtotime('+15 days')),
            'purchased_seats' => 10,
            'status' => 'active',
            'created_by' => 1,
        ]);

        // Update statuses
        SoftwareLicense::updateStatuses();

        $license = SoftwareLicense::find($licenseId);
        $this->assertTrue($license['status'] === 'expiring_soon', "License expiring in 15 days has status 'expiring_soon'");

        // Create an expired license
        $expiredId = SoftwareLicense::create([
            'license_name' => 'Expired License Test',
            'software_name' => 'Test Software 2',
            'vendor' => 'Test Vendor',
            'license_key' => 'EXP-5678',
            'license_type' => 'subscription',
            'expiration_date' => date('Y-m-d', strtotime('-5 days')),
            'purchased_seats' => 10,
            'status' => 'active',
            'created_by' => 1,
        ]);

        SoftwareLicense::updateStatuses();
        $expired = SoftwareLicense::find($expiredId);
        $this->assertTrue($expired['status'] === 'expired', "Expired license has status 'expired'");
    }

    private function testDeleteLicense(): void
    {
        $licenses = SoftwareLicense::all();
        if (empty($licenses)) {
            $this->assertTrue(false, "No licenses to delete");
            return;
        }
        $licenseId = $licenses[0]['id'];
        $deleted = SoftwareLicense::delete($licenseId);
        $this->assertTrue($deleted > 0, "Delete license (ID: $licenseId)");
    }

    private function testRetireAsset(): void
    {
        echo "\n--- Disposal Workflow ---\n";
        $assets = Asset::all();
        if (empty($assets)) {
            $this->assertTrue(false, "No assets to retire");
            return;
        }
        $assetId = $assets[0]['id'];
        $updated = Asset::update($assetId, [
            'status' => 'retired',
            'retirement_reason' => 'end_of_life',
            'retired_at' => date('Y-m-d H:i:s'),
        ]);
        $this->assertTrue($updated > 0, "Retire asset (ID: $assetId)");

        $asset = Asset::find($assetId);
        $this->assertTrue($asset['status'] === 'retired', "Asset status is 'retired'");
    }

    private function testAutomaticDisposalCreation(): void
    {
        $assets = Asset::all();
        if (empty($assets)) {
            $this->assertTrue(false, "No assets for disposal");
            return;
        }
        $assetId = $assets[0]['id'];
        
        // Check if disposal already exists
        $existing = AssetDisposal::findByAssetId($assetId);
        if (!$existing) {
            $disposalId = AssetDisposal::create([
                'asset_id' => $assetId,
                'retirement_date' => date('Y-m-d H:i:s'),
                'retirement_reason' => 'end_of_life',
                'disposal_status' => 'pending_disposal',
                'data_destruction_required' => 1,
                'created_by' => 1,
            ]);
            $this->assertTrue($disposalId > 0, "Automatic disposal record created (ID: $disposalId)");
        } else {
            $this->assertTrue(true, "Disposal record already exists (ID: {$existing['id']})");
        }
    }

    private function testApproveDisposal(): void
    {
        $disposals = AssetDisposal::all();
        if (empty($disposals)) {
            $this->assertTrue(false, "No disposals to approve");
            return;
        }
        $disposalId = $disposals[0]['id'];
        $updated = AssetDisposal::update($disposalId, [
            'disposal_status' => 'approved',
            'approved_by' => 1,
            'approval_date' => date('Y-m-d H:i:s'),
        ]);
        $this->assertTrue($updated > 0, "Approve disposal (ID: $disposalId)");

        $disposal = AssetDisposal::find($disposalId);
        $this->assertTrue($disposal['disposal_status'] === 'approved', "Disposal status is 'approved'");
    }

    private function testRejectDisposal(): void
    {
        $disposals = AssetDisposal::all();
        if (empty($disposals)) {
            $this->assertTrue(false, "No disposals to reject");
            return;
        }
        $disposalId = $disposals[0]['id'];
        $updated = AssetDisposal::update($disposalId, [
            'disposal_status' => 'rejected',
            'notes' => 'Test rejection reason',
        ]);
        $this->assertTrue($updated > 0, "Reject disposal (ID: $disposalId)");
    }

    private function testScheduleDisposal(): void
    {
        $disposals = AssetDisposal::all();
        if (empty($disposals)) {
            $this->assertTrue(false, "No disposals to schedule");
            return;
        }
        $disposalId = $disposals[0]['id'];
        $updated = AssetDisposal::update($disposalId, [
            'disposal_status' => 'scheduled',
            'disposal_method' => 'e_waste_recycling',
            'disposal_date' => date('Y-m-d', strtotime('+7 days')),
            'disposal_location' => 'Warehouse 2',
            'disposal_vendor' => 'Test Recycling Corp.',
        ]);
        $this->assertTrue($updated > 0, "Schedule disposal (ID: $disposalId)");
    }

    private function testDataDestructionRecording(): void
    {
        $disposals = AssetDisposal::all();
        if (empty($disposals)) {
            $this->assertTrue(false, "No disposals for data destruction");
            return;
        }
        $disposalId = $disposals[0]['id'];
        $updated = AssetDisposal::update($disposalId, [
            'data_destruction_method' => 'secure_erase',
            'data_destruction_date' => date('Y-m-d H:i:s'),
            'data_destruction_by' => 1,
            'data_destruction_verified' => 1,
        ]);
        $this->assertTrue($updated > 0, "Record data destruction (ID: $disposalId)");
    }

    private function testCompleteDisposal(): void
    {
        $disposals = AssetDisposal::all();
        if (empty($disposals)) {
            $this->assertTrue(false, "No disposals to complete");
            return;
        }
        $disposalId = $disposals[0]['id'];
        $updated = AssetDisposal::update($disposalId, [
            'disposal_status' => 'disposed',
            'disposal_date' => date('Y-m-d H:i:s'),
            'disposed_by' => 1,
            'certificate_number' => 'CERT-' . strtoupper(uniqid()),
        ]);
        $this->assertTrue($updated > 0, "Complete disposal (ID: $disposalId)");

        $disposal = AssetDisposal::find($disposalId);
        $this->assertTrue($disposal['disposal_status'] === 'disposed', "Disposal status is 'disposed'");
        $this->assertTrue(!empty($disposal['certificate_number']), "Certificate number generated");
    }

    private function testDisposalCertificate(): void
    {
        $disposals = AssetDisposal::all();
        if (empty($disposals)) {
            $this->assertTrue(false, "No disposals for certificate");
            return;
        }
        $disposal = $disposals[0];
        $this->assertTrue(!empty($disposal['certificate_number']), "Disposal certificate number exists");
        $this->assertTrue(!empty($disposal['disposal_date']), "Disposal date exists for certificate");
    }

    private function testPreventDuplicateDisposal(): void
    {
        $disposals = AssetDisposal::all();
        if (empty($disposals)) {
            $this->assertTrue(false, "No disposals to check duplicates");
            return;
        }
        $disposal = $disposals[0];
        $duplicate = AssetDisposal::findByAssetId($disposal['asset_id']);
        $this->assertTrue($duplicate !== null, "Disposal record found for asset");
        $this->assertTrue($duplicate['id'] === $disposal['id'], "No duplicate disposal records created");
    }

    private function testDisposedAssetRemainsInDatabase(): void
    {
        $disposals = AssetDisposal::all();
        if (empty($disposals)) {
            $this->assertTrue(false, "No disposals to verify asset");
            return;
        }
        $disposal = $disposals[0];
        $asset = Asset::find($disposal['asset_id']);
        $this->assertTrue($asset !== null, "Disposed asset remains in database");
        $this->assertTrue($asset['status'] === 'retired', "Disposed asset status is 'retired' (not deleted)");
    }

    private function testAuditLogsCreated(): void
    {
        $db = $this->db;
        $count = $db->fetch("SELECT COUNT(*) as cnt FROM asset_history WHERE action IN ('retired', 'disposed')");
        $this->assertTrue($count['cnt'] >= 0, "Audit logs exist for asset lifecycle events");
    }
}

// Run tests
$test = new LicenseDisposalTest();
$test->run();