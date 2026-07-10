<?php

namespace App\Livewire\Clerk;

use App\Models\MedicationPurchase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class PurchasesTable extends PowerGridComponent
{
    public string $tableName = 'purchasesTable';

    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return MedicationPurchase::query()
            ->with('supplier:id,name');
    }

    public function relationSearch(): array
    {
        return [
            'supplier' => ['name'],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('supplier_name', fn (MedicationPurchase $purchase) => $purchase->supplier?->name ?? 'Sin proveedor')
            ->add('invoice')
            ->add('invoice_formatted', fn (MedicationPurchase $purchase) => $purchase->invoice ?: '-')
            ->add('subtotal')
            ->add('subtotal_formatted', fn (MedicationPurchase $purchase) => '$' . number_format((float) $purchase->subtotal, 2))
            ->add('total')
            ->add('total_formatted', fn (MedicationPurchase $purchase) => '$' . number_format((float) $purchase->total, 2))
            ->add('status')
            ->add('status_badge', fn (MedicationPurchase $purchase) => $this->statusBadge((string) $purchase->status))
            ->add('created_at')
            ->add('created_at_formatted', fn (MedicationPurchase $purchase) => Carbon::parse($purchase->created_at)->format('d/m/Y H:i'));
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')
                ->sortable(),

            Column::make('Proveedor', 'supplier_name', 'supplier.name')
                ->searchable()
                ->sortable(),

            Column::make('Factura', 'invoice_formatted', 'invoice')
                ->searchable()
                ->sortable(),

            Column::make('Subtotal', 'subtotal_formatted', 'subtotal')
                ->sortable(),

            Column::make('Total', 'total_formatted', 'total')
                ->sortable(),

            Column::make('Estatus', 'status_badge', 'status')
                ->sortable(),

            Column::make('Fecha', 'created_at_formatted', 'created_at')
                ->sortable(),

            Column::action('Acciones'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::inputText('invoice')->operators(['contains']),
            Filter::select('status', 'status')
                ->dataSource(collect([
                    ['id' => 'requested', 'name' => 'Solicitado'],
                    ['id' => 'received', 'name' => 'Recibido'],
                    ['id' => 'partial', 'name' => 'Parcial'],
                    ['id' => 'closed', 'name' => 'Cerrado'],
                ]))
                ->optionValue('id')
                ->optionLabel('name'),
        ];
    }

    public function actions(MedicationPurchase $row): array
    {
        return [
            Button::add('details')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="eye" variant="outline" class="w-5 h-5"/><span>Ver detalle</span></div>'))
                ->id()
                ->class('text-slate-600 hover:bg-slate-50 px-2 py-1 rounded transition-colors')
                ->dispatch('showPurchaseDetails', ['purchaseId' => $row->id]),

            Button::add('pdf')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="arrow-down-tray" variant="outline" class="w-5 h-5"/><span>PDF</span></div>'))
                ->id()
                ->class('text-teal-600 hover:bg-teal-50 px-2 py-1 rounded transition-colors')
                ->dispatch('downloadPurchaseInvoicePdf', ['purchaseId' => $row->id]),

            Button::add('xml')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="document" variant="outline" class="w-5 h-5"/><span>XML</span></div>'))
                ->id()
                ->class('text-cyan-600 hover:bg-cyan-50 px-2 py-1 rounded transition-colors')
                ->dispatch('downloadPurchaseInvoiceXml', ['purchaseId' => $row->id]),
        ];
    }

    private function statusBadge(string $status): string
    {
        [$label, $badgeClass] = match ($status) {
            'requested' => ['Solicitado', 'bg-amber-100 text-amber-700'],
            'received' => ['Recibido', 'bg-sky-100 text-sky-700'],
            'partial' => ['Parcial', 'bg-orange-100 text-orange-700'],
            'closed' => ['Cerrado', 'bg-emerald-100 text-emerald-700'],
            default => [ucfirst($status), 'bg-neutral-100 text-neutral-700'],
        };

        return '<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ' . $badgeClass . '">' . e($label) . '</span>';
    }
}
