<?php

declare(strict_types=1);

/**
 * One-shot generator for the Filament resource Page classes across V3/V4/V5.
 *
 * Page classes are pure boilerplate (only the namespace + resource class
 * change), so generating them keeps the three parallel resource sets in sync
 * without hand-writing ~50 near-identical files. Run once per version:
 *
 *   php scripts/gen-filament-pages.php V5
 *
 * Pages use Filament\Actions\* in every version (CreateAction/DeleteAction),
 * so the bodies are identical; only the version segment of the namespace
 * differs. This script is committed for reproducibility but is not loaded at
 * runtime.
 */
$version = $argv[1] ?? null;

if (! in_array($version, ['V3', 'V4', 'V5'], true)) {
    fwrite(STDERR, "Usage: php scripts/gen-filament-pages.php <V3|V4|V5>\n");
    exit(1);
}

$base = __DIR__.'/../src/Filament/'.$version.'/Resources';
$ns = 'Kurt\\Modules\\Events\\Filament\\'.$version.'\\Resources';

/**
 * resource => [singular model word for page class names, list of page kinds].
 * Kinds: list, create, edit.
 */
$resources = [
    'EventResource' => ['Event', ['list', 'create', 'edit']],
    'TicketTypeResource' => ['TicketType', ['list', 'create', 'edit']],
    'OrderResource' => ['Order', ['list', 'edit']],
    'ApplicationResource' => ['Application', ['list', 'edit']],
    'DiscountCodeResource' => ['DiscountCode', ['list', 'create', 'edit']],
    'DocumentVerificationResource' => ['DocumentVerification', ['list', 'edit']],
    'RefundResource' => ['Refund', ['list', 'edit']],
    'WaitlistResource' => ['WaitlistEntry', ['list']],
];

// Plural list-page class names that don't follow naive +s pluralisation.
$listClassOverrides = [
    'EventResource' => 'ListEvents',
    'TicketTypeResource' => 'ListTicketTypes',
    'OrderResource' => 'ListOrders',
    'ApplicationResource' => 'ListApplications',
    'DiscountCodeResource' => 'ListDiscountCodes',
    'DocumentVerificationResource' => 'ListDocumentVerifications',
    'RefundResource' => 'ListRefunds',
    'WaitlistResource' => 'ListWaitlistEntries',
];

foreach ($resources as $resource => [$word, $kinds]) {
    $dir = $base.'/'.$resource.'/Pages';
    if (! is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $resourceFqcn = $ns.'\\'.$resource;
    $pagesNs = $ns.'\\'.$resource.'\\Pages';

    foreach ($kinds as $kind) {
        if ($kind === 'list') {
            $class = $listClassOverrides[$resource];
            $hasCreate = in_array('create', $kinds, true);
            $code = listPage($pagesNs, $class, $resource, $resourceFqcn, $hasCreate);
        } elseif ($kind === 'create') {
            $class = 'Create'.$word;
            $code = createPage($pagesNs, $class, $resource, $resourceFqcn);
        } else {
            $class = 'Edit'.$word;
            $code = editPage($pagesNs, $class, $resource, $resourceFqcn);
        }

        file_put_contents($dir.'/'.$class.'.php', $code);
        echo "wrote {$version}/{$resource}/Pages/{$class}.php\n";
    }
}

function header_block(string $pagesNs): string
{
    return "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$pagesNs};\n\n";
}

function listPage(string $pagesNs, string $class, string $resource, string $resourceFqcn, bool $hasCreate): string
{
    $h = header_block($pagesNs);

    if ($hasCreate) {
        return $h
            ."use Filament\\Actions\\Action;\n"
            ."use Filament\\Actions\\CreateAction;\n"
            ."use Filament\\Resources\\Pages\\ListRecords;\n"
            ."use {$resourceFqcn};\n\n"
            ."class {$class} extends ListRecords\n{\n"
            ."    protected static string \$resource = {$resource}::class;\n\n"
            ."    /**\n     * @return array<int, Action>\n     */\n"
            ."    protected function getHeaderActions(): array\n    {\n"
            ."        return [\n            CreateAction::make(),\n        ];\n    }\n}\n";
    }

    return $h
        ."use Filament\\Resources\\Pages\\ListRecords;\n"
        ."use {$resourceFqcn};\n\n"
        ."class {$class} extends ListRecords\n{\n"
        ."    protected static string \$resource = {$resource}::class;\n}\n";
}

function createPage(string $pagesNs, string $class, string $resource, string $resourceFqcn): string
{
    return header_block($pagesNs)
        ."use Filament\\Resources\\Pages\\CreateRecord;\n"
        ."use {$resourceFqcn};\n\n"
        ."class {$class} extends CreateRecord\n{\n"
        ."    protected static string \$resource = {$resource}::class;\n}\n";
}

function editPage(string $pagesNs, string $class, string $resource, string $resourceFqcn): string
{
    return header_block($pagesNs)
        ."use Filament\\Actions\\Action;\n"
        ."use Filament\\Actions\\DeleteAction;\n"
        ."use Filament\\Resources\\Pages\\EditRecord;\n"
        ."use {$resourceFqcn};\n\n"
        ."class {$class} extends EditRecord\n{\n"
        ."    protected static string \$resource = {$resource}::class;\n\n"
        ."    /**\n     * @return array<int, Action>\n     */\n"
        ."    protected function getHeaderActions(): array\n    {\n"
        ."        return [\n            DeleteAction::make(),\n        ];\n    }\n}\n";
}
