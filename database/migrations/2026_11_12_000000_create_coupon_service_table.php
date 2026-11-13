<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupon_service', function (Blueprint $table) {
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->primary(['coupon_id', 'service_id']);
        });

        DB::table('coupons')->whereNotNull('service_id')->orderBy('id')
            ->chunkById(500, function ($coupons) {
                DB::table('coupon_service')->insert($coupons->map(fn ($coupon) => [
                    'coupon_id' => $coupon->id,
                    'service_id' => $coupon->service_id,
                ])->all());
            });
    }

    public function down(): void
    {
        if (DB::table('coupon_service')->select('coupon_id')
            ->groupBy('coupon_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('No se puede revertir la migración mientras existan cupones con varios servicios.');
        }

        Schema::dropIfExists('coupon_service');
    }
};
