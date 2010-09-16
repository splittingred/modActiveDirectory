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
 * Handles OnUserNotFound event
 *
 * @package activedirectory
 */
$scriptProperties = $modx->event->params;
if (empty($scriptProperties['username'])) return;
$modx->event->_output = false;

$username = is_object($scriptProperties['user']) && $scriptProperties['user'] instanceof modUser ? $scriptProperties['user']->get('username') : $scriptProperties['username'];

/* connect to activedirectory */
$connected = $madDriver->connect();
if (!$connected) {
    $modx->log(modX::LOG_LEVEL_ERROR,'[ActiveDirectory] Could not connect via LDAP to Active Directory.');
    $modx->event->output(false);
    return;
}

/* authenticate the user */
if ($madDriver->authenticate($username,$scriptProperties['password'])) {
    $user =& $scriptProperties['user'];
    $user = $modx->getObject('modActiveDirectoryUser',array('username' => $username));
    if (empty($user)) {
        $user = $modx->newObject('modActiveDirectoryUser');
        $user->set('username', $username);
        $user->set('active',true);
        $user->save();
        $profile = $modx->newObject('modUserProfile');
        $profile->set('internalKey',$user->get('id'));
        $profile->save();
        $user->Profile =& $profile;
    } else {
        $user->getOne('Profile');
    }
    $modx->event->_output = $user;
    $modx->event->stopPropagation();
    return;
}
$modx->log(modX::LOG_LEVEL_INFO,'[ActiveDirectory] Could not authenticate user: "'.$username.'" with password "'.$scriptProperties['password'].'".');
$modx->event->_output = false;
return;
