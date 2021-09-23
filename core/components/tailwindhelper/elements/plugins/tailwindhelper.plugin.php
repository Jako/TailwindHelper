<?php
/**
 * TailwindHelper Plugin
 *
 * @package tailwindhelper
 * @subpackage plugin
 *
 * @var modX $modx
 * @var array $scriptProperties
 */

$className = 'TreehillStudio\TailwindHelper\Plugins\Events\\' . $modx->event->name;

$corePath = $modx->getOption('tailwindhelper.core_path', null, $modx->getOption('core_path') . 'components/tailwindhelper/');
/** @var TailwindHelper $tailwindhelper */
$tailwindhelper = $modx->getService('tailwindhelper', 'TailwindHelper', $corePath . 'model/tailwindhelper/', [
    'core_path' => $corePath
]);

if ($tailwindhelper) {
    if (class_exists($className)) {
        $handler = new $className($modx, $scriptProperties);
        if (get_class($handler) == $className) {
            $handler->run();
        } else {
            $modx->log(xPDO::LOG_LEVEL_ERROR, $className . ' could not be initialized!', '', 'TailwindHelper Plugin');
        }
    } else {
        $modx->log(xPDO::LOG_LEVEL_ERROR, $className . ' was not found!', '', 'TailwindHelper Plugin');
    }
}

return;