<?php

namespace Tests\Feature;

use App\Livewire\Coupons\CouponsPage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CouponsFormTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default('Active');
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->string('name');
            $table->string('type');
            $table->decimal('value', 8, 2);
            $table->decimal('limit_min', 8, 2);
            $table->decimal('limit_max', 8, 2);
            $table->decimal('inmax_coverage_percent', 5, 2);
            $table->string('status')->default('Active');
            $table->timestamps();
        });

        Schema::create('coupon_service', function (Blueprint $table) {
            $table->unsignedBigInteger('coupon_id');
            $table->unsignedBigInteger('service_id');
            $table->primary(['coupon_id', 'service_id']);
        });

        DB::table('services')->insert([
            ['id' => 1, 'name' => 'Servicio uno'],
            ['id' => 2, 'name' => 'Servicio dos'],
        ]);
    }

    public function test_percentage_coupon_discards_limits(): void
    {
        Livewire::test(CouponsPage::class)
            ->set('form.name', 'Porcentaje')
            ->set('form.type', 'Percentage')
            ->set('form.service', '1')
            ->set('form.value', '25')
            ->set('form.min', '100')
            ->set('form.max', '500')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('coupons', [
            'name' => 'Porcentaje',
            'value' => 25,
            'limit_min' => 0,
            'limit_max' => 0,
        ]);
    }

    public function test_service_coupon_stores_full_value_and_no_limits(): void
    {
        Livewire::test(CouponsPage::class)
            ->set('form.name', 'Servicio')
            ->set('form.type', 'Service')
            ->set('form.service', '1')
            ->set('form.value', '25')
            ->set('form.min', '100')
            ->set('form.max', '500')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('coupons', [
            'name' => 'Servicio',
            'value' => 100,
            'limit_min' => 0,
            'limit_max' => 0,
        ]);
    }

    public function test_amount_coupon_stores_and_loads_multiple_services(): void
    {
        Livewire::test(CouponsPage::class)
            ->set('form.name', 'Importe')
            ->set('form.type', 'Amount')
            ->set('form.services', ['1', '2'])
            ->set('form.value', '1500')
            ->call('save')
            ->assertHasNoErrors();

        $id = DB::table('coupons')->where('name', 'Importe')->value('id');
        $this->assertDatabaseHas('coupon_service', ['coupon_id' => $id, 'service_id' => 1]);
        $this->assertDatabaseHas('coupon_service', ['coupon_id' => $id, 'service_id' => 2]);

        Livewire::test(CouponsPage::class)
            ->call('edit', $id)
            ->assertSet('form.services', ['1', '2'])
            ->set('form.services', ['2'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('coupon_service', ['coupon_id' => $id, 'service_id' => 1]);
        $this->assertDatabaseHas('coupon_service', ['coupon_id' => $id, 'service_id' => 2]);
        $this->assertDatabaseHas('coupons', ['id' => $id, 'service_id' => 2]);
    }

    public function test_service_selection_is_required(): void
    {
        Livewire::test(CouponsPage::class)
            ->set('form.name', 'Sin servicio')
            ->call('save')
            ->assertHasErrors(['form.services' => 'required']);

        Livewire::test(CouponsPage::class)
            ->set('form.name', 'Sin servicio')
            ->set('form.type', 'Percentage')
            ->call('save')
            ->assertHasErrors(['form.service' => 'required']);
    }

    public function test_the_visible_fields_depend_on_coupon_type(): void
    {
        Livewire::test(CouponsPage::class)
            ->assertSee('coupon-services-multiple')
            ->assertSee('Limite inferior')
            ->set('form.type', 'Percentage')
            ->assertSee('coupon-service-single')
            ->assertSee('Porcentaje')
            ->assertDontSee('Limite inferior')
            ->set('form.type', 'Service')
            ->assertSee('coupon-service-single')
            ->assertDontSee('name="value"', false)
            ->assertDontSee('Limite superior');
    }
}
