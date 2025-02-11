## Menu Entry

To generate a safelist.json file with the content of chunks, templates,
resources and template variables, you simply have to click on the TailwindHelper
menu entry in the Extras menu. The target path of the safelist.json can be
changed by a system setting.

The CSS classes in the files are detected in standard `class="whatever"`
attributes and Alpine.js `:class="{ 'whatever': variable === 'foo' }"`
attributes. These examples will add `whatever` and `foo` to the detected
classes. The class names are separated by the space character and included in
the list as individual classes.

## System Settings

TailwindHelper uses the following system settings in the namespace
`tailwindhelper`:

| Key                           | Name             | Description                                                                                                                                                                                     | Default                                                |
|-------------------------------|------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------------------------------------------------|
| tailwindhelper.debug          | Debug            | Log debug information in the MODX error log.                                                                                                                                                    | No                                                     |
| tailwindhelper.filepaths      | File Paths       | Comma-separated list of file paths that are recursively searched for files with the mimetype text/plain or text/html. The content of these files is searched for CSS classes for safelist.json. | -                                                      |
| tailwindhelper.removeModxTags | Remove MODX Tags | Remove remaining MODX tags in the safelist.json.                                                                                                                                                | Yes                                                    |
| tailwindhelper.safelistFolder | Safelist Folder  | The location of the safelist.json.                                                                                                                                                              | `{core_path}components/tailwindhelper/elements/purge/` |

## Example usage 

### Parcel 2/Webpack

If you want to purge your tailwind css with [Parcel 2](https://parceljs.org/) or in [Webpack](https://webpack.js.org/),
you have to refer the created `safelist.json` in the `tailwind.config.js` to reduce
the size of the build:

```js
const safelist = require('./path-to-your-core/core/components/tailwindhelper/elements/purge/safelist.json');
safelist = safelist.concat([
    'additional-class'
]);

module.exports = {
    ...
    purge: {
        content: ['./src/**/*.html'],
        safelist: safelist
    },
    ...
```

In Tailwind 3 purgeCSS is not used anymore and you have to reference the safelist.json directly in a safelist configuration:

```js
const safelist = require('./path-to-your-core/core/components/tailwindhelper/elements/purge/safelist.json');
safelist = safelist.concat([
    'additional-class'
]);

module.exports = {
    ...
    content: {
        content: ['./src/**/*.html'],
    },
    safelist: safelist
    ...
```
