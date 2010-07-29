<?php
/**
 * @package activedirectory
 * @subpackage controllers
 */
require_once dirname(dirname(__FILE__)).'/model/activedirectory/modactivedirectory.class.php';
$adir = new modActiveDirectory($modx);
return $adir->initialize('mgr');