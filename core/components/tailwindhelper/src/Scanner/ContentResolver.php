<?php
/**
 * Content Resolver
 *
 * @package tailwindhelper
 * @subpackage scanner
 */

namespace TreehillStudio\TailwindHelper\Scanner;

use modX;
use TreehillStudio\TailwindHelper\TailwindHelper;
use xPDOObject;

/**
 * Get the content of MODX objects, that is scanned for CSS classes. Files of
 * static elements and resources and the file bindings of template variables
 * and ContentBlocks templates are read.
 */
class ContentResolver
{
    /**
     * The classes of static resources in MODX 2 and MODX 3
     */
    const STATIC_RESOURCE_CLASSES = ['modStaticResource', 'MODX\Revolution\modStaticResource'];

    /**
     * The classes of template variables and their values in MODX 2 and MODX 3
     */
    const TEMPLATE_VAR_CLASSES = [
        'modTemplateVar',
        'MODX\Revolution\modTemplateVar',
        'modTemplateVarResource',
        'MODX\Revolution\modTemplateVarResource',
    ];

    /**
     * A reference to the modX instance
     * @var modX $modx
     */
    private $modx;

    /**
     * A reference to the TailwindHelper instance
     * @var TailwindHelper $tailwindhelper
     */
    private $tailwindhelper;

    /**
     * @var FileReader $fileReader
     */
    private $fileReader;

    /**
     * ContentResolver constructor
     *
     * @param modX $modx
     * @param TailwindHelper $tailwindhelper
     * @param FileReader $fileReader
     */
    public function __construct(modX $modx, TailwindHelper $tailwindhelper, FileReader $fileReader)
    {
        $this->modx = $modx;
        $this->tailwindhelper = $tailwindhelper;
        $this->fileReader = $fileReader;
    }

    /**
     * Get the content of an object field, that is scanned for classes.
     *
     * @param xPDOObject $object
     * @param string $keyName
     * @return string The content or an empty string
     */
    public function getContent($object, string $keyName): string
    {
        $content = $object->get($keyName);

        // Read the file of a static element, the stored content could be outdated or empty
        if (method_exists($object, 'isStatic') && $object->isStatic()) {
            $fileContent = $object->getFileContent();
            if (is_string($fileContent) && $fileContent !== '') {
                $content = $fileContent;
            }
        }
        if (!is_string($content) || $content === '') {
            return '';
        }

        // The content of a static resource is the path of its file
        if ($this->isA($object, self::STATIC_RESOURCE_CLASSES)) {
            return $this->fileReader->readTextFile((string)$object->getSourceFile());
        }

        // Template variable values and default values could be bound to a file
        if ($this->isA($object, self::TEMPLATE_VAR_CLASSES)) {
            return $this->getTemplateVarBoundContent($content);
        }

        // ContentBlocks templates could be bound to a file
        if ($object instanceof \cbField || $object instanceof \cbLayout) {
            return $this->getContentBlocksBoundContent($content);
        }

        return $content;
    }

    /**
     * Get the content of a template variable value with a @FILE binding.
     * MODX reads the path as it is. In the manager or frontend it is an
     * absolute path or a path relative to the MODX base path.
     *
     * @param string $value
     * @return string
     */
    public function getTemplateVarBoundContent(string $value): string
    {
        $match = [];
        if (!preg_match('/^\s*@FILE\s+(?<path>.+?)\s*$/is', $value, $match)) {
            return $value;
        }
        $path = $match['path'];
        if (!file_exists($path)) {
            $path = $this->modx->getOption('base_path', null, MODX_BASE_PATH) . ltrim($path, '/');
        }
        return $this->fileReader->readTextFile($path);
    }

    /**
     * Get the content of a ContentBlocks template with a @FILE or @PDO_FILE
     * binding.
     *
     * @param string $value
     * @return string
     */
    public function getContentBlocksBoundContent(string $value): string
    {
        $match = [];
        if (!preg_match('/^(?<type>@FILE|@PDO_FILE) (?<file>.*)$/s', $value, $match)) {
            return $value;
        }
        $file = ltrim(trim($match['file']), '/');
        return ($match['type'] === '@FILE') ? $this->getMediaSourceFile($file) : $this->getPdoToolsFile($file);
    }

    /**
     * Read a ContentBlocks @FILE template with the file template media source
     * like ContentBlocks does.
     *
     * @param string $file
     * @return string
     */
    private function getMediaSourceFile(string $file): string
    {
        if (!class_exists('modMediaSource')) {
            $this->modx->loadClass('sources.modMediaSource');
        }
        $sourceId = $this->modx->getOption('contentblocks.file_template_source', null, 1, true);
        $source = \modMediaSource::getDefaultSource($this->modx, $sourceId, false);
        if (!$source || !$source->getWorkingContext() || !$source->initialize()) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not initialize the media source ' . $sourceId, '', 'TailwindHelper');
            return '';
        }
        $file = trim($this->modx->getOption('contentblocks.file_template_path', null, 'contentblocks/', true), '/') . '/' . $file;
        try {
            $contents = $source->getObjectContents($file);
        } catch (\Exception $e) {
            $contents = [];
        }
        if (!is_array($contents) || empty($contents['content'])) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not read the file "' . $file . '" in the media source ' . $sourceId, '', 'TailwindHelper');
            return '';
        }
        return $contents['content'];
    }

    /**
     * Read a ContentBlocks @PDO_FILE template in the pdoTools elements path
     * like pdoTools does.
     *
     * @param string $file
     * @return string
     */
    private function getPdoToolsFile(string $file): string
    {
        $path = $this->tailwindhelper->translatePath($this->modx->getOption('pdotools_elements_path', null, '{core_path}elements/', true));
        $basePath = $this->modx->getOption('base_path', null, MODX_BASE_PATH);
        $corePath = $this->modx->getOption('core_path', null, MODX_CORE_PATH);
        if (strpos($path, $basePath) === false && strpos($path, $corePath) === false) {
            $path = $basePath . $path;
        }
        $path = rtrim($path, '/') . '/' . $file;
        if (!is_file($path) || !is_readable($path)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not read the file "' . $path . '"', '', 'TailwindHelper');
            return '';
        }
        return (string)file_get_contents($path);
    }

    /**
     * Check if an object is an instance of one of the classes.
     *
     * @param object $object
     * @param string[] $classNames
     * @return bool
     */
    private function isA($object, array $classNames): bool
    {
        foreach ($classNames as $className) {
            if (is_a($object, $className)) {
                return true;
            }
        }
        return false;
    }
}
