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
        $classes = [];

        /** @var \modChunk[] $chunks */
        $chunks = $this->modx->getIterator('modChunk');
        foreach ($chunks as $chunk) {
            $chunkContent = $chunk->get('content');

            $classes = array_merge($classes, $this->tailwindhelper->getDefaultClasses($chunkContent));
            $classes = array_merge($classes, $this->tailwindhelper->getAlpineClasses($chunkContent));
        }
        $this->modx->log(modX::LOG_LEVEL_INFO, $this->modx->lexicon('tailwindhelper.scan_chunks'));
        $this->modx->log(modX::LOG_LEVEL_INFO, $this->modx->lexicon('tailwindhelper.scan_found', ['count' => count($classes)]));

        /** @var \modTemplate[] $templates */
        $templates = $this->modx->getIterator('modTemplate');
        foreach ($templates as $template) {
            $templateContent = $template->get('content');

            $classes = array_merge($classes, $this->tailwindhelper->getDefaultClasses($templateContent));
            $classes = array_merge($classes, $this->tailwindhelper->getAlpineClasses($templateContent));
        }
        $this->modx->log(modX::LOG_LEVEL_INFO, $this->modx->lexicon('tailwindhelper.scan_templates'));
        $this->modx->log(modX::LOG_LEVEL_INFO, $this->modx->lexicon('tailwindhelper.scan_found', ['count' => count($classes)]));

        /** @var \modResource[] $resources */
        $resources = $this->modx->getIterator('modResource');
        foreach ($resources as $resource) {
            $resourceContent = $resource->get('content');

            $classes = array_merge($classes, $this->tailwindhelper->getDefaultClasses($resourceContent));
            $classes = array_merge($classes, $this->tailwindhelper->getAlpineClasses($resourceContent));
        }
        $this->modx->log(modX::LOG_LEVEL_INFO, $this->modx->lexicon('tailwindhelper.scan_resources'));
        $this->modx->log(modX::LOG_LEVEL_INFO, $this->modx->lexicon('tailwindhelper.scan_found', ['count' => count($classes)]));

        /** @var \modTemplateVarResource[] $templateVars */
        $templateVars = $this->modx->getIterator('modTemplateVarResource');
        foreach ($templateVars as $templateVar) {
            $templateVarContent = $templateVar->get('value');

            $classes = array_merge($classes, $this->tailwindhelper->getDefaultClasses($templateVarContent));
            $classes = array_merge($classes, $this->tailwindhelper->getAlpineClasses($templateVarContent));
        }
        $this->modx->log(modX::LOG_LEVEL_INFO, $this->modx->lexicon('tailwindhelper.scan_tvs'));
        $this->modx->log(modX::LOG_LEVEL_INFO, $this->modx->lexicon('tailwindhelper.scan_found', ['count' => count($classes)]));

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

        $this->modx->log(modX::LOG_LEVEL_INFO, $this->modx->lexicon('tailwindhelper.scan_result', ['path' => $path]));

        sleep(1);

        return $this->success(json_encode($classes, JSON_PRETTY_PRINT));
    }
}

return 'TailwindHelperScanClassesProcessor';
