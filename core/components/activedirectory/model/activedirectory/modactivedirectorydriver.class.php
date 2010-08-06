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
 * modActiveDirectory LDAP class
 *
 * Based on adLDAP class here: http://adldap.sourceforge.net/wiki/doku.php?id=api
 *
 * @package activedirectory
 */
class modActiveDirectoryDriver {
    /* random constants for future dev */
    const NORMAL_ACCOUNT = 805306368;
    const WORKSTATION_TRUST = 805306369;
    const INTERDOMAIN_TRUST = 805306370;
    const SECURITY_GLOBAL_GROUP = 268435456;
    const DISTRIBUTION_GROUP = 268435457;
    const SECURITY_LOCAL_GROUP = 536870912;
    const DISTRIBUTION_LOCAL_GROUP = 536870913;
    const FOLDER = 'OU';
    const CONTAINER = 'CN';

    /**
     * @var string The account suffix for your domain, can be set when the class is invoked
     */
    const OPT_ACCOUNT_SUFFIX = 'activedirectory.account_suffix';
    /**
     * @var string The base dn for your domain
     */
    const OPT_BASE_DN = 'activedirectory.base_dn';
    /**
     * @var string Comma-separated list of domain controllers. Specifiy multiple
     * controllers if you would like the class to balance the LDAP queries
     * amongst multiple servers
     */
    const OPT_DOMAIN_CONTROLLERS = 'activedirectory.domain_controllers';
    /**
     * @var bool AD does not return the primary group.
     * http://support.microsoft.com/?kbid=321360
     * This tweak will resolve the real primary group. Setting to false will
     * fudge "Domain Users" and is much faster. Keep in mind though that if
     * someone's primary group is NOT domain users, this is obviously going
     * to mess up the results.
     */
    const OPT_REAL_PRIMARYGROUP = 'activedirectory.real_primarygroup';
    /**
     * @var bool Use SSL (LDAPS), your server needs to be setup, please see
     * http://adldap.sourceforge.net/wiki/doku.php?id=ldap_over_ssl
     */
    const OPT_USE_SSL = 'activedirectory.use_ssl';
    /**
     * @var bool If you wish to use TLS you should ensure use_ssl is set to
     * false and vice-versa
     */
    const OPT_USE_TLS = 'activedirectory.use_tls';
    /**
     * @var bool When querying group memberships, do it recursively
     * eg. User Fred is a member of Group A, which is a member of Group B,
     * which is a member of Group C user_ingroup("Fred","C") will returns true
     * with this option turned on, false if turned off
     */
    const OPT_RECURSIVE_GROUPS = 'activedirectory.recursive_groups';
    /**
     * @var string Optional account with higher privileges for searching.
     * This should be set to a domain admin account.
     */
    const OPT_ADMIN_USERNAME = 'activedirectory.admin_username';
    const OPT_ADMIN_PASSWORD = 'activedirectory.admin_password';

    public $config = array();
    public $modx = null;
    
   /**
    * Connection and bind default variables
    *
    * @var mixed
    * @var mixed
    */
    protected $_conn;
    protected $_bind;

    /**
    * Default Constructor
    *
    * Tries to bind to the AD domain over LDAP or LDAPs
    *
    * @param array $options Array of options to pass to the constructor
    * @return bool
    */
    function __construct(modX $modx, array $config = array()) {
        $this->modx =& $modx;
        $this->config = array_merge(array(
        ),$config);

        if ($this->checkLdapSupport() === false) {
            $this->modx->log(modX::LOG_LEVEL_ERROR,'[ActiveDirectory] No LDAP support for PHP. See: http://www.php.net/ldap');
        }

        return $this->connect();
    }

    /**
    * Default Destructor
    *
    * Closes the LDAP connection
    *
    * @return void
    */
    function __destruct() { 
        $this->close();
    }

    public function setOption($k,$v) {
        $this->config[$k] = $v;
    }

    public function getOption($k,$return = '') {
        return $this->modx->getOption($k,$this->config,$return);
    }

    /**
    * Connects and Binds to the Domain Controller
    *
    * @return bool
    */
    public function connect() {
        $useSsl = $this->getOption(modActiveDirectoryDriver::OPT_USE_SSL,false);
        $useTls = $this->getOption(modActiveDirectoryDriver::OPT_USE_TLS,false);
        
        // Connect to the AD/LDAP server as the username/password
        $dc = $this->getRandomController();
        if ($useSsl) {
            $this->_conn = ldap_connect("ldaps://".$dc, 636);
        } else {
            $this->_conn = ldap_connect($dc);
        }

        // Set some ldap options for talking to AD
        ldap_set_option($this->_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->_conn, LDAP_OPT_REFERRALS, 1);
        ldap_set_option($this->_conn, LDAP_OPT_TIMELIMIT, 10);

        if ($useTls) {
            ldap_start_tls($this->_conn);
        }

        // Bind as a domain admin if they've set it up
        $username = $this->getOption(modActiveDirectoryDriver::OPT_ADMIN_USERNAME,'');
        $password = $this->getOption(modActiveDirectoryDriver::OPT_ADMIN_PASSWORD,'');
        $accountSuffix = $this->getOption(modActiveDirectoryDriver::OPT_ACCOUNT_SUFFIX,'@forest.local');
        if (!empty($password) && !empty($password)) {
            $this->_bind = @ldap_bind($this->_conn,$username.$accountSuffix,$password);
            if (!$this->_bind) {
                if ($useSsl && !$useTls) {
                    // If you have problems troubleshooting, remove the @ character from the ldap_bind command above to get the actual error message
                    $this->modx->log(modX::LOG_LEVEL_ERROR,'Bind to Active Directory failed. Either the LDAPs connection failed or the login credentials are incorrect. AD said: ' . $this->getLastError());
                } else {
                    $this->modx->log(modX::LOG_LEVEL_ERROR,'Bind to Active Directory failed. Check the login credentials and/or server details. AD said: ' . $this->getLastError());
                }
            }
        }

        $baseDn = $this->getOption(modActiveDirectoryDriver::OPT_BASE_DN,'');
        if (empty($baseDn)) {
            $this->setOption(modActiveDirectoryDriver::OPT_BASE_DN,$this->findBaseDn());
        }

        return true;
    }

    /**
    * Closes the LDAP connection
    *
    * @return void
    */
    public function close() {
        ldap_close($this->_conn);
    }

    /**
    * Validate a user's login credentials
    *
    * @param string $username A user's AD username
    * @param string $password A user's AD password
    * @param bool optional $prevent_rebind
    * @return bool
    */
    public function authenticate($username,$password,$preventRebind = false){
        if (empty($username) || empty($password)) { return false; }

        // Bind as the user
        $accountSuffix = $this->getOption(modActiveDirectoryDriver::OPT_ACCOUNT_SUFFIX,'@forest.local');
        $this->_bind = @ldap_bind($this->_conn,$username.$accountSuffix,$password);
        if (!$this->_bind) { return false; }

        /* Once we've checked their details, kick back into admin mode if we have it */
        $adminPassword = $this->getOption(modActiveDirectoryDriver::OPT_ADMIN_PASSWORD);
        if (!empty($adminPassword) && !$preventRebind) {
            $this->_bind = @ldap_bind($this->_conn,modActiveDirectoryDriver::OPT_ADMIN_USERNAME.$accountSuffix,modActiveDirectoryDriver::OPT_ADMIN_PASSWORD);
            if (!$this->_bind) {
                /* This should never happen in theory */
                $this->modx->log(modX::LOG_LEVEL_ERROR,'[ActiveDirectory] Rebind to Active Directory failed. AD said: ' . $this->getLastError());
                return false;
            }
        }

        return true;
    }


    //************************************************************************************************************
    // SERVER FUNCTIONS

    /**
    * Find the Base DN of your domain controller
    *
    * @return string
    */
    public function findBaseDn() {
        $namingContext = $this->getRootDse(array('defaultnamingcontext'));
        return $namingContext[0]['defaultnamingcontext'][0];
    }

    /**
    * Get the RootDSE properties from a domain controller
    *
    * @param array $attributes The attributes you wish to query e.g. defaultnamingcontext
    * @return array
    */
    public function getRootDse(array $attributes = array('*','+')) {
        if (!$this->_bind){ return false; }

        $sr = @ldap_read($this->_conn,null,'objectClass=*',$attributes);
        $entries = @ldap_get_entries($this->_conn,$sr);
        return $entries;
    }

    //************************************************************************************************************
    // UTILITY FUNCTIONS (Many of these functions are protected and can only be called from within the class)

    /**
    * Get last error from Active Directory
    *
    * This function gets the last message from Active Directory
    * This may indeed be a 'Success' message but if you get an unknown error
    * it might be worth calling this function to see what errors were raised
    *
    * @return string
    */
    public function getLastError() {
        return @ldap_error($this->_conn);
    }

    /**
    * Detect LDAP support in php
    *
    * @return bool
    */
    protected function checkLdapSupport() {
        return function_exists('ldap_connect');
    }

    /**
    * Schema
    *
    * @param array $attributes Attributes to be queried
    * @return array
    */
    protected function adldap_schema($attributes){

        // LDAP doesn't like NULL attributes, only set them if they have values
        // If you wish to remove an attribute you should set it to a space
        // TO DO: Adapt user_modify to use ldap_mod_delete to remove a NULL attribute
        $mod = array();

        // Check every attribute to see if it contains 8bit characters and then UTF8 encode them
        array_walk($attributes, array($this, 'encode8bit'));

        if ($attributes["address_city"]){ $mod["l"][0]=$attributes["address_city"]; }
        if ($attributes["address_code"]){ $mod["postalCode"][0]=$attributes["address_code"]; }
        //if ($attributes["address_country"]){ $mod["countryCode"][0]=$attributes["address_country"]; } // use country codes?
        if ($attributes["address_country"]){ $mod["c"][0]=$attributes["address_country"]; }
        if ($attributes["address_pobox"]){ $mod["postOfficeBox"][0]=$attributes["address_pobox"]; }
        if ($attributes["address_state"]){ $mod["st"][0]=$attributes["address_state"]; }
        if ($attributes["address_street"]){ $mod["streetAddress"][0]=$attributes["address_street"]; }
        if ($attributes["company"]){ $mod["company"][0]=$attributes["company"]; }
        if ($attributes["change_password"]){ $mod["pwdLastSet"][0]=0; }
        if ($attributes["department"]){ $mod["department"][0]=$attributes["department"]; }
        if ($attributes["description"]){ $mod["description"][0]=$attributes["description"]; }
        if ($attributes["display_name"]){ $mod["displayName"][0]=$attributes["display_name"]; }
        if ($attributes["email"]){ $mod["mail"][0]=$attributes["email"]; }
        if ($attributes["expires"]){ $mod["accountExpires"][0]=$attributes["expires"]; } //unix epoch format?
        if ($attributes["firstname"]){ $mod["givenName"][0]=$attributes["firstname"]; }
        if ($attributes["home_directory"]){ $mod["homeDirectory"][0]=$attributes["home_directory"]; }
        if ($attributes["home_drive"]){ $mod["homeDrive"][0]=$attributes["home_drive"]; }
        if ($attributes["initials"]){ $mod["initials"][0]=$attributes["initials"]; }
        if ($attributes["logon_name"]){ $mod["userPrincipalName"][0]=$attributes["logon_name"]; }
        if ($attributes["manager"]){ $mod["manager"][0]=$attributes["manager"]; }  //UNTESTED ***Use DistinguishedName***
        if ($attributes["office"]){ $mod["physicalDeliveryOfficeName"][0]=$attributes["office"]; }
        if ($attributes["password"]){ $mod["unicodePwd"][0]=$this->encodePassword($attributes["password"]); }
        if ($attributes["profile_path"]){ $mod["profilepath"][0]=$attributes["profile_path"]; }
        if ($attributes["script_path"]){ $mod["scriptPath"][0]=$attributes["script_path"]; }
        if ($attributes["surname"]){ $mod["sn"][0]=$attributes["surname"]; }
        if ($attributes["title"]){ $mod["title"][0]=$attributes["title"]; }
        if ($attributes["telephone"]){ $mod["telephoneNumber"][0]=$attributes["telephone"]; }
        if ($attributes["mobile"]){ $mod["mobile"][0]=$attributes["mobile"]; }
        if ($attributes["pager"]){ $mod["pager"][0]=$attributes["pager"]; }
        if ($attributes["ipphone"]){ $mod["ipphone"][0]=$attributes["ipphone"]; }
        if ($attributes["web_page"]){ $mod["wWWHomePage"][0]=$attributes["web_page"]; }
        if ($attributes["fax"]){ $mod["facsimileTelephoneNumber"][0]=$attributes["fax"]; }
        if ($attributes["enabled"]){ $mod["userAccountControl"][0]=$attributes["enabled"]; }

        // Distribution List specific schema
        if ($attributes["group_sendpermission"]){ $mod["dlMemSubmitPerms"][0]=$attributes["group_sendpermission"]; }
        if ($attributes["group_rejectpermission"]){ $mod["dlMemRejectPerms"][0]=$attributes["group_rejectpermission"]; }

        // Exchange Schema
        if ($attributes["exchange_homemdb"]){ $mod["homeMDB"][0]=$attributes["exchange_homemdb"]; }
        if ($attributes["exchange_mailnickname"]){ $mod["mailNickname"][0]=$attributes["exchange_mailnickname"]; }
        if ($attributes["exchange_proxyaddress"]){ $mod["proxyAddresses"][0]=$attributes["exchange_proxyaddress"]; }
        if ($attributes["exchange_usedefaults"]){ $mod["mDBUseDefaults"][0]=$attributes["exchange_usedefaults"]; }
        if ($attributes["exchange_policyexclude"]){ $mod["msExchPoliciesExcluded"][0]=$attributes["exchange_policyexclude"]; }
        if ($attributes["exchange_policyinclude"]){ $mod["msExchPoliciesIncluded"][0]=$attributes["exchange_policyinclude"]; }

        // This schema is designed for contacts
        if ($attributes["exchange_hidefromlists"]){ $mod["msExchHideFromAddressLists"][0]=$attributes["exchange_hidefromlists"]; }
        if ($attributes["contact_email"]){ $mod["targetAddress"][0]=$attributes["contact_email"]; }

        //echo ("<pre>"); print_r($mod);
        /*
        // modifying a name is a bit fiddly
        if ($attributes["firstname"] && $attributes["surname"]){
            $mod["cn"][0]=$attributes["firstname"]." ".$attributes["surname"];
            $mod["displayname"][0]=$attributes["firstname"]." ".$attributes["surname"];
            $mod["name"][0]=$attributes["firstname"]." ".$attributes["surname"];
        }
        */

        return empty($mod) ? false : $mod;
    }

    /**
    * Coping with AD not returning the primary group
    * http://support.microsoft.com/?kbid=321360
    *
    * This is a re-write based on code submitted by Bruce which prevents the
    * need to search each security group to find the true primary group
    *
    * @param string $gid Group ID
    * @param string $usersid User's Object SID
    * @return string
    */
    protected function getPrimaryGroup($gid, $usersid){
        if ($gid === null || $usersid === null){ return false; }
        $r=false;

        $gsid = substr_replace($usersid,pack('V',$gid),strlen($usersid)-4,4);
        $filter = '(objectsid='.$this->getTextSID($gsid).')';
        $fields = array("samaccountname","distinguishedname");
        $baseDn = $this->findBaseDn();
        $sr = ldap_search($this->_conn,$baseDn,$filter,$fields);
        if ($sr) {
            $entries = ldap_get_entries($this->_conn, $sr);
        } else {
            return false;
        }

        return $entries[0]['distinguishedname'][0];
     }

    /**
    * Convert a binary SID to a text SID
    *
    * @param string $binsid A Binary SID
    * @return string
    */
     protected function getTextSID($binsid) {
        $hex_sid = bin2hex($binsid);
        $rev = hexdec(substr($hex_sid, 0, 2));
        $subcount = hexdec(substr($hex_sid, 2, 2));
        $auth = hexdec(substr($hex_sid, 4, 12));
        $result = "$rev-$auth";

        for ($x=0;$x < $subcount; $x++) {
            $subauth[$x] =
                hexdec($this->littleEndian(substr($hex_sid, 16 + ($x * 8), 8)));
                $result .= "-" . $subauth[$x];
        }

        // Cheat by tacking on the S-
        return 'S-' . $result;
     }

    /**
    * Converts a little-endian hex number to one that hexdec() can convert
    *
    * @param string $hex A hex code
    * @return string
    */
     protected function littleEndian($hex) {
        $result = '';
        for ($x = strlen($hex) - 2; $x >= 0; $x = $x - 2) {
            $result .= substr($hex, $x, 2);
        }
        return $result;
     }

    /**
    * Converts a binary attribute to a string
    *
    * @param string $bin A binary LDAP attribute
    * @return string
    */
    protected function binary2text($bin) {
        $hex_guid = bin2hex($bin);
        $hex_guid_to_guid_str = '';
        for($k = 1; $k <= 4; ++$k) {
            $hex_guid_to_guid_str .= substr($hex_guid, 8 - 2 * $k, 2);
        }
        $hex_guid_to_guid_str .= '-';
        for($k = 1; $k <= 2; ++$k) {
            $hex_guid_to_guid_str .= substr($hex_guid, 12 - 2 * $k, 2);
        }
        $hex_guid_to_guid_str .= '-';
        for($k = 1; $k <= 2; ++$k) {
            $hex_guid_to_guid_str .= substr($hex_guid, 16 - 2 * $k, 2);
        }
        $hex_guid_to_guid_str .= '-' . substr($hex_guid, 16, 4);
        $hex_guid_to_guid_str .= '-' . substr($hex_guid, 20);
        return strtoupper($hex_guid_to_guid_str);
    }

    /**
    * Converts a binary GUID to a string GUID
    *
    * @param string $binaryGuid The binary GUID attribute to convert
    * @return string
    */
    public function decodeGuid($binaryGuid) {
        if ($binaryGuid === null) { return ("Missing compulsory field [binaryGuid]"); }

        $strGUID = $this->binary2text($binaryGuid);
        return ($strGUID);
    }

    /**
    * Converts a string GUID to a hexdecimal value so it can be queried
    *
    * @param string $strGUID A string representation of a GUID
    * @return string
    */
    protected function strguid2hex($strGUID) {
        $strGUID = str_replace('-', '', $strGUID);

        $octet_str = '\\' . substr($strGUID, 6, 2);
        $octet_str .= '\\' . substr($strGUID, 4, 2);
        $octet_str .= '\\' . substr($strGUID, 2, 2);
        $octet_str .= '\\' . substr($strGUID, 0, 2);
        $octet_str .= '\\' . substr($strGUID, 10, 2);
        $octet_str .= '\\' . substr($strGUID, 8, 2);
        $octet_str .= '\\' . substr($strGUID, 14, 2);
        $octet_str .= '\\' . substr($strGUID, 12, 2);
        //$octet_str .= '\\' . substr($strGUID, 16, strlen($strGUID));
        for ($i=16; $i<=(strlen($strGUID)-2); $i++) {
            if (($i % 2) == 0) {
                $octet_str .= '\\' . substr($strGUID, $i, 2);
            }
        }

        return $octet_str;
    }

    /**
    * Obtain the user's distinguished name based on their userid
    *
    *
    * @param string $username The username
    * @param bool $isGUID Is the username passed a GUID or a samAccountName
    * @return string
    */
    protected function userDn($username,$isGUID=false){
        $user = $this->userInfo($username,array('cn'),$isGUID);
        if ($user[0]["dn"] === null) return false;
        $user_dn = $user[0]["dn"];
        return $user_dn;
    }

    /**
    * Find information about the users
    *
    * @param string $username The username to query
    * @param array $fields Array of parameters to query
    * @param bool $isGUID Is the username passed a GUID or a samAccountName
    * @return array
    */
    public function userInfo($username,array $fields = array(),$isGUID = false){
        if ($username === null) return false;
        if (!$this->_bind) return false;

        if ($isGUID === true) {
            $username = $this->strguid2hex($username);
            $filter = "objectguid=".$username;
        } else {
            $filter = "samaccountname=".$username;
        }
        $baseDn = $this->findBaseDn();
        $sr = ldap_search($this->_conn,$baseDn,$filter,$fields);
        $entries = ldap_get_entries($this->_conn, $sr);

        if ($entries[0]['count'] >= 1) {
            // AD does not return the primary group in the ldap query, we may need to fudge it
            if ($this->getOption(modActiveDirectoryDriver::OPT_REAL_PRIMARYGROUP) && isset($entries[0]["primarygroupid"][0]) && isset($entries[0]["objectsid"][0])) {
                $entries[0]["memberof"][] = $this->getPrimaryGroup($entries[0]["primarygroupid"][0], $entries[0]["objectsid"][0]);
            } else {
                $entries[0]["memberof"][] = "CN=Domain Users,CN=Users,".$baseDn;
            }
        }

        $entries[0]["memberof"]["count"]++;
        return $entries;
    }

    /**
    * Encode a password for transmission over LDAP
    *
    * @param string $password The password to encode
    * @return string
    */
    protected function encodePassword($password){
        $password = '"'.$password.'"';
        $encoded = '';
        for ($i=0; $i < strlen($password); $i++) {
            $encoded .= $password[$i]."\000";
        }
        return $encoded;
    }

    /**
    * Escape strings for the use in LDAP filters
    *
    * DEVELOPERS SHOULD BE DOING PROPER FILTERING IF THEY'RE ACCEPTING USER INPUT
    * Ported from Perl's Net::LDAP::Util escape_filter_value
    *
    * @param string $str The string the parse
    * @author Port by Andreas Gohr <andi@splitbrain.org>
    * @return string
    */
    protected function ldapSlashes($str){
        return preg_replace('/([\x00-\x1F\*\(\)\\\\])/e',
                            '"\\\\\".join("",unpack("H2","$1"))',
                            $str);
    }

    /**
    * Select a random domain controller from your domain controller array
    *
    * @return string
    */
    protected function getRandomController() {
        mt_srand(doubleval(microtime()) * 100000000); // For older PHP versions
        $controllers = explode(',',$this->getOption(modActiveDirectoryDriver::OPT_DOMAIN_CONTROLLERS,'127.0.0.1'));
        return $controllers[array_rand($controllers)];
    }

    /**
    * Account control options
    *
    * @param array $options The options to convert to int
    * @return int
    */
    protected function accountControl($options){
        $val=0;

        if (is_array($options)){
            if (in_array("SCRIPT",$options)){ $val=$val+1; }
            if (in_array("ACCOUNTDISABLE",$options)){ $val=$val+2; }
            if (in_array("HOMEDIR_REQUIRED",$options)){ $val=$val+8; }
            if (in_array("LOCKOUT",$options)){ $val=$val+16; }
            if (in_array("PASSWD_NOTREQD",$options)){ $val=$val+32; }
            //PASSWD_CANT_CHANGE Note You cannot assign this permission by directly modifying the UserAccountControl attribute.
            //For information about how to set the permission programmatically, see the "Property flag descriptions" section.
            if (in_array("ENCRYPTED_TEXT_PWD_ALLOWED",$options)){ $val=$val+128; }
            if (in_array("TEMP_DUPLICATE_ACCOUNT",$options)){ $val=$val+256; }
            if (in_array("NORMAL_ACCOUNT",$options)){ $val=$val+512; }
            if (in_array("INTERDOMAIN_TRUST_ACCOUNT",$options)){ $val=$val+2048; }
            if (in_array("WORKSTATION_TRUST_ACCOUNT",$options)){ $val=$val+4096; }
            if (in_array("SERVER_TRUST_ACCOUNT",$options)){ $val=$val+8192; }
            if (in_array("DONT_EXPIRE_PASSWORD",$options)){ $val=$val+65536; }
            if (in_array("MNS_LOGON_ACCOUNT",$options)){ $val=$val+131072; }
            if (in_array("SMARTCARD_REQUIRED",$options)){ $val=$val+262144; }
            if (in_array("TRUSTED_FOR_DELEGATION",$options)){ $val=$val+524288; }
            if (in_array("NOT_DELEGATED",$options)){ $val=$val+1048576; }
            if (in_array("USE_DES_KEY_ONLY",$options)){ $val=$val+2097152; }
            if (in_array("DONT_REQ_PREAUTH",$options)){ $val=$val+4194304; }
            if (in_array("PASSWORD_EXPIRED",$options)){ $val=$val+8388608; }
            if (in_array("TRUSTED_TO_AUTH_FOR_DELEGATION",$options)){ $val=$val+16777216; }
        }
        return $val;
    }

    /**
    * Take an LDAP query and return the nice names, without all the LDAP prefixes (eg. CN, DN)
    *
    * @param array $groups
    * @return array
    */
    protected function niceNames($groups){
        $groupArray=array();
        for ($i = 0; $i<$groups["count"]; $i++){ // For each group
            $line = $groups[$i];

            if (strlen($line)>0) {
                // More presumptions, they're all prefixed with CN=
                // so we ditch the first three characters and the group
                // name goes up to the first comma
                $bits = explode(",",$line);
                $groupArray[] = substr($bits[0],3,(strlen($bits[0])-3));
            }
        }
        return $groupArray;
    }

    /**
    * Delete a distinguished name from Active Directory
    * You should never need to call this yourself, just use the wrapper functions user_delete and contact_delete
    *
    * @param string $dn The distinguished name to delete
    * @return bool
    */
    protected function dnDelete($dn){
        $result = ldap_delete($this->_conn, $dn);
        return !empty($result);
    }

    /**
    * Convert a boolean value to a string
    * You should never need to call this yourself
    *
    * @param bool $bool Boolean value
    * @return string
    */
    protected function bool2str($bool) {
        return $bool ? 'TRUE' : 'FALSE';
    }

    /**
    * Convert 8bit characters e.g. accented characters to UTF8 encoded characters
    */
    protected function encode8bit(&$item, $key) {
        $encode = false;
        if (is_string($item)) {
            for ($i=0; $i< strlen($item); $i++) {
                if (ord($item[$i]) >> 7) {
                    $encode = true;
                }
            }
        }
        if ($encode === true && $key != 'password') {
            $item = utf8_encode($item);
        }
    }
}