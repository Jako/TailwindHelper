<?php
/**
 * Class Parser
 *
 * @package tailwindhelper
 * @subpackage parser
 */

namespace TreehillStudio\TailwindHelper\Parser;

/**
 * Find the CSS classes in HTML, MODX tags and Alpine.js attributes. The parser
 * does not need MODX.
 */
class ClassParser
{
    /**
     * The output modifiers and snippet properties, that contain classes
     */
    const CLASS_VALUE_KEYS = ['ifnotempty', 'isnotempty', 'notempty', '!empty', 'default', 'ifempty', 'isempty', 'empty', 'then', 'else'];

    /**
     * Remove the classes, that contain MODX tags
     * @var bool $removeModxTags
     */
    private $removeModxTags;

    /**
     * ClassParser constructor
     *
     * @param bool $removeModxTags Remove the classes, that contain MODX tags.
     */
    public function __construct(bool $removeModxTags = true)
    {
        $this->removeModxTags = $removeModxTags;
    }

    /**
     * Get the values of an attribute in a string. The attribute value can be
     * delimited by escaped quotes, i.e. in JSON encoded HTML or in a PHP or
     * JavaScript string. The quotes can be escaped multiple times, i.e. in
     * nested MIGX values, where JSON is encoded in JSON. These values are
     * unescaped.
     *
     * @param string $content
     * @param string $attributePattern The regular expression pattern of the attribute name
     * @return string[]
     */
    private function getAttributeValues($content, $attributePattern)
    {
        $matches = [];
        preg_match_all('/(?:^|\s)' . $attributePattern . '\s*=\s*(?\'quote\'\\\\*[\'"])(?\'value\'.*?)\k\'quote\'/is', (string)$content, $matches, PREG_SET_ORDER);
        $values = [];
        foreach ($matches as $match) {
            $value = $match['value'];
            // Each escaping level doubles the backslashes before the quote and adds one: \" → \\\" → \\\\\\\"
            $backslashes = strlen($match['quote']) - 1;
            while ($backslashes > 0) {
                $value = stripslashes($value);
                $backslashes = intdiv($backslashes - 1, 2);
            }
            $values[] = $value;
        }
        return $values;
    }

    /**
     * Get standard HTML classes in a string
     *
     * @param string $content
     * @return array
     */
    public function getDefaultClasses($content)
    {
        $classes = [];
        foreach ($this->getAttributeValues($content, 'class') as $currentClasses) {
            $classes = array_merge($classes, $this->parseClassList($currentClasses));
        }

        $classes = array_filter($classes, [$this, 'filterEmpty']);
        $classes = array_filter($classes, [$this, 'filterModxTags']);

        return $classes;
    }

    /**
     * Split a class list into classes. MODX tags are handled as a whole, so
     * classes in modifiers like [[+placeholder:notempty=`class1 class2`]] or
     * in If snippet calls like [[!If? … &then=`class1` &else=`class2`]] are
     * extracted even if the tag contains whitespace.
     *
     * A MODX tag that is part of a longer class like bg-[[+color]] is kept as
     * part of that class. A [[ that does not start a valid MODX tag, like in
     * the Tailwind class grid-cols-[[full-start]_1fr], is handled as text.
     *
     * @param string $classList
     * @return array
     */
    private function parseClassList($classList)
    {
        $classes = [];
        $token = '';
        $length = strlen($classList);
        for ($i = 0; $i < $length; $i++) {
            $char = $classList[$i];
            if (ctype_space($char)) {
                $classes[] = trim($token, '`');
                $token = '';
                continue;
            }
            if ($char === '[' && substr($classList, $i, 2) === '[[') {
                $end = $this->getTagEnd($classList, $i);
                if (is_int($end)) {
                    $tag = substr($classList, $i, $end + 1 - $i);
                    if ($token === '' && ($end + 1 === $length || ctype_space($classList[$end + 1]))) {
                        // A standalone MODX tag
                        $classes = array_merge($classes, $this->parseModxTag($tag));
                    } else {
                        // A MODX tag in a longer class
                        $token .= $tag;
                    }
                    $i = $end;
                    continue;
                }
            }
            $token .= $char;
        }
        $classes[] = trim($token, '`');
        return $classes;
    }

    /**
     * Get the position of the closing ]] of a MODX tag that starts at a
     * position. Nested MODX tags are skipped. A single ] outside of
     * backticks is not valid in a MODX tag and is used in Tailwind arbitrary
     * values instead.
     *
     * @param string $string
     * @param int $start
     * @return int|false|null The position of the last ] of the tag, false if no valid tag starts at the position or null if the tag is not closed.
     */
    private function getTagEnd($string, $start)
    {
        $length = strlen($string);
        $depth = 0;
        $inBackticks = false;
        for ($i = $start; $i < $length; $i++) {
            $char = $string[$i];
            if ($char === '`') {
                $inBackticks = !$inBackticks;
            } elseif ($char === '[' && substr($string, $i, 2) === '[[') {
                $depth++;
                $i++;
            } elseif ($char === ']' && substr($string, $i, 2) === ']]') {
                $depth--;
                $i++;
                if ($depth === 0) {
                    return $i;
                }
            } elseif ($char === ']' && !$inBackticks) {
                return false;
            }
        }
        return null;
    }

    /**
     * Get the classes in the class values of a MODX tag. A tag without class
     * values is returned as it is.
     *
     * @param string $tag
     * @return array
     */
    private function parseModxTag($tag)
    {
        $matches = [];
        // Match all modifiers and properties with a value, so that the values of the others are skipped too
        preg_match_all('/[:&]([!\w.-]+)=`/', $tag, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
        $classes = [];
        $found = false;
        $consumed = 0;
        foreach ($matches as $match) {
            $start = $match[0][1] + strlen($match[0][0]);
            // Skip the matches in the value of a previous match, i.e. in a nested tag
            if ($match[0][1] < $consumed) {
                continue;
            }
            $end = $this->getValueEnd($tag, $start);
            if ($end === false) {
                break;
            }
            $consumed = $end + 1;
            if (in_array(strtolower($match[1][0]), self::CLASS_VALUE_KEYS)) {
                $found = true;
                $classes = array_merge($classes, $this->parseClassList(substr($tag, $start, $end - $start)));
            }
        }
        return $found ? $classes : [$tag];
    }

    /**
     * Get the position of the closing backtick of a value in a MODX tag that
     * starts at a position. Nested MODX tags in the value are skipped.
     *
     * @param string $tag
     * @param int $start
     * @return int|false
     */
    private function getValueEnd($tag, $start)
    {
        $length = strlen($tag);
        for ($i = $start; $i < $length; $i++) {
            if ($tag[$i] === '`') {
                return $i;
            }
            if ($tag[$i] === '[' && substr($tag, $i, 2) === '[[') {
                $end = $this->getTagEnd($tag, $i);
                if (is_int($end)) {
                    $i = $end;
                }
            }
        }
        return false;
    }

    /**
     * Get Alpine HTML classes in a string
     *
     * @param string $content
     * @return array
     */
    public function getAlpineClasses($content)
    {
        $classes = [];

        // detect :class="classlist" and x-bind:class="classlist" attributes
        foreach ($this->getAttributeValues($content, '(?:x-bind)?:class') as $currentClasses) {
            $alpineClasses = [];
            // Strings in single quotes, the quotes could be escaped in a PHP string
            preg_match_all('/\\\\?\'(?\'classlist\'.*?)\\\\?\'/ms', $currentClasses, $alpineClasses);
            foreach ($alpineClasses['classlist'] as $currentAlpineClasses) {
                $classes = array_merge($classes, preg_split('/\s+/', $currentAlpineClasses));
            }
        }

        // detect x-transition:event="classlist" attributes
        foreach ($this->getAttributeValues($content, 'x-transition:[\w.:-]+') as $currentClasses) {
            $classes = array_merge($classes, preg_split('/\s+/', $currentClasses));
        }

        $classes = array_filter($classes, [$this, 'filterEmpty']);
        $classes = array_filter($classes, [$this, 'filterModxTags']);

        return $classes;
    }

    /**
     * Get the classes in the properties of MODX tags, which contain class or
     * cls in their name, like [[$chunk? &cls=`p-4`]] or the &hereClass,
     * &rowClass and &outerClass properties of menu snippets.
     *
     * @param string $content
     * @return array
     */
    public function getPropertyClasses($content)
    {
        $content = (string)$content;
        $classes = [];
        $matches = [];
        preg_match_all('/&(?\'name\'[\w.-]*(?:class|cls)[\w.-]*)\s*=\s*`/i', $content, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
        foreach ($matches as $match) {
            $start = $match[0][1] + strlen($match[0][0]);
            $end = $this->getValueEnd($content, $start);
            if ($end === false) {
                continue;
            }
            $propertyClasses = $this->parseClassList(substr($content, $start, $end - $start));
            if (strtolower($match['name'][0]) === 'class') {
                // The &class property of snippets like pdoResources contains an xPDO class name like modResource
                $propertyClasses = array_filter($propertyClasses, function ($class) {
                    return !(preg_match('/^[A-Za-z_][\w\\\\]*$/', $class) && preg_match('/[A-Z\\\\]/', $class));
                });
            }
            $classes = array_merge($classes, $propertyClasses);
        }

        $classes = array_filter($classes, [$this, 'filterEmpty']);
        $classes = array_filter($classes, [$this, 'filterModxTags']);

        return $classes;
    }

    /**
     * Get all classes in a string: in class attributes, in Alpine.js
     * attributes and in class properties of MODX tags.
     *
     * @param string $content
     * @return array
     */
    public function getClasses($content)
    {
        return array_merge(
            $this->getDefaultClasses($content),
            $this->getAlpineClasses($content),
            $this->getPropertyClasses($content)
        );
    }

    /**
     * @param mixed $var
     * @return bool
     */
    private function filterEmpty($var): bool
    {
        return ($var !== null && $var !== false && $var !== '');
    }

    /**
     * @param mixed $var
     * @return bool
     */
    private function filterModxTags($var): bool
    {
        return !$this->removeModxTags || !$this->containsModxTag($var);
    }

    /**
     * Check if a class contains a MODX tag or a part of it. Tailwind
     * arbitrary values like grid-cols-[[full-start]_1fr] are no MODX tags.
     *
     * @param string $class
     * @return bool
     */
    public function containsModxTag($class): bool
    {
        $pos = strpos($class, '[[');
        if ($pos === false) {
            // The end of a MODX tag
            return strpos($class, ']]') !== false;
        }
        while ($pos !== false) {
            // A valid or an unclosed MODX tag
            if ($this->getTagEnd($class, $pos) !== false) {
                return true;
            }
            $pos = strpos($class, '[[', $pos + 2);
        }
        return false;
    }
}
