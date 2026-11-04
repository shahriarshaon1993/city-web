<?php

namespace App\Http\Requests\Admin;

use App\Actions\Tables\ExportTable;
use App\Queries\Admin\AdminTables;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ADM-TBL-012: the export URL carries the table's own state (search, filter, sort) plus the
 * format and the visible columns in order.
 */
class ExportTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        $table = AdminTables::find((string) $this->route('table'));

        return $table !== null && $table->isVisibleTo($this->user());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'format' => ['required', Rule::in(ExportTable::FORMATS)],
            'columns' => ['nullable', 'string', 'max:300'],
        ];
    }

    /**
     * @return list<string>
     */
    public function columnIds(): array
    {
        return array_values(array_filter(explode(',', (string) $this->input('columns'))));
    }
}
