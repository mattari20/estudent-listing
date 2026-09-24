<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('import_rows', function(Blueprint $table): void { $table->string('parent_subject_type')->nullable()->after('canonical_subject_id'); $table->unsignedBigInteger('parent_subject_id')->nullable()->after('parent_subject_type'); $table->index(['parent_subject_type','parent_subject_id']); }); }
 public function down(): void { Schema::table('import_rows', function(Blueprint $table): void { $table->dropIndex(['parent_subject_type','parent_subject_id']); $table->dropColumn(['parent_subject_type','parent_subject_id']); }); }
};