<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NotificationTemplate;

/**
 * Seeds default notification templates
 */
class NotificationTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Interview Scheduled',
                'subject' => 'Interview Scheduled - {{position_title}}',
                'body' => "Dear {{candidate_name}},\n\nWe are pleased to inform you that an interview has been scheduled for the {{position_title}} position.\n\n📅 Date: {{interview_date}}\n🕐 Time: {{interview_time}}\n📍 Location: {{interview_location}}\n🎯 Type: {{interview_type}}\n\nPlease ensure you're available at the scheduled time. If you need to reschedule, please contact us as soon as possible.\n\nWe look forward to meeting you!",
                'type' => 'interview_scheduled',
                'variables' => [
                    'candidate_name' => 'Full name of the candidate',
                    'position_title' => 'Job position title',
                    'interview_date' => 'Date of the interview',
                    'interview_time' => 'Time of the interview',
                    'interview_location' => 'Location or meeting link',
                    'interview_type' => 'Type of interview (phone, video, in-person)',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Interview Reminder',
                'subject' => 'Reminder: Interview Tomorrow - {{position_title}}',
                'body' => "Dear {{candidate_name}},\n\nThis is a friendly reminder that you have an interview scheduled for tomorrow.\n\n📅 Date: {{interview_date}}\n🕐 Time: {{interview_time}}\n📍 Location: {{interview_location}}\n\nPlease ensure you're prepared and arrive on time. If you have any questions, please don't hesitate to reach out.\n\nGood luck!",
                'type' => 'interview_reminder',
                'variables' => [
                    'candidate_name' => 'Full name of the candidate',
                    'position_title' => 'Job position title',
                    'interview_date' => 'Date of the interview',
                    'interview_time' => 'Time of the interview',
                    'interview_location' => 'Location or meeting link',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Offer Sent',
                'subject' => 'Job Offer - {{position_title}} at {{company_name}}',
                'body' => "Dear {{candidate_name}},\n\nCongratulations! We are thrilled to extend an official job offer to you for the position of {{position_title}}.\n\n💼 Position: {{position_title}}\n💰 Salary: {{salary_offered}}\n📅 Start Date: {{start_date}}\n\nThis offer is valid until {{expiry_date}}. Please review the details carefully and let us know your decision.\n\nWe sincerely hope you'll join us and look forward to welcoming you to the team!",
                'type' => 'offer_sent',
                'variables' => [
                    'candidate_name' => 'Full name of the candidate',
                    'position_title' => 'Job position title',
                    'company_name' => 'Company name',
                    'salary_offered' => 'Salary amount',
                    'start_date' => 'Proposed start date',
                    'expiry_date' => 'Offer expiration date',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Offer Accepted',
                'subject' => 'Welcome to {{company_name}}!',
                'body' => "Dear {{candidate_name}},\n\nWe are delighted to confirm that you have accepted our offer for the {{position_title}} position!\n\n🎉 Welcome to the {{company_name}} team!\n\n📅 Your start date: {{start_date}}\n\nOur HR team will be in touch shortly with onboarding information and next steps.\n\nWe can't wait to have you on board!",
                'type' => 'offer_accepted',
                'variables' => [
                    'candidate_name' => 'Full name of the candidate',
                    'position_title' => 'Job position title',
                    'company_name' => 'Company name',
                    'start_date' => 'Start date',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Offer Rejected',
                'subject' => 'Thank You for Your Consideration',
                'body' => "Dear {{candidate_name}},\n\nThank you for considering the {{position_title}} position at {{company_name}}.\n\nWe understand you have decided to decline our offer. While we're disappointed, we respect your decision.\n\nWe wish you all the best in your future endeavors. Please keep us in mind for future opportunities - we would love to stay connected!",
                'type' => 'offer_rejected',
                'variables' => [
                    'candidate_name' => 'Full name of the candidate',
                    'position_title' => 'Job position title',
                    'company_name' => 'Company name',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Application Received',
                'subject' => 'Application Received - {{position_title}}',
                'body' => "Dear {{candidate_name}},\n\nThank you for applying for the {{position_title}} position at {{company_name}}!\n\nWe have received your application and our recruitment team will review it carefully. We will be in touch if your qualifications match our requirements.\n\nThank you for your interest in joining our team!",
                'type' => 'application_received',
                'variables' => [
                    'candidate_name' => 'Full name of the candidate',
                    'position_title' => 'Job position title',
                    'company_name' => 'Company name',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Application Status Update',
                'subject' => 'Application Status Update - {{position_title}}',
                'body' => "Dear {{candidate_name}},\n\nWe wanted to update you on the status of your application for the {{position_title}} position.\n\n📋 Status: {{status}}\n\n{{status_message}}\n\nIf you have any questions, please don't hesitate to reach out.",
                'type' => 'application_status',
                'variables' => [
                    'candidate_name' => 'Full name of the candidate',
                    'position_title' => 'Job position title',
                    'status' => 'Current application status',
                    'status_message' => 'Additional status details',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'General Reminder',
                'subject' => 'Reminder: {{reminder_title}}',
                'body' => "Dear {{recipient_name}},\n\nThis is a reminder regarding: {{reminder_title}}\n\n{{reminder_message}}\n\nPlease take the necessary action at your earliest convenience.",
                'type' => 'reminder',
                'variables' => [
                    'recipient_name' => 'Recipient name',
                    'reminder_title' => 'Title of the reminder',
                    'reminder_message' => 'Reminder details',
                ],
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::updateOrCreate(
                ['name' => $template['name']],
                $template
            );
        }

        $this->command->info('Notification templates seeded successfully!');
        $this->command->info('Total templates: ' . count($templates));
    }
}
