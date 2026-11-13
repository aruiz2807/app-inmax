<?php

namespace Tests\Feature;

use App\Models\PolicyService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PolicyServiceCoverageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('services', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_id')->nullable();
        });
        Schema::create('coupon_service', function (Blueprint $table) {
            $table->unsignedBigInteger('coupon_id');
            $table->unsignedBigInteger('service_id');
        });
        Schema::create('coupon_doctors', function (Blueprint $table) {
            $table->unsignedBigInteger('coupon_id');
            $table->unsignedBigInteger('doctor_id');
        });
        Schema::create('policy_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('policy_id');
            $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('coupon_id')->nullable();
            $table->unsignedInteger('used');
            $table->unsignedInteger('included');
        });

        DB::table('services')->insert([['id' => 1], ['id' => 2], ['id' => 3]]);
        DB::table('doctors')->insert([['id' => 1], ['id' => 2]]);
        DB::table('coupons')->insert(['id' => 10, 'service_id' => 1]);
        DB::table('coupon_service')->insert([
            ['coupon_id' => 10, 'service_id' => 1],
            ['coupon_id' => 10, 'service_id' => 2],
        ]);
        DB::table('coupon_doctors')->insert(['coupon_id' => 10, 'doctor_id' => 1]);
        DB::table('policy_services')->insert([
            'policy_id' => 5, 'coupon_id' => 10, 'service_id' => null, 'used' => 0, 'included' => 1,
        ]);
    }

    public function test_coupon_covers_each_associated_service_only_for_assigned_doctor(): void
    {
        $this->assertSame('by coupon', PolicyService::coversService(5, 2, 1));
        $this->assertSame('by coupon', PolicyService::coversService(5, 2));
        $this->assertFalse(PolicyService::coversService(5, 2, 2));
        $this->assertFalse(PolicyService::coversService(5, 3, 1));
        $this->assertFalse(PolicyService::coversService(6, 2, 1));
    }

    public function test_existing_single_service_and_usage_limit_are_preserved(): void
    {
        DB::table('coupon_service')->where('coupon_id', 10)->delete();
        $this->assertSame('by coupon', PolicyService::coversService(5, 1, 1));
        $this->assertFalse(PolicyService::coversService(5, 1, 2));

        DB::table('policy_services')->where('coupon_id', 10)->update(['used' => 1]);
        $this->assertFalse(PolicyService::coversService(5, 1, 1));
    }

    public function test_direct_service_takes_precedence(): void
    {
        DB::table('policy_services')->insert([
            'policy_id' => 5, 'coupon_id' => null, 'service_id' => 2, 'used' => 0, 'included' => 1,
        ]);

        $this->assertSame('by service', PolicyService::coversService(5, 2, 2));
    }
}
