<?php

namespace App\Support\Import;

use App\Models\User;

/**
 * What one import type knows: its columns, how to judge a row without writing anything (the preview
 * and the run use the same `check`) and how to write it through the Actions the admin screens use.
 * One instance serves one run, so a definition may keep state between rows (a prepared rule set).
 */
abstract class ImportDefinition
{
    /**
     * @return list<ImportField>
     */
    abstract public function fields(): array;

    /**
     * Judges one row with no side effects.
     *
     * @param  array<string, string>  $row  field key → the cell's trimmed text; unmapped fields are ''
     */
    abstract public function check(array $row): RowResult;

    /**
     * Writes a row that `check` accepted.
     *
     * @param  array<string, mixed>  $options  the run's options
     */
    abstract protected function write(User $actor, RowResult $checked, array $options): RowResult;

    /**
     * @param  array<string, string>  $row
     * @param  array<string, mixed>  $options
     */
    final public function commit(User $actor, array $row, array $options): RowResult
    {
        $checked = $this->check($row);

        return $checked->writes() ? $this->write($actor, $checked, $options) : $checked;
    }

    /**
     * Extra confirmations the person must give before the run starts (a consent statement, say).
     *
     * @return list<array{key: string, label: string, required: bool}>
     */
    public function options(): array
    {
        return [];
    }

    /** One plain sentence under the type's name on the first screen and in the mapping step. */
    abstract public function help(): string;

    /**
     * @return array<string, string> field key → example cell
     */
    final public function example(): array
    {
        $example = [];

        foreach ($this->fields() as $field) {
            $example[$field->key] = $field->example;
        }

        return $example;
    }
}
