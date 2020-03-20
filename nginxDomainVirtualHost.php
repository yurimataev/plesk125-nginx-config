<?php
/**
 * @var Template_VariableAccessor $VAR
 * @var array $OPT
 * To rebuild:
 * /usr/local/psa/admin/bin/httpdmng --reconfigure-domain domain.com
 */
?>

<?php 
	/* Conf file path */
	$conf_path = $VAR->domain->physicalHosting->customConfigFile;
	$conf_path = str_replace('/vhost.conf','',$conf_path);

	/* Custom hack for clean nginx redirect to ssl */
	$redirect_to_ssl_file = $conf_path.'/redirect_to_ssl.php';
	if (is_file($redirect_to_ssl_file)) include($redirect_to_ssl_file);
	if ($redirect_to_ssl or $OPT['ssl']) 
			$OPT['redirect_scheme'] = 'https';
	else 	$OPT['redirect_scheme'] = 'http';
?>

<?php 
	/* Custom hack for clean nginx Wordpress URL re-writing */
	if (is_file($VAR->domain->physicalHosting->httpDir.'/wp-blog-header.php') or is_file($VAR->domain->physicalHosting->httpsDir.'/wp-blog-header.php')) $OPT['wordpress_site'] = true;
?>

<?php echo $VAR->includeTemplate('domain/service/nginxSeoSafeRedirects.php', array('ipAddress' => $OPT['ipAddress']->escapedAddress, 'ssl' => $OPT['ssl'], 'frontendPort' => $OPT['frontendPort'], 'redirect_scheme' => $OPT['redirect_scheme'])); ?>

server {
    listen <?php echo $OPT['ipAddress']->escapedAddress . ':' . $OPT['frontendPort'] .
        ($OPT['default'] ? ' default_server' : '') . ($OPT['ssl'] ? ' ssl' : '') .
        ($OPT['ssl'] && $VAR->domain->physicalHosting->proxySettings['nginxHttp2'] ? ' http2' : '') ?>;

<?php if ($VAR->domain->isWildcard): ?>
    server_name ~^<?php echo $VAR->domain->pcreName ?>$;
<?php else: ?>
<?php   if ($VAR->domain->isSeoRedirectToLanding) : ?>
    server_name <?php echo $VAR->domain->asciiName ?>;
<?php   elseif ($VAR->domain->isSeoRedirectToWww): ?>
    server_name www.<?php echo $VAR->domain->asciiName ?>;
<?php   else: ?>
	server_name <?php echo $VAR->domain->asciiName ?>;
	server_name www.<?php echo $VAR->domain->asciiName ?>;
<?php   endif; ?>
<?php   if ($OPT['ipAddress']->isIpV6()): ?>
    server_name ipv6.<?php echo $VAR->domain->asciiName ?>;
<?php   else: ?>
    server_name ipv4.<?php echo $VAR->domain->asciiName ?>;
<?php   endif ?>
<?php endif ?>
<?php if ($VAR->domain->previewDomainName): ?>
    server_name "<?php echo $VAR->domain->previewDomainName ?>";
<?php endif ?>

<?php if ($redirect_to_ssl && !$OPT['ssl']): ?>
	return 301 https://<?php echo $VAR->domain->targetName ?>$request_uri;
<?php else: ?>

<?php if ($OPT['ssl']): ?>
<?php $sslCertificate = $VAR->server->sni && $VAR->domain->physicalHosting->sslCertificate ?
    $VAR->domain->physicalHosting->sslCertificate :
    $OPT['ipAddress']->sslCertificate; ?>
<?php   if ($sslCertificate->ce): ?>
    ssl_certificate             <?php echo $sslCertificate->ceFilePath ?>;
    ssl_certificate_key         <?php echo $sslCertificate->ceFilePath ?>;
<?php       if ($sslCertificate->ca): ?>
    ssl_client_certificate      <?php echo $sslCertificate->caFilePath ?>;
<?php       endif ?>
<?php   endif ?>
<?php endif ?>

<?php if (!empty($VAR->domain->physicalHosting->proxySettings['nginxClientMaxBodySize'])): ?>
    client_max_body_size <?php echo $VAR->domain->physicalHosting->proxySettings['nginxClientMaxBodySize'] ?>;
<?php endif; ?>

<?php if ($VAR->domain->physicalHosting->scriptTimeout): ?>
    proxy_read_timeout <?php echo min($VAR->domain->physicalHosting->scriptTimeout, 2147483); ?>;
<?php endif; ?>

    root "<?php echo $OPT['ssl'] ? $VAR->domain->physicalHosting->httpsDir : $VAR->domain->physicalHosting->httpDir ?>";
    access_log "<?php echo $VAR->domain->physicalHosting->logsDir . '/' . ($OPT['ssl'] ? 'proxy_access_ssl_log' : 'proxy_access_log') ?>";
    error_log "<?php echo $VAR->domain->physicalHosting->logsDir . '/proxy_error_log' ?>";

<?php if ($OPT['default']): ?>
<?php echo $VAR->includeTemplate('service/nginxSitePreview.php') ?>
<?php endif; ?>

<?php echo $VAR->domain->physicalHosting->proxySettings['allowDeny'] ?>

    location / {
<?php if ($OPT['wordpress_site']): ?>
        index index.php index.cgi index.pl index.html index.xhtml index.htm index.shtml;
        try_files $uri $uri/ /index.php?$args;
<?php else: ?>
<?php    echo $VAR->includeTemplate('domain/service/proxy.php', $OPT); ?>
<?php endif; ?>
    }

<?php if (!$VAR->domain->physicalHosting->proxySettings['nginxTransparentMode'] && !$VAR->domain->physicalHosting->proxySettings['nginxServeStatic']): ?>
    location /internal-nginx-static-location/ {
        alias <?php echo $OPT['documentRoot'] ?>/;
        add_header X-Powered-By PleskLin;
        internal;
    }
<?php endif ?>

<?php if ($VAR->domain->active && !$VAR->domain->physicalHosting->proxySettings['nginxTransparentMode']): ?>

<?php if ($VAR->domain->physicalHosting->php && $VAR->domain->physicalHosting->proxySettings['nginxServePhp']
            || $VAR->domain->physicalHosting->proxySettings['nginxServeStatic']): ?>

<?php if ($VAR->domain->physicalHosting->proxySettings['fileSharingPrefix']): ?>
    location ~ ^/<?php echo $VAR->domain->physicalHosting->proxySettings['fileSharingPrefix'] ?>/ {
<?php echo $VAR->includeTemplate('domain/service/proxy.php', $OPT); ?>
    }
<?php endif; ?>

<?php endif; ?>

    location @apache_proxy {
<?php echo $VAR->includeTemplate('domain/service/proxy.php', $OPT); ?>
    }

<?php if ($VAR->domain->physicalHosting->proxySettings['nginxServeStatic']): ?>

<?php echo $VAR->includeTemplate('domain/service/nginxProtectedDirectories.php', $OPT); ?>

    location ~ ^/(.*\.(<?php echo $VAR->domain->physicalHosting->proxySettings['nginxStaticExtensions'] ?>))$ {
        try_files $uri @apache_proxy;
    }
<?php endif ?>

<?php if ($VAR->domain->physicalHosting->php && $VAR->domain->physicalHosting->proxySettings['nginxServePhp']): ?>

<?php if ($VAR->domain->physicalHosting->hasWebstat): ?>
    location ~ ^/(plesk-stat|webstat|webstat-ssl|ftpstat|anon_ftpstat|awstats-icon) {
        <?php echo $VAR->includeTemplate('domain/service/proxy.php', $OPT); ?>
    }
<?php endif; ?>

    location ~ ^/~(.+?)(/.*?\.php)(/.*)?$ {
        alias <?php echo $VAR->domain->physicalHosting->webUsersDir ?>/$1/$2;
        <?php echo $VAR->includeTemplate('domain/service/fpm.php'); ?>
    }

    location ~ ^/~(.+?)(/.*)?$ {
        <?php echo $VAR->includeTemplate('domain/service/proxy.php', $OPT); ?>
    }

    <?php echo $VAR->includeTemplate('domain/service/nginxWordpress.php', $OPT); ?>

    location ~ \.php(/.*)?$ {
        <?php echo $VAR->includeTemplate('domain/service/fpm.php'); ?>
    }

    <?php echo $VAR->includeTemplate('domain/service/nginxWordpressIndexing.php', $OPT); ?>

<?php endif ?>

	location ~ /$ {
        <?php echo $VAR->domain->physicalHosting->proxySettings['directoryIndex'] ?>
		try_files $uri $uri/ /index.php?$args;
    }

<?php endif ?>

<?php if (is_file($VAR->domain->physicalHosting->customNginxConfigFile)): ?>
    include "<?php echo $VAR->domain->physicalHosting->customNginxConfigFile ?>";
<?php endif; ?>
<?php endif; ?>
}