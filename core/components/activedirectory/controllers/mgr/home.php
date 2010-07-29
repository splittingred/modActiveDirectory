<?php
/**
 * Loads the home page.
 *
 * @package activedirectory
 * @subpackage controllers
 */
$modx->regClientStartupScript($adir->config['jsUrl'].'mgr/widgets/home.panel.js');
$modx->regClientStartupScript($adir->config['jsUrl'].'mgr/sections/home.js');
$output = '<div id="adir-panel-home-div"></div>';

return $output;
