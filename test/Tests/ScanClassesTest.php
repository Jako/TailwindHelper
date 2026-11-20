<?php
/**
 * TailwindHelper Scan Classes Processor Tests
 *
 * @package tailwindhelper
 * @subpackage test
 */

use TreehillStudio\TailwindHelper\Scanner\ContentResolver;
use TreehillStudio\TailwindHelper\Scanner\FileReader;

class ScanClassesTest extends TailwindHelperTestCase
{
    /**
     * @var string $dataPath The path of the test data
     */
    private $dataPath;
    /**
     * @var string $safelistPath A temporary folder for the safelist.json
     */
    private $safelistPath;
    /**
     * @var string $templatesPath A temporary folder in the MODX base path for the file templates
     */
    private $templatesPath;
    /**
     * @var string $templatesFolder The templates folder relative to the MODX base path
     */
    private $templatesFolder;
    /**
     * @var array $log The log messages of the current test
     */
    private $log = [];
    /**
     * @var mixed $logTarget The previous log target
     */
    private $logTarget;

    public function setUp(): void
    {
        parent::setUp();

        $this->dataPath = dirname(__DIR__) . '/Data/';
        $this->safelistPath = sys_get_temp_dir() . '/tailwindhelper-test-' . getmypid() . '-' . uniqid();

        // The file templates have to be in the MODX base path
        $this->templatesFolder = 'tailwindhelper-test-' . getmypid() . '-' . uniqid() . '/';
        $this->templatesPath = $this->modx->getOption('base_path') . $this->templatesFolder;
        mkdir($this->templatesPath);
        foreach (['pdo.tpl', 'cb.html', 'tv.html'] as $file) {
            copy($this->dataPath . 'Templates/' . $file, $this->templatesPath . $file);
        }
        // Remove the temporary folder in the MODX base path, even if a test stops with a fatal error
        $templatesPath = $this->templatesPath;
        register_shutdown_function(function () use ($templatesPath) {
            $this->removeFolder($templatesPath);
        });

        // Collect the log messages of the current test
        $this->log = [];
        $this->logTarget = $this->modx->setLogTarget(['target' => 'ARRAY', 'options' => ['var' => &$this->log]]);
    }

    public function tearDown(): void
    {
        $this->modx->setLogTarget($this->logTarget);
        $this->removeFolder($this->templatesPath);
        if (is_dir($this->safelistPath)) {
            chmod($this->safelistPath, 0755);
            $this->removeFolder($this->safelistPath);
        }

        parent::tearDown();
    }

    /**
     * @param string $path
     */
    private function removeFolder(string $path)
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (array_diff(scandir($path), ['.', '..']) as $file) {
            is_dir($path . '/' . $file) ? $this->removeFolder($path . '/' . $file) : unlink($path . '/' . $file);
        }
        rmdir($path);
    }

    /**
     * Run the scan classes processor.
     *
     * @return modProcessorResponse|\MODX\Revolution\Processors\ProcessorResponse
     */
    private function runScan()
    {
        return $this->modx->runProcessor('mgr/tailwindhelper/scanclasses', [], [
            'processors_path' => $this->tailwindhelper->getOption('processorsPath')
        ]);
    }

    /**
     * @return array|null
     */
    private function getSafelist()
    {
        $file = rtrim($this->safelistPath, '/') . '/safelist.json';
        return file_exists($file) ? json_decode(file_get_contents($file), true) : null;
    }

    /**
     * @return ContentResolver
     */
    private function getResolver(): ContentResolver
    {
        return new ContentResolver($this->modx, $this->tailwindhelper, new FileReader($this->modx));
    }

    /**
     * @return string
     */
    private function getLogMessages(): string
    {
        return is_array($this->log) ? print_r($this->log, true) : (string)$this->log;
    }

    public function testScanFilePaths()
    {
        $this->setConfig('tailwindhelper.filepaths', $this->dataPath . 'Files , , ' . $this->dataPath . 'Missing');
        // Without a trailing slash
        $this->setConfig('tailwindhelper.safelistFolder', $this->safelistPath);
        $this->tailwindhelper->options['filepaths'] = $this->modx->config['tailwindhelper.filepaths'];
        $this->tailwindhelper->options['safelistFolder'] = $this->safelistPath;

        $response = $this->runScan();
        $this->assertFalse($response->isError(), $response->getMessage());

        $safelist = $this->getSafelist();
        $this->assertIsArray($safelist);
        // HTML, PHP and JavaScript files are scanned, also in sub folders
        $this->assertContains('html-cls', $safelist);
        $this->assertContains('php-cls', $safelist);
        $this->assertContains('js-cls', $safelist);
        // Binary files are not scanned
        $this->assertNotContains('bin-cls', $safelist);
        // Classes that are not valid UTF-8 are skipped, the other classes in that file are found
        $this->assertContains('latin1-cls', $safelist);
        $this->assertCount(0, preg_grep('/^caf/', $safelist));
        // Classes in escaped quotes in JSON and PHP files are found
        $this->assertContains('migx-cls', $safelist);
        $this->assertContains('w-1/2', $safelist);
        $this->assertContains('php-escaped', $safelist);
        $this->assertContains('migx-nested-cls', $safelist);
        // The folder that does not exist is logged
        $this->assertStringContainsString('Missing', $this->getLogMessages());
        // The unique classes in the files are counted: html-cls, php-cls, js-cls, latin1-cls, café,
        // migx-cls, w-1/2, migx-nested-cls, php-escaped and dup-cls
        $this->assertStringContainsString($this->modx->lexicon('tailwindhelper.scan_found', ['count' => 10]), $this->getLogMessages());
    }

    public function testUnreadableSubFolder()
    {
        if (function_exists('posix_getuid') && posix_getuid() === 0) {
            $this->markTestSkipped('The root user can read every folder.');
        }
        // A folder with an unreadable sub folder, that is sorted before a readable one
        $filesPath = $this->safelistPath . '-files';
        mkdir($filesPath . '/a-locked', 0755, true);
        mkdir($filesPath . '/b-open', 0755, true);
        file_put_contents($filesPath . '/a-locked/locked.html', '<p class="locked-cls">');
        file_put_contents($filesPath . '/b-open/open.html', '<p class="open-cls">');
        file_put_contents($filesPath . '/root.html', '<p class="root-cls">');
        chmod($filesPath . '/a-locked', 0000);

        try {
            $this->tailwindhelper->options['filepaths'] = $filesPath;
            $this->tailwindhelper->options['safelistFolder'] = $this->safelistPath . '/';

            $response = $this->runScan();
            $this->assertFalse($response->isError(), $response->getMessage());
            $safelist = $this->getSafelist();
            $this->assertContains('open-cls', $safelist);
            $this->assertContains('root-cls', $safelist);
            $this->assertNotContains('locked-cls', $safelist);
            // The scan of the folder is not stopped, the order of the sub folders depends on the file system
            $this->assertStringNotContainsString($filesPath, $this->getLogMessages());
        } finally {
            chmod($filesPath . '/a-locked', 0755);
            $this->removeFolder($filesPath);
        }
    }

    public function testUnwritableSafelistFolder()
    {
        if (function_exists('posix_getuid') && posix_getuid() === 0) {
            $this->markTestSkipped('The root user can write in every folder.');
        }
        mkdir($this->safelistPath);
        chmod($this->safelistPath, 0555);
        $this->setConfig('tailwindhelper.filepaths', '');
        $this->tailwindhelper->options['filepaths'] = '';
        $this->tailwindhelper->options['safelistFolder'] = $this->safelistPath . '/';

        $response = $this->runScan();
        $this->assertTrue($response->isError());
        $this->assertNull($this->getSafelist());
    }

    public function testInlineTemplate()
    {
        $this->assertSame('<p class="inline">', $this->getResolver()->getContentBlocksBoundContent('<p class="inline">'));
    }

    /**
     * @return array
     */
    public function pdoElementsPathProvider(): array
    {
        return [
            'curly braces placeholder' => ['{base_path}%s'],
            'placeholder' => ['[[+base_path]]%s'],
            'relative to the base path' => ['%s'],
        ];
    }

    /**
     * @dataProvider pdoElementsPathProvider
     * @param string $elementsPath
     */
    public function testPdoFileTemplate(string $elementsPath)
    {
        $this->setConfig('pdotools_elements_path', sprintf($elementsPath, $this->templatesFolder));
        $this->assertSame('<div class="pdo-file">', trim($this->getResolver()->getContentBlocksBoundContent('@PDO_FILE pdo.tpl')));
    }

    public function testMissingPdoFileTemplate()
    {
        $this->setConfig('pdotools_elements_path', '{base_path}' . $this->templatesFolder);
        $this->assertSame('', $this->getResolver()->getContentBlocksBoundContent('@PDO_FILE missing.tpl'));
        $this->assertStringContainsString('missing.tpl', $this->getLogMessages());
    }

    public function testFileTemplate()
    {
        $this->setConfig('contentblocks.file_template_source', 1);
        $this->setConfig('contentblocks.file_template_path', $this->templatesFolder);
        $this->assertSame('<div class="cb-file">', trim($this->getResolver()->getContentBlocksBoundContent('@FILE cb.html')));
    }

    public function testMissingFileTemplate()
    {
        $this->setConfig('contentblocks.file_template_source', 1);
        $this->setConfig('contentblocks.file_template_path', $this->templatesFolder);
        $this->assertSame('', $this->getResolver()->getContentBlocksBoundContent('@FILE missing.html'));
        $this->assertStringContainsString('missing.html', $this->getLogMessages());
    }

    public function testStaticChunkFile()
    {
        $chunk = $this->modx->newObject('modChunk');
        $chunk->fromArray([
            'name' => 'tailwindhelper-test',
            'content' => '<div class="stored-content">',
            'static' => 1,
            'source' => 0,
            'static_file' => $this->dataPath . 'Templates/static.html',
        ]);
        $this->assertSame('<div class="static-file">', trim($this->getResolver()->getContent($chunk, 'content')));
        $this->assertTrue($chunk->isNew(), 'The chunk must not be saved');

        // A static chunk with an empty stored content
        $chunk->set('content', '');
        $this->assertSame('<div class="static-file">', trim($this->getResolver()->getContent($chunk, 'content')));

        // The stored content is used, if the static file does not exist
        $chunk->set('content', '<div class="stored-content">');
        $chunk->set('static_file', $this->dataPath . 'Templates/missing.html');
        $this->assertSame('<div class="stored-content">', $this->getResolver()->getContent($chunk, 'content'));
    }

    public function testStaticResourceFile()
    {
        $this->setConfig('resource_static_path', $this->dataPath . 'Templates/');
        $this->setConfig('resource_static_allow_absolute', false);

        $resource = $this->modx->newObject('modStaticResource');
        $resource->set('content', 'static-resource.html');
        $this->assertSame('<div class="static-resource-cls">', trim($this->getResolver()->getContent($resource, 'content')));

        // Binary and missing files are not read
        $resource->set('content', 'static-resource.png');
        $this->assertSame('', $this->getResolver()->getContent($resource, 'content'));
        $resource->set('content', 'missing.html');
        $this->assertSame('', $this->getResolver()->getContent($resource, 'content'));
    }

    public function testTemplateVarFileBinding()
    {
        // An absolute path in the default value
        $tv = $this->modx->newObject('modTemplateVar');
        $tv->set('default_text', '@FILE ' . $this->dataPath . 'Templates/tv.html');
        $this->assertSame('<div class="tv-file-cls">', trim($this->getResolver()->getContent($tv, 'default_text')));

        // A path relative to the MODX base path in a resource value
        $value = $this->modx->newObject('modTemplateVarResource');
        $value->set('value', '@FILE ' . $this->templatesFolder . 'tv.html');
        $this->assertSame('<div class="tv-file-cls">', trim($this->getResolver()->getContent($value, 'value')));

        // Missing files and values without a binding
        $value->set('value', '@FILE ' . $this->templatesFolder . 'missing.html');
        $this->assertSame('', $this->getResolver()->getContent($value, 'value'));
        $value->set('value', '<div class="plain">');
        $this->assertSame('<div class="plain">', $this->getResolver()->getContent($value, 'value'));
    }

    public function testContentBlocksFieldProperties()
    {
        $cbPath = $this->modx->getOption('contentblocks.core_path', null, $this->modx->getOption('core_path') . 'components/contentblocks/') . 'model/';
        if (!file_exists($cbPath)) {
            $this->markTestSkipped('ContentBlocks is not installed.');
        }
        $this->modx->addPackage('contentblocks', $cbPath);
        $this->setConfig('pdotools_elements_path', '{base_path}' . $this->templatesFolder);

        $field = $this->modx->newObject('cbField');
        $field->fromArray([
            'input' => 'repeater',
            'template' => '<div class="tpl">',
            'properties' => json_encode([
                'wrapper_template' => '<div class="wrapper [[+rows]]">',
                'row_template' => '@PDO_FILE pdo.tpl',
            ]),
        ]);
        $this->assertSame('<div class="tpl">', $this->getResolver()->getContent($field, 'template'));
        $this->assertSame('<div class="wrapper [[+rows]]">', $this->getResolver()->getContent($field, 'wrapper_template'));
        $this->assertSame('<div class="pdo-file">', trim($this->getResolver()->getContent($field, 'row_template')));
        $this->assertSame('', $this->getResolver()->getContent($field, 'nested_template'));
    }
}
