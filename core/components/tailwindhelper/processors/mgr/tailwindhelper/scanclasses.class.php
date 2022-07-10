<?php
/**
 * Create a Feed
 *
 * @package tailwindhelper
 * @subpackage processors
 */

use TreehillStudio\TailwindHelper\Processors\Processor;

class TailwindHelperScanClassesProcessor extends Processor
{
    public $languageTopics = ['core:default', 'tailwindhelper:default'];

    /**
     * {@inheritDoc}
     * @return array|mixed|string
     */
    function process()
    {
        $this->modx->addPackage('contentblocks', $this->modx->getOption('contentblocks.core_path', null, $this->modx->getOption('core_path') . 'components/contentblocks/') . 'model/');

        $classes = [];

        $classes = array_merge($classes, $this->getTypeClasses('modChunk', 'content', $this->modx->lexicon('tailwindhelper.scan_chunks')));
        $classes = array_merge($classes, $this->getTypeClasses('modTemplate', 'content', $this->modx->lexicon('tailwindhelper.scan_templates')));
        $classes = array_merge($classes, $this->getTypeClasses('modResource', 'content', $this->modx->lexicon('tailwindhelper.scan_resources')));
        $classes = array_merge($classes, $this->getTypeClasses('modTemplateVarResource', 'value', $this->modx->lexicon('tailwindhelper.scan_tvs')));
        $classes = array_merge($classes, $this->getTypeClasses('cbField', 'template', $this->modx->lexicon('tailwindhelper.scan_cb_field')));
        $classes = array_merge($classes, $this->getTypeClasses('cbLayout', 'template', $this->modx->lexicon('tailwindhelper.scan_cb_layout')));

        $classes = array_unique(array_filter($classes));
        sort($classes);
        $path = $this->tailwindhelper->getOption('safelistFolder');
        if (!file_exists($path)) {
            if (!$this->modx->cacheManager->writeTree($path)) {
                $message = 'Could not create "' . $path . '"';
                return $this->failure($message);
            }
        }
        $this->modx->cacheManager->writeFile($path . 'safelist.json', json_encode($classes, JSON_PRETTY_PRINT));

        $this->modx->log(xPDO::LOG_LEVEL_INFO, $this->modx->lexicon('tailwindhelper.scan_result'));
        $this->modx->log(xPDO::LOG_LEVEL_INFO, $path);

        sleep(1);

        return $this->success(json_encode($classes, JSON_PRETTY_PRINT));
    }

    /**
     * @param string $className
     * @param string $keyName
     * @param string $message
     * @return array
     */
    private function getTypeClasses($className, $keyName, $message)
    {
        $classes = [];
        /** @var xPDOObject[] $objects */
        $objects = $this->modx->getIterator($className);
        foreach ($objects as $object) {
            $objectContent = $object->get($keyName);

            $classes = array_merge($classes, $this->tailwindhelper->getDefaultClasses($objectContent));
            $classes = array_merge($classes, $this->tailwindhelper->getAlpineClasses($objectContent));
        }
        $this->modx->log(xPDO::LOG_LEVEL_INFO,  $message);
        $this->modx->log(xPDO::LOG_LEVEL_INFO, $this->modx->lexicon('tailwindhelper.scan_found', ['count' => count($classes)]));

        return $classes;
    }
}

return 'TailwindHelperScanClassesProcessor';
