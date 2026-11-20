<?php
/**
 * Scan classes
 *
 * @package tailwindhelper
 * @subpackage processors
 */

use TreehillStudio\TailwindHelper\Processors\Processor;
use TreehillStudio\TailwindHelper\Scanner\ContentResolver;
use TreehillStudio\TailwindHelper\Scanner\FileReader;
use TreehillStudio\TailwindHelper\Scanner\Safelist;
use TreehillStudio\TailwindHelper\Scanner\SafelistException;

class TailwindHelperScanClassesProcessor extends Processor
{
    public $languageTopics = ['core:default', 'tailwindhelper:default'];
    public $permission = 'settings';

    /**
     * The scanned MODX objects: the class, the scanned fields and the lexicon key of the console message
     */
    const SOURCES = [
        ['modChunk', ['content'], 'tailwindhelper.scan_chunks'],
        ['modTemplate', ['content'], 'tailwindhelper.scan_templates'],
        ['modResource', ['content'], 'tailwindhelper.scan_resources'],
        ['modTemplateVar', ['default_text'], 'tailwindhelper.scan_tv_defaults'],
        ['modTemplateVarResource', ['value'], 'tailwindhelper.scan_tvs'],
    ];

    /**
     * The scanned ContentBlocks objects. The input types like repeater, list
     * or table store their additional templates in the field properties.
     */
    const CONTENTBLOCKS_SOURCES = [
        ['cbField', ['template', 'wrapper_template', 'nested_template', 'row_template'], 'tailwindhelper.scan_cb_field'],
        ['cbLayout', ['template'], 'tailwindhelper.scan_cb_layout'],
    ];

    /**
     * @var FileReader $fileReader
     */
    private $fileReader;

    /**
     * @var ContentResolver $contentResolver
     */
    private $contentResolver;

    /**
     * {@inheritDoc}
     * @return array|mixed|string
     */
    function process()
    {
        $this->fileReader = new FileReader($this->modx);
        $this->contentResolver = new ContentResolver($this->modx, $this->tailwindhelper, $this->fileReader);

        $sources = self::SOURCES;
        $cbPath = $this->modx->getOption('contentblocks.core_path', null, $this->modx->getOption('core_path') . 'components/contentblocks/') . 'model/';
        if (file_exists($cbPath)) {
            $this->modx->addPackage('contentblocks', $cbPath);
            $sources = array_merge($sources, self::CONTENTBLOCKS_SOURCES);
        }

        $classes = [];
        foreach ($sources as [$className, $keyNames, $lexiconKey]) {
            $classes = array_merge($classes, $this->getObjectClasses($className, $keyNames, $lexiconKey));
        }
        $folders = array_filter(array_map('trim', explode(',', (string)$this->tailwindhelper->getOption('filepaths'))));
        $classes = array_merge($classes, $this->getFileClasses($folders, 'tailwindhelper.scan_files'));

        $safelist = new Safelist($this->modx);
        try {
            $classes = $safelist->prepare($classes);
            $json = $safelist->encode($classes);
            $folder = $safelist->write($this->tailwindhelper->getOption('safelistFolder'), $json);
        } catch (SafelistException $e) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', 'TailwindHelper');
            return $this->failure($e->getMessage());
        }

        $this->modx->log(xPDO::LOG_LEVEL_INFO, $this->modx->lexicon('tailwindhelper.scan_result'));
        $this->modx->log(xPDO::LOG_LEVEL_INFO, $folder);

        // Give the console window the time to show the log messages
        if ($this->getProperty('register')) {
            sleep(1);
        }

        return $this->success($json);
    }

    /**
     * Get the classes in the fields of all objects of a class.
     *
     * @param string $className
     * @param string[] $keyNames
     * @param string $lexiconKey
     * @return array
     */
    private function getObjectClasses(string $className, array $keyNames, string $lexiconKey): array
    {
        $classes = [];
        foreach ($this->modx->getIterator($className) as $object) {
            foreach ($keyNames as $keyName) {
                $content = $this->contentResolver->getContent($object, $keyName);
                if ($content !== '') {
                    $classes = array_merge($classes, $this->tailwindhelper->getClasses($content));
                }
            }
        }
        $this->logFound($lexiconKey, $classes);
        return $classes;
    }

    /**
     * Get the classes in the text files of folders.
     *
     * @param string[] $folders
     * @param string $lexiconKey
     * @return array
     */
    private function getFileClasses(array $folders, string $lexiconKey): array
    {
        if (empty($folders)) {
            return [];
        }
        $classes = [];
        foreach ($this->fileReader->getTextFiles($folders) as $content) {
            $classes = array_merge($classes, $this->tailwindhelper->getClasses($content));
        }
        $this->logFound($lexiconKey, $classes);
        return $classes;
    }

    /**
     * Log the scanned source and the count of the unique classes found in it.
     *
     * @param string $lexiconKey
     * @param array $classes
     */
    private function logFound(string $lexiconKey, array $classes)
    {
        $this->modx->log(xPDO::LOG_LEVEL_INFO, $this->modx->lexicon($lexiconKey));
        $this->modx->log(xPDO::LOG_LEVEL_INFO, $this->modx->lexicon('tailwindhelper.scan_found', ['count' => count(array_unique($classes))]));
    }
}

return 'TailwindHelperScanClassesProcessor';
