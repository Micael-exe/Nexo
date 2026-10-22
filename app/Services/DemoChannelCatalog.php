<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Catálogo FICTÍCIO para demonstração. Não representa a situação real de nenhum canal.
 * Substituir por trechos cadastrados e observações validadas quando o módulo municipal existir.
 */
final class DemoChannelCatalog
{
    public static function all(): array
    {
        return [
            ['id' => 'TR-004', 'name' => 'Canal do Centro', 'section' => 'Trecho 04', 'district' => 'Centro', 'status' => 'em_dia', 'length_m' => 480,
                'condition' => 'Inspecionado sem obstrução.', 'observed_at' => '2026-10-12', 'last_maintenance' => '2026-09-28', 'next_inspection' => '2026-11-12'],
            ['id' => 'TR-008', 'name' => 'Canal do Pina', 'section' => 'Trecho 08', 'district' => 'Pina', 'status' => 'atencao', 'length_m' => 350,
                'condition' => 'Acúmulo de resíduos em ponto isolado; nova verificação recomendada.', 'observed_at' => '2026-10-15', 'last_maintenance' => '2026-08-30', 'next_inspection' => '2026-10-29'],
            ['id' => 'TR-002', 'name' => 'Canal de Janga', 'section' => 'Trecho 02', 'district' => 'Janga', 'status' => 'em_dia', 'length_m' => 620,
                'condition' => 'Inspecionado sem obstrução.', 'observed_at' => '2026-10-09', 'last_maintenance' => '2026-10-01', 'next_inspection' => '2026-11-09'],
            ['id' => 'TR-006', 'name' => 'Canal de Abacaxi', 'section' => 'Trecho 06', 'district' => 'Abacaxi', 'status' => 'com_obstrucao', 'length_m' => 410,
                'condition' => 'Obstrução parcial confirmada por vegetação e resíduos; intervenção em avaliação.', 'observed_at' => '2026-10-18', 'last_maintenance' => null, 'next_inspection' => '2026-10-25'],
            ['id' => 'TR-011', 'name' => 'Canal de Maranguape', 'section' => 'Trecho 11', 'district' => 'Maranguape', 'status' => 'em_dia', 'length_m' => 530,
                'condition' => 'Inspecionado sem obstrução.', 'observed_at' => '2026-10-07', 'last_maintenance' => '2026-09-15', 'next_inspection' => '2026-11-07'],
        ];
    }

    /** Todos os termos precisam aparecer (sem diferenciar acentos ou caixa). */
    public static function search(string $term = '', string $status = ''): array
    {
        $terms = array_filter(explode(' ', self::normalize($term)));

        return array_values(array_filter(self::all(), function (array $channel) use ($terms, $status): bool {
            if ($status !== '' && $channel['status'] !== $status) {
                return false;
            }
            $haystack = self::normalize(implode(' ', [$channel['name'], $channel['section'], $channel['district'], $channel['id']]));
            foreach ($terms as $needle) {
                if (! str_contains($haystack, $needle)) {
                    return false;
                }
            }

            return true;
        }));
    }

    private static function normalize(string $value): string
    {
        return Str::lower(Str::ascii($value));
    }
}
