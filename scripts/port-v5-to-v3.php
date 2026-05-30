<?php

declare(strict_types=1);

/**
 * One-shot porter: copy the V5 resource classes to V3, applying the Filament
 * v5 -> v3 API deltas. Unlike v4 (whose namespaces match v5), v3 predates the
 * Schemas unification and the Actions consolidation, and uses string icons.
 *
 * Transformations applied to each V5 resource:
 *   - \V5 -> \V3 (namespace + use statements)
 *   - form container: Filament\Schemas\Schema -> Filament\Forms\Form
 *     (signature form(Schema $schema): Schema -> form(Form $form): Form,
 *      return $schema -> return $form, top-level ->components([ -> ->schema([)
 *   - layout components: Filament\Schemas\Components\{Section,Tabs,Tabs\Tab}
 *     -> Filament\Forms\Components\{...}
 *   - table actions: Filament\Actions\{Action,EditAction,DeleteAction,BulkAction,
 *     BulkActionGroup,DeleteBulkAction} -> Filament\Tables\Actions\{...}
 *     (every action in these resources lives inside table(), so all map to the
 *      table namespace; page header actions live in Pages/ and keep
 *      Filament\Actions\*, which v3 also uses)
 *   - icons: Heroicon enum -> v3 string icons; drop the Heroicon import
 *   - navigation property types: union enum types -> ?string
 *
 * Action modal forms stay ->form([ (v3, like v5 — only v4 uses ->schema([),
 * so V3 derives cleanly from V5 with no modal-form change.
 *
 * Run: php scripts/port-v5-to-v3.php
 * Pages are generated separately by gen-filament-pages.php V3.
 */
$srcRoot = __DIR__.'/../src/Filament/V5/Resources';
$dstRoot = __DIR__.'/../src/Filament/V3/Resources';

if (! is_dir($dstRoot)) {
    mkdir($dstRoot, 0777, true);
}

/** @var array<string, string> Heroicon enum case => v3 string icon. */
$iconMap = [
    'Heroicon::ArrowUturnLeft' => "'heroicon-m-arrow-uturn-left'",
    'Heroicon::Check' => "'heroicon-m-check'",
    'Heroicon::XMark' => "'heroicon-m-x-mark'",
    'Heroicon::OutlinedBanknotes' => "'heroicon-o-banknotes'",
    'Heroicon::OutlinedCalendarDays' => "'heroicon-o-calendar-days'",
    'Heroicon::OutlinedDocumentCheck' => "'heroicon-o-document-check'",
    'Heroicon::OutlinedInboxArrowDown' => "'heroicon-o-inbox-arrow-down'",
    'Heroicon::OutlinedQueueList' => "'heroicon-o-queue-list'",
    'Heroicon::OutlinedReceiptPercent' => "'heroicon-o-receipt-percent'",
    'Heroicon::OutlinedShoppingCart' => "'heroicon-o-shopping-cart'",
    'Heroicon::OutlinedTicket' => "'heroicon-o-ticket'",
];

/** @var array<string, string> Action use-statement remaps (v5 -> v3 table ns). */
$actionUseMap = [
    'use Filament\\Actions\\Action;' => 'use Filament\\Tables\\Actions\\Action;',
    'use Filament\\Actions\\BulkAction;' => 'use Filament\\Tables\\Actions\\BulkAction;',
    'use Filament\\Actions\\BulkActionGroup;' => 'use Filament\\Tables\\Actions\\BulkActionGroup;',
    'use Filament\\Actions\\DeleteAction;' => 'use Filament\\Tables\\Actions\\DeleteAction;',
    'use Filament\\Actions\\DeleteBulkAction;' => 'use Filament\\Tables\\Actions\\DeleteBulkAction;',
    'use Filament\\Actions\\EditAction;' => 'use Filament\\Tables\\Actions\\EditAction;',
];

/** @var array<string, string> Layout component use-statement remaps. */
$layoutUseMap = [
    'use Filament\\Schemas\\Components\\Section;' => 'use Filament\\Forms\\Components\\Section;',
    'use Filament\\Schemas\\Components\\Tabs;' => 'use Filament\\Forms\\Components\\Tabs;',
    'use Filament\\Schemas\\Components\\Tabs\\Tab;' => 'use Filament\\Forms\\Components\\Tabs\\Tab;',
];

foreach (glob($srcRoot.'/*.php') ?: [] as $file) {
    $code = file_get_contents($file);
    if ($code === false) {
        continue;
    }

    // Namespace segment.
    $code = str_replace('\\V5', '\\V3', $code);

    // Form container + signature + body.
    $code = str_replace('use Filament\\Schemas\\Schema;', 'use Filament\\Forms\\Form;', $code);
    $code = str_replace('public static function form(Schema $schema): Schema', 'public static function form(Form $form): Form', $code);
    $code = str_replace('return $schema', 'return $form', $code);
    $code = str_replace('->components([])', '->schema([])', $code); // WaitlistResource empty form
    $code = str_replace("->components([\n", "->schema([\n", $code);

    // Layout component imports.
    $code = strtr($code, $layoutUseMap);

    // Table action imports.
    $code = strtr($code, $actionUseMap);

    // Drop the Heroicon import line, then map icon enum cases to strings.
    $code = preg_replace('/^use Filament\\\\Support\\\\Icons\\\\Heroicon;\n/m', '', $code) ?? $code;
    $code = strtr($code, $iconMap);

    // Navigation property types.
    $code = str_replace('protected static string|\\BackedEnum|null $navigationIcon', 'protected static ?string $navigationIcon', $code);
    $code = str_replace('protected static string|\\UnitEnum|null $navigationGroup', 'protected static ?string $navigationGroup', $code);

    $dst = $dstRoot.'/'.basename($file);
    file_put_contents($dst, $code);
    echo 'wrote V3/Resources/'.basename($file)."\n";
}
