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
 * Handle plugin events
 * 
 * @package activedirectory
 */
if (!$modx->getOption('activedirectory.enabled',$scriptProperties,false)) return;
$mad = $modx->getService('mad','modActiveDirectory',$modx->getOption('activedirectory.core_path',null,$modx->getOption('core_path').'components/activedirectory/').'model/activedirectory/',$scriptProperties);
if (!($mad instanceof modActiveDirectory)) {
    $modx->log(modX::LOG_LEVEL_ERROR,'[ActiveDirectory] Could not load ActiveDirectory class.');
    $modx->event->output(false);
    return;
}
$madDriver = $mad->loadDriver();
if (!($madDriver instanceof modActiveDirectoryDriver)) {
    $modx->log(modX::LOG_LEVEL_ERROR,'[ActiveDirectory] Could not load ActiveDirectoryDriver class.');
    $modx->event->output(false);
    return;
}

/* grab correct event processor */
$eventProcessor = false;
switch ($modx->event->name) {
    /* authentication */
    case 'OnWebAuthentication':
    case 'OnManagerAuthentication':
        $eventProcessor = 'onauthentication';
        break;
    /* onusernotfound */
    case 'OnUserNotFound':
        $eventProcessor = 'onusernotfound';
        break;
}

/* if found processor, load it */
if (!empty($eventProcessor)) {
    $eventProcessor = $mad->config['eventsPath'].$eventProcessor.'.php';
    if (file_exists($eventProcessor)) {
        include $eventProcessor;
    }
}
return;