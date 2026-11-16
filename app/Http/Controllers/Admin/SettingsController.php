<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Settings\UpdateSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Http\Resources\Admin\MediaAssetResource;
use App\Models\MediaAsset;
use App\Models\Setting;
use App\Support\Settings\Field;
use App\Support\Settings\Settings;
use App\Support\Settings\SettingsGroup;
use App\Support\Settings\SettingsRegistry;
use App\Support\Toasts\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ADM-SCR-15 Settings: one tab per group (spec/cms.md §8.2). The registry says what a tab holds and
 * the Policy who may open it; secrets are never sent back to the browser (ADM-LOG-003).
 */
class SettingsController extends Controller
{
    public function index(Request $request, ?string $group = null): Response|RedirectResponse
    {
        $tabs = SettingsRegistry::forUser($request->user());

        if ($group === null) {
            return to_route('admin.settings.index', ['group' => $tabs[0]->key ?? 'general']);
        }

        $definition = SettingsRegistry::find($group);

        abort_if($definition === null, 404);
        Gate::authorize('viewGroup', [Setting::class, $definition]);

        return Inertia::render('admin/Settings', [
            'tabs' => array_map(fn (SettingsGroup $tab): array => [
                'key' => $tab->key,
                'label' => $tab->label,
                'nav' => $tab->nav,
                'href' => route('admin.settings.index', ['group' => $tab->key]),
            ], $tabs),
            'group' => [
                'key' => $definition->key,
                'label' => $definition->label,
                'description' => $definition->description,
                'sections' => $definition->schema(),
            ],
            'values' => $this->values($definition),
            'secrets' => $this->secrets($definition),
            'assets' => $this->assets($request, $definition),
            'lastSaved' => Settings::lastSaved($definition->key),
            'breadcrumbs' => [
                ['title' => 'System', 'href' => null],
                ['title' => 'Settings', 'href' => null],
            ],
        ]);
    }

    public function update(UpdateSettingsRequest $request, UpdateSettings $updateSettings): RedirectResponse
    {
        $group = $request->group();

        $changed = $updateSettings->handle(
            $request->user(),
            $group,
            $request->validated('values') ?? [],
            $request->validated('clear') ?? [],
        );

        if ($changed === []) {
            Toast::info('No changes to save')->flash();

            return back();
        }

        Toast::success("{$group->label} settings saved")
            ->description('Changed: '.Str::limit(implode(', ', $changed), 90))
            ->flash();

        return back();
    }

    /**
     * What the form starts with (strings never null, secrets empty).
     *
     * @return array<string, mixed>
     */
    private function values(SettingsGroup $group): array
    {
        $values = Settings::group($group->key);
        $out = [];

        foreach ($group->fields() as $field) {
            $out[$field->key] = $field->clientValue($values[$field->key] ?? null);
        }

        return $out;
    }

    /**
     * Which secrets already hold a value, so the form can say "Set" without revealing it.
     *
     * @return array<string, bool>
     */
    private function secrets(SettingsGroup $group): array
    {
        $out = [];

        foreach ($group->fields() as $field) {
            if ($field->isSecret()) {
                $out[$field->key] = Settings::hasSecret($group->key, $field->key);
            }
        }

        return $out;
    }

    /**
     * The library items chosen in media fields, for the preview in each field.
     *
     * @return array<int, array<string, mixed>>
     */
    private function assets(Request $request, SettingsGroup $group): array
    {
        $values = Settings::group($group->key);
        $ids = [];

        foreach ($group->fields() as $field) {
            if ($field->type === Field::MEDIA && ($values[$field->key] ?? null) !== null) {
                $ids[] = (int) $values[$field->key];
            }
        }

        if ($ids === []) {
            return [];
        }

        return MediaAsset::query()->with('media')->whereIn('id', $ids)->get()
            ->mapWithKeys(fn (MediaAsset $asset): array => [$asset->id => (new MediaAssetResource($asset))->resolve($request)])
            ->all();
    }
}
