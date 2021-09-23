<?php
/**
 * Scan chunks, templates and content for HTML classes
 *
 * @package tailwindhelper
 * @subpackage snippet
 *
 * @var modX $modx
 * @var array $scriptProperties
 */

use TreehillStudio\TailwindHelper\Snippets\TailwindScanClasses;

$corePath = $modx->getOption('tailwindhelper.core_path', null, $modx->getOption('core_path') . 'components/tailwindhelper/');
/** @var TailwindHelper $tailwindhelper */
$tailwindhelper = $modx->getService('tailwindhelper', 'TailwindHelper', $corePath . 'model/tailwindhelper/', [
    'core_path' => $corePath
]);

$snippet = new TailwindScanClasses($modx, $scriptProperties);
if ($snippet instanceof TreehillStudio\TailwindHelper\Snippets\TailwindScanClasses) {
    return $snippet->execute();
}
return 'TreehillStudio\TailwindHelper\Snippets\TailwindScanClasses class not found';