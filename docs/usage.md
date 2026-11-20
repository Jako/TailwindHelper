## Menu Entry

To generate a safelist.json file, you simply have to click on the TailwindHelper
menu entry in the Extras menu. TailwindHelper collects the CSS classes in the
content of:

- chunks
- templates
- resources
- template variable default values
- template variable values of the resources
- ContentBlocks field and layout templates, if ContentBlocks is installed
- text files in the folders of the `tailwindhelper.filepaths` system setting

For static chunks and templates, the content of the static file is scanned. For
static resources, the content of their file is scanned, if it is a text file.
Template variable values and default values with a `@FILE` binding are read
from the file.

The target path of the safelist.json can be changed with the
`tailwindhelper.safelistFolder` system setting. The menu entry is only shown to
manager users with the `settings` permission.

## Detected Classes

The CSS classes are detected in:

- standard `class="whatever"` attributes
- Alpine.js `:class="{ 'whatever': variable === 'foo' }"` and `x-bind:class`
  attributes. All strings in single quotes are added, so this example will add
  `whatever` and `foo` to the detected classes.
- Alpine.js `x-transition` attributes like `x-transition:enter="whatever"`
- properties of MODX tags with `class` or `cls` in their name like
  ``[[$chunk? &cls=`whatever`]]`` or the `&hereClass`, `&rowClass` and
  `&outerClass` properties of menu snippets. xPDO class names in the `&class`
  property of snippets like pdoResources are skipped.

The attributes are also detected with escaped quotes like `class=\"whatever\"`
in JSON encoded HTML (i.e. in MIGX template variable values) or in PHP and
JavaScript strings. Quotes, that are escaped multiple times like in nested MIGX
values, are detected too.

The class names are separated by whitespace and included in the list as
individual classes.

MODX tags in the class attributes are handled as well. The classes in the values
of output modifiers like ``[[+placeholder:notempty=`whatever`]]`` (`then`,
`else`, `notempty`, `isnotempty`, `ifnotempty`, `!empty`, `empty`, `isempty`,
`ifempty` and `default`) and in the `&then` and `&else` properties of If snippet
calls are added to the detected classes. Other MODX tags like `[[+placeholder]]`
are removed from the list, if the `tailwindhelper.removeModxTags` system setting
is enabled.

A MODX tag in a longer class like `bg-[[+color]]` is kept as part of that class.
With the `tailwindhelper.removeModxTags` system setting enabled, the whole class
is removed from the list. Tailwind arbitrary values with `[[` like
`grid-cols-[[full-start]_1fr]` are no MODX tags and stay in the list.

## System Settings

TailwindHelper uses the following system settings in the namespace
`tailwindhelper`:

| Key                           | Name             | Description                                                                                                                                                                                                                                                                               | Default                                                |
|-------------------------------|------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------------------------------------------------|
| tailwindhelper.debug          | Debug            | Log debug information in the MODX error log.                                                                                                                                                                                                                                              | No                                                     |
| tailwindhelper.filepaths      | File Paths       | Comma-separated list of folders that are recursively searched for text files (e.g. HTML, PHP, JavaScript, JSON, XML or SVG files). The content of these files is searched for CSS classes for the safelist.json. The placeholders {core_path}, {base_path} and {assets_path} can be used. | -                                                      |
| tailwindhelper.removeModxTags | Remove MODX Tags | Remove the classes with MODX tags from the safelist.json.                                                                                                                                                                                                                                 | Yes                                                    |
| tailwindhelper.safelistFolder | Safelist Folder  | The folder of the safelist.json. The placeholders {core_path}, {base_path} and {assets_path} can be used.                                                                                                                                                                                 | `{core_path}components/tailwindhelper/elements/purge/` |

## Example usage

The created `safelist.json` has to be referenced in your Tailwind configuration,
so that the used CSS classes are not removed from the build. The examples use the
default location of the `safelist.json`. Change the path, if you have changed the
`tailwindhelper.safelistFolder` system setting.

### Tailwind 4

Tailwind 4 scans the files in an `@source` directive for class names. Add the
`safelist.json` as a source in your main CSS file. The path is relative to that
CSS file:

```css
@import "tailwindcss";
@source "./path-to-your-core/core/components/tailwindhelper/elements/purge/safelist.json";
```

### Tailwind 3

Reference the `safelist.json` in the `safelist` option of the `tailwind.config.js`:

```js
const safelist = require('./path-to-your-core/core/components/tailwindhelper/elements/purge/safelist.json').concat([
    'additional-class'
]);

module.exports = {
    content: ['./src/**/*.html'],
    safelist: safelist,
    // your other options
};
```

### Tailwind 2

Tailwind 2 removes the unused classes with PurgeCSS in a production build.
Reference the `safelist.json` in the `purge.options.safelist` option of the
`tailwind.config.js`:

```js
const safelist = require('./path-to-your-core/core/components/tailwindhelper/elements/purge/safelist.json').concat([
    'additional-class'
]);

module.exports = {
    purge: {
        content: ['./src/**/*.html'],
        options: {
            safelist: safelist
        }
    },
    // your other options
};
```
