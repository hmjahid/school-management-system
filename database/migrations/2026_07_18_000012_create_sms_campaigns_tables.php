<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->string('audience_type', 32); // all, class, section, staff, custom
            $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->text('message');
            $table->timestamp('scheduled_at')->nullable();
            $table->string('status', 20)->default('draft'); // draft, sending, sent, failed
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('sms_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_campaign_id')->constrained('sms_campaigns')->cascadeOnDelete();
            $table->string('phone', 32);
            $table->string('user_type', 32)->nullable(); // student, guardian, staff
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status', 20)->default('queued'); // queued, sent, failed
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_campaign_recipients');
        Schema::dropIfExists('sms_campaigns');
    }
};