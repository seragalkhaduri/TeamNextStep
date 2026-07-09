<?php

namespace Database\Seeders;

use App\Domain\Notifications\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'id' => 'PASSWORD_RESET',
                'name_en' => 'Password Reset Link',
                'name_ar' => 'رابط إعادة تعيين كلمة المرور',
                'subject_en' => 'Reset Your UIMP Password',
                'subject_ar' => 'إعادة تعيين كلمة مرور UIMP',
                'body_en' => 'Hello {{ name }}, please use the following link to reset your password: {{ link }}. This link expires in 60 minutes.',
                'body_ar' => 'مرحباً {{ name }}، يرجى استخدام الرابط التالي لإعادة تعيين كلمة المرور الخاصة بك: {{ link }}. تنتهي صلاحية هذا الرابط خلال 60 دقيقة.',
                'channels' => ['email'],
            ],
            [
                'id' => 'WELCOME_USER',
                'name_en' => 'Welcome to UIMP',
                'name_ar' => 'مرحباً بك في UIMP',
                'subject_en' => 'Welcome to UIMP Core Platform',
                'subject_ar' => 'مرحباً بك في منصة UIMP الرئيسية',
                'body_en' => 'Welcome {{ name }}! Your account has been successfully created. Username: {{ username }}.',
                'body_ar' => 'مرحباً {{ name }}! تم إنشاء حسابك بنجاح. اسم المستخدم: {{ username }}.',
                'channels' => ['email', 'in_app'],
            ],
            [
                'id' => 'STUDENT_ADMITTED',
                'name_en' => 'Student Admission Confirmation',
                'name_ar' => 'تأكيد قبول الطالب',
                'subject_en' => 'Admission Confirmed',
                'subject_ar' => 'تم تأكيد القبول الدراسي',
                'body_en' => 'Dear {{ name }}, you have been admitted as a student under ID: {{ institutionalId }}.',
                'body_ar' => 'عزيزنا {{ name }}، تم قبولك كطالب في الجامعة بالرقم الدراسي: {{ institutionalId }}.',
                'channels' => ['email', 'sms'],
            ],
            [
                'id' => 'EMPLOYEE_HIRED',
                'name_en' => 'Employee Hiring Notice',
                'name_ar' => 'إشعار توظيف موظف جديد',
                'subject_en' => 'Hiring Completed',
                'subject_ar' => 'اكتملت إجراءات التوظيف',
                'body_en' => 'Hello {{ name }}, welcome to the university staff under ID: {{ institutionalId }}.',
                'body_ar' => 'مرحباً {{ name }}، مرحباً بك في الكادر الوظيفي للجامعة بالرقم الوظيفي: {{ institutionalId }}.',
                'channels' => ['email'],
            ],
        ];

        foreach ($templates as $data) {
            NotificationTemplate::firstOrCreate(['id' => $data['id']], $data);
        }

        $this->command->info('✅ Notification templates seeded.');
    }
}
