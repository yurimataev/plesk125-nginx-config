<?php if ($VAR->domain->isSeoRedirectToLanding) : ?>
server {
    <?php if ($OPT['ssl']): ?>
    listen <?php echo $OPT['ipAddress'] . ':' . $OPT['frontendPort'] . ($OPT['ssl'] ? ' ssl' : '') .
        ($OPT['ssl'] && $VAR->domain->physicalHosting->proxySettings['nginxHttp2'] ? ' http2' : '') ?>;
    <?php endif; ?>
    # <?php echo $OPT['ssl'] ? 'listen '.$OPT['frontendPort'].";\n" : "\n"; ?>
    server_name www.<?php echo $VAR->domain->asciiName ?>;
    return 301 <?php echo $OPT['redirect_scheme']; ?>://<?php echo $VAR->domain->asciiName ?>$request_uri;
}
<?php elseif ($VAR->domain->isSeoRedirectToWww): ?>
server {
    <?php if ($OPT['ssl']): ?>
    listen <?php echo $OPT['ipAddress'] . ':' . $OPT['frontendPort'] . ($OPT['ssl'] ? ' ssl' : '') .
        ($OPT['ssl'] && $VAR->domain->physicalHosting->proxySettings['nginxHttp2'] ? ' http2' : '') ?>;
    <?php endif; ?>
    # <?php echo $OPT['ssl'] ? 'listen '.$OPT['frontendPort'].";\n" : "\n"; ?>
    server_name <?php echo $VAR->domain->asciiName ?>;
    return 301 <?php echo $OPT['redirect_scheme']; ?>://www.<?php echo $VAR->domain->asciiName ?>$request_uri;
}
<?php endif; ?>
<?php if ($VAR->domain->isAliasRedirected): ?>
<?php     foreach ($VAR->domain->webAliases AS $alias): ?>
<?php         if ($alias->isSeoRedirect) : ?>
server {
    <?php if ($OPT['ssl']): ?>
    listen <?php echo $OPT['ipAddress'] . ':' . $OPT['frontendPort'] . ($OPT['ssl'] ? ' ssl' : '') .
        ($OPT['ssl'] && $VAR->domain->physicalHosting->proxySettings['nginxHttp2'] ? ' http2' : '') ?>;
    <?php endif; ?>
    # <?php echo $OPT['ssl'] ? 'listen '.$OPT['frontendPort'].";\n" : "\n"; ?>
    server_name <?php echo $alias->asciiName ?>;
    return 301 <?php echo $OPT['redirect_scheme']; ?>://<?php echo $VAR->domain->targetName ?>$request_uri;
}
server {
    <?php if ($OPT['ssl']): ?>
    listen <?php echo $OPT['ipAddress'] . ':' . $OPT['frontendPort'] . ($OPT['ssl'] ? ' ssl' : '') .
        ($OPT['ssl'] && $VAR->domain->physicalHosting->proxySettings['nginxHttp2'] ? ' http2' : '') ?>;
    <?php endif; ?>
    # <?php echo $OPT['ssl'] ? 'listen '.$OPT['frontendPort'].";\n" : "\n"; ?>
    server_name www.<?php echo $alias->asciiName ?>;
    return 301 <?php echo $OPT['redirect_scheme']; ?>://<?php echo $VAR->domain->targetName ?>$request_uri;
}
<?php         endif; ?>
<?php     endforeach; ?>
<?php endif; ?>