<?php

namespace Database\Seeders;

use App\Domain\Facilities\Models\Building;
use App\Domain\Facilities\Models\Campus;
use App\Domain\Facilities\Models\Room;
use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Faculty;
use App\Domain\Organization\Models\Program;
use Illuminate\Database\Seeder;

/**
 * Seed demo reference data for immediate testing.
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Faculties ───────────────────────────────────────────
        $engineering = Faculty::firstOrCreate(['code' => 'ENG'], [
            'name_en' => 'Faculty of Engineering',
            'name_ar' => 'كلية الهندسة',
            'code' => 'ENG',
        ]);

        $science = Faculty::firstOrCreate(['code' => 'SCI'], [
            'name_en' => 'Faculty of Science',
            'name_ar' => 'كلية العلوم',
            'code' => 'SCI',
        ]);

        $arts = Faculty::firstOrCreate(['code' => 'ART'], [
            'name_en' => 'Faculty of Arts',
            'name_ar' => 'كلية الآداب',
            'code' => 'ART',
        ]);

        $medicine = Faculty::firstOrCreate(['code' => 'MED'], [
            'name_en' => 'Faculty of Medicine',
            'name_ar' => 'كلية الطب',
            'code' => 'MED',
        ]);

        // ─── Departments ─────────────────────────────────────────
        $cs = Department::firstOrCreate(['code' => 'CS'], [
            'name_en' => 'Computer Science', 'name_ar' => 'علوم الحاسوب',
            'code' => 'CS', 'faculty_id' => $engineering->id,
        ]);

        $ee = Department::firstOrCreate(['code' => 'EE'], [
            'name_en' => 'Electrical Engineering', 'name_ar' => 'الهندسة الكهربائية',
            'code' => 'EE', 'faculty_id' => $engineering->id,
        ]);

        $math = Department::firstOrCreate(['code' => 'MATH'], [
            'name_en' => 'Mathematics', 'name_ar' => 'الرياضيات',
            'code' => 'MATH', 'faculty_id' => $science->id,
        ]);

        $physics = Department::firstOrCreate(['code' => 'PHYS'], [
            'name_en' => 'Physics', 'name_ar' => 'الفيزياء',
            'code' => 'PHYS', 'faculty_id' => $science->id,
        ]);

        $english = Department::firstOrCreate(['code' => 'ENGL'], [
            'name_en' => 'English Language', 'name_ar' => 'اللغة الإنجليزية',
            'code' => 'ENGL', 'faculty_id' => $arts->id,
        ]);

        // ─── Programs ────────────────────────────────────────────
        Program::firstOrCreate(['name_en' => 'BSc Computer Science', 'department_id' => $cs->id], [
            'name_en' => 'BSc Computer Science', 'name_ar' => 'بكالوريوس علوم الحاسوب',
            'degree_level' => 'BSc', 'department_id' => $cs->id,
        ]);

        Program::firstOrCreate(['name_en' => 'MSc Computer Science', 'department_id' => $cs->id], [
            'name_en' => 'MSc Computer Science', 'name_ar' => 'ماجستير علوم الحاسوب',
            'degree_level' => 'MSc', 'department_id' => $cs->id,
        ]);

        Program::firstOrCreate(['name_en' => 'BSc Electrical Engineering', 'department_id' => $ee->id], [
            'name_en' => 'BSc Electrical Engineering', 'name_ar' => 'بكالوريوس الهندسة الكهربائية',
            'degree_level' => 'BSc', 'department_id' => $ee->id,
        ]);

        Program::firstOrCreate(['name_en' => 'BSc Mathematics', 'department_id' => $math->id], [
            'name_en' => 'BSc Mathematics', 'name_ar' => 'بكالوريوس الرياضيات',
            'degree_level' => 'BSc', 'department_id' => $math->id,
        ]);

        // ─── Campuses ────────────────────────────────────────────
        $mainCampus = Campus::firstOrCreate(['name_en' => 'Main Campus'], [
            'name_en' => 'Main Campus', 'name_ar' => 'الحرم الجامعي الرئيسي',
            'address' => 'Tripoli, Libya',
        ]);

        $westCampus = Campus::firstOrCreate(['name_en' => 'West Campus'], [
            'name_en' => 'West Campus', 'name_ar' => 'الحرم الغربي',
            'address' => 'Tripoli West, Libya',
        ]);

        // ─── Buildings ───────────────────────────────────────────
        $engBuilding = Building::firstOrCreate(['code' => 'ENG-A'], [
            'name_en' => 'Engineering Building A', 'name_ar' => 'مبنى الهندسة أ',
            'code' => 'ENG-A', 'campus_id' => $mainCampus->id,
        ]);

        $sciBuilding = Building::firstOrCreate(['code' => 'SCI-B'], [
            'name_en' => 'Science Building B', 'name_ar' => 'مبنى العلوم ب',
            'code' => 'SCI-B', 'campus_id' => $mainCampus->id,
        ]);

        $adminBuilding = Building::firstOrCreate(['code' => 'ADM-1'], [
            'name_en' => 'Administration Building', 'name_ar' => 'مبنى الإدارة',
            'code' => 'ADM-1', 'campus_id' => $mainCampus->id,
        ]);

        // ─── Rooms ───────────────────────────────────────────────
        Room::firstOrCreate(['name' => 'ENG-A-101'], [
            'name' => 'ENG-A-101', 'code' => 'ENG-A-101',
            'room_type' => 'LECTURE_HALL', 'capacity' => 120,
            'availability_status' => 'AVAILABLE', 'building_id' => $engBuilding->id,
        ]);

        Room::firstOrCreate(['name' => 'ENG-A-LAB1'], [
            'name' => 'ENG-A-LAB1', 'code' => 'ENG-A-LAB1',
            'room_type' => 'LAB', 'capacity' => 30,
            'availability_status' => 'AVAILABLE', 'building_id' => $engBuilding->id,
        ]);

        Room::firstOrCreate(['name' => 'SCI-B-201'], [
            'name' => 'SCI-B-201', 'code' => 'SCI-B-201',
            'room_type' => 'LECTURE_HALL', 'capacity' => 80,
            'availability_status' => 'AVAILABLE', 'building_id' => $sciBuilding->id,
        ]);

        Room::firstOrCreate(['name' => 'ADM-1-OFFICE-1'], [
            'name' => 'ADM-1-OFFICE-1', 'code' => 'ADM-1-OFF-1',
            'room_type' => 'OFFICE', 'capacity' => 4,
            'availability_status' => 'OCCUPIED', 'building_id' => $adminBuilding->id,
        ]);

        $this->command->info('✅ Reference data seeded: faculties, departments, programs, campuses, buildings, rooms.');
    }
}
