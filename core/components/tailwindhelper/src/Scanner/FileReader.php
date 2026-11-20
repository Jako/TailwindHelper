<?php
/**
 * File Reader
 *
 * @package tailwindhelper
 * @subpackage scanner
 */

namespace TreehillStudio\TailwindHelper\Scanner;

use modX;

/**
 * Read text files, that could contain CSS classes.
 */
class FileReader
{
    /**
     * The MIME types besides text/*, that could contain CSS classes
     */
    const TEXT_MIME_TYPES = [
        'application/javascript',
        'application/x-javascript',
        'application/x-php',
        'application/x-httpd-php',
        'application/json',
        'application/xml',
        'image/svg+xml',
    ];

    /**
     * A reference to the modX instance
     * @var modX $modx
     */
    private $modx;

    /**
     * The fileinfo resource
     * @var resource|\finfo|false $finfo
     */
    private $finfo;

    /**
     * FileReader constructor
     *
     * @param modX $modx
     */
    public function __construct(modX $modx)
    {
        $this->modx = $modx;
        $this->finfo = finfo_open(FILEINFO_MIME_TYPE);
    }

    public function __destruct()
    {
        if ($this->finfo) {
            finfo_close($this->finfo);
        }
    }

    /**
     * Check if a MIME type contains text, that could contain CSS classes.
     *
     * @param string|false $mime
     * @return bool
     */
    public function isTextMime($mime): bool
    {
        if (!$mime) {
            return false;
        }
        return strpos($mime, 'text/') === 0 || in_array($mime, self::TEXT_MIME_TYPES);
    }

    /**
     * Read a file, if it exists and contains text.
     *
     * @param string $path
     * @return string The file content or an empty string
     */
    public function readTextFile(string $path): string
    {
        if ($path === '' || !is_file($path) || !is_readable($path) || !$this->isTextMime(finfo_file($this->finfo, $path))) {
            return '';
        }
        $content = file_get_contents($path);
        return is_string($content) ? $content : '';
    }

    /**
     * Get the content of the text files in folders and their sub folders.
     * Folders, that can't be scanned, are logged. Sub folders, that can't be
     * read, are skipped.
     *
     * @param string[] $folders
     * @return \Generator The file content by the file path
     */
    public function getTextFiles(array $folders): \Generator
    {
        foreach ($folders as $folder) {
            try {
                $dirIterator = new \RecursiveDirectoryIterator($folder, \FilesystemIterator::SKIP_DOTS);
                // Skip the sub folders, that can't be read, instead of stopping the scan of the folder
                $iterator = new \RecursiveIteratorIterator($dirIterator, \RecursiveIteratorIterator::LEAVES_ONLY, \RecursiveIteratorIterator::CATCH_GET_CHILD);
                /** @var \SplFileInfo $file */
                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $content = $this->readTextFile($file->getPathname());
                        if ($content !== '') {
                            yield $file->getPathname() => $content;
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not scan the folder "' . $folder . '": ' . $e->getMessage(), '', 'TailwindHelper');
            }
        }
    }
}
