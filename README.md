Export (module for Omeka S)
===============================

Export is a module for Omeka S which exports items, media or items sets in various formats: CSV, JSON, TXT and BibTex.

Export files in CSV/JSON include resource metadata and a link to associated media. 
TXT and BibTex only include metadata.

Installation
------------

See general end user documentation for [Installing a module](http://omeka.org/s/docs/user-manual/modules/#installing-modules).

BibTex export config (optional)
-------------------------------

Each property like "dcterms:title" in Omeka is by default mapped to a BibTex property like "Title". The default mapping should be good enough.
This mapping process can be found in config/DefaultBibtexMapping.json. If a user wishes to create their own mapping, they can!
The default mapping file should be kept, and the custom mapping should be done in config/CustomBibtexMapping.json (create the file if it doesn't exist).

The custom mapping must follow the pattern of the default mapping, that is, a valid JSON file.
Each entry `"key" : { ... }` represents a mapping to the BibTex property "key". The content of the mapping is usually like the following:
```json
"key": {
    "mappings": ["dcterms:keyProperty", "bibo:myKey"]
}
```
This is one of the simplest mappings you can do. In that case, the Omeka properties "dcterms:keyProperty" and "bibo:myKey" will be exported as "key" in the Bibtex file.
If multiple values exist, like if "dcterms:keyProperty" has 2 values or if "dcterms:keyProperty" has one value and "bibo:myKey" has one value too for a resource, when exporting that resource, a default separator will be used: " AND " (translated). If you don't like it, you can specify:
```json
"key": {
    "mappings": ["dcterms:keyProperty", "bibo:myKey"],
    "separator": ", "
}
```
If no value is found, for all the "mappings" properties, the "key" value will not be present in the file!

You can also have a list as an entry like this:
```json
"key": [
    {
        "mappings": ["dcterms:keyProperty"],
        "separator": [" + "]
    },
    {
        "mappings": ["bibo:myKey"]
    }
]
```
This means it will first try to get the "dcterms:keyProperty" with a " + " separator. If "dcterms:keyProperty" exists, **it will NOT try** the next entries for that key! In that case it means it will only export a "dcterms:keyProperty" as "key" and not "bibo:myKey". If it does not find a "dcterms:keyProperty", it will try "bibo:myKey" (or whatever the next one is). In the example, "bibo:MyKey" has no specific "separator" (so it uses " AND " by default).

Lastly, you may specify a "type":
```json
"key": {
    "mappings": ["dcterms:myKey"],
    "type": "year"
}
```
This means it will try to read "dcterms:myKey" as a date and export the year. It if fails, it doesn't export that "key" (or try the next mapping if there is one).

This is the list of all "types" avaiables currently:
- "year": tries to read the value of the field as a date, and if it succeeds it exports the year.
- "month": same but for month ("Jan", "Feb", ...).

- "format": exports the value in a specified format in the "format" field, like in this example:
```json
"key": {
    "type": "format",
    "format": "Reource created by %s and published by %s!",
    "mappings": ["dcterms:creator", "dcterms:publisher"]
}
```
each "%s" will be replaced with the values of the "mappings" in order. If one of the "mappings" is not found, it doesn't export that "key" (or try the next mapping if there is one).

- "resourceUrl": exports the resource's url. It doesn't require any "mappings".
```json
"howpublished": {
    "type": "url"
}
```
here the "howpublished" will be equal to something like "\\url{https://example.com/api/items/102}".

- "accessDate": exports the date of access of the item.
```json
"note": {
    "type": "accessDate"
}
```
here the "note" will be equal to something like "Accessed on: 2025-10-16".

The config/DefaultBibtexMapping.json is the best example you can have!

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
* BibLibre

Copyright
---------

* Copyright Laura Eiford, 2019
* Copyright Biblibre, 2025

[Omeka S]: https://omeka.org/s
[Omeka forum]: https://forum.omeka.org/c/omeka-s/modules
[GNU/GPL v3]: https://www.gnu.org/licenses/gpl-3.0.html


