<?php
/**
 * Scan classes
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
        $cbPath = $this->modx->getOption('contentblocks.core_path', null, $this->modx->getOption('core_path') . 'components/contentblocks/') . 'model/';
        $filePaths = $this->tailwindhelper->getOption('filepaths') ? explode(',', $this->tailwindhelper->getOption('filepaths')) : [];

        if (file_exists($cbPath)) {
            $this->modx->addPackage('contentblocks', $cbPath);
        }

        $classes = [];

        $classes = array_merge($classes, $this->getTypeClasses('modChunk', 'content', $this->modx->lexicon('tailwindhelper.scan_chunks')));
        $classes = array_merge($classes, $this->getTypeClasses('modTemplate', 'content', $this->modx->lexicon('tailwindhelper.scan_templates')));
        $classes = array_merge($classes, $this->getTypeClasses('modResource', 'content', $this->modx->lexicon('tailwindhelper.scan_resources')));
        $classes = array_merge($classes, $this->getTypeClasses('modTemplateVarResource', 'value', $this->modx->lexicon('tailwindhelper.scan_tvs')));

        if (file_exists($cbPath)) {
            $classes = array_merge($classes, $this->getTypeClasses('cbField', 'template', $this->modx->lexicon('tailwindhelper.scan_cb_field')));
            $classes = array_merge($classes, $this->getTypeClasses('cbLayout', 'template', $this->modx->lexicon('tailwindhelper.scan_cb_layout')));
        }

        $classes = array_merge($classes, $this->getFileClasses($filePaths, $this->modx->lexicon('tailwindhelper.scan_files')));

        $classes = array_unique(array_filter($classes));
        sort($classes);
        $path = $this->tailwindhelper->getOption('safelistFolder');
        if (!file_exists($path)) {
            if (!$this->modx->cacheManager->writeTree($path)) {
                $message = 'Could not create "' . $path . '"';
                return $this->failure($message);
            }
        }
        $this->modx->cacheManager->writeFile($path . 'safelist.json', json_encode($classes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

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

            if (in_array($className, ['cbField', 'cbLayout'])) {
                $objectContent = $this->getBoundContent($objectContent);
            }

            $classes = array_merge($classes, $this->tailwindhelper->getDefaultClasses($objectContent));
            $classes = array_merge($classes, $this->tailwindhelper->getAlpineClasses($objectContent));
        }
        $this->modx->log(xPDO::LOG_LEVEL_INFO, $message);
        $this->modx->log(xPDO::LOG_LEVEL_INFO, $this->modx->lexicon('tailwindhelper.scan_found', ['count' => count($classes)]));

        return $classes;
    }

    /**
     * @param string[] $paths
     * @param string $message
     * @return array
     */
    private function getFileClasses($paths, $message)
    {
        $classes = [];

        foreach ($paths as $path) {
            try {
                $dirIterator = new \RecursiveDirectoryIterator($path);
                /** @var \RecursiveDirectoryIterator | \RecursiveIteratorIterator $it */
                $it = new \RecursiveIteratorIterator($dirIterator);
                while ($it->valid()) {
                    if (!$it->isDot() && $it->isFile() && $it->isReadable()) {
                        $file = $it->current();
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime = finfo_file($finfo, $file->getPathname());
                        if (in_array($mime, ['text/plain', 'text/html'])) {
                            $fileContent = file_get_contents($file->getPathname());
                            $classes = array_merge($classes, $this->tailwindhelper->getDefaultClasses($fileContent));
                            $classes = array_merge($classes, $this->tailwindhelper->getAlpineClasses($fileContent));
                        }
                    }
                    $it->next();
                }
            } catch (\Exception $e) {
            }
        }

        $this->modx->log(xPDO::LOG_LEVEL_INFO, $message);
        $this->modx->log(xPDO::LOG_LEVEL_INFO, $this->modx->lexicon('tailwindhelper.scan_found', ['count' => count($classes)]));

        return $classes;
    }

    /**
     * @param string $objectContent
     * @return string
     */
    private function getBoundContent($objectContent)
    {
        $validTypes = ['@FILE', '@PDO_FILE'];
        $type = '';
        $value = '';
        if (strpos($objectContent, '@') === 0) {
            $endPos = strpos($objectContent, ' ');
            if ($endPos > 2 && $endPos < 10) {
                $tt = substr($objectContent, 0, $endPos);
                if (in_array($tt, $validTypes)) {
                    $type = $tt;
                    $value = substr($objectContent, $endPos + 1);
                }
            }
        }
        if (!empty($type)) {
            switch ($type) {
                case '@FILE':
                    $source = modMediaSource::getDefaultSource($this->modx, $this->modx->getOption('contentblocks.file_template_source'), false);
                    if ($source && $source->getWorkingContext()) {
                        $source->initialize();
                        $path = $source->getBasePath($this->modx->getOption('contentblocks.file_template_path') . $value);
                        if (file_exists($path)) {
                            $value = file_get_contents($path);
                        } else {
                            $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not read file: ' . $path);
                        }
                    } else {
                        $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not initialize media source: ' . $this->modx->getOption('contentblocks.file_template_source'));
                    }
                case '@PDO_FILE':
                    $path = $this->modx->getOption('pdotools_elements_path') . $value;
                    if (file_exists($path)) {
                        $value = file_get_contents($path);
                    } else {
                        $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not read file: ' . $path);
                    }
                    break;
            }
        }
        return $value;
    }
}

return 'TailwindHelperScanClassesProcessor';
