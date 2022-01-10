<?php
/**
 * Event List Snippet
 *
 * @package tailwindhelper
 * @subpackage snippet
 */

namespace TreehillStudio\TailwindHelper\Snippets;

use modX;

class TailwindScanClasses extends Snippet
{
    /**
     * Get default snippet properties.
     *
     * @return array
     */
    public function getDefaultProperties()
    {
        return [
            'listId' => $this->tailwindhelper->getOption('list_id', [], (isset($this->modx->resource)) ? $this->modx->resource->get('id') : 0),
        ];
    }

    /**
     * Execute the snippet and return the result.
     *
     * @return string
     * @throws /Exception
     */
    public function execute()
    {
        $processorsPath = $this->tailwindhelper->getOption('processorsPath');

        /** @var \modProcessorResponse $classes */
        $response = $this->modx->runProcessor('mgr/tailwindhelper/scanclasses', [], [
            'processors_path' => $processorsPath
        ]);

        if ($response->isError()) {
            return $response->getMessage();
        } else {
            return '<pre>' . modX::replaceReserved($response->getMessage()) . '</pre>';
        }
    }
}
