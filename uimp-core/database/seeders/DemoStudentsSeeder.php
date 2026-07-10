<?php

namespace Database\Seeders;

use App\Domain\Organization\Models\Program;
use App\Domain\Students\Models\Student;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seed demo students for testing.
 */
class DemoStudentsSeeder extends Seeder
{
    public function run(): void
    {
        $createdBy = User::where('username', 'registrar')->first()?->id
            ?? User::first()->id;

        $bscCs = Program::where('name_en', 'BSc Computer Science')->first();
        $bscEe = Program::where('name_en', 'BSc Electrical Engineering')->first();

        $students = [
            [
                'institutional_id' => 'STU-2024-001001',
                'national_id' => 'LY-1234567890',
                'name_en' => 'Ahmed Mohamed Ali',
                'name_ar' => 'أحمد محمد علي',
                'date_of_birth' => '2001-03-15',
                'gender' => 'MALE',
                'nationality' => 'Libyan',
                'email' => 'ahmed.ali@student.uimp.edu.ly',
                'phone' => '+218-91-1234567',
                'enrollment_status' => 'ACTIVE',
                'admission_date' => '2024-09-01',
                'program' => $bscCs,
            ],
            [
                'institutional_id' => 'STU-2024-001002',
                'national_id' => 'LY-1234567891',
                'name_en' => 'Fatima Hassan Ibrahim',
                'name_ar' => 'فاطمة حسن إبراهيم',
                'date_of_birth' => '2002-07-22',
                'gender' => 'FEMALE',
                'nationality' => 'Libyan',
                'email' => 'fatima.ibrahim@student.uimp.edu.ly',
                'phone' => '+218-92-2345678',
                'enrollment_status' => 'ACTIVE',
                'admission_date' => '2024-09-01',
                'program' => $bscCs,
            ],
            [
                'institutional_id' => 'STU-2023-000501',
                'national_id' => 'LY-9876543210',
                'name_en' => 'Omar Khalid Saleh',
                'name_ar' => 'عمر خالد صالح',
                'date_of_birth' => '2000-11-08',
                'gender' => 'MALE',
                'nationality' => 'Libyan',
                'email' => 'omar.saleh@student.uimp.edu.ly',
                'phone' => '+218-94-3456789',
                'enrollment_status' => 'ACTIVE',
                'admission_date' => '2023-09-01',
                'program' => $bscEe,
            ],
            [
                'institutional_id' => 'STU-2021-000100',
                'national_id' => 'LY-5555555555',
                'name_en' => 'Mariam Abdulrahman Othman',
                'name_ar' => 'مريم عبدالرحمن عثمان',
                'date_of_birth' => '1999-01-30',
                'gender' => 'FEMALE',
                'nationality' => 'Libyan',
                'email' => 'mariam.othman@student.uimp.edu.ly',
                'phone' => '+218-91-5555555',
                'enrollment_status' => 'GRADUATED',
                'admission_date' => '2021-09-01',
                'graduation_date' => '2025-06-15',
                'program' => $bscCs,
            ],
        ];

        foreach ($students as $data) {
            $program = $data['program'];
            unset($data['program']);
            $data['created_by'] = $createdBy;

            $student = Student::firstOrCreate(
                ['institutional_id' => $data['institutional_id']],
                $data
            );

            if ($program) {
                $student->programs()->syncWithoutDetaching([
                    $program->id => [
                        'id' => Str::uuid(),
                        'enrollment_date' => $data['admission_date'],
                    ],
                ]);
            }
        }

        $this->command->info('✅ Demo students seeded: ' . count($students) . ' students.');
    }
}
