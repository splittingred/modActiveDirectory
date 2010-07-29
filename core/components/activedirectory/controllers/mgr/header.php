<?php
/**
 * Loads the header for mgr pages.
 *
 * @package activedirectory
 * @subpackage controllers
 */
$modx->regClientCSS($adir->config['cssUrl'].'mgr.css');
$modx->regClientStartupScript($adir->config['jsUrl'].'mgr/adir.js');
$modx->regClientStartupHTMLBlock('<script type="text/javascript">
Ext.onReady(function() {
    ADir.config = '.$modx->toJSON($adir->config).';
    ADir.config.connector_url = "'.$adir->config['connectorUrl'].'";
    ADir.action = "'.(!empty($_REQUEST['a']) ? $_REQUEST['a'] : 0).'";
});
</script>');

return '';