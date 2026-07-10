<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Seed the RBAC role hierarchy from SDD §3.1.
 *
 * Hierarchy (highest to lowest):
 * SYSTEM_ADMIN > UNIVERSITY_ADMIN > DEPARTMENT_ADMIN > REGISTRAR_STAFF > HR_STAFF > ACADEMIC_STAFF > STUDENT > EMPLOYEE
 *
 * Plus two cross-cutting roles:
 * - AUDITOR: read-only, cross-cutting
 * - SUBSYSTEM_DEVELOPER: API/integration access only, no dashboard access
 *
 * Users may hold multiple roles simultaneously (FR-AUTH-003).
 */
class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'SYSTEM_ADMIN',
                'description_en' => 'Full system access, manages all platform settings and users',
                'description_ar' => 'وصول كامل للنظام، إدارة جميع إعدادات المنصة والمستخدمين',
                'is_system' => true,
            ],
            [
                'name' => 'UNIVERSITY_ADMIN',
                'description_en' => 'University-wide administrative access',
                'description_ar' => 'وصول إداري على مستوى الجامعة',
                'is_system' => true,
            ],
            [
                'name' => 'DEPARTMENT_ADMIN',
                'description_en' => 'Department-level administrative access',
                'description_ar' => 'وصول إداري على مستوى القسم',
                'is_system' => true,
            ],
            [
                'name' => 'REGISTRAR_STAFF',
                'description_en' => 'Student registration and enrollment management',
                'description_ar' => 'إدارة تسجيل الطلاب والقبول',
                'is_system' => true,
            ],
            [
                'name' => 'HR_STAFF',
                'description_en' => 'Human resources and employee management',
                'description_ar' => 'إدارة الموارد البشرية والموظفين',
                'is_system' => true,
            ],
            [
                'name' => 'ACADEMIC_STAFF',
                'description_en' => 'Academic teaching and research staff',
                'description_ar' => 'الهيئة الأكاديمية للتدريس والبحث',
                'is_system' => true,
            ],
            [
                'name' => 'STUDENT',
                'description_en' => 'Student self-service access',
                'description_ar' => 'وصول الخدمة الذاتية للطلاب',
                'is_system' => true,
            ],
            [
                'name' => 'EMPLOYEE',
                'description_en' => 'General employee self-service access',
                'description_ar' => 'وصول الخدمة الذاتية للموظفين',
                'is_system' => true,
            ],
            // Cross-cutting roles (outside the hierarchy)
            [
                'name' => 'AUDITOR',
                'description_en' => 'Read-only cross-cutting audit access',
                'description_ar' => 'وصول تدقيق للقراءة فقط شامل',
                'is_system' => true,
            ],
            [
                'name' => 'SUBSYSTEM_DEVELOPER',
                'description_en' => 'API and integration access only, no dashboard',
                'description_ar' => 'وصول واجهة برمجة التطبيقات والتكامل فقط',
                'is_system' => true,
            ],
        ];

        foreach ($roles as $roleData) {
            $role = Role::firstOrCreate(
                ['name' => $roleData['name'], 'guard_name' => 'web'],
            );

            // Update the extended fields
            $role->update([
                'description_en' => $roleData['description_en'],
                'description_ar' => $roleData['description_ar'],
                'is_system' => $roleData['is_system'],
            ]);
        }

        $this->command->info('✅ UIMP roles seeded: ' . count($roles) . ' roles with bilingual descriptions.');
    }
}
