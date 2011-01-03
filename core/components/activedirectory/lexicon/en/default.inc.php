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
 * Default English language file for ActiveDirectory
 *
 * @package activedirectory
 */
$_lang['activedirectory'] = 'Active Directory';

$_lang['setting_activedirectory.account_suffix'] = 'Account Suffix';
$_lang['setting_activedirectory.account_suffix_desc'] = 'The account suffix for your domain. Usually in @forest.domain format.';
$_lang['setting_activedirectory.autoadd_adgroups'] = 'Auto-Add ActiveDirectory Groups';
$_lang['setting_activedirectory.autoadd_adgroups_desc'] = 'If true, will grab all Active Directory groups the User belongs to, and search for any matching UserGroups in MODx. If any are found, the MODx User will automatically be added to the matching MODx UserGroups.';
$_lang['setting_activedirectory.autoadd_usergroups'] = 'Auto-Add User Groups';
$_lang['setting_activedirectory.autoadd_usergroups_desc'] = 'A comma-separated list of MODx UserGroup names which the User will always be added to.';
$_lang['setting_activedirectory.enabled'] = 'ActiveDirectory SSO Enabled';
$_lang['setting_activedirectory.enabled_desc'] = 'When set to Yes, enables the modActiveDirectory SSO integration. If set to No, the plugin and login system will be bypassed.';
$_lang['setting_activedirectory.base_dn'] = 'Base DN';
$_lang['setting_activedirectory.base_dn_desc'] = 'The base dn for your domain. This can usually be left blank, as MODx will automatically calculate it for you.';
$_lang['setting_activedirectory.domain_controllers'] = 'Domain Controllers';
$_lang['setting_activedirectory.domain_controllers_desc'] = 'Comma-separated list of domain controllers. Specifiy multiple controllers if you would like the class to balance the LDAP queries.';
$_lang['setting_activedirectory.real_primarygroup'] = 'Real Primary Group';
$_lang['setting_activedirectory.real_primarygroup_desc'] = 'This tweak will resolve the real primary group. Setting to false will fudge "Domain Users" and is much faster. Keep in mind though that if someones primary group is NOT "Domain Users", this is obviously going to mess up the results. Related to <a href="http://support.microsoft.com/?kbid=321360" target="blank">http://support.microsoft.com/?kbid=321360</a>.';
$_lang['setting_activedirectory.recursive_groups'] = 'Recursive Groups';
$_lang['setting_activedirectory.recursive_groups_desc'] = 'When querying group memberships, do so recursively. Recommended to leave as Yes.';
$_lang['setting_activedirectory.use_ssl'] = 'Use SSL';
$_lang['setting_activedirectory.use_ssl_desc'] = 'Use SSL (LDAPS). Your AD server will need to be setup to support this. Works only if use_tls is off.';
$_lang['setting_activedirectory.use_tls'] = 'Use TLS';
$_lang['setting_activedirectory.use_tls_desc'] = 'Use TLS. Your AD server will need to be setup to support this. Works only if use_ssl is off.';
