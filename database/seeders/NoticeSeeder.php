<?php

namespace Database\Seeders;

use App\Models\Notice;
use App\Models\User;
use Illuminate\Database\Seeder;

class NoticeSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::where('email', 'admin@school.com')->value('id') ?? 1;

        $notices = [
            [
                'title' => 'Admission Open for 2026',
                'content' => 'Online admission is now open for the academic year 2026. Apply before the deadline to secure your seat.',
                'is_urgent' => true,
                'pinned' => true,
                'audience' => ['public'],
            ],
            [
                'title' => 'Parent-Teacher Meeting — August 10',
                'content' => 'All parents are requested to attend the parent-teacher meeting scheduled for August 10, 2026 at 10:00 AM.',
                'is_urgent' => false,
                'pinned' => true,
                'audience' => ['public'],
            ],
            [
                'title' => 'Mid-Term Examination Schedule Released',
                'content' => 'The mid-term examination schedule has been published. Students are advised to check the routine and prepare accordingly.',
                'is_urgent' => false,
                'pinned' => false,
                'audience' => ['students'],
            ],
            [
                'title' => 'School Closure — National Holiday',
                'content' => 'The school will remain closed on August 15 due to the national holiday. Regular classes will resume on August 16.',
                'is_urgent' => true,
                'pinned' => false,
                'audience' => ['public'],
            ],
            [
                'title' => 'Annual Sports Day — September 5',
                'content' => 'Annual sports day will be held on September 5. All students must participate in at least one event.',
                'is_urgent' => false,
                'pinned' => false,
                'audience' => ['students'],
            ],
        ];

        foreach ($notices as $index => $notice) {
            Notice::updateOrCreate(
                ['title' => $notice['title']],
                array_merge($notice, ['created_by' => $adminId])
            );
        }
    }
}
