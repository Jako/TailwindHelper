<?php
/**
 * TailwindHelper File Reader Tests
 *
 * @package tailwindhelper
 * @subpackage test
 */

use TreehillStudio\TailwindHelper\Scanner\FileReader;

class FileReaderTest extends TailwindHelperTestCase
{
    /**
     * @return array
     */
    public function mimeProvider(): array
    {
        return [
            'HTML' => ['text/html', true],
            'plain text' => ['text/plain', true],
            'PHP' => ['text/x-php', true],
            'JavaScript' => ['application/javascript', true],
            'JSON' => ['application/json', true],
            'SVG' => ['image/svg+xml', true],
            'PNG' => ['image/png', false],
            'PDF' => ['application/pdf', false],
            'binary' => ['application/octet-stream', false],
            'unknown' => [false, false],
        ];
    }

    /**
     * @dataProvider mimeProvider
     * @param string|false $mime
     * @param bool $expected
     */
    public function testIsTextMime($mime, bool $expected)
    {
        $this->assertSame($expected, (new FileReader($this->modx))->isTextMime($mime));
    }

    public function testReadTextFile()
    {
        $reader = new FileReader($this->modx);
        $dataPath = dirname(__DIR__) . '/Data/';
        $this->assertSame('<p class="html-cls">', trim($reader->readTextFile($dataPath . 'Files/a.html')));
        $this->assertSame('', $reader->readTextFile($dataPath . 'Files/d.png'));
        $this->assertSame('', $reader->readTextFile($dataPath . 'Files/missing.html'));
        $this->assertSame('', $reader->readTextFile($dataPath . 'Files'));
        $this->assertSame('', $reader->readTextFile(''));
    }

    public function testGetTextFiles()
    {
        $dataPath = dirname(__DIR__) . '/Data/Files';
        $files = iterator_to_array((new FileReader($this->modx))->getTextFiles([$dataPath]));
        // The text files, also in sub folders, without the binary file
        $this->assertArrayHasKey($dataPath . '/a.html', $files);
        $this->assertArrayHasKey($dataPath . '/sub/c.js', $files);
        $this->assertArrayNotHasKey($dataPath . '/d.png', $files);
    }
}
