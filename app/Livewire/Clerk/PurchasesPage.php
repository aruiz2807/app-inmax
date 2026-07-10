<?php

namespace App\Livewire\Clerk;

use App\Enums\MedicationPurchasesStatus;
use App\Enums\MedicationMovementType;
use App\Models\Medication;
use App\Models\MedicationMovement;
use App\Models\MedicationPurchase;
use App\Models\MedicationPurchaseDetail;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class PurchasesPage extends Component
{
    use WithFileUploads;

    public ?int $uploadSupplierId = null;
    public $uploadInvoicePdfFile = null;
    public $uploadInvoiceXmlFile = null;
    public $selectedPurchase = null;

    #[Layout('layouts.app')]
    public function render()
    {
        $suppliers = Supplier::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.clerk.purchases-page', [
            'suppliers' => $suppliers,
        ]);
    }

    public function uploadInvoice()
    {
        $validated = $this->validate([
            'uploadSupplierId' => ['required', 'exists:suppliers,id'],
            'uploadInvoicePdfFile' => ['required', 'file', 'mimes:pdf', 'max:5120'],
            'uploadInvoiceXmlFile' => ['required', 'file', 'mimes:xml', 'max:2048'],
        ], [
            'uploadSupplierId.required' => 'Seleccione un proveedor.',
            'uploadSupplierId.exists' => 'El proveedor seleccionado no es valido.',
            'uploadInvoicePdfFile.required' => 'Seleccione el archivo PDF.',
            'uploadInvoicePdfFile.mimes' => 'El archivo PDF debe ser un PDF valido.',
            'uploadInvoicePdfFile.max' => 'El PDF no debe superar 5MB.',
            'uploadInvoiceXmlFile.required' => 'Seleccione el archivo XML.',
            'uploadInvoiceXmlFile.mimes' => 'El archivo XML debe ser un XML valido.',
            'uploadInvoiceXmlFile.max' => 'El XML no debe superar 2MB.',
        ]);

        $purchase = MedicationPurchase::create([
            'supplier_id' => (int) $validated['uploadSupplierId'],
            'invoice' => null,
            'subtotal' => 0,
            'total' => 0,
            'status' => MedicationPurchasesStatus::Requested->value,
        ]);

        $storedPdfPath = $validated['uploadInvoicePdfFile']->storeAs(
            'purchase-invoices',
            "purchase-{$purchase->id}.pdf"
        );

        $storedXmlPath = $validated['uploadInvoiceXmlFile']->storeAs(
            'purchase-invoices',
            "purchase-{$purchase->id}.xml"
        );

        try {
            $parsedSummary = $this->processInvoiceXml($purchase, $storedXmlPath);
            $parsedSummary['matched_items'] = $parsedSummary['matched_items'] - $parsedSummary['new_items'];
        } catch (Throwable $exception) {
            Log::error('PurchasesPage failed processing invoice XML', [
                'purchase_id' => $purchase->id,
                'invoice_path' => $storedPdfPath,
                'xml_path' => $storedXmlPath,
                'message' => $exception->getMessage(),
                'line' => $exception->getLine(),
                'file' => $exception->getFile(),
            ]);

            $this->dispatch(
                'notify',
                type: 'warning',
                content: 'Se respaldaron PDF y XML, pero no fue posible procesar el XML.',
                duration: 5000
            );

            $this->dispatch('close-upload-invoice-modal');
            $this->resetUploadForm();
            $this->dispatch('pg:eventRefresh-purchasesTable');

            return;
        }

        $this->dispatch(
            'notify',
            type: 'success',
            content: "Factura cargada. Detalles procesados: {$parsedSummary['matched_items']} encontrados, {$parsedSummary['new_items']} nuevos.",
            duration: 4000
        );

        $this->dispatch('close-upload-invoice-modal');
        $this->resetUploadForm();
        $this->dispatch('pg:eventRefresh-purchasesTable');
    }

    public function resetUploadForm()
    {
        $this->uploadSupplierId = null;
        $this->uploadInvoicePdfFile = null;
        $this->uploadInvoiceXmlFile = null;
        $this->resetValidation(['uploadSupplierId', 'uploadInvoicePdfFile', 'uploadInvoiceXmlFile']);
    }

    #[On('downloadPurchaseInvoicePdf')]
    public function downloadInvoice(int $purchaseId)
    {
        $purchase = MedicationPurchase::query()->findOrFail($purchaseId);

        $legacyPath = (string) $purchase->invoice;
        $currentPath = "purchase-invoices/purchase-{$purchase->id}.pdf";

        if (str_contains($legacyPath, '/') && Storage::disk('local')->exists($legacyPath)) {
            return Storage::disk('local')->download(
                $legacyPath,
                basename($legacyPath)
            );
        }

        if (Storage::disk('local')->exists($currentPath)) {
            $downloadName = ($purchase->invoice ?: ('compra-' . $purchase->id)) . '.pdf';

            return Storage::disk('local')->download(
                $currentPath,
                $downloadName
            );
        }

        if (blank($purchase->invoice)) {
            $this->dispatch(
                'notify',
                type: 'error',
                content: 'No se encontro la factura para esta compra.',
                duration: 4000
            );

            return null;
        }

        $this->dispatch(
            'notify',
            type: 'error',
            content: 'No se encontro el archivo PDF para esta compra.',
            duration: 4000
        );

        return null;
    }

    #[On('downloadPurchaseInvoiceXml')]
    public function downloadInvoiceXml(int $purchaseId)
    {
        $purchase = MedicationPurchase::query()->findOrFail($purchaseId);

        $xmlPath = "purchase-invoices/purchase-{$purchase->id}.xml";

        if (! Storage::disk('local')->exists($xmlPath)) {
            $this->dispatch(
                'notify',
                type: 'error',
                content: 'No se encontro el archivo XML para esta compra.',
                duration: 4000
            );

            return null;
        }

        $downloadName = ($purchase->invoice ?: ('compra-' . $purchase->id)) . '.xml';

        return Storage::disk('local')->download($xmlPath, $downloadName);
    }

    #[On('showPurchaseDetails')]
    public function showPurchaseDetails(int $purchaseId)
    {
        $this->selectedPurchase = MedicationPurchase::query()
            ->with([
                'supplier:id,name',
                'details.medication:id,ean_code,name,trade_name',
            ])
            ->findOrFail($purchaseId);

        $this->dispatch('open-purchase-details-modal');
    }

    public function resetPurchaseDetails()
    {
        $this->selectedPurchase = null;
    }

    private function processInvoiceXml(MedicationPurchase $purchase, string $xmlPath)
    {
        $userId = auth()->id();

        if (! $userId) {
            throw new \RuntimeException('No authenticated user found for medication movement creation.');
        }

        $fullPath = Storage::disk('local')->path($xmlPath);
        $xmlContents = file_get_contents($fullPath);

        if ($xmlContents === false) {
            throw new \RuntimeException('Unable to read XML contents.');
        }

        $xml = simplexml_load_string($xmlContents);

        if ($xml === false) {
            throw new \RuntimeException('Unable to parse XML document.');
        }

        $parsedInvoice = $this->extractInvoiceDataFromXml($xml);
        $medicationsByEan = $this->buildMedicationEanIndex();

        /*Log::info('PurchasesPage XML parse summary', [
            'purchase_id' => $purchase->id,
            'folio' => $parsedInvoice['folio'] ?? null,
            'subtotal' => $parsedInvoice['subtotal'] ?? null,
            'total' => $parsedInvoice['total'] ?? null,
            'parsed_items_count' => count($parsedInvoice['items'] ?? []),
            'ean_index_count' => count($medicationsByEan),
        ]);*/

        foreach (($parsedInvoice['items'] ?? []) as $idx => $parsedItem) {
            /*Log::info('PurchasesPage parsed XML item', [
                'purchase_id' => $purchase->id,
                'row' => $idx + 1,
                'ean' => $parsedItem['ean'] ?? null,
                'quantity' => $parsedItem['quantity'] ?? null,
                'importe' => $parsedItem['importe'] ?? null,
                'raw_chunk' => $parsedItem['raw_chunk'] ?? null,
            ]);*/
        }

        $matchedItems = 0;
        $newItems = 0;

        DB::transaction(function () use ($purchase, $parsedInvoice, &$medicationsByEan, &$matchedItems, &$newItems, $userId) {
            foreach ($parsedInvoice['items'] as $item) {
                $medication = $this->findMedicationByEan((string) $item['ean'], $medicationsByEan);

                if (! $medication) {
                    Log::warning('PurchasesPage medication not found by EAN (XML)', [
                        'purchase_id' => $purchase->id,
                        'ean' => $item['ean'] ?? null,
                        'normalized_ean' => $this->normalizeEan((string) ($item['ean'] ?? '')),
                        'quantity' => $item['quantity'] ?? null,
                        'importe' => $item['importe'] ?? null,
                        'raw_chunk' => $item['raw_chunk'] ?? null,
                    ]);

                    // TODO: aqui se agregara la logica alternativa para medicamentos no registrados.
                    $this->createNewMedication($item);

                    $medicationsByEan = $this->buildMedicationEanIndex();
                    $medication = $this->findMedicationByEan((string) $item['ean'], $medicationsByEan);

                    $newItems++;
                    // continue;
                }

                /*Log::info('PurchasesPage medication matched by EAN (XML)', [
                    'purchase_id' => $purchase->id,
                    'ean' => $item['ean'] ?? null,
                    'normalized_ean' => $this->normalizeEan((string) ($item['ean'] ?? '')),
                    'medication_id' => $medication->id,
                    'medication_ean_code' => $medication->ean_code,
                    'quantity' => $item['quantity'] ?? null,
                    'importe' => $item['importe'] ?? null,
                ]);*/

                try {
                    $quantity = (int) $item['quantity'];
                    $price = (float) $item['importe'];

                    MedicationPurchaseDetail::create([
                        'medication_purchase_id' => $purchase->id,
                        'medication_id' => $medication->id,
                        'requested_quantity' => $quantity,
                        'received_quantity' => $quantity,
                        'price' => $price,
                    ]);

                    MedicationMovement::create([
                        'medication_id' => $medication->id,
                        'type' => MedicationMovementType::IN->value,
                        'adjustment' => false,
                        'adjustment_comment' => null,
                        'quantity' => $quantity,
                        'reference' => 'Compra #' . $purchase->id . ' - Factura: ' . ($parsedInvoice['folio'] ?: ('SIN-FOLIO-' . $purchase->id)),
                        'prescription_id' => null,
                        'medication_purchase_id' => $purchase->id,
                        'user_id' => (int) $userId,
                    ]);

                    $matchedItems++;
                } catch (Throwable $rowException) {
                    Log::error('PurchasesPage failed persisting parsed XML row', [
                        'purchase_id' => $purchase->id,
                        'ean' => $item['ean'] ?? null,
                        'normalized_ean' => $this->normalizeEan((string) ($item['ean'] ?? '')),
                        'medication_id' => $medication->id,
                        'quantity' => $item['quantity'] ?? null,
                        'importe' => $item['importe'] ?? null,
                        'message' => $rowException->getMessage(),
                        'line' => $rowException->getLine(),
                        'file' => $rowException->getFile(),
                    ]);

                    $newItems++;
                    continue;
                }
            }

            $status = MedicationPurchasesStatus::Received->value;

            $purchase->update([
                'invoice' => $parsedInvoice['folio'] ?: ('SIN-FOLIO-' . $purchase->id),
                'subtotal' => (float) $parsedInvoice['subtotal'],
                'total' => (float) $parsedInvoice['total'],
                'status' => $status,
            ]);
        });

        return [
            'matched_items' => $matchedItems,
            'new_items' => $newItems,
            'subtotal' => (float) $parsedInvoice['subtotal'],
            'total' => (float) $parsedInvoice['total'],
        ];
    }

    private function extractInvoiceDataFromXml(\SimpleXMLElement $xml)
    {
        $attributes = $xml->attributes();

        $folio = trim((string) ($attributes['Folio'] ?? ''));
        $subtotal = $this->toFloatNumber((string) ($attributes['SubTotal'] ?? '0'));
        $total = $this->toFloatNumber((string) ($attributes['Total'] ?? '0'));

        $items = $this->extractInvoiceItemsFromXml($xml);

        return [
            'folio' => $folio !== '' ? $folio : null,
            'subtotal' => $subtotal,
            'total' => $total,
            'items' => $items,
        ];
    }

    private function extractInvoiceItemsFromXml(\SimpleXMLElement $xml)
    {
        $items = [];
        $seen = [];

        $namespaces = $xml->getDocNamespaces(true);
        if (isset($namespaces['cfdi'])) {
            $xml->registerXPathNamespace('cfdi', $namespaces['cfdi']);
            $concepts = $xml->xpath('//cfdi:Concepto') ?: [];
        } else {
            $concepts = $xml->xpath('//Concepto') ?: [];
        }

        foreach ($concepts as $concept) {
            $attributes = $concept->attributes();

            $rawIdentifier = (string) ($attributes['NoIdentificacion'] ?? '');
            $description = (string) ($attributes['Descripcion'] ?? '');

            $ean = $this->normalizeEan($rawIdentifier);
            if ($ean === '' && preg_match('/\b(\d{13})\b/', $description, $eanMatch)) {
                $ean = $eanMatch[1];
            }

            $quantity = (int) round($this->toFloatNumber((string) ($attributes['Cantidad'] ?? '0')));
            $importe = $this->toFloatNumber((string) ($attributes['Importe'] ?? '0'));

            if ($ean === '' || $quantity <= 0 || $importe <= 0) {
                continue;
            }

            $item = [
                'ean' => $ean,
                'description' => $description,
                'quantity' => $quantity,
                'importe' => $importe,
                'raw_chunk' => trim($rawIdentifier . ' ' . $description),
            ];

            $key = implode('|', [
                $item['ean'],
                $item['description'],
                (string) $item['quantity'],
                number_format((float) $item['importe'], 2, '.', ''),
            ]);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $items[] = $item;
        }

        return $items;
    }

    private function createNewMedication(array $item)
    {
        return Medication::create([
            'code' => '',
            'ean_code' => $this->normalizeEan((string) ($item['ean'] ?? '')),
            'name' => $item['description'] ?? 'Medicamento desconocido',
            'trade_name' => '',
            'active_substance' => '',
            'lab' => '',
            'packaging' => '',
            'price_public' => round($item['importe'] / ($item['quantity'] ?? 1), 2),
            'price_members' => 0.0,
            'status' => 'Active',
        ]);
    }

    private function buildMedicationEanIndex()
    {
        $index = [];

        $medications = Medication::query()
            ->whereNotNull('ean_code')
            ->get(['id', 'ean_code']);

        foreach ($medications as $medication) {
            $normalized = $this->normalizeEan((string) $medication->ean_code);

            if ($normalized === '') {
                continue;
            }

            if (! isset($index[$normalized])) {
                $index[$normalized] = $medication;
            }

            $withoutLeadingZeros = ltrim($normalized, '0');
            if ($withoutLeadingZeros !== '' && ! isset($index[$withoutLeadingZeros])) {
                $index[$withoutLeadingZeros] = $medication;
            }
        }

        return $index;
    }

    private function findMedicationByEan(string $ean, array $medicationsByEan)
    {
        $normalized = $this->normalizeEan($ean);

        if ($normalized === '') {
            return null;
        }

        if (isset($medicationsByEan[$normalized])) {
            return $medicationsByEan[$normalized];
        }

        $withoutLeadingZeros = ltrim($normalized, '0');

        if ($withoutLeadingZeros !== '' && isset($medicationsByEan[$withoutLeadingZeros])) {
            return $medicationsByEan[$withoutLeadingZeros];
        }

        return null;
    }

    private function normalizeEan(string $ean)
    {
        return preg_replace('/\D+/', '', trim($ean)) ?? '';
    }

    private function toFloatNumber(string $value)
    {
        $clean = str_replace(['$', ' '], '', trim($value));

        if (str_contains($clean, ',') && str_contains($clean, '.')) {
            $lastComma = strrpos($clean, ',');
            $lastDot = strrpos($clean, '.');

            if ($lastComma > $lastDot) {
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            } else {
                $clean = str_replace(',', '', $clean);
            }
        } elseif (str_contains($clean, ',')) {
            $clean = str_replace(',', '.', $clean);
        }

        return is_numeric($clean) ? (float) $clean : 0.0;
    }
}
