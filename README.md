#THIS PROJECT IS DEPRECATED

xdbfilter is not maintained anymore. It maybe does not work in Evolution 1.1 anymore. Please fork it and bring it back to life, if you need it.

xdbfilter
================================================================================

xdbfilter searches any database table for defined fields and generates the according filterboxes with the found values.

Features
--------------------------------------------------------------------------------

According to the filter checkboxes checked (could be radio or dropdown selects too) a placeholder will be filled with a comma separated list of the field contents i.e. if the standard field id is used, a placeholder [+xdbf_id+] will be filled with a comma separated list of document ids matching the filter selection. This placeholder can be i.e. used in MaxiGallery parameter &pic_query_ids or in Ditto parameter &documents.

xdbfilter can be used without the filterboxes too using only the &filters parameter.

custom template variables can be used too in xdbfilter (most @ bindings are not evaluated - only @EVAL).


Parameters
--------------------------------------------------------------------------------


Parameter | Description | Default
----------|-------------|--------
filters | Has to be used if a filter should be preselected by parameter. Will be overrided by $_GET or $_REQUEST. |
filterFields | For this comma separated fields filter boxes are generated. Template variables should have the Prefix 'tv'. |
showempty | Shows a checkbox with this label for each field. If this checkbox is active all rows of the database table are shown,   where this field is empty | 0
refine | The the filter boxes will be filtered too, so the result can be refined easier | 0
preselect | Same as &filters, but it can't be modified by $_GET and $_REQUEST |
tablename | Name of a database table | site_content
sql | A sql-select query as datesource |
where | A where clause for the standard sql-select query of xdbfilter |
outputFields | A comma separated list of fields. The content of this (filtered) fields is listed comma separated in a placeholder  [+xdbf_FIELDNAME+] If the separator should not be a comma it can be defined by adding :SEPARATOR after the fieldname i.e. 'id:&#124;' | 'id', particular [+xdbf_id+]
includeTvs | Include TVs for in the result | 0
id | An unique id if there is more than one xdbfilter call on a page. Created placeholder will use the id as prefix [+id_xdbf_FIELDNAME+] |
display | Show the filterboxes | 1

IMPORTANT: A snippet parameter **can't** contain the '=' character. Use 'eq' for that (in parameters &sql and &where).


Examples
--------------------------------------------------------------------------------

```
&filters=tvCategory(rings|earrings)||parent([*id*])||tvAlloy(585Gg|585Wg)
```

All documents are returned where TV Category is rings or earrings and which are subdocuments of the current document and where the TV alloy is 585Gg or 585Wg.

Snippet Template Placeholder: see example templates

The following placeholder could be used outside of the Snippet: `[+xdbf_FELDNAME+]` or `[+id_xdbf_FELDNAME+]` with the parameter &id `[+filterlink+]` to link the GET or REQUEST parameter to other documents i.e. previous & next links in Ditto or `[+id_filterlink+]` with the parameter &id.

###MaxiGallery example

```
[!xdbfilter? 
&filterOuterTpl=`filterOuterTpl` 
&filterTpl=`filterTpl` 
&filterItemTpl=`filterItemTpl`
&filterFields=`stones,title,filename`
&filters=`stones(Diamond|Citrin)||title(ring)` 
&showempty=`if empty`!]

[!MaxiGallery? 
&display=`embedded` 
&embedtype=`slimbox`
&galleryPictureTpl=`gallerypicturetpl` 
&customFields=`stones`
&managePictureTpl=`managePictureTpl` 
&galleryOuterTpl=`galleryOuterTpl`
&pageNumberTpl=`pagenumbertpl` 
&pics_per_page=`20`
&pic_query_ids=`[+xdbf_id+]`!]
```

###Ditto example:

```
[!xdbfilter? &refine=`1` 
&order_by=`pagetitle` &preselect=`` 
&tablename=`site_content ` 
&filterOuterTpl=`filterOuterTpl` 
&filterTpl=`filterTpl`
&filterItemTpl=`filterItemTpl` 
&filterFields=`parent,published,type,pagetitle`
&showempty=`not set`
&outputfield=`id`!]

[!Ditto? &documents=`[+xdbf_id+]` &display=`all`!] 
```


Notes
--------------------------------------------------------------------------------

The folder chunkie/modifiers contains a PHx modifier used in the example
templates.
