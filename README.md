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
