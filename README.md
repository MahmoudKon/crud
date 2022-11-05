# Requirements:

- This CRUD package requires Yajra datatable ^v9.

##

# Installation:

1- You can install this package via composer using:

```bash
    composer require mahmoudkon/crud
```

2- Make publish for stubs files:

```php
    php artisan vendor:publish --tag=crud-stubs
```

##

# Features:

<p>1- Create model with his relations</p>
<p>2- Create request validation</p>
<p>3- Create Datatable class with button for create new record</p>
<p>4- Create Controller with all CRUD methods</p>
<p>5- Append routes in route file</p>
<p>6- Add translation file for datatable</p>
<p>7- Each field added has a translation in the translation files</p>
<p>8- Create index | create | edit | show pages</p>

##

# Customization:

Make publish for config file:

```php
    php artisan vendor:publish --tag=crud-config
```

1- `` layout `` : Select a layout file name.

2- `` route-file `` : Specifies the name of the paths file in which to place the new path.

3- `` languages `` : To set languages for columns translation.

4- `` translation-file-name `` : Specifies the name of the translation file.