<?php

use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const CODE = 'view.doctor.virtual_notes';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permission = Permission::query()->firstOrCreate(
            ['code' => self::CODE],
            [
                'name' => 'Ver recetas virtuales',
                'group_name' => 'Doctor',
                'description' => 'Permite generar recetas virtuales sin consulta.',
                'is_active' => true,
            ]
        );

        $doctorIds = User::query()->where('profile', 'Doctor')->pluck('id');

        $permission->users()->syncWithoutDetaching($doctorIds);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permission = Permission::query()->where('code', self::CODE)->first();

        if ($permission) {
            $permission->users()->detach();
            $permission->delete();
        }
    }
};
