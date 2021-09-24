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

Key | Description | Default
----|-------------|--------
tailwindhelper.safelistFolder | The location of the safelist.json | `{core_path}components/tailwindhelper/elements/purge/`
