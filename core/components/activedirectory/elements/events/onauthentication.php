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
 * Authenticates the user and syncs profile data via On*Authentication events
 * 
 * @package activedirectory
 */
$scriptProperties = $modx->event->params;
if (empty($scriptProperties['user']) || !is_object($scriptProperties['user'])) {
    $modx->event->output(true);
    return;
}
$classKey = $scriptProperties['user']->get('class_key');

/* authenticate the user */
$success = false;
$user =& $scriptProperties['user'];
if (!is_object($user) || !($user instanceof modUser)) {
    $modx->log(modX::LOG_LEVEL_INFO,'[ActiveDirectory] The user specified is not a valid modUser.');
    $modx->event->output(true);
    return;
}

/* if not an AD user, skip */
if ($user->get('class_key') != 'modActiveDirectoryUser') {
    $username = is_object($user) ? $user->get('username') : $user;
    $modx->log(modX::LOG_LEVEL_INFO,'[ActiveDirectory] User "'.$username.'" is not a modActiveDirectoryUser and therefore is being skipped.');
    $modx->event->output(true);
    return;
}

$username = $user->get('username');
$password = $scriptProperties['password'];

/* connect to activedirectory */
$connected = $madDriver->connect();
if (!$connected) {
    $modx->log(modX::LOG_LEVEL_ERROR,'[ActiveDirectory] Could not connect via LDAP to Active Directory.');
    $modx->event->output(false);
    return;
}
/* attempt to authenticate */
if (!($madDriver->authenticate($username,$password))) {
    $modx->log(modX::LOG_LEVEL_INFO,'[ActiveDirectory] Failed to authenticate "'.$user->get('username').'" with password "'.$password.'"');
    $modx->event->output(false);
    return;
}

/* get user info */
$userData = $madDriver->userInfo($username);
if (!empty($userData) && !empty($userData[0])) $userData = $userData[0];

/* setup profile data */
if (!empty($userData) && $user instanceof modActiveDirectoryUser) {
    $profile = $user->getOne('Profile');
    if (!empty($profile)) {
        $mad->syncProfile($profile,$userData);
    }
}

/* TODO: add ability to auto-setup user settings here */

/* always auto-add users to these groups */
$autoAddUserGroups = $modx->getOption('activedirectory.autoadd_usergroups',null,'');
if (!empty($autoAddUserGroups)) {
    $autoAddUserGroups = explode(',',$autoAddUserGroups);
    foreach ($autoAddUserGroups as $group) {
        $group = $modx->getObject('modUserGroup', array('name' => $group));
        if ($group) {
            $exists = $modx->getObject('modUserGroupMember', array('user_group' => $group->get('id'), 'member' => $user->get('id')));
            if (!$exists) {
                $membership = $modx->newObject('modUserGroupMember', array('user_group' => $group->get('id'), 'member' => $user->get('id'), 'role' => 1));
                $membership->save();
            }
        }
    }
}

/* if true, auto-add users to AD groups that exist as MODx groups */
$autoAddAdGroups = $modx->getOption('activedirectory.autoadd_adgroups',null,true);
if (!empty($autoAddAdGroups) && !empty($userData)) {
    $adGroups = $mad->getGroupsFromInfo($userData);
    foreach ($adGroups as $group) {
        $group = $modx->getObject('modUserGroup', array('name' => $group));
        if ($group) {
            $exists = $modx->getObject('modUserGroupMember', array('user_group' => $group->get('id'), 'member' => $user->get('id')));
            if (!$exists) {
                $membership = $modx->newObject('modUserGroupMember', array('user_group' => $group->get('id'), 'member' => $user->get('id'), 'role' => 1));
                $membership->save();
            }
        }
    }
}

$modx->event->_output = true;
return;