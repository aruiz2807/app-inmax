<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppMessageTemplate;

class WhatsAppTemplateDefinition
{
    /**
     * @return array{header_format: ?string, body_variables: int, header_variables: int, slots: array<int, array{component: string, position: int}>}
     */
    public function requirements(WhatsAppMessageTemplate $template): array
    {
        $headerFormat = null;
        $slots = [];
        $bodyVariables = 0;
        $headerVariables = 0;

        foreach ($template->components ?? [] as $component) {
            $type = strtolower((string) ($component['type'] ?? ''));

            if (! in_array($type, ['header', 'body'], true)) {
                continue;
            }

            if ($type === 'header') {
                $headerFormat = strtoupper((string) ($component['format'] ?? 'TEXT'));
            }

            preg_match_all('/{{\s*(\d+)\s*}}/', (string) ($component['text'] ?? ''), $matches);

            foreach (array_unique($matches[1] ?? []) as $position) {
                $slots[] = ['component' => $type, 'position' => (int) $position];

                if ($type === 'body') {
                    $bodyVariables++;
                } else {
                    $headerVariables++;
                }
            }
        }

        usort($slots, fn (array $left, array $right): int => [$left['component'], $left['position']] <=> [$right['component'], $right['position']]);

        return [
            'header_format' => $headerFormat,
            'body_variables' => $bodyVariables,
            'header_variables' => $headerVariables,
            'slots' => $slots,
        ];
    }

    public function headerMediaType(WhatsAppMessageTemplate $template): ?string
    {
        $format = $this->requirements($template)['header_format'];

        return match ($format) {
            'IMAGE' => 'image',
            'VIDEO' => 'video',
            'DOCUMENT' => 'document',
            default => null,
        };
    }
}
