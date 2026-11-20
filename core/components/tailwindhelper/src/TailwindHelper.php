<?php
/**
 * TailwindHelper
 *
 * Copyright 2021-2026 by Thomas Jakobi <office@treehillstudio.com>
 *
 * @package tailwindhelper
 * @subpackage classfile
 */

namespace TreehillStudio\TailwindHelper;

use modX;
use TreehillStudio\TailwindHelper\Parser\ClassParser;

/**
 * Class TailwindHelper
 */
class TailwindHelper
{
    /**
     * A reference to the modX instance
     * @var modX $modx
     */
    public $modx;

    /**
     * The namespace
     * @var string $namespace
     */
    public $namespace = 'tailwindhelper';

    /**
     * The package name
     * @var string $packageName
     */
    public $packageName = 'TailwindHelper';

    /**
     * The version
     * @var string $version
     */
    public $version = '1.1.0';

    /**
     * The class options
     * @var array $options
     */
    public $options = [];

    /**
     * TailwindHelper constructor
     *
     * @param modX $modx A reference to the modX instance.
     * @param array $options An array of options. Optional.
     */
    public function __construct(modX &$modx, $options = [])
    {
        $this->modx =& $modx;

        $corePath = $this->getOption('core_path', $options, $this->modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/' . $this->namespace . '/');
        $assetsPath = $this->getOption('assets_path', $options, $this->modx->getOption('assets_path', null, MODX_ASSETS_PATH) . 'components/' . $this->namespace . '/');
        $assetsUrl = $this->getOption('assets_url', $options, $this->modx->getOption('assets_url', null, MODX_ASSETS_URL) . 'components/' . $this->namespace . '/');
        $modxversion = $this->modx->getVersionData();

        // Load some default paths for easier management
        $this->options = array_merge([
            'namespace' => $this->namespace,
            'version' => $this->version,
            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            'vendorPath' => $corePath . 'vendor/',
            'chunksPath' => $corePath . 'elements/chunks/',
            'pagesPath' => $corePath . 'elements/pages/',
            'snippetsPath' => $corePath . 'elements/snippets/',
            'pluginsPath' => $corePath . 'elements/plugins/',
            'controllersPath' => $corePath . 'controllers/',
            'processorsPath' => $corePath . 'processors/',
            'templatesPath' => $corePath . 'templates/',
            'assetsPath' => $assetsPath,
            'assetsUrl' => $assetsUrl,
            'jsUrl' => $assetsUrl . 'js/',
            'cssUrl' => $assetsUrl . 'css/',
            'imagesUrl' => $assetsUrl . 'images/',
            'connectorUrl' => $assetsUrl . 'connector.php'
        ], $options);

        $lexicon = $this->modx->getService('lexicon', 'modLexicon');
        $lexicon->load($this->namespace . ':default');

        $this->packageName = $this->modx->lexicon('tailwindhelper');

        // Add default options
        $this->options = array_merge($this->options, [
            'debug' => (bool)$this->getOption('debug', $options, false),
            'modxversion' => $modxversion['version'],
            'safelistFolder' => $this->translatePath($this->getOption('safelistFolder', $options, '{core_path}components/tailwindhelper/elements/purge/')),
            'filepaths' => $this->translatePath($this->getOption('filepaths', $options, '')),
        ]);
    }

    /**
     * Get a local configuration option or a namespaced system setting by key.
     *
     * @param string $key The option key to search for.
     * @param array $options An array of options that override local options.
     * @param mixed $default The default value returned if the option is not found locally or as a
     * namespaced system setting; by default this value is null.
     * @return mixed The option value or the default value specified.
     */
    public function getOption(string $key, $options = [], $default = null)
    {
        $option = $default;
        if (!empty($key) && is_string($key)) {
            if ($options != null && array_key_exists($key, $options)) {
                $option = $options[$key];
            } elseif (array_key_exists($key, $this->options)) {
                $option = $this->options[$key];
            } elseif (array_key_exists("$this->namespace.$key", $this->modx->config)) {
                $option = $this->modx->getOption("$this->namespace.$key");
            }
        }
        return $option;
    }

    /**
     * Replace the {core_path}, {base_path} and {assets_path} placeholders
     * and their [[+core_path]] forms in a path.
     *
     * @param string $path
     * @return string
     */
    public function translatePath($path)
    {
        $paths = [
            'core_path' => $this->modx->getOption('core_path', null, MODX_CORE_PATH),
            'base_path' => $this->modx->getOption('base_path', null, MODX_BASE_PATH),
            'assets_path' => $this->modx->getOption('assets_path', null, MODX_ASSETS_PATH),
        ];
        foreach ($paths as $key => $value) {
            $path = str_replace(['{' . $key . '}', '[[+' . $key . ']]', '[[++' . $key . ']]'], $value, $path);
        }
        return $path;
    }

    /**
     * Get the class parser with the current removeModxTags option.
     *
     * @return ClassParser
     */
    public function getParser(): ClassParser
    {
        return new ClassParser((bool)$this->getOption('removeModxTags'));
    }

    /**
     * Get all classes in a string: in class attributes, in Alpine.js
     * attributes and in class properties of MODX tags.
     *
     * @param string $content
     * @return array
     */
    public function getClasses($content)
    {
        return $this->getParser()->getClasses($content);
    }

    /**
     * Get standard HTML classes in a string
     *
     * @param string $content
     * @return array
     */
    public function getDefaultClasses($content)
    {
        return $this->getParser()->getDefaultClasses($content);
    }

    /**
     * Get Alpine HTML classes in a string
     *
     * @param string $content
     * @return array
     */
    public function getAlpineClasses($content)
    {
        return $this->getParser()->getAlpineClasses($content);
    }

    /**
     * Get the classes in the properties of MODX tags, which contain class or
     * cls in their name.
     *
     * @param string $content
     * @return array
     */
    public function getPropertyClasses($content)
    {
        return $this->getParser()->getPropertyClasses($content);
    }

    /**
     * Get the options used by the manager scripts, so that no server paths are output in the manager
     *
     * @param array|null $options Options to filter instead of the class options
     * @return array
     */
    public function getManagerOptions(?array $options = null): array
    {
        return array_intersect_key($options ?? $this->options, array_flip([
            'assetsUrl',
            'connectorUrl',
            'debug',
            'modxversion',
            'version',
        ]));
    }
}
