<?php
/**
 * Abstract processor
 *
 * @package tailwindhelper
 * @subpackage processor
 */

namespace TreehillStudio\TailwindHelper\Processors;

use TreehillStudio\TailwindHelper\TailwindHelper;
use modProcessor;
use modX;

/**
 * Class Processor
 */
abstract class Processor extends modProcessor
{
    public $languageTopics = ['tailwindhelper:default'];

    /** @var TailwindHelper */
    public $tailwindhelper;

    /**
     * {@inheritDoc}
     * @param modX $modx A reference to the modX instance
     * @param array $properties An array of properties
     */
    function __construct(modX &$modx, array $properties = [])
    {
        parent::__construct($modx, $properties);

        $corePath = $this->modx->getOption('tailwindhelper.core_path', null, $this->modx->getOption('core_path') . 'components/tailwindhelper/');
        $this->tailwindhelper = $this->modx->getService('tailwindhelper', 'TailwindHelper', $corePath . 'model/tailwindhelper/');
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
