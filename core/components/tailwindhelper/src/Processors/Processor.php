<?php
/**
 * Abstract processor
 *
 * @package tailwindhelper
 * @subpackage processors
 */

namespace TreehillStudio\TailwindHelper\Processors;

use modProcessor;
use modX;
use TreehillStudio\TailwindHelper\TailwindHelper;

/**
 * Class Processor
 */
abstract class Processor extends modProcessor
{
    public $languageTopics = ['tailwindhelper:default'];

    /** @var TailwindHelper $tailwindhelper */
    public $tailwindhelper;

    /**
     * {@inheritDoc}
     * @param modX $modx A reference to the modX instance
     * @param array $properties An array of properties
     */
    public function __construct(modX &$modx, array $properties = [])
    {
        parent::__construct($modx, $properties);

        $corePath = $this->modx->getOption('tailwindhelper.core_path', null, $this->modx->getOption('core_path') . 'components/tailwindhelper/');
        $this->tailwindhelper = $this->modx->getService('tailwindhelper', 'TailwindHelper', $corePath . 'model/tailwindhelper/');
    }

    /**
     * {@inheritDoc}
     * @return bool
     */
    public function checkPermissions()
    {
        return !empty($this->permission) ? $this->modx->hasPermission($this->permission) : true;
    }

    abstract public function process();

    /**
     * Get a boolean property.
     * @param string $k
     * @param mixed $default
     * @return bool
     */
    public function getBooleanProperty($k, $default = null)
    {
        return ($this->getProperty($k, $default) === 'true' || $this->getProperty($k, $default) === true || $this->getProperty($k, $default) === '1' || $this->getProperty($k, $default) === 1);
    }
}
