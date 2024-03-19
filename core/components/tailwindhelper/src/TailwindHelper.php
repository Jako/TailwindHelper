<?php
/**
 * TailwindHelper
 *
 * Copyright 2021-2024 by Thomas Jakobi <office@treehillstudio.com>
 *
 * @package tailwindhelper
 * @subpackage classfile
 */

namespace TreehillStudio\TailwindHelper;

use modX;

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
    public $version = '1.0.9';

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
        $this->namespace = $this->getOption('namespace', $options, $this->namespace);

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
            'safelistFolder' => $this->translatePath($this->getOption('safelistFolder', $options, '{core_path}components/tailwindclasses/elements/purge/')),
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
     * @param string $path
     * @return string
     */
    public function translatePath($path)
    {
        return str_replace(array(
            '{core_path}',
            '{base_path}',
            '{assets_path}',
        ), array(
            $this->modx->getOption('core_path', null, MODX_CORE_PATH),
            $this->modx->getOption('base_path', null, MODX_BASE_PATH),
            $this->modx->getOption('assets_path', null, MODX_ASSETS_PATH),
        ), $path);
    }

    /**
     * Get standard HTML classes in a string
     *
     * @param string $content
     * @return array
     */
    function getDefaultClasses($content)
    {
        $classes = [];
        $contentClasses = [];
        preg_match_all('/ class=([\'"])(?\'classlist\'.*?)\1/mis', $content, $contentClasses);
        if (empty($contentClasses['classlist'])) {
            return [];
        }
        foreach ($contentClasses['classlist'] as $currentClasses) {
            if (!empty(trim($currentClasses))) {
                $currentClasses = preg_split("/\s+/", $currentClasses);
                foreach ($currentClasses as $k => $currentClass) {
                    if (strpos($currentClass, '[[+') === 0 || strpos($currentClass, '[[!+') === 0) {
                        $currentClasses = $this->handleModifierClasses($currentClasses, $k);
                    } elseif (strpos($currentClass, '&') === 0) {
                        $currentClasses = $this->handleIfClasses($currentClasses, $k);
                    } else {
                        $currentClasses[$k] = trim($currentClass, '`');
                    }
                }
                $classes = array_merge($classes, $currentClasses);
            }
        }

        $classes = array_filter($classes, [$this, 'filterEmpty']);
        $classes = array_filter($classes, [$this, 'filterModxTags']);

        return $classes;
    }

    /**
     * Handle 'modifier' classes like [[+placeholder:notempty=`class`]]
     *
     * @param array $currentClasses
     * @param int $k
     * @return array
     */
    private function handleModifierClasses($currentClasses, $k)
    {
        $modifierClasses = [];
        preg_match_all('/(ifnotempty|isnotempty|notempty|!empty|default|ifempty|isempty|empty|then|else)=`(?\'classlist\'.*?)`/mis', $currentClasses[$k], $modifierClasses);
        if (!empty($modifierClasses['classlist'])) {
            foreach ($modifierClasses['classlist'] as $currentModifierClasses) {
                $currentModifierClasses = explode(' ', $currentModifierClasses);
                if (!empty($currentModifierClasses)) {
                    $currentClasses[$k] = '';
                    $currentClasses = array_merge($currentClasses, $currentModifierClasses);
                }
            }
        }
        return $currentClasses;
    }

    /**
     * @param array $currentClasses
     * @param int $k
     * @return array
     */
    private function handleIfClasses($currentClasses, $k)
    {
        $ifClasses = [];
        preg_match_all('/(&else|&then)=`(?\'classlist\'.*?)$/mis', $currentClasses[$k], $ifClasses);
        $currentClasses[$k] = '';
        if (!empty($ifClasses['classlist'])) {
            $currentClasses = array_merge($currentClasses, $ifClasses['classlist']);
        }
        return $currentClasses;
    }

    /**
     * Get Alpine HTML classes in a string
     *
     * @param string $content
     * @return array
     */
    function getAlpineClasses($content)
    {
        $classes = [];
        $contentClasses = [];

        // detect :class="classlist" attributes
        preg_match_all('/ :class=([\'"])(?\'classlist\'.*?)\1/mis', $content, $contentClasses);
        if (!empty($contentClasses['classlist'])) {
            foreach ($contentClasses['classlist'] as $currentClasses) {
                $alpineClasses = [];
                preg_match_all('/\'(?\'classlist\'.*?)\'/ms', $currentClasses, $alpineClasses);
                if (empty($alpineClasses['classlist'])) {
                    continue;
                }
                foreach ($alpineClasses['classlist'] as $currentAlpineClasses) {
                    $currentAlpineClasses = explode(' ', $currentAlpineClasses);
                    if (!empty($currentAlpineClasses)) {
                        $classes = array_merge($classes, $currentAlpineClasses);
                    }
                }
            }
        }

        // detect x-transition:event="classlist" attributes
        preg_match_all('/ x-transition:.*?=([\'"])(?\'classlist\'.*?)\1/mis', $content, $contentClasses);
        if (!empty($contentClasses['classlist'])) {
            foreach ($contentClasses['classlist'] as $currentClasses) {
                $alpineClasses = [];
                $currentAlpineClasses = explode(' ', $currentClasses);
                if (!empty($currentAlpineClasses)) {
                    $classes = array_merge($classes, $currentAlpineClasses);
                }
            }
        }

        $classes = array_filter($classes, [$this, 'filterEmpty']);
        $classes = array_filter($classes, [$this, 'filterModxTags']);

        return $classes;
    }

    /**
     * @param mixed $var
     * @return bool
     */
    private function filterEmpty($var): bool
    {
        return ($var !== null && $var !== false && $var !== "");
    }

    /**
     * @param mixed $var
     * @return bool
     */
    private function filterModxTags($var): bool
    {
        if ($this->getOption('removeModxTags')) {
            return (strpos($var, '[[') === false && strpos($var, ']]') === false);
        } else {
            return true;
        }
    }
}
