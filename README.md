Icons Plugin for DokuWiki
=========================

Embed icons to your DokuWiki

All documentation for this plugin can be found at
http://www.dokuwiki.org/plugin:icons

If you install this plugin manually, make sure it is installed in
lib/plugins/icons/ - if the folder is called different it
will not work!

Please refer to http://www.dokuwiki.org/plugins for additional info
on how to install plugins in DokuWiki.

Bundled Font Awesome Free
-------------------------

Font Awesome Free **7.3.1** is included locally as SVG data; no CDN or font
installation is required. The default pack is `fa`, which prefers solid icons
and also includes brands. The icon picker opens this default pack.
Font Awesome icons use the short, explicit `{{fa>wrench}}` form;
other collections keep an explicit prefix such as `{{icon>fa-regular:user}}`.
Use "Search this collection" to filter icons by name as you type. Matching is
case-insensitive; spaces and underscores match hyphens (for example, `arrow left`).
The count shows matching icons in the selected collection. Clear the search or
use Reset to show them all again.
The O (original) size option inserts no size flag, leaving sizing to the syntax
handler's defaults (`fa>` uses the plugin's configured default size). The FW
button inserts `?fw` for a 1em-high icon in a 1.25em-wide box, useful for aligning
icons in navigation menus and lists.
Small/medium/large retain their explicit 24/32/48 pixel sizes.

Examples using current Font Awesome names:

```text
{{icon>wrench?42}}
{{fa>house}}
{{fa>github}}
{{icon>fa-solid:user}}
{{icon>fa-regular:user}}
{{icon>fa-brands:python}}
```

The separate solid, regular and brand collections are also updated. Old Font
Awesome 4/5 icon names are not retained as compatibility aliases; update page
markup to current names where needed (for example `home` becomes `house`).
Other explicitly selected icon collections continue to work.

ISO 7010 safety signs and PDF export
-----------------------------------

The **ISO 7010 Safety Signs** collection contains all 332 signs from
[iso-safety-signs 2.0.0](https://github.com/karlnorling/iso-safety-signs), bundled
locally with their original colours. Find it under **Safety** in the icon picker.
Search by sign code or description, for example `m003`, `ear protection`, or
`fire extinguisher`. The picker inserts the descriptive name; short lowercase
codes also work:

```text
{{icon>iso7010:m003?96}}
{{icon>iso7010:m003-wear-ear-protection?96}}
{{icon>iso7010:w001?120|General warning}}
{{icon>iso7010:f001?96|Fire extinguisher}}
```

The number after `?` sets the icon's size box in pixels; the artwork keeps its
proportions. Safety signs retain their own colours rather than taking the text
colour. No new browser library or CDN is needed.
Explicit sizes (including `?120`), configured Font Awesome sizes and size
modifiers reserve the same width and height in the browser placeholder and
loaded SVG, preventing layout shifts while the icon data loads.

During DW2PDF export, Icons renders bundled SVG collections as **local cached
SVG images**, using the same size syntax. This also works for `fa>` and other
bundled Iconify collections, including short aliases, explicit colours on
monochrome icons, and `fw`/`2x`–`5x` sizing. Browser pages still use Iconify's
JavaScript placeholders: SVG path data is not added to the page HTML passed to
the template's DOM parser. PDF export performs no HTTP requests for these icons
and does not require modifications to DW2PDF. Other plugins can reuse
`dokuwiki\plugin\icons\PdfIcon::file()` to obtain a local SVG cache file;
BootsWrapper uses this for its own icons. Arbitrary JavaScript placeholders
from other plugins are not automatically converted.

Artwork attribution, source links and individual licences are recorded in
`assets/iso7010-attribution.json`. The upstream MIT code licence is separate
from the artwork licences; see `assets/iso7010-LICENSE.txt`.

The import expands SVG path commands and normalises presentation attributes
once, when building the bundled collection, to avoid mPDF SVG parsing errors.
No conversion tools run on the wiki server. To reproduce the import, check out
the revision pinned in `tools/import-iso7010.py`, install `svgpath@2.6.0` in a
temporary build directory, and run:

```sh
python3 tools/import-iso7010.py /path/to/iso-safety-signs /path/to/attribution.json /path/to/node_modules/svgpath
```

The attribution input maps each upstream SVG asset path to its Wikimedia
`source` URL and `metadata` values. The bundled attribution file's `signs`
entries retain that information along with each `asset` path and source hash.

Updating Font Awesome
---------------------

For maintainers, `tools/import-fontawesome.py` converts the official free npm
archive and verifies its pinned SHA-512 integrity hash. To reproduce these assets:

```sh
python3 tools/import-fontawesome.py /path/to/fontawesome-free-7.3.1.tgz
```

For a later release, verify its official npm metadata and update the script's
version and integrity pin before importing. Attribution and the original license
are included under `assets/`.

----
Copyright (C) Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

See the COPYING file in your DokuWiki folder for details
