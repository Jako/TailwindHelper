<?php
/**
 * Safelist
 *
 * @package tailwindhelper
 * @subpackage scanner
 */

namespace TreehillStudio\TailwindHelper\Scanner;

use modX;

/**
 * Prepare, encode and write the safelist.json.
 */
class Safelist
{
    /**
     * The file name of the safelist
     */
    const FILENAME = 'safelist.json';

    /**
     * A reference to the modX instance
     * @var modX $modx
     */
    private $modx;

    /**
     * Safelist constructor
     *
     * @param modX $modx
     */
    public function __construct(modX $modx)
    {
        $this->modx = $modx;
    }

    /**
     * Remove empty and duplicate classes and sort them. Classes that are not
     * valid UTF-8 (i.e. in files with another encoding) can't be encoded as
     * JSON. They are skipped with a warning.
     *
     * @param array $classes
     * @return string[]
     */
    public function prepare(array $classes): array
    {
        $classes = array_unique(array_filter($classes));
        $validClasses = array_filter($classes, function ($class) {
            return preg_match('//u', $class) === 1;
        });
        if (count($validClasses) < count($classes)) {
            $this->modx->log(modX::LOG_LEVEL_WARN, $this->modx->lexicon('tailwindhelper.scan_invalid', ['count' => count($classes) - count($validClasses)]), '', 'TailwindHelper');
        }
        sort($validClasses);
        return $validClasses;
    }

    /**
     * Encode the classes as JSON.
     *
     * @param string[] $classes
     * @return string
     * @throws SafelistException
     */
    public function encode(array $classes): string
    {
        $json = json_encode($classes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new SafelistException($this->modx->lexicon('tailwindhelper.err_json', ['error' => json_last_error_msg()]));
        }
        return $json;
    }

    /**
     * Write the JSON to the safelist.json in a folder. The folder is created,
     * if it does not exist.
     *
     * @param string $folder
     * @param string $json
     * @return string The path of the folder with a trailing slash
     * @throws SafelistException
     */
    public function write(string $folder, string $json): string
    {
        $folder = rtrim($folder, '/') . '/';
        if (!file_exists($folder) && !$this->modx->cacheManager->writeTree($folder)) {
            throw new SafelistException($this->modx->lexicon('tailwindhelper.err_create_folder', ['path' => $folder]));
        }
        if (!$this->modx->cacheManager->writeFile($folder . self::FILENAME, $json)) {
            throw new SafelistException($this->modx->lexicon('tailwindhelper.err_write_file', ['file' => $folder . self::FILENAME]));
        }
        return $folder;
    }
}
