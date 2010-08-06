<?php
/**
 * modActiveDirectory
 *
 * Copyright 2010 by Shaun McCormick <shaun@modx.com>
 *
 * This file is part of modActiveDirectory, which integrates Active Directory
 * authentication into MODx Revolution.
 *
 * modActiveDirectory is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option) any
 * later version.
 *
 * modActiveDirectory is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * modActiveDirectory; if not, write to the Free Software Foundation, Inc.,
 * 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package activedirectory
 */
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