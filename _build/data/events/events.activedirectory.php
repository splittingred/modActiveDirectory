<?php
/**
 * Add events to ActiveDirectory plugin
 * 
 * @package activedirectory
 * @subpackage build
 */
$events = array();

$events['OnManagerAuthentication']= $modx->newObject('modPluginEvent');
$events['OnManagerAuthentication']->fromArray(array(
    'event' => 'OnManagerAuthentication',
    'priority' => 0,
    'propertyset' => 0,
),'',true,true);
$events['OnWebAuthentication']= $modx->newObject('modPluginEvent');
$events['OnWebAuthentication']->fromArray(array(
    'event' => 'OnWebAuthentication',
    'priority' => 0,
    'propertyset' => 0,
),'',true,true);
$events['OnUserNotFound']= $modx->newObject('modPluginEvent');
$events['OnUserNotFound']->fromArray(array(
    'event' => 'OnUserNotFound',
    'priority' => 0,
    'propertyset' => 0,
),'',true,true);

return $events;