joomla-mendeley
===============

Mendeley integration for Joomla: a package of extensions for putting Mendeley bibliographies into Joomla articles.

Use `{mendeley}user{/mendeley}` to insert a list of user's authored documents.


Support for Mendeley REST API is encapsulated in library/src/mendeley.php, which is a small reusable library independent from Joomla.

> Unfortunately Mendeley changed its API soon after the plugin was written :(
> The plugin needs to be updated to the new API in order to be functional again.

## Installation

1. Build extension from sources:
```
# install composer
php -r "readfile('https://getcomposer.org/installer');" | php
# fetch dependencies
composer install
# build
vendor/bin/phing
```
2. Successful build produces `target/pkg_mendeley.zip` which can be uploaded and installed in Joomla.
3. After successfull installation go to Extensions -> Mendeley -> Options and configure Mendeley integration parameters.
4. Configure Mendeley access tokens and assign aliases to them. Currently there is no UI for that, token information has to be inserted in the database manually.
