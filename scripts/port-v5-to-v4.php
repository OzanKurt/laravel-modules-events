<?php

declare(strict_types=1);

/**
 * One-shot porter: copy the V5 resource classes to V4, applying the only
 * three API deltas between Filament v5 and v4 (the namespaces are identical):
 *
 *   1. namespace/use segment  \V5  ->  \V4
 *   2. table row actions   ->actions([     ->  ->recordActions([
 *   3. table bulk actions  ->bulkActions([ ->  ->toolbarActions([
 *   4. action modal forms  ->form([        ->  ->schema([
 *
 * (4) is safe because a resource's form method is `form(Schema $schema)`, never
 * `form([`, so the only `->form([` occurrences are action modal definitions,
 * which v4 declares with ->schema([.
 *
 * Run: php scripts/port-v5-to-v4.php
 * Pages are generated separately by gen-filament-pages.php V4.
 */
$srcRoot = __DIR__.'/../src/Filament/V5/Resources';
$dstRoot = __DIR__.'/../src/Filament/V4/Resources';

if (! is_dir($dstRoot)) {
    mkdir($dstRoot, 0777, true);
}

$resources = glob($srcRoot.'/*.php') ?: [];

foreach ($resources as $file) {
    $code = file_get_contents($file);
    if ($code === false) {
        continue;
    }

    $code = str_replace('\\V5', '\\V4', $code);
    $code = str_replace('->actions([', '->recordActions([', $code);
    $code = str_replace('->bulkActions([', '->toolbarActions([', $code);
    $code = str_replace('->form([', '->schema([', $code);

    $dst = $dstRoot.'/'.basename($file);
    file_put_contents($dst, $code);
    echo 'wrote V4/Resources/'.basename($file)."\n";
}
