<?php

namespace Database\Seeders;

use App\Domain\Subsystems\Enums\SubsystemStatus;
use App\Domain\Subsystems\Models\Subsystem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoSubsystemsSeeder extends Seeder
{
    public function run(): void
    {
        // We will seed a demo Portal subsystem and a demo LMS subsystem.
        // For convenience in local testing, we hash a known key: "uimp_demo_portal_key_1234567890" and "uimp_demo_lms_key_1234567890"
        
        $portalKey = 'uimp_demo_portal_key_1234567890';
        $portalHash = hash('sha256', $portalKey);

        $lmsKey = 'uimp_demo_lms_key_1234567890';
        $lmsHash = hash('sha256', $lmsKey);

        $subsystems = [
            [
                'name_en' => 'Student Portal Subsystem',
                'name_ar' => 'بوابة الطلاب الفرعية',
                'description_en' => 'Provides student self-service and profiles dashboard',
                'description_ar' => 'يوفر خدمة الطلاب الذاتية ولوحة تحكم الملفات الشخصية',
                'api_key_hash' => $portalHash,
                'status' => SubsystemStatus::ACTIVE->value,
                'webhook_url' => 'https://portal.uimp-demo.edu.ly/webhooks',
                'webhook_secret' => 'whsec_portal_secret_1234567890',
                'contact_email' => 'portal-dev@uimp.edu.ly',
            ],
            [
                'name_en' => 'Learning Management System (LMS)',
                'name_ar' => 'نظام إدارة التعلم (LMS)',
                'description_en' => 'Handles course content and student assignments',
                'description_ar' => 'يتعامل مع محتوى المقررات والمهام الطلابية',
                'api_key_hash' => $lmsHash,
                'status' => SubsystemStatus::ACTIVE->value,
                'webhook_url' => 'https://lms.uimp-demo.edu.ly/webhooks',
                'webhook_secret' => 'whsec_lms_secret_1234567890',
                'contact_email' => 'lms-dev@uimp.edu.ly',
            ],
        ];

        foreach ($subsystems as $data) {
            Subsystem::firstOrCreate(
                ['contact_email' => $data['contact_email']],
                $data
            );
        }

        $this->command->info('✅ Demo subsystems seeded: ' . count($subsystems) . ' subsystems.');
        $this->command->info('🔑 Demo Portal API Key: ' . $portalKey);
        $this->command->info('🔑 Demo LMS API Key: ' . $lmsKey);
    }
}
