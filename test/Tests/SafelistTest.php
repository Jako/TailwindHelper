<?php
/**
 * TailwindHelper Safelist Tests
 *
 * @package tailwindhelper
 * @subpackage test
 */

use TreehillStudio\TailwindHelper\Scanner\Safelist;
use TreehillStudio\TailwindHelper\Scanner\SafelistException;

class SafelistTest extends TailwindHelperTestCase
{
    /**
     * @var string $folder A temporary folder for the safelist.json
     */
    private $folder;

    public function setUp(): void
    {
        parent::setUp();
        $this->folder = sys_get_temp_dir() . '/tailwindhelper-safelist-' . getmypid() . '-' . uniqid();
    }

    public function tearDown(): void
    {
        if (is_dir($this->folder)) {
            chmod($this->folder, 0755);
            @unlink($this->folder . '/' . Safelist::FILENAME);
            rmdir($this->folder);
        }
        parent::tearDown();
    }

    public function testPrepare()
    {
        $log = [];
        $logTarget = $this->modx->setLogTarget(['target' => 'ARRAY', 'options' => ['var' => &$log]]);
        try {
            $classes = (new Safelist($this->modx))->prepare(['p-4', '', 'a', 'p-4', "caf\xe9", 'b']);
        } finally {
            $this->modx->setLogTarget($logTarget);
        }
        // Without empty, duplicate and invalid UTF-8 classes, sorted
        $this->assertSame(['a', 'b', 'p-4'], $classes);
        $this->assertStringContainsString($this->modx->lexicon('tailwindhelper.scan_invalid', ['count' => 1]), print_r($log, true));
    }

    public function testEncode()
    {
        $this->assertSame(['a', 'w-1/2'], json_decode((new Safelist($this->modx))->encode(['a', 'w-1/2']), true));
        $this->expectException(SafelistException::class);
        (new Safelist($this->modx))->encode(["caf\xe9"]);
    }

    public function testWriteCreatesTheFolder()
    {
        $folder = (new Safelist($this->modx))->write($this->folder, '["a"]');
        $this->assertSame($this->folder . '/', $folder);
        $this->assertSame('["a"]', file_get_contents($this->folder . '/' . Safelist::FILENAME));
    }

    public function testWriteInAnUnwritableFolder()
    {
        if (function_exists('posix_getuid') && posix_getuid() === 0) {
            $this->markTestSkipped('The root user can write in every folder.');
        }
        mkdir($this->folder);
        chmod($this->folder, 0555);
        $this->expectException(SafelistException::class);
        (new Safelist($this->modx))->write($this->folder . '/', '["a"]');
    }
}
