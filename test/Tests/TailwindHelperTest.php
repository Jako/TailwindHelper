<?php
/**
 * TailwindHelper Tests
 *
 * @package tailwindhelper
 * @subpackage test
 */

use TreehillStudio\TailwindHelper\Parser\ClassParser;

class TailwindHelperTest extends TailwindHelperTestCase
{
    public function testParserUsesTheRemoveModxTagsOption()
    {
        $content = '<div class="a [[+cls]]">';
        $this->tailwindhelper->options['removeModxTags'] = true;
        $this->assertClasses(['a'], $this->tailwindhelper->getClasses($content));
        $this->tailwindhelper->options['removeModxTags'] = false;
        $this->assertClasses(['a', '[[+cls]]'], $this->tailwindhelper->getClasses($content));
    }

    public function testDelegatesToTheParser()
    {
        $content = '<div class="a" :class="\'b\'" x-transition:enter="c">[[$chunk? &cls=`d`]]</div>';
        $this->tailwindhelper->options['removeModxTags'] = true;
        $parser = new ClassParser(true);
        $this->assertClasses($parser->getClasses($content), $this->tailwindhelper->getClasses($content));
        $this->assertClasses($parser->getDefaultClasses($content), $this->tailwindhelper->getDefaultClasses($content));
        $this->assertClasses($parser->getAlpineClasses($content), $this->tailwindhelper->getAlpineClasses($content));
        $this->assertClasses($parser->getPropertyClasses($content), $this->tailwindhelper->getPropertyClasses($content));
    }

    /**
     * Paths with placeholders.
     *
     * @return array
     */
    public function pathProvider(): array
    {
        return [
            'curly braces' => ['{core_path}components/tailwindhelper/', 'core_path', 'components/tailwindhelper/'],
            'placeholder' => ['[[+base_path]]elements/', 'base_path', 'elements/'],
            'system setting tag' => ['[[++assets_path]]files/', 'assets_path', 'files/'],
            'no placeholder' => ['/absolute/path/', '', '/absolute/path/'],
        ];
    }

    /**
     * @dataProvider pathProvider
     * @param string $path
     * @param string $setting
     * @param string $suffix
     */
    public function testTranslatePath(string $path, string $setting, string $suffix)
    {
        $prefix = $setting ? $this->modx->getOption($setting) : '';
        $this->assertSame($prefix . $suffix, $this->tailwindhelper->translatePath($path));
    }

    public function testManagerOptionsContainNoServerPaths()
    {
        $options = $this->tailwindhelper->getManagerOptions();
        $this->assertEqualsCanonicalizing(['assetsUrl', 'connectorUrl', 'debug', 'modxversion', 'version'], array_keys($options));
        foreach ($options as $value) {
            $this->assertStringNotContainsString($this->modx->getOption('base_path'), (string)$value);
        }
    }
}
