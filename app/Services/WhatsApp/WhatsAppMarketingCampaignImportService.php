<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\UploadedFile;
use OpenSpout\Reader\Common\Creator\ReaderFactory;

class WhatsAppMarketingCampaignImportService
{
    private const MAX_ROWS = 5000;

    /**
     * @return array{columns: array<int, array{key: string, label: string}>, rows: array<int, array{row_number: int, data: array<string, string>}>, errors: array<int, string>, skipped_header: bool}
     */
    public function read(UploadedFile $file, int $limit = self::MAX_ROWS): array
    {
        $reader = ReaderFactory::createFromFile($file->getClientOriginalName() ?: $file->getRealPath());
        $reader->open($file->getRealPath());

        $rows = [];
        $errors = [];
        $firstRow = null;
        $rowNumber = 0;
        $skippedHeader = false;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rowNumber++;
                $values = $this->normalizeValues($row->toArray());

                if ($this->isEmptyRow($values)) {
                    continue;
                }

                if ($firstRow === null) {
                    $firstRow = $values;

                    if ($this->looksLikeHeader($values)) {
                        $skippedHeader = true;
                        continue;
                    }
                }

                if (count($rows) >= $limit) {
                    $errors[] = "Maximo {$limit} destinatarios por campaña.";
                    break 2;
                }

                $rows[] = [
                    'row_number' => $rowNumber,
                    'data' => $this->rowData($values),
                ];
            }

            break;
        }

        $reader->close();

        return [
            'columns' => $this->columns($firstRow ?? []),
            'rows' => $rows,
            'errors' => $errors,
            'skipped_header' => $skippedHeader,
        ];
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    private function normalizeValues(array $values): array
    {
        return array_map(function (mixed $value): string {
            if ($value instanceof \DateTimeInterface) {
                return $value->format('d/m/Y');
            }

            return trim((string) $value);
        }, $values);
    }

    /**
     * @param  array<int, string>  $values
     * @return array<string, string>
     */
    private function rowData(array $values): array
    {
        $data = [];

        foreach ($values as $index => $value) {
            $data[$this->columnKey($index)] = $value;
        }

        return $data;
    }

    /**
     * @param  array<int, string>  $values
     * @return array<int, array{key: string, label: string}>
     */
    private function columns(array $values): array
    {
        $count = max(count($values), 2);
        $columns = [];

        for ($index = 0; $index < $count; $index++) {
            $key = $this->columnKey($index);
            $columns[] = [
                'key' => $key,
                'label' => match ($key) {
                    'A' => 'Columna A - Nombre',
                    'B' => 'Columna B - WhatsApp',
                    default => "Columna {$key}",
                },
            ];
        }

        return $columns;
    }

    private function columnKey(int $index): string
    {
        $letters = '';
        $number = $index + 1;

        while ($number > 0) {
            $mod = ($number - 1) % 26;
            $letters = chr(65 + $mod).$letters;
            $number = intdiv($number - $mod, 26);
        }

        return $letters;
    }

    /**
     * @param  array<int, string>  $values
     */
    private function isEmptyRow(array $values): bool
    {
        return collect($values)->every(fn (string $value): bool => $value === '');
    }

    /**
     * @param  array<int, string>  $values
     */
    private function looksLikeHeader(array $values): bool
    {
        $a = str($values[0] ?? '')->lower()->ascii()->replace([' ', '_', '-'], '')->value();
        $b = str($values[1] ?? '')->lower()->ascii()->replace([' ', '_', '-'], '')->value();

        return in_array($a, ['nombre', 'cliente', 'customer', 'customername', 'name'], true)
            && in_array($b, ['telefono', 'celular', 'whatsapp', 'phone', 'numero', 'numerowhatsapp'], true);
    }
}
