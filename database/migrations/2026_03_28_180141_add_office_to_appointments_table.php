<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        if ($isSqlite) {
            $addRequestedBy = ! Schema::hasColumn('appointments', 'requested_by_user_id');
            $addOffice = ! Schema::hasColumn('appointments', 'office_id');

            if ($addRequestedBy || $addOffice) {
                Schema::table('appointments', function (Blueprint $table) use ($addRequestedBy, $addOffice) {
                    if ($addRequestedBy) {
                        $table->foreignId('requested_by_user_id')->nullable();
                    }

                    if ($addOffice) {
                        $table->foreignId('office_id')->nullable();
                    }
                });
            }

            if (Schema::hasColumn('appointments', 'covered')) {
                Schema::table('appointments', function (Blueprint $table) {
                    $table->dropColumn('covered');
                });
            }

            return;
        }

        Schema::table('appointments', function (Blueprint $table) use ($isSqlite) {
            Schema::disableForeignKeyConstraints();
            DB::table('appointments')->truncate();
            Schema::enableForeignKeyConstraints();

            if (! $isSqlite) {
                $table->dropForeign('appointments_doctor_id_foreign');
            }
            $table->dropColumn('doctor_id');

            $table->foreignId('requested_by_user_id')->nullable()->after('user_id')->constrained(
                table: 'users'
            );

            $table->foreignId('office_id')->nullable()->after('user_id')->constrained(
                table: 'offices'
            );

            $table->foreignId('doctor_id')->nullable()->after('user_id')->constrained(
                table: 'doctors'
            );

            $table->dropColumn('covered');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        if ($isSqlite) {
            if (! Schema::hasColumn('appointments', 'covered')) {
                Schema::table('appointments', function (Blueprint $table) {
                    $table->boolean('covered')->default(false);
                });
            }

            $dropRequestedBy = Schema::hasColumn('appointments', 'requested_by_user_id');
            $dropOffice = Schema::hasColumn('appointments', 'office_id');

            if ($dropRequestedBy || $dropOffice) {
                Schema::table('appointments', function (Blueprint $table) use ($dropRequestedBy, $dropOffice) {
                    if ($dropRequestedBy && $dropOffice) {
                        $table->dropColumn(['requested_by_user_id', 'office_id']);

                        return;
                    }

                    if ($dropRequestedBy) {
                        $table->dropColumn('requested_by_user_id');
                    }

                    if ($dropOffice) {
                        $table->dropColumn('office_id');
                    }
                });
            }

            return;
        }

        Schema::table('appointments', function (Blueprint $table) use ($isSqlite) {
            $table->boolean('covered')->after('time');

            if (! $isSqlite) {
                $table->dropForeign('appointments_doctor_id_foreign');
            }
            $table->dropColumn('doctor_id');

            if (! $isSqlite) {
                $table->dropForeign('appointments_office_id_foreign');
            }
            $table->dropColumn('office_id');

            if (! $isSqlite) {
                $table->dropForeign('appointments_requested_by_user_id_foreign');
            }
            $table->dropColumn('requested_by_user_id');

            $table->foreignId('doctor_id')->after('user_id')->constrained(
                table: 'doctors'
            );
        });
    }
};
