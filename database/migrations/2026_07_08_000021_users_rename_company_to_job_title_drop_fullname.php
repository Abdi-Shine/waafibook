<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // users.company — string column that stored job_title (misnamed). Renaming
    // removes the attribute-vs-relationship conflict: $user->company previously
    // returned this string before Eloquent could reach the BelongsToTenant
    // company() relationship, so CheckSubscriptionStatus and
    // SubscriptionController silently got a string (usually null) instead of
    // the Company model. After this rename, $user->company resolves the
    // relationship correctly and both callers work without any code change.
    //
    // users.fullname — redundant display-name column written alongside name.
    // ProfileController and the two registration paths now write first+last into
    // name directly; Blade views already fall back to name.

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('company', 'job_title');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('fullname');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('fullname')->nullable()->after('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('job_title', 'company');
        });
    }
};
