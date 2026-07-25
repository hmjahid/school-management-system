<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
            DemoUsersSeeder::class,
            WebsiteSettingSeeder::class,
            WebsiteContentSeeder::class,
            PaymentGatewaySeeder::class,
            DemoAcademicSeeder::class,
            DemoTeacherSeeder::class,
            DemoStudentSeeder::class,
            DemoExamSeeder::class,
            DemoAttendanceSeeder::class,
            DemoFeeSeeder::class,
            DemoTransportSeeder::class,
            DemoEventSeeder::class,
            DemoGallerySeeder::class,
            DemoLeaveSeeder::class,
            DemoLedgerSeeder::class,
            NoticeSeeder::class,
        ]);
    }
}
