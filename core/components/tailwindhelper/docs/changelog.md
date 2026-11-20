# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-11-20

### Added

- Scan the default values of template variables, the files of static resources and the files of template variable `@FILE` bindings
- Detect the classes in Alpine.js `x-bind:class` attributes and in MODX tag properties with `class` or `cls` in their name like `&hereClass`

### Changed

- Scan all text files in the `tailwindhelper.filepaths` folders and skip the sub folders that can't be read
- Restrict the menu entry and the scan to manager users with the `settings` permission
- Don't expose server paths in the manager page options

### Fixed

- Find the classes in class attributes with line breaks or escaped quotes (i.e. in JSON, nested MIGX values or PHP strings), in output modifiers with multiple or nested classes and in If snippet `&then` and `&else` values
- Keep Tailwind classes with `[[` in arbitrary values like `grid-cols-[[full-start]_1fr]`
- Read the files of static chunks and templates and all ContentBlocks templates: inline, `@FILE` and `@PDO_FILE` templates and the template properties of the fields
- Write the `safelist.json` reliably: skip classes that are not valid UTF-8 instead of writing an empty file, show the write errors and use the right default folder
- Enable the buttons of the scan console window, if the scan fails

## [1.0.11] - 2025-02-11

### Added

- New tailwindhelper.filepaths system setting containing a comma separated list of folders searched for CSS classes.

## [1.0.10] - 2024-05-08

### Added

- Search ContentBlocks field templates with @FILE or @PDO_FILE bindings

## [1.0.9] - 2024-03-18

### Fixed

- Fix for a not found ContentBlocks class

## [1.0.8] - 2022-10-17

### Added

- Handle classes switched with If snippet
- Detect Alpine.js transition classes

## [1.0.7] - 2022-07-10

### Added

- Added ContentBlocks field/layout templates scan

## [1.0.6] - 2022-07-09

### Added

- Add all variations of notempty and default output modifiers

## [1.0.5] - 2022-05-29

### Added

- Remove all remaining rows with MODX tags by option
- Decrease the timeout in the statistics resolver

## [1.0.4] - 2021-11-17

### Added

- Weblate configuration
- German lexicon

## [1.0.3] - 2021-09-24

### Fixed

- Fix detection of Alpine.js classes 

## [1.0.2] - 2021-09-24

### Added

- Scan classes by menu item

## [1.0.1] - 2021-09-17

### Added

- Scan template variable values

## [1.0.0] - 2021-09-10

### Added

- Initial release
