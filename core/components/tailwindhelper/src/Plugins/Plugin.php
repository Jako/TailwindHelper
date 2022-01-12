<?php
/**
 * Abstract plugin
 *
 * @package tailwindhelper
 * @subpackage plugin
 */

namespace TreehillStudio\TailwindHelper\Plugins;

use modX;
use TailwindHelper;

/**
 * Class Plugin
 */
abstract class Plugin
{
    /** @var modX $modx */
    protected $modx;
    /** @var TailwindHelper $tailwindhelper */
    protected $tailwindhelper;
    /** @var array $scriptProperties */
    protected $scriptProperties;

    /**
     * Plugin constructor.
     *
     * @param $modx
     * @param $scriptProperties
     */
    public function __construct($modx, &$scriptProperties)
    {
        $this->scriptProperties = &$scriptProperties;
        $this->modx =& $modx;
        $corePath = $this->modx->getOption('tailwindhelper.core_path', null, $this->modx->getOption('core_path') . 'components/tailwindhelper/');
        $this->tailwindhelper = $this->modx->getService('tailwindhelper', 'TailwindHelper', $corePath . 'model/tailwindhelper/', [
            'core_path' => $corePath
        ]);
    }

    /**
     * Run the plugin event.
     */
    public function run()
    {
        $init = $this->init();
        if ($init !== true) {
            return;
        }

        $this->process();
    }

    /**
     * Initialize the plugin event.
     *
     * @return bool
     */
    public function init()
    {
        return true;
    }

    /**
     * Process the plugin event code.
     *
     * @return mixed
     */
    abstract public function process();
}