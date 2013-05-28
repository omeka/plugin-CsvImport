CSV Import Full (plugin for Omeka)
==================================


Summary
-------

This plugin for [Omeka] allows users to import items from a simple CSV (comma
separated values) file, and then map the CSV column data to multiple elements,
files, and/or tags. Each row in the file represents metadata for a single item.
This plugin is useful for exporting data from one database and importing that
data into an Omeka site.

This plugin is a fork of the original [Csv Import] plugin that allows:

* use of tabulation as a separator,
* import of metadata of files,
* import of files one by one to avoid overloading server.

The similar tool [Xml Import] can be useful too, depending on your types of
data.


Installation
------------

Uncompress files and rename plugin folder "CsvImport".

Then install it like any other Omeka plugin and follow the config instructions.


Examples
--------

Two examples of csv files are available in the csv_files folder (standard [Csv Import]):

* `test.csv`: a basic list of three books with images of Wikipedia, with
non Dublin Core tags.
* `test_automap_columns_to_elements.csv`: the same list with some Dublin Core
attributes in order to automap the columns with the Omeka fields.
* `test_automap_columns_to_elements.csv`: the same list with some Dublin Core
attributes in order to automap the columns with the Omeka fields.

To try them, you just need to check `Item metadata`, to use the default
delimiters `,` and, for the second file, to check option `Automap column`. Note
that even you don't use the Automap option, the plugin will try to get matching
columns if field names are the same in your file and in the drop-down list.

Two other files are available with [Csv Import Full]:

* `test_special_delimiters.csv`: a file to try any delimiters. Special
delimiters of this file are:
    - Column delimiter: tabulation
    - Element delimiter: custom ^^ (used by Csv Report)
    - Tag delimiter: double space
    - File delimiter: semi-colon
* `test_files_metadata.csv`: a file to used to import metadata of files. To try
it, you should to import items before with any of previous csv files, and check
`File metadata` in the first form and `Filename` in the first row of the second
form.

_Warning_
Depending of your environment and database, if you imports items with encoded
urls, they should be decoded when your import files. For example, you can import
an item with the file "Edmond_Dant%C3%A8s.JPG", but you may import your file
metadata with the filename "Edmond_Dantès.JPG".


Status page
-----------

The status page indicates situation of previous, queued and current imports. You
can make an action on any import process.

Note that can't undo a files metadata import, because previous ones are
overwritten.

The column "Skipped rows" means that some imported lines were non complete or
with too many columns, so you need to check your import file.

The column "Skipped record" means that an item or a file can't be created,
usually because of a bad url or a bad formatted row.


Warning
-------

Use it at your own risk.

It's always recommended to backup your database so you can roll back if needed.


Troubleshooting
---------------

See online [Csv Import issues] (original plugin) and [Csv Import Full issues].


License
-------

This plugin is published under [GNU/GPL].

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
details.

You should have received a copy of the GNU General Public License along with
this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.


Contact
-------

Current maintainers:

Csv Import (original plugin):

* Center for History and New Media (see [CHNM])

Csv Import Full (forked plugin):

* Daniel Berthereau (see [Daniel-KM])
* Shawn Averkamp (see [saverkamp], for the version 1.3.4)

This plugin has been forked for [University of Iowa Libraries] and upgraded for
[École des Ponts ParisTech] and [Pop Up Archive].


Copyright
---------

Csv Import (original plugin):

* Copyright Center for History and New Media, 2012

Csv Import Full (forked plugin):

* Copyright Daniel Berthereau, 2012-2013
* Copyright Shawn Averkamp, 2012


[Omeka]: https://omeka.org "Omeka.org"
[Csv Import]: https://github.com/omeka/plugin-CsvImport "Omeka plugin Csv Import"
[Csv Import Full]: https://github.com/Daniel-KM/CsvImport "GitHub Csv Import Full"
[Xml Import]: https://github.com/Daniel-KM/XmlImport "GitHub XmlImport"
[Csv Import issues]: https://github.com/omeka/plugin-CsvImport/Issues "GitHub Csv Import"
[Csv Import Full issues]: https://github.com/Daniel-KM/CsvImport/Issues "GitHub Csv Import Full"
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html "GNU/GPL v3"
[CHNM]: https://github.com/omeka "Center for History and New Media"
[Daniel-KM]: https://github.com/Daniel-KM "Daniel Berthereau"
[saverkamp]: https://github.com/saverkamp "saverkamp"
[University of Iowa Libraries]: http://www.lib.uiowa.edu
[École des Ponts ParisTech]: http://bibliotheque.enpc.fr "École des Ponts ParisTech / ENPC"
[Pop Up Archive]: http://popuparchive.org/
