<?php

namespace Tests\Feature;

use App\Livewire\Clerk\CheckoutModal;
use App\Models\Appointment;
use App\Models\Medication;
use App\Models\MedicationMovement;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CheckoutStockTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.checkout_test' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('checkout_test');

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status_prescription')->nullable();
            $table->timestamps();
        });
        Schema::create('medications', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('appointment_prescriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->unsignedBigInteger('medication_id')->nullable();
            $table->integer('required_quantity')->nullable();
            $table->integer('delivered_quantity')->default(0);
            $table->string('status')->default('Prescribed');
            $table->timestamps();
        });
        Schema::create('medication_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('medication_id');
            $table->string('type');
            $table->boolean('adjustment')->default(false);
            $table->integer('quantity');
            $table->string('reference')->nullable();
            $table->unsignedBigInteger('prescription_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
    }

    public function test_initial_and_updated_quantities_do_not_exceed_stock(): void
    {
        [$component, $medication, $prescriptions] = $this->checkout(3, [8]);
        $id = $prescriptions[0]->id;

        $this->assertSame(3, $component->deliveryQuantities[$id]);
        $component->updatedDeliveryQuantities(7, $id);
        $this->assertSame(3, $component->deliveryQuantities[$id]);
        $component->updatedDeliveryQuantities(2, $id);
        $this->assertSame(2, $component->deliveryQuantities[$id]);
    }

    public function test_changed_stock_prevents_any_dispensation(): void
    {
        [$component, $medication, $prescriptions] = $this->checkout(3, [3]);
        MedicationMovement::create(['medication_id' => $medication->id, 'type' => 'OUT', 'quantity' => 1]);

        $component->dispense();

        $this->assertTrue($component->getErrorBag()->has("deliveryQuantities.{$prescriptions[0]->id}"));
        $this->assertSame(0, $prescriptions[0]->fresh()->delivered_quantity);
        $this->assertSame(1, MedicationMovement::where('type', 'OUT')->count());
    }

    public function test_repeated_medication_quantities_are_checked_together(): void
    {
        [$component, $medication, $prescriptions] = $this->checkout(3, [2, 2]);

        $component->dispense();

        $this->assertTrue($component->getErrorBag()->has("deliveryQuantities.{$prescriptions[1]->id}"));
        $this->assertSame(0, MedicationMovement::where('type', 'OUT')->count());
    }

    public function test_exact_stock_can_be_dispensed(): void
    {
        [$component, $medication, $prescriptions] = $this->checkout(3, [3]);

        $component->dispense();

        $this->assertSame(3, $prescriptions[0]->fresh()->delivered_quantity);
        $this->assertSame(0, $medication->fresh()->existences);
    }

    private function checkout(int $stock, array $required): array
    {
        $appointment = Appointment::create(['status_prescription' => 'Partial']);
        $medication = Medication::create(['name' => 'Medicamento']);
        MedicationMovement::create(['medication_id' => $medication->id, 'type' => 'IN', 'quantity' => $stock]);
        $prescriptions = [];
        foreach ($required as $quantity) {
            $prescriptions[] = $appointment->prescriptions()->create([
                'medication_id' => $medication->id,
                'required_quantity' => $quantity,
            ]);
        }

        $component = new CheckoutModal;
        $component->open_prescription($appointment->id);

        return [$component, $medication, $prescriptions];
    }
}
