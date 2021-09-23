<?php
/**
 * TailwindHelper connector
 *
 * @package tailwindhelper
 * @subpackage connector
 *
 * @var modX $modx
 */

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php';
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
require_once MODX_CONNECTORS_PATH . 'index.php';

$corePath = $modx->getOption('tailwindhelper.core_path', null, $modx->getOption('core_path') . 'components/tailwindhelper/');
/** @var TailwindHelper $tailwindhelper */
$tailwindhelper = $modx->getService('tailwindhelper', 'TailwindHelper', $corePath . 'model/tailwindhelper/', [
    'core_path' => $corePath
]);

// Handle request
$modx->request->handleRequest([
    'processors_path' => $tailwindhelper->getOption('processorsPath'),
    'location' => ''
]);