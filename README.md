Export (module for Omeka S)
===============================

Export is a module for Omeka S which exports item sets into CSV files. Each row in the file represents metadata for a single item.

Export CSV files include item metadata and a link to associated media. 

This module has not been tested with very large item sets. Consider breaking very large item sets in to smaller item sets for exporting.

Compatibility and CSV format
----------------------------

This update targets Omeka S 4.2.1 and PHP 8.5. The module requires PHP 7.4
or later; Omeka S 4.2.1 itself requires PHP 8.1 or later. Earlier Omeka version
constraints are retained; CI is configured to run the regression suite on PHP 8.1 and 8.5.
The legacy Zend namespaces use Omeka S 4.2.1's bundled Laminas compatibility
bridge.

Export requires a POST request with a positive integer `add_to_item_set`
selection. Other methods return HTTP 405; invalid selections return HTTP 400
without querying items.

CSV property headers are unique. The final column, `item:full`, contains the
item's JSON metadata (excluding the separately exported `media:full` field).
If a custom property already uses `item:full`, the JSON column name gains
`:json` suffixes until it is unique.
Consumers that relied on duplicate headers or an unlabelled final column
should use the new column names. CSV uses doubled quotes for escaping,
with PHP's proprietary backslash escape disabled.

Development tests
-----------------

Run the regression suite without installing dependencies:

```sh
php tests/run.php
```

The suite treats warnings and deprecations as failures and checks request
validation, API query scope, media export, CSV column alignment, CSV escaping,
empty results, and production PHP syntax. Small framework doubles let the
real controller and CSV template run outside Omeka; these are not integration
tests. Before release, verify module activation, the export form, a populated
and empty item set, and item visibility permissions in Omeka S 4.2.1 on PHP 8.5.


Installation
------------

See general end user documentation for [Installing a module](http://omeka.org/s/docs/user-manual/modules/#installing-modules).

Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check your archives regularly so you can roll back if needed.

Troubleshooting
---------------

See online issues on the [Omeka forum] and the [module issues] page on GitHub.

License
-------

This plugin is published under [GNU/GPL v3].

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

Contact
-------

Current maintainers:

* Laura Eiford

Copyright
---------

* Copyright Laura Eiford, 2019

[Omeka S]: https://omeka.org/s
[Omeka forum]: https://forum.omeka.org/c/omeka-s/modules
[GNU/GPL v3]: https://www.gnu.org/licenses/gpl-3.0.html


