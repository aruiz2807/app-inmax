<?php

namespace App\Livewire\Forms;

use App\Models\Coupon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class CouponsForm extends Form
{
    #[Validate('required|string|max:200')]
    public $name = '';

    #[Validate('required')]
    public $type = 'Amount';

    #[Validate('required')]
    public $value = '0';

    #[Validate('required')]
    public $min = '0';

    #[Validate('required')]
    public $max = '0';

    public $service = null;

    public array $services = [];

    #[Validate('required')]
    public $inmax_coverage_percent = '0';

    /**
    * Store the service in the DB.
    */
    public function store()
    {
        $this->normalizeFieldsForType();
        $this->validateCoupon();

        DB::transaction(function () {
            $coupon = Coupon::create($this->couponAttributes());
            $coupon->services()->sync($this->selectedServices());
        });
    }

    /**
    * Sets the service to edit.
    */
    public function set(Coupon $coupon)
    {
        $this->service = $coupon->service_id;
        $this->services = $coupon->services()->pluck('services.id')->map(fn ($id) => (string) $id)->all();
        if ($this->services === [] && $coupon->service_id) {
            $this->services = [(string) $coupon->service_id];
        }
        $this->name = $coupon->name;
        $this->type = $coupon->type;
        $this->value = $coupon->value;
        $this->min = $coupon->limit_min;
        $this->max = $coupon->limit_max;
        $this->inmax_coverage_percent = $coupon->inmax_coverage_percent;
    }

    /**
    * Updates the service in the DB.
    */
    public function update($couponId)
    {
        $this->normalizeFieldsForType();
        $this->validateCoupon();

        DB::transaction(function () use ($couponId) {
            $coupon = Coupon::findOrFail($couponId);
            $coupon->update($this->couponAttributes());
            $coupon->services()->sync($this->selectedServices());
        });
    }

    private function couponAttributes(): array
    {
        return [
            'service_id' => $this->selectedServices()[0],
            'name' => $this->name,
            'type' => $this->type,
            'value' => str_replace(',', '', $this->value),
            'limit_min' => str_replace(',', '', $this->min),
            'limit_max' => str_replace(',', '', $this->max),
            'inmax_coverage_percent' => str_replace(',', '', $this->inmax_coverage_percent),
        ];
    }

    private function selectedServices(): array
    {
        return $this->type === 'Amount' ? $this->services : [$this->service];
    }

    private function validateCoupon(): void
    {
        $this->validate([
            'name' => 'required|string|max:200',
            'type' => ['required', Rule::in(['Amount', 'Percentage', 'Service'])],
            'value' => ['required', 'numeric', 'min:0', $this->type === 'Percentage' ? 'max:100' : 'max:999999.99'],
            'min' => 'required|numeric|min:0|max:999999.99',
            'max' => 'required|numeric|min:0|max:999999.99',
            'inmax_coverage_percent' => 'required|numeric|min:0|max:100',
            'services' => $this->type === 'Amount' ? 'required|array|min:1' : 'array',
            'services.*' => 'required|integer|distinct|exists:services,id',
            'service' => $this->type === 'Amount' ? 'nullable' : 'required|integer|exists:services,id',
        ]);
    }

    private function normalizeFieldsForType(): void
    {
        $this->value = str_replace(',', '', (string) $this->value);
        $this->min = str_replace(',', '', (string) $this->min);
        $this->max = str_replace(',', '', (string) $this->max);
        $this->inmax_coverage_percent = str_replace(',', '', (string) $this->inmax_coverage_percent);

        if ($this->type !== 'Amount') {
            $this->min = '0';
            $this->max = '0';
        }

        if ($this->type === 'Service') {
            $this->value = '100';
        }
    }
}
