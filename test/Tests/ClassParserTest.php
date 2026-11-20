<?php
/**
 * TailwindHelper Class Parser Tests
 *
 * These tests don't need a MODX installation.
 *
 * @package tailwindhelper
 * @subpackage test
 */

use PHPUnit\Framework\TestCase;
use TreehillStudio\TailwindHelper\Parser\ClassParser;

class ClassParserTest extends TestCase
{
    /**
     * Compare the found classes regardless of their order.
     *
     * @param array $expected
     * @param array $actual
     * @param string $message
     */
    private function assertClasses(array $expected, array $actual, string $message = '')
    {
        $this->assertEqualsCanonicalizing($expected, array_values($actual), $message);
    }

    /**
     * Class lists in a class attribute with the expected classes, if MODX tags are removed or kept.
     *
     * @return array
     */
    public function classListProvider(): array
    {
        return [
            'simple classes' => ['a b c', ['a', 'b', 'c'], ['a', 'b', 'c']],
            'empty class list' => ['  ', [], []],
            'backticks are trimmed' => ['`a` b', ['a', 'b'], ['a', 'b']],
            'tabs and line breaks' => ["x\t[[+y:notempty=`q`]]\ny", ['x', 'q', 'y'], ['x', 'q', 'y']],

            // Output modifiers
            'modifier with one class' => ['a [[+x:notempty=`b`]] c', ['a', 'b', 'c'], ['a', 'b', 'c']],
            'modifier with multiple classes' => ['a [[+x:notempty=`b1 b2`]] c', ['a', 'b1', 'b2', 'c'], ['a', 'b1', 'b2', 'c']],
            'then and else modifiers' => ['a [[+x:is=`1`:then=`t1 t2`:else=`e1`]]', ['a', 't1', 't2', 'e1'], ['a', 't1', 't2', 'e1']],
            'modifier name in another case' => ['[[+a:ellipsis=`10`:NotEmpty=`up`]]', ['up'], ['up']],
            'tag in a then value' => ['[[+a:is=`x`:then=`[[+b]]`:notempty=`n`]]', ['n'], ['[[+b]]', 'n']],

            // If snippet
            'If snippet' => [
                '[[!If? &subject=`[[+x]]` &operator=`EQ` &operand=`1` &then=`t1 t2` &else=`e1`]] z',
                ['t1', 't2', 'e1', 'z'],
                ['t1', 't2', 'e1', 'z'],
            ],
            'If snippet subject is no class value' => [
                '[[!If? &subject=`[[+x:notempty=`no`]]` &then=`[[+c]] t1` &else=`e1`]]',
                ['t1', 'e1'],
                ['[[+c]]', 't1', 'e1'],
            ],

            // Nested output modifiers
            'nested modifier' => ['[[+a:notempty=`[[+b:notempty=`x`]] y`]] z', ['x', 'y', 'z'], ['x', 'y', 'z']],
            'nested modifiers in then' => [
                '[[+a:is=`1`:then=`[[+b:notempty=`n1 n2`:default=`d1`]] t`:else=`e`]]',
                ['n1', 'n2', 'd1', 't', 'e'],
                ['n1', 'n2', 'd1', 't', 'e'],
            ],
            'unclosed nested tag' => ['[[+a:notempty=`[[+broken y`]]', [], ['[[+a:notempty=`[[+broken y`]]']],

            // Other MODX tags
            'placeholder' => ['a [[+cls]] b', ['a', 'b'], ['a', '[[+cls]]', 'b']],
            'unclosed tag' => ['`a` b [[+broken', ['a', 'b'], ['a', 'b', '[[+broken']],
            'end of a tag' => ['b]] c', ['c'], ['b]]', 'c']],
            'tag in a longer class' => ['bg-[[+color]] p-2', ['p-2'], ['bg-[[+color]]', 'p-2']],
            'modifier with whitespace in a longer class' => ['bg-[[+x:default=`red 500`]] p-3', ['p-3'], ['bg-[[+x:default=`red 500`]]', 'p-3']],
            'modifier after a class' => ['a[[+x:notempty=`-b`]] c', ['c'], ['a[[+x:notempty=`-b`]]', 'c']],

            // Tailwind arbitrary values
            'arbitrary values' => ['w-[calc(100%-2rem)] [&_p]:mt-2', ['w-[calc(100%-2rem)]', '[&_p]:mt-2'], ['w-[calc(100%-2rem)]', '[&_p]:mt-2']],
            'arbitrary values in a modifier' => ['[[+x:notempty=`w-[10px] [&_p]:mt-2`]]', ['w-[10px]', '[&_p]:mt-2'], ['w-[10px]', '[&_p]:mt-2']],
            'grid line name' => ['grid-cols-[[full-start]_1fr] mt-2', ['grid-cols-[[full-start]_1fr]', 'mt-2'], ['grid-cols-[[full-start]_1fr]', 'mt-2']],
            'grid line names' => ['grid-cols-[[a]_1fr_[b]] p-1', ['grid-cols-[[a]_1fr_[b]]', 'p-1'], ['grid-cols-[[a]_1fr_[b]]', 'p-1']],
            'grid line name and placeholder' => ['[[full]_x] [[+y]]', ['[[full]_x]'], ['[[full]_x]', '[[+y]]']],
        ];
    }

    /**
     * @dataProvider classListProvider
     * @param string $classList
     * @param array $expectedRemoved
     * @param array $expectedKept
     */
    public function testClassList(string $classList, array $expectedRemoved, array $expectedKept)
    {
        $content = '<div class="' . $classList . '">';
        $this->assertClasses($expectedRemoved, (new ClassParser(true))->getDefaultClasses($content), 'removeModxTags enabled');
        $this->assertClasses($expectedKept, (new ClassParser(false))->getDefaultClasses($content), 'removeModxTags disabled');
    }

    /**
     * Class attributes in different positions and formats.
     *
     * @return array
     */
    public function classAttributeProvider(): array
    {
        return [
            'attribute after a line break' => ["<div\nclass=\"nl\">", ['nl']],
            'attribute after a tab with whitespace around the equal sign' => ["<div\tclass = 'tab'>", ['tab']],
            'attribute at the start of the content' => ['class="start"', ['start']],
            'data-class attribute' => ['<div data-class="no">', []],
            'Alpine class attribute only' => ['<div :class="\'y\'">', []],

            // Escaped quotes
            'JSON encoded HTML' => ['[{"html":"<div class=\"migx-cls w-1\/2\">"}]', ['migx-cls', 'w-1/2']],
            'PHP string in double quotes' => ['echo "<div class=\"php-escaped\">";', ['php-escaped']],
            'PHP string in single quotes' => ['echo \'<div class=\\\'sq-escaped\\\'>\';', ['sq-escaped']],
            'escaped and unescaped attributes' => ['<p class="plain"> "<div class=\"escaped\">"', ['plain', 'escaped']],
            'JSON encoded twice like nested MIGX values' => [
                json_encode(['inner' => json_encode([['html' => '<div class="nested-cls w-1/2">']])]),
                ['nested-cls', 'w-1/2'],
            ],
            'JSON encoded three times' => [
                json_encode(['a' => json_encode(['b' => json_encode(['html' => '<div class="deep-cls">'])])]),
                ['deep-cls'],
            ],
        ];
    }

    /**
     * @dataProvider classAttributeProvider
     * @param string $content
     * @param array $expected
     */
    public function testClassAttribute(string $content, array $expected)
    {
        $this->assertClasses($expected, (new ClassParser())->getDefaultClasses($content));
    }

    /**
     * Alpine.js attributes with the expected classes.
     *
     * @return array
     */
    public function alpineProvider(): array
    {
        return [
            'class object' => ['<div :class="{ \'al\': open }">', ['al']],
            'all strings in single quotes' => ['<div :class="{ \'whatever\': variable === \'foo\' }">', ['whatever', 'foo']],
            'x-bind:class after a line break' => ["<div\nx-bind:class=\"o ? 'xb1 xb2' : ''\">", ['xb1', 'xb2']],
            'class and :class attribute' => ['<div class="x" :class="\'y\'">', ['y']],
            'classes separated by a tab and a line break' => ["<div :class=\"o ? 'a\tb\nc' : ''\">", ['a', 'b', 'c']],
            'x-transition attributes' => ["<div x-transition:enter=\"t1\n t2\" x-transition:leave.opacity=\"t3\">", ['t1', 't2', 't3']],
            'class attribute only' => ['<div class="x">', []],

            // Escaped quotes
            'JSON encoded :class' => ['{"html":"<div :class=\"{ \'json-al\': open }\">"}', ['json-al']],
            'PHP string in single quotes with :class' => ['echo \'<div :class="{ \\\'php-al\\\': open }">\';', ['php-al']],
            'JSON encoded x-transition' => ['{"html":"<div x-transition:enter=\"json-t1 json-t2\">"}', ['json-t1', 'json-t2']],
            'JSON encoded twice :class' => [
                json_encode(['inner' => json_encode(['html' => '<div :class="{ \'nested-al\': open }">'])]),
                ['nested-al'],
            ],
        ];
    }

    /**
     * @dataProvider alpineProvider
     * @param string $content
     * @param array $expected
     */
    public function testAlpineClasses(string $content, array $expected)
    {
        $this->assertClasses($expected, (new ClassParser())->getAlpineClasses($content));
    }

    /**
     * MODX tag properties with the expected classes.
     *
     * @return array
     */
    public function propertyProvider(): array
    {
        return [
            'chunk property' => ['[[$chunk? &cls=`p-4 mt-2`]]', ['p-4', 'mt-2']],
            'menu snippet properties' => [
                '[[pdoMenu? &parents=`0` &hereClass=`active font-bold` &rowClass=`py-1` &outerClass=`flex gap-4`]]',
                ['active', 'font-bold', 'py-1', 'flex', 'gap-4'],
            ],
            'property name in another case' => ['[[$c? &CssClass=`upper-key`]]', ['upper-key']],
            'class property with an xPDO class name' => ['[[!pdoResources? &class=`modResource` &tpl=`row`]]', []],
            'class property with a namespaced class name' => ['[[!pdoResources? &class=`MODX\Revolution\modResource`]]', []],
            'class property with classes' => ['[[!pdoResources? &class=`msProduct`]] [[$x? &class=`card p-2`]]', ['card', 'p-2']],
            'modifier in a property' => ['[[$chunk? &cls=`[[+active:notempty=`bg-red-500`]] p-4`]]', ['bg-red-500', 'p-4']],
            'placeholder in a property' => ['[[$chunk? &wrapperClass=`[[+cls]] mx-auto`]]', ['mx-auto']],
            'other properties' => ['[[$chunk? &title=`Some title` &tpl=`row`]]', []],
            'HTML entity' => ['<p>&classic</p>', []],
        ];
    }

    /**
     * @dataProvider propertyProvider
     * @param string $content
     * @param array $expected
     */
    public function testPropertyClasses(string $content, array $expected)
    {
        $this->assertClasses($expected, (new ClassParser())->getPropertyClasses($content));
    }

    public function testAllClasses()
    {
        $content = '<div class="a [[$chunk? &cls=`b`]]" :class="\'c\'" x-transition:enter="d">[[$chunk? &rowClass=`e`]]</div>';
        $this->assertClasses(['a', 'b', 'c', 'd', 'e'], (new ClassParser())->getClasses($content));
    }
}
