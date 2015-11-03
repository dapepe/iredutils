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
  show [<DOMAIN> --search=<SEARCH>]
  add <EMAIL> [--password=<PASSWORD> --maildir=<MAILDIR>]
  update <EMAIL> [--password=<PASSWORD>]
  remove <EMAIL
```

### Alias

```
iredcli alias
  show [<DOMAIN|EMAIL> --search=<SEARCH>]
  add <ALIAS> <MAILBOX>
  remove <ALIAS> [<MAILBOX>]
```


License
-------

Copyright (C) 2015 [Peter Haider](http://about.me/peterhaider)

This work is licensed under the GNU Lesser General Public License (LGPL) which should be included with this software. You may also get a copy of the GNU Lesser General Public License from [http://www.gnu.org/licenses/lgpl.txt](http://www.gnu.org/licenses/lgpl.txt).