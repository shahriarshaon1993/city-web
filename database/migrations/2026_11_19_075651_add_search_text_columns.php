<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FR-SRCH-010: Scout's database engine runs one MySQL MATCH … AGAINST over the columns of the model's own
 * table, so each searchable table gets a plain-text `search_text` column (the secondary text: tagline,
 * area names, overview / excerpt, body / meta description) beside its title, and one FULLTEXT index over both.
 * `search_text` is kept by `App\Actions\Search\RefreshSearchText`; `php artisan search:refresh` rebuilds it.
 */
return new class extends Migration
{
    /** @var array<string, string> table => title column */
    private const array TABLES = ['projects' => 'name', 'posts' => 'title', 'pages' => 'title'];

    public function up(): void
    {
        foreach (self::TABLES as $table => $title) {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $title): void {
                $blueprint->longText('search_text')->nullable();
                $blueprint->fullText([$title, 'search_text'], "{$table}_search_fulltext");
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table => $title) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->dropFullText("{$table}_search_fulltext");
                $blueprint->dropColumn('search_text');
            });
        }
    }
};
