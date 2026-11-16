<?php

// php artisan tinker --execute 'require "docs/checks/12.2-menus.php";'
// Runs the 12.2 menu paths inside a transaction that is rolled back, and prints "expect …" lines.

use App\Actions\Menus\SaveMenu;
use App\Http\Requests\Admin\SaveMenuRequest;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\User;
use App\Queries\Admin\MenuEditor;
use App\Support\Menus\LinkTargets;
use App\Support\Menus\LinkType;
use App\Support\Menus\MenuKey;
use App\Support\Menus\MenuStore;
use App\Support\Menus\PublicMenus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

$validate = function (string $key, array $payload): array {
    $menu = Menu::query()->where('key', $key)->firstOrFail();
    $request = new SaveMenuRequest;
    $request->replace($payload);
    $request->setRouteResolver(fn () => new class($menu)
    {
        public function __construct(private Menu $menu) {}

        public function parameter(string $name, $default = null)
        {
            return $this->menu;
        }
    });

    $validator = Validator::make($payload, $request->rules(), $request->messages(), $request->attributes());

    foreach ($request->after() as $after) {
        $validator->after($after);
    }

    return $validator->errors()->toArray();
};

$item = fn (string $label, array $extra = []): array => [
    'id' => null, 'label' => $label, 'link_type' => 'url', 'linkable_id' => null, 'url' => '/x', 'anchor' => null,
    'new_tab' => false, 'icon' => null, 'description' => null, 'is_mega' => false, 'mega_columns' => [], 'children' => [],
    ...$extra,
];

DB::beginTransaction();

try {
    MenuStore::flushMemo();

    $super = User::query()->where('email', 'super-admin@gemcon.test')->first() ?? User::query()->firstOrFail();
    $save = app(SaveMenu::class);
    $legal = Menu::query()->where('key', 'legal')->firstOrFail();
    $header = Menu::query()->where('key', 'header')->firstOrFail();

    // --- Seeded menus --------------------------------------------------------------------------------
    echo 'expect 7 menus: '.Menu::query()->count().PHP_EOL;
    echo 'expect header 6 top-level items: '.count(PublicMenus::all()['header']).PHP_EOL;
    echo 'expect Projects mega with 4 columns: '.count(PublicMenus::all()['header'][0]['mega'] ?? []).PHP_EOL;
    echo 'expect About has 4 sub-items: '.count(PublicMenus::all()['header'][4]['children']).PHP_EOL;
    echo 'expect footer headings: '.implode(' | ', array_column(PublicMenus::footerColumns(), 'heading')).PHP_EOL;

    // --- Save: reorder, nest, rename, remove ---------------------------------------------------------
    $tree = MenuStore::fresh($legal);
    $tree = array_map(fn (array $row): array => [...$row, 'mega_columns' => []], $tree);
    $result = $save->handle($super, $legal, 'Legal', array_reverse($tree));
    echo 'expect reorder = 2 updated (sort_order): '.json_encode($result).PHP_EOL;
    echo 'expect Terms first publicly: '.PublicMenus::all()['legal'][0]['label'].PHP_EOL;

    $result = $save->handle($super, $legal, 'Legal', array_reverse(MenuStore::fresh($legal)));
    echo 'expect no change after saving same tree back reversed? (2 updated again): '.json_encode($result).PHP_EOL;

    $same = $save->handle($super, $legal, 'Legal', MenuStore::fresh($legal));
    echo 'expect saving unchanged = nothing: '.json_encode($same).' changed='.var_export(SaveMenu::changed($same), true).PHP_EOL;

    $one = MenuStore::fresh($legal);
    $result = $save->handle($super, $legal, 'Legal links', [$one[0]]);
    echo 'expect 1 removed + renamed: '.json_encode($result).PHP_EOL;
    echo 'expect public legal has 1 item after cache bump: '.count(PublicMenus::all()['legal']).PHP_EOL;

    // --- Entity links: stored by id, hidden when unpublished ------------------------------------------
    $scheduled = null;

    foreach (LinkTargets::search(LinkType::Post, limit: 100) as $target) {
        if (! $target->published) {
            $scheduled = $target;
        }
    }

    $published = LinkTargets::search(LinkType::Post, limit: 100)[count(LinkTargets::search(LinkType::Post, limit: 100)) - 1];
    echo 'expect a scheduled post exists: '.($scheduled?->label ?? 'NONE').PHP_EOL;

    $save->handle($super, $legal, 'Legal', [
        $item('Scheduled', ['link_type' => 'post', 'linkable_id' => $scheduled?->id, 'url' => null]),
        $item('Gone', ['link_type' => 'project', 'linkable_id' => 9999, 'url' => null]),
        $item('Visible', ['link_type' => 'post', 'linkable_id' => $published->id, 'url' => null]),
    ]);
    echo 'expect only "Visible" on the public site: '.implode(',', array_column(PublicMenus::all()['legal'], 'label')).PHP_EOL;
    $described = MenuEditor::menu($legal->fresh())['items'];
    echo 'expect admin statuses unpublished,missing,ok: '.implode(',', array_column(array_column($described, 'link'), 'status')).PHP_EOL;
    echo 'expect tab flags 2 hidden links in legal: '.collect(MenuEditor::tabs())->firstWhere('key', 'legal')['hidden'].PHP_EOL;

    // --- Validation -----------------------------------------------------------------------------------
    $errors = $validate('legal', ['name' => 'Legal', 'items' => [$item(''), $item('Ok', ['url' => 'ftp://x']), $item('Child', ['children' => [$item('c')]])]]);
    echo 'expect label required: '.($errors['items.0.label'][0] ?? 'none').PHP_EOL;
    echo 'expect url pattern: '.($errors['items.1.url'][0] ?? 'none').PHP_EOL;
    $oneLevel = $validate('legal', ['name' => 'Legal', 'items' => [$item('Child', ['children' => [$item('c')]])]]);
    echo 'expect one-level menu refuses children: '.($oneLevel['items.0.children'][0] ?? 'none').PHP_EOL;

    $tooMany = array_map(fn (int $i): array => $item("Item {$i}"), range(1, 7));
    echo 'expect legal max 6 items: '.(($validate('legal', ['name' => 'Legal', 'items' => $tooMany]))['items'][0] ?? 'none').PHP_EOL;

    $deep = $item('Top', ['children' => [$item('Mid', ['children' => [$item('Deep')]])]]);
    echo 'expect header refuses 3 levels: '.(($validate('header', ['name' => 'Header', 'items' => [$deep]]))['items.0.children.0.children'][0] ?? 'none').PHP_EOL;

    $mega = $item('Mega', ['is_mega' => true, 'children' => [$item('Child')], 'mega_columns' => []]);
    $errors = $validate('header', ['name' => 'Header', 'items' => [$mega]]);
    echo 'expect mega + children refused: '.($errors['items.0.is_mega'][0] ?? 'none').PHP_EOL;
    echo 'expect mega without columns refused: '.($errors['items.0.mega_columns'][0] ?? 'none').PHP_EOL;

    $errors = $validate('legal', ['name' => 'Legal', 'items' => [$item('Mega', ['is_mega' => true, 'mega_columns' => [['kind' => 'segments', 'heading' => null, 'items' => []]]])]]);
    echo 'expect mega only in header: '.($errors['items.0.is_mega'][0] ?? 'none').PHP_EOL;

    $errors = $validate('header', ['name' => 'Header', 'items' => [$item('Link', ['link_type' => 'page', 'linkable_id' => 9999, 'url' => null])]]);
    echo 'expect missing page refused: '.($errors['items.0.linkable_id'][0] ?? 'none').PHP_EOL;

    $errors = $validate('header', ['name' => 'Header', 'items' => [$item('Link', ['link_type' => 'anchor', 'url' => '/about', 'anchor' => '9bad'])]]);
    echo 'expect anchor pattern: '.($errors['items.0.anchor'][0] ?? 'none').PHP_EOL;

    $ok = $validate('header', ['name' => 'Header', 'items' => [$item('Home', ['url' => '/']), $item('About', ['url' => '/about', 'children' => [$item('Team', ['url' => '/about#team'])]])]]);
    echo 'expect a valid two-level tree has no errors: '.json_encode($ok).PHP_EOL;

    // --- Mega columns with link lists ------------------------------------------------------------------
    $links = [['label' => 'One', 'link_type' => 'url', 'linkable_id' => null, 'url' => '/one', 'anchor' => null, 'new_tab' => false]];
    $save->handle($super, $header, 'Header', [$item('Mega', ['is_mega' => true, 'url' => '/projects', 'mega_columns' => [
        ['kind' => 'links', 'heading' => 'Custom', 'items' => $links],
        ['kind' => 'locations', 'heading' => null, 'items' => []],
    ]])]);
    $columns = PublicMenus::all()['header'][0]['mega'];
    echo 'expect 2 columns links+locations: '.implode(',', array_column($columns, 'kind')).PHP_EOL;
    echo 'expect locations column has 8 areas: '.count($columns[1]['items']).PHP_EOL;

    echo 'expect activity entries logged (>= 5): '.DB::table('activity_log')->where('log_name', 'menus')->count().PHP_EOL;
} finally {
    DB::rollBack();
    MenuStore::flushMemo();
}

echo 'expect rollback restored 6 header items: '.MenuItem::query()->where('menu_id', Menu::query()->where('key', MenuKey::Header->value)->value('id'))->whereNull('parent_id')->count().PHP_EOL;
