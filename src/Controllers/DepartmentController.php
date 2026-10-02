<?php
/**
 * Department Controller
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Asset;
use App\Models\Department;
use App\Services\AuditService;

class DepartmentController extends Controller
{
    private AuditService $auditService;

    public function __construct()
    {
parent::__construct();
        $this->requireRole('admin');
        $this->auditService = new AuditService();
    }

    /**
     * List departments
     */
    public function index(): void
    {
        $departments = Department::getAllWithCounts();
        $this->render('departments/index', [
            'title' => 'Departments',
            'departments' => $departments,
        ]);
    }

    /**
     * List all assets registered to a department
     */
    public function assets(int $id): void
    {
        // Parameterized lookup — never interpolate the id into SQL
        $department = Department::find($id);

        if (!$department) {
            $this->redirectWith('/departments', 'Department not found.', 'danger');
            return;
        }

        $assets = Asset::getAllWithRelations(
            'a.department_id = ?',
            [$id],
            'a.asset_tag',
            'ASC'
        );

        $this->render('departments/assets', [
            'title' => 'Assets — ' . $department['name'],
            'department' => $department,
            'assets' => $assets,
            'assetCount' => count($assets),
        ]);
    }

    /**
     * Store new department
     */
    public function store(): void
    {
        $data = $this->request->only(['name', 'code', 'section', 'description']);

        $validated = $this->validate($data, [
            'name' => 'required|max:100',
            'code' => 'required|max:20|unique:departments,code',
            'section' => 'max:100',
            'description' => 'max:500',
        ]);

        if ($this->validationFails()) {
            $this->redirectWith('/departments', 'Please fix validation errors.', 'danger');
            return;
        }

        $departmentId = Department::create($validated);
        $this->auditService->log('department_created', 'department', $departmentId, $validated['code'], 'name', null, $validated['name'], 'Department created');

        $this->redirectWith('/departments', 'Department created successfully.', 'success');
    }

    /**
     * Update department
     */
    public function update(int $id): void
    {
        $data = $this->request->only(['name', 'code', 'section', 'description']);

        $validated = $this->validate(array_merge($data, ['id' => $id]), [
            'name' => 'required|max:100',
            'code' => 'required|max:20|unique:departments,code,' . $id,
            'section' => 'max:100',
            'description' => 'max:500',
        ]);

        if ($this->validationFails()) {
            $this->redirectWith('/departments', 'Please fix validation errors.', 'danger');
            return;
        }

        $before = Department::find($id);

        Department::update($id, $validated);

        // One audit entry per changed key, with the old and new value.
        foreach (['name', 'code', 'section', 'description'] as $field) {
            if (!array_key_exists($field, $validated)) {
                continue;
            }
            $old = $before[$field] ?? null;
            if ((string) $old === (string) $validated[$field]) {
                continue;
            }
            $this->auditService->log('department_updated', 'department', $id, $validated['code'], $field, $old, $validated[$field], 'Department updated');
        }

        $this->redirectWith('/departments', 'Department updated successfully.', 'success');
    }

    /**
     * Delete department
     */
    public function destroy(int $id): void
    {
        $department = Department::find($id);

        Department::delete($id);

        $this->auditService->log('department_deleted', 'department', $id, $department['code'] ?? ('#' . $id), 'name', $department['name'] ?? null, null, 'Department deleted');

        $this->redirectWith('/departments', 'Department deleted successfully.', 'success');
    }

    /**
     * Get departments as JSON (for API)
     */
    public function list(): void
    {
        $departments = Department::getOptions();
        $this->json(['success' => true, 'data' => $departments]);
    }
}

