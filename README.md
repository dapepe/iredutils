CLI Utilities for iRedMail
==========================

Installation
------------

* Install iRedMail
* Clone the repository into `/opt/iredutils/`
* Install the composer dependencies through `composer install`
* Adapt your `config.php` if necessary
* Create an alias in `/user/bin`: `ln -s /opt/iredutils/iredcli /user/bin/iredcli`


Usage
-----

### Domains

```
iredcli domain
  show
  add <DOMAIN>
  remove <DOMAIN>
```


### Mailbox

```
iredcli mailbox
  list [<DOMAIN> --search=<SEARCH>]
  add <EMAIL> [--password=<PASSWORD> --maildir=<MAILDIR>]
  update <EMAIL> [--password=<PASSWORD>]
  remove <EMAIL
```

### Alias

```
iredcli domain
  show
  add <DOMAIN>
  remove <DOMAIN>
```

License
-------