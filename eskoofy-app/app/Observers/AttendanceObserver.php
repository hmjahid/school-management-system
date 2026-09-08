<?php

namespace App\Observers;

use App\Jobs\SendAbsenceSmsJob;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\WebsiteSetting;

class AttendanceObserver
{
    public function created(Attendance $attendance): void
    {
        if ($attendance->status !== Attendance::STATUS_ABSENT) {
            return;
        }

        $setting = WebsiteSetting::first();
        if (! $setting || ! ($setting->send_absence_sms ?? false)) {
            return;
        }

        $student = Student::find($attendance->student_id);
        if (! $student) {
            return;
        }

        $phone = $student->guardian_phone ?: $student->father_phone ?: $student->mother_phone ?: $student->phone_1;
        if (! $phone) {
            return;
        }

        $template = $setting->absence_sms_template ?: __(':name was absent on :date.', [
            'name' => $student->full_name ?? trim($student->first_name.' '.$student->last_name),
            'date' => $attendance->date->format('M j, Y'),
        ]);

        $message = strtr($template, [
            ':name' => $student->full_name ?? trim($student->first_name.' '.$student->last_name),
            ':date' => $attendance->date->format('M j, Y'),
            ':class' => $student->class?->name ?? '',
        ]);

        SendAbsenceSmsJob::dispatch($phone, $message);
    }
}
