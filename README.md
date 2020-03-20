# Plesk 12.5 Nginx Templates Redirect Fix

By default, Plesk does not use best practices in its nginx config, notably using `if` directives for redirects (see ![If Is Evil](https://www.nginx.com/resources/wiki/start/topics/depth/ifisevil/) for why you shouldn't do this!) This is still unaddressed in the latest versions of Plesk (Plesk 17.8.11, as of the time of this writing), despite the issue being brought up on Plesk support forums. These alternate Plesk templates fix the issue.

The files also include a hack that enables a SEO-safe redirect from the non-SSL to SSL version of the site (this feature is available with current versions of Plesk, but was unavailable in Plesk 12.5). To make use of this feature, create a file called `redirect_to_ssl.php` and place it in the same folder as `vhost.conf`. The file should have simply the following contents:

```php
<?php

$redirect_to_ssl = true;

?>
```

To install these templates, place:
`nginxSeoSafeRedirects.php` in `/usr/local/psa/admin/conf/templates/custom/domain/service`
`nginxDomainVirtualHost.php` in `/usr/local/psa/admin/conf/templates/custom/domain`

There is also ![a version that works with Plesk 17.X (Plesk Onyx)](https://github.com/yurimataev/plesk17-nginx-config).