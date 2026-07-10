<?php

namespace Database\Seeders;

use App\Domain\Employees\Models\Employee;
use App\Domain\Organization\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoEmployeesSeeder extends Seeder
{
    public function run(): void
    {
        $cs = Department::where('code', 'CS')->first();
        $ee = Department::where('code', 'EE')->first();
        $math = Department::where('code', 'MATH')->first();

        $employees = [
            [
                'institutional_id' => 'EMP-2020-00401',
                'staff_type' => 'ACADEMIC',
                'name_en' => 'Salem Omar Mansour',
                'name_ar' => 'سالم عمر منصور',
                'email' => 'salem.mansour@uimp.edu.ly',
                'phone' => '+218-91-9876543',
                'address' => 'Tripoli, Libya',
                'academic_rank' => 'ASSOCIATE_PROFESSOR',
                'hire_date' => '2020-02-15',
                'status' => 'ACTIVE',
                'departments' => [$cs],
            ],
            [
                'institutional_id' => 'EMP-2018-00205',
                'staff_type' => 'ACADEMIC',
                'name_en' => 'Aisha Belgasem Al-Taher',
                'name_ar' => 'عائشة بالقاسم الطاهر',
                'email' => 'aisha.altaher@uimp.edu.ly',
                'phone' => '+218-92-3456780',
                'address' => 'Tripoli, Libya',
                'academic_rank' => 'PROFESSOR',
                'hire_date' => '2018-09-01',
                'status' => 'ACTIVE',
                'departments' => [$ee, $cs], // Assigned to both
            ],
            [
                'institutional_id' => 'EMP-2022-00890',
                'staff_type' => 'NON_ACADEMIC',
                'name_en' => 'Muna Ali Al-Hadi',
                'name_ar' => 'منى علي الهادي',
                'email' => 'muna.alhadi@uimp.edu.ly',
                'phone' => '+218-91-8765432',
                'address' => 'Tripoli, Libya',
                'academic_rank' => null,
                'hire_date' => '2022-11-10',
                'status' => 'ACTIVE',
                'departments' => [$math],
            ],
        ];

        foreach ($employees as $data) {
            $depts = $data['departments'];
            unset($data['departments']);

            $employee = Employee::firstOrCreate(
                ['institutional_id' => $data['institutional_id']],
                $data
            );

            if (!empty($depts)) {
                $syncData = [];
                foreach ($depts as $dept) {
                    if ($dept) {
                        $syncData[$dept->id] = ['id' => Str::uuid()];
                    }
                }
                $employee->departments()->syncWithoutDetaching($syncData);
            }
        }

        $this->command->info('✅ Demo employees seeded: ' . count($employees) . ' employees.');
    }
}
