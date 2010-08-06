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
 * @package activedirectory
 */
class modActiveDirectoryUser extends modUser {

    function __construct(xPDO & $xpdo) {
        parent :: __construct($xpdo);
        $this->set('class_key','modActiveDirectoryUser');
    }

    /**
     * Not available until Revo 2.0.1
     */
    public function syncProfile(array $data = array()) {
        if (empty($data)) return false;
        $profile = $this->getOne('Profile');
        if (empty($profile)) return false;
        $saved = false;

        $data = $data[0];
        /* map of ActiveDirectory => MODx Profile fields */
        $map = array(
            'name' => 'fullname',
            'mail' => 'email',
            'streetaddress' => 'address',
            'l' => 'city',
            'st' => 'state',
            'co' => 'country',
            'postalcode' => 'zip',
            'mobile' => 'mobilephone',
            'telephonenumber' => 'phone',
            'info' => 'comment',
            'wwwhomepage' => 'website',
        );

        foreach ($data as $k => $v) {
            if (!is_array($v) || !array_key_exists($k,$map)) continue;
            $this->xpdo->log(xPDO::LOG_LEVEL_DEBUG,'[ActiveDirectory] Syncing field "'.$map[$k].'" to: "'.$v[0].'"');
            $profile->set($map[$k],$v[0]);
        }
        $id = $this->get('id');
        if (!empty($id)) {
            $saved = $profile->save();
        }
        return $saved;
    }
}