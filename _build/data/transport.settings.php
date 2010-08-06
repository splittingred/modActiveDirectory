<?php
/**
 * Add in system settings
 * 
 * @package activedirectory
 * @subpackage build
 */
$settings = array();

$settings['activedirectory.account_suffix']= $modx->newObject('modSystemSetting');
$settings['activedirectory.account_suffix']->fromArray(array(
    'key' => 'activedirectory.account_suffix',
    'value' => '@forest.local',
    'xtype' => 'textfield',
    'namespace' => 'activedirectory',
    'area' => 'ActiveDirectory',
),'',true,true);

$settings['activedirectory.autoadd_adgroups']= $modx->newObject('modSystemSetting');
$settings['activedirectory.autoadd_adgroups']->fromArray(array(
    'key' => 'activedirectory.autoadd_adgroups',
    'value' => true,
    'xtype' => 'combo-boolean',
    'namespace' => 'activedirectory',
    'area' => 'ActiveDirectory',
),'',true,true);

$settings['activedirectory.autoadd_usergroups']= $modx->newObject('modSystemSetting');
$settings['activedirectory.autoadd_usergroups']->fromArray(array(
    'key' => 'activedirectory.autoadd_usergroups',
    'value' => '',
    'xtype' => 'textfield',
    'namespace' => 'activedirectory',
    'area' => 'ActiveDirectory',
),'',true,true);

$settings['activedirectory.base_dn']= $modx->newObject('modSystemSetting');
$settings['activedirectory.base_dn']->fromArray(array(
    'key' => 'activedirectory.base_dn',
    'value' => '',
    'xtype' => 'textfield',
    'namespace' => 'activedirectory',
    'area' => 'ActiveDirectory',
),'',true,true);

$settings['activedirectory.domain_controllers']= $modx->newObject('modSystemSetting');
$settings['activedirectory.domain_controllers']->fromArray(array(
    'key' => 'activedirectory.domain_controllers',
    'value' => '127.0.0.1',
    'xtype' => 'textfield',
    'namespace' => 'activedirectory',
    'area' => 'ActiveDirectory',
),'',true,true);

$settings['activedirectory.real_primarygroup']= $modx->newObject('modSystemSetting');
$settings['activedirectory.real_primarygroup']->fromArray(array(
    'key' => 'activedirectory.real_primarygroup',
    'value' => true,
    'xtype' => 'combo-boolean',
    'namespace' => 'activedirectory',
    'area' => 'ActiveDirectory',
),'',true,true);

$settings['activedirectory.recursive_groups']= $modx->newObject('modSystemSetting');
$settings['activedirectory.recursive_groups']->fromArray(array(
    'key' => 'activedirectory.recursive_groups',
    'value' => true,
    'xtype' => 'combo-boolean',
    'namespace' => 'activedirectory',
    'area' => 'ActiveDirectory',
),'',true,true);

$settings['activedirectory.use_ssl']= $modx->newObject('modSystemSetting');
$settings['activedirectory.use_ssl']->fromArray(array(
    'key' => 'activedirectory.use_ssl',
    'value' => false,
    'xtype' => 'combo-boolean',
    'namespace' => 'activedirectory',
    'area' => 'ActiveDirectory',
),'',true,true);

$settings['activedirectory.use_tls']= $modx->newObject('modSystemSetting');
$settings['activedirectory.use_tls']->fromArray(array(
    'key' => 'activedirectory.use_tls',
    'value' => false,
    'xtype' => 'textfield',
    'namespace' => 'activedirectory',
    'area' => 'ActiveDirectory',
),'',true,true);

return $settings;