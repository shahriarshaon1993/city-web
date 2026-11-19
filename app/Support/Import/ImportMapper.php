<?php

namespace App\Support\Import;

/**
 * Column mapping of the wizard (ADM-IO-001): which column of the file feeds which field. A mapping is
 * `field key → column index`, null when the field is left out.
 */
final class ImportMapper
{
    /**
     * Matches each field to the first column whose header names it; a column is used once.
     *
     * @param  list<string>  $headers
     * @return array<string, int|null>
     */
    public static function auto(ImportDefinition $definition, array $headers): array
    {
        $mapping = [];
        $taken = [];

        foreach ($definition->fields() as $field) {
            $mapping[$field->key] = null;

            foreach ($headers as $index => $header) {
                if (! isset($taken[$index]) && $field->matches($header)) {
                    $mapping[$field->key] = $index;
                    $taken[$index] = true;

                    break;
                }
            }
        }

        return $mapping;
    }

    /**
     * Keeps only known fields and columns that exist; anything else is dropped.
     *
     * @param  array<string, mixed>  $mapping
     * @return array<string, int|null>
     */
    public static function clean(ImportDefinition $definition, array $mapping, int $columnCount): array
    {
        $clean = [];

        foreach ($definition->fields() as $field) {
            $value = $mapping[$field->key] ?? null;
            $index = is_numeric($value) ? (int) $value : null;
            $clean[$field->key] = $index !== null && $index >= 0 && $index < $columnCount ? $index : null;
        }

        return $clean;
    }

    /**
     * The row as the definition reads it: field key → trimmed text ('' for an unmapped field).
     *
     * @param  list<string>  $cells
     * @param  array<string, int|null>  $mapping
     * @return array<string, string>
     */
    public static function row(array $cells, array $mapping): array
    {
        $row = [];

        foreach ($mapping as $key => $index) {
            $row[$key] = $index === null ? '' : ($cells[$index] ?? '');
        }

        return $row;
    }

    /**
     * Required fields that no column feeds.
     *
     * @param  array<string, int|null>  $mapping
     * @return list<ImportField>
     */
    public static function missingRequired(ImportDefinition $definition, array $mapping): array
    {
        return array_values(array_filter(
            $definition->fields(),
            fn (ImportField $field): bool => $field->required && ($mapping[$field->key] ?? null) === null,
        ));
    }
}
