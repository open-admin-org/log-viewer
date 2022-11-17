Log viewer for open-admin
============================

[![StyleCI](https://styleci.io/repos/491059283/shield?branch=main)](https://styleci.io/repos/491059283)
[![Packagist](https://img.shields.io/github/license/open-admin-org/log-viewer.svg?style=flat-square&color=brightgreen)](https://packagist.org/packages/open-admin-ext/log-viewer)
[![Total Downloads](https://img.shields.io/packagist/dt/open-admin-ext/log-viewer.svg?style=flat-square)](https://packagist.org/packages/open-admin-ext/log-viewer)
[![Pull request welcome](https://img.shields.io/badge/pr-welcome-green.svg?style=flat-square&color=brightgreen)]()

## Screenshot

![image](https://user-images.githubusercontent.com/86517067/167827896-7a426d57-ee14-48a3-83e2-eae434d090e0.png)


## Installation

```
$ composer require open-admin-ext/log-viewer

$ php artisan admin:import log-viewer
```

Open `http://localhost/admin/logs`.


## Configuration
If your server doesn't allow you to access log files for example by blocking requests with '.log' in the url you can enable the following bypass function.

See `config/admin.php` and add in the `extensions` section
```php
'extensions' => [
    'log-viewer' => [
        'bypass_protected_urls' => true,
        //'bypass_protected_urls_find' => ['.'],          // default ['.']
        //'bypass_protected_urls_replace' => ['[dot]'],   // default ['[dot]']
    ]
]
```

License
------------
Licensed under [The MIT License (MIT)](LICENSE).
