<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            $table->string('issue_type')->nullable()->after('transcribed_text');
            $table->json('parts_used')->nullable()->after('issue_type');
            $table->decimal('estimated_price', 10, 2)->nullable()->after('parts_used');
            $table->string('service_type')->nullable()->after('estimated_price');
        });
    }

    public function down(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            $table->dropColumn(['issue_type', 'parts_used', 'estimated_price', 'service_type']);
        });
    }
};
