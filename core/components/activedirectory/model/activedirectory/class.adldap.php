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
* Main adLDAP class
*
* Can be initialised using $adldap = new adLDAP();
*
* Something to keep in mind is that Active Directory is a permissions
* based directory. If you bind as a domain user, you can't fetch as
* much information on other users as you could as a domain admin.
*
* Before asking questions, please read the Documentation at
* http://adldap.sourceforge.net/wiki/doku.php?id=api
*/
class adLDAP {
    /**
    * The account suffix for your domain, can be set when the class is invoked
    *
    * @var string
    */
    protected $_account_suffix = "@exemple.com";

    /**
    * The base dn for your domain
    *
    * @var string
    */
    protected $_base_dn = "DC=exemple,DC=com";

    /**
    * Array of domain controllers. Specifiy multiple controllers if you
    * would like the class to balance the LDAP queries amongst multiple servers
    *
    * @var array
    */
    protected $_domain_controllers = array ("dc1.exemple.com","dc2.exemple.com");

    /**
    * Optional account with higher privileges for searching
    * This should be set to a domain admin account
    *
    * @var string
    * @var string
    */
	protected $_ad_username="login";
    protected $_ad_password="password";

    /**
    * AD does not return the primary group. http://support.microsoft.com/?kbid=321360
    * This tweak will resolve the real primary group.
    * Setting to false will fudge "Domain Users" and is much faster. Keep in mind though that if
    * someone's primary group is NOT domain users, this is obviously going to mess up the results
    *
    * @var bool
    */
	protected $_real_primarygroup=true;

    /**
    * Use SSL (LDAPS), your server needs to be setup, please see
    * http://adldap.sourceforge.net/wiki/doku.php?id=ldap_over_ssl
    *
    * @var bool
    */
	protected $_use_ssl=false;

    /**
    * Use TLS
    * If you wish to use TLS you should ensure that $_use_ssl is set to false and vice-versa
    *
    * @var bool
    */
    protected $_use_tls=false;

    /**
    * When querying group memberships, do it recursively
    * eg. User Fred is a member of Group A, which is a member of Group B, which is a member of Group C
    * user_ingroup("Fred","C") will returns true with this option turned on, false if turned off
    *
    * @var bool
    */
	protected $_recursive_groups=true;

	// You should not need to edit anything below this line
	//******************************************************************************************

	/**
    * Connection and bind default variables
    *
    * @var mixed
    * @var mixed
    */
	protected $_conn;
	protected $_bind;

    /**
    * Getters and Setters
    */

    /**
    * Set the account suffix
    *
    * @param string $_account_suffix
    * @return void
    */
    public function set_account_suffix($_account_suffix)
    {
          $this->_account_suffix = $_account_suffix;
    }

    /**
    * Get the account suffix
    *
    * @return string
    */
    public function get_account_suffix()
    {
          return $this->_account_suffix;
    }

    /**
    * Set the domain controllers array
    *
    * @param array $_domain_controllers
    * @return void
    */
    public function set_domain_controllers(array $_domain_controllers)
    {
          $this->_domain_controllers = $_domain_controllers;
    }

    /**
    * Get the list of domain controllers
    *
    * @return void
    */
    public function get_domain_controllers()
    {
          return $this->_domain_controllers;
    }

    /**
    * Set the username of an account with higher priviledges
    *
    * @param string $_ad_username
    * @return void
    */
    public function set_ad_username($_ad_username)
    {
          $this->_ad_username = $_ad_username;
    }

    /**
    * Get the username of the account with higher priviledges
    *
    * This will throw an exception for security reasons
    */
    public function get_ad_username()
    {
          throw new adLDAPException('For security reasons you cannot access the domain administrator account details');
    }

    /**
    * Set the password of an account with higher priviledges
    *
    * @param string $_ad_password
    * @return void
    */
    public function set_ad_password($_ad_password)
    {
          $this->_ad_password = $_ad_password;
    }

    /**
    * Get the password of the account with higher priviledges
    *
    * This will throw an exception for security reasons
    */
    public function get_ad_password()
    {
          throw new adLDAPException('For security reasons you cannot access the domain administrator account details');
    }

    /**
    * Set whether to detect the true primary group
    *
    * @param bool $_real_primary_group
    * @return void
    */
    public function set_real_primarygroup($_real_primarygroup)
    {
          $this->_real_primarygroup = $_real_primarygroup;
    }

    /**
    * Get the real primary group setting
    *
    * @return bool
    */
    public function get_real_primarygroup()
    {
          return $this->_real_primarygroup;
    }

    /**
    * Set whether to use SSL
    *
    * @param bool $_use_ssl
    * @return void
    */
    public function set_use_ssl($_use_ssl)
    {
          $this->_use_ssl = $_use_ssl;
    }

    /**
    * Get the SSL setting
    *
    * @return bool
    */
    public function get_use_ssl()
    {
          return $this->_use_ssl;
    }

    /**
    * Set whether to use TLS
    *
    * @param bool $_use_tls
    * @return void
    */
    public function set_use_tls($_use_tls)
    {
          $this->_use_tls = $_use_tls;
    }

    /**
    * Get the TLS setting
    *
    * @return bool
    */
    public function get_use_tls()
    {
          return $this->_use_tls;
    }

    /**
    * Set whether to lookup recursive groups
    *
    * @param bool $_recursive_groups
    * @return void
    */
    public function set_recursive_groups($_recursive_groups)
    {
          $this->_recursive_groups = $_recursive_groups;
    }

    /**
    * Get the recursive groups setting
    *
    * @return bool
    */
    public function get_recursive_groups()
    {
          return $this->_recursive_groups;
    }

    /**
    * Default Constructor
    *
    * Tries to bind to the AD domain over LDAP or LDAPs
    *
    * @param array $options Array of options to pass to the constructor
    * @throws Exception - if unable to bind to Domain Controller
    * @return bool
    */
    function __construct($options=array()){
        // You can specifically overide any of the default configuration options setup above
        if (count($options)>0){
            if (array_key_exists("account_suffix",$options)){ $this->_account_suffix=$options["account_suffix"]; }
            if (array_key_exists("base_dn",$options)){ $this->_base_dn=$options["base_dn"]; }
            if (array_key_exists("domain_controllers",$options)){ $this->_domain_controllers=$options["domain_controllers"]; }
            if (array_key_exists("ad_username",$options)){ $this->_ad_username=$options["ad_username"]; }
            if (array_key_exists("ad_password",$options)){ $this->_ad_password=$options["ad_password"]; }
            if (array_key_exists("real_primarygroup",$options)){ $this->_real_primarygroup=$options["real_primarygroup"]; }
            if (array_key_exists("use_ssl",$options)){ $this->_use_ssl=$options["use_ssl"]; }
            if (array_key_exists("use_tls",$options)){ $this->_use_tls=$options["use_tls"]; }
            if (array_key_exists("recursive_groups",$options)){ $this->_recursive_groups=$options["recursive_groups"]; }
        }

        if ($this->ldap_supported() === false) {
            throw new adLDAPException('No LDAP support for PHP.  See: http://www.php.net/ldap');
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
    function __destruct(){ $this->close(); }

    /**
    * Connects and Binds to the Domain Controller
    *
    * @return bool
    */
    public function connect() {
        // Connect to the AD/LDAP server as the username/password
        $dc=$this->random_controller();
        if ($this->_use_ssl){
            $this->_conn = ldap_connect("ldaps://".$dc, 636);
        } else {
            $this->_conn = ldap_connect($dc);
        }

        // Set some ldap options for talking to AD
        ldap_set_option($this->_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->_conn, LDAP_OPT_REFERRALS, 0);

        if ($this->_use_tls) {
            ldap_start_tls($this->_conn);
        }

        // Bind as a domain admin if they've set it up
        if ($this->_ad_username!=NULL && $this->_ad_password!=NULL){
            $this->_bind = @ldap_bind($this->_conn,$this->_ad_username.$this->_account_suffix,$this->_ad_password);
            if (!$this->_bind){
                if ($this->_use_ssl && !$this->_use_tls){
                    // If you have problems troubleshooting, remove the @ character from the ldap_bind command above to get the actual error message
                    throw new adLDAPException('Bind to Active Directory failed. Either the LDAPs connection failed or the login credentials are incorrect. AD said: ' . $this->get_last_error());
                } else {
                    throw new adLDAPException('Bind to Active Directory failed. Check the login credentials and/or server details. AD said: ' . $this->get_last_error());
                }
            }
        }

        if ($this->_base_dn == NULL) {
            $this->_base_dn = $this->find_base_dn();
        }

        return (true);
    }

    /**
    * Closes the LDAP connection
    *
    * @return void
    */
    public function close() {
        ldap_close ($this->_conn);
    }

    /**
    * Validate a user's login credentials
    *
    * @param string $username A user's AD username
    * @param string $password A user's AD password
    * @param bool optional $prevent_rebind
    * @return bool
    */
    public function authenticate($username,$password,$prevent_rebind=false){
        // Prevent null binding
        if ($username===NULL || $password===NULL){ return (false); }
        if (empty($username) || empty($password)){ return (false); }

        // Bind as the user
        $this->_bind = @ldap_bind($this->_conn,$username.$this->_account_suffix,$password);
        if (!$this->_bind){ return (false); }

        // Cnce we've checked their details, kick back into admin mode if we have it
        if ($this->_ad_username!=NULL && !$prevent_rebind){
            $this->_bind = @ldap_bind($this->_conn,$this->_ad_username.$this->_account_suffix,$this->_ad_password);
            if (!$this->_bind){
                // This should never happen in theory
                throw new adLDAPException('Rebind to Active Directory failed. AD said: ' . $this->get_last_error());
            }
        }

        return (true);
    }


    //************************************************************************************************************
    // SERVER FUNCTIONS

    /**
    * Find the Base DN of your domain controller
    *
    * @return string
    */
    public function find_base_dn() {
        $namingContext = $this->get_root_dse(array('defaultnamingcontext'));
        return $namingContext[0]['defaultnamingcontext'][0];
    }

    /**
    * Get the RootDSE properties from a domain controller
    *
    * @param array $attributes The attributes you wish to query e.g. defaultnamingcontext
    * @return array
    */
    public function get_root_dse($attributes = array("*", "+")) {
        if (!$this->_bind){ return (false); }

        $sr = @ldap_read($this->_conn, NULL, 'objectClass=*', $attributes);
        $entries = @ldap_get_entries($this->_conn, $sr);
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
    * return string
    */
    public function get_last_error() {
        return @ldap_error($this->_conn);
    }

    /**
    * Detect LDAP support in php
    *
    * @return bool
    */
    protected function ldap_supported() {
        if (!function_exists('ldap_connect')) {
            return (false);
        }
        return (true);
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
        $mod=array();

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
        if ($attributes["password"]){ $mod["unicodePwd"][0]=$this->encode_password($attributes["password"]); }
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

        if (count($mod)==0){ return (false); }
        return ($mod);
    }

    /**
    * Coping with AD not returning the primary group
    * http://support.microsoft.com/?kbid=321360
    *
    * For some reason it's not possible to search on primarygrouptoken=XXX
    * If someone can show otherwise, I'd like to know about it :)
    * this way is resource intensive and generally a pain in the @#%^
    *
    * @deprecated deprecated since version 3.1, see get get_primary_group
    * @param string $gid Group ID
    * @return string
    */
    protected function group_cn($gid){
        if ($gid===NULL){ return (false); }
        $r=false;

        $filter="(&(objectCategory=group)(samaccounttype=". ADLDAP_SECURITY_GLOBAL_GROUP ."))";
        $fields=array("primarygrouptoken","samaccountname","distinguishedname");
        $sr=ldap_search($this->_conn,$this->_base_dn,$filter,$fields);
        $entries = ldap_get_entries($this->_conn, $sr);

        for ($i=0; $i<$entries["count"]; $i++){
            if ($entries[$i]["primarygrouptoken"][0]==$gid){
                $r=$entries[$i]["distinguishedname"][0];
                $i=$entries["count"];
            }
        }

        return ($r);
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
    protected function get_primary_group($gid, $usersid){
        if ($gid===NULL || $usersid===NULL){ return (false); }
        $r=false;

        $gsid = substr_replace($usersid,pack('V',$gid),strlen($usersid)-4,4);
        $filter='(objectsid='.$this->getTextSID($gsid).')';
        $fields=array("samaccountname","distinguishedname");
        $sr=ldap_search($this->_conn,$this->_base_dn,$filter,$fields);
        $entries = ldap_get_entries($this->_conn, $sr);

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
                hexdec($this->little_endian(substr($hex_sid, 16 + ($x * 8), 8)));
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
     protected function little_endian($hex) {
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
        if ($binaryGuid === null){ return ("Missing compulsory field [binaryGuid]"); }

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
    protected function user_dn($username,$isGUID=false){
        $user=$this->user_info($username,array("cn"),$isGUID);
        if ($user[0]["dn"]===NULL){ return (false); }
        $user_dn=$user[0]["dn"];
        return ($user_dn);
    }

    /**
    * Find information about the users
    *
    * @param string $username The username to query
    * @param array $fields Array of parameters to query
    * @param bool $isGUID Is the username passed a GUID or a samAccountName
    * @return array
    */
    public function user_info($username,$fields=NULL,$isGUID=false){
        if ($username===NULL){ return (false); }
        if (!$this->_bind){ return (false); }

        if ($isGUID === true) {
            $username = $this->strguid2hex($username);
            $filter="objectguid=".$username;
        }
        else {
            $filter="samaccountname=".$username;
        }
        if ($fields===NULL){ $fields=array("samaccountname","mail","memberof","department","displayname","telephonenumber","primarygroupid","objectsid"); }
        $sr=ldap_search($this->_conn,$this->_base_dn,$filter,$fields);
        $entries = ldap_get_entries($this->_conn, $sr);

        if ($entries[0]['count'] >= 1) {
            // AD does not return the primary group in the ldap query, we may need to fudge it
            if ($this->_real_primarygroup && isset($entries[0]["primarygroupid"][0]) && isset($entries[0]["objectsid"][0])){
                //$entries[0]["memberof"][]=$this->group_cn($entries[0]["primarygroupid"][0]);
                $entries[0]["memberof"][]=$this->get_primary_group($entries[0]["primarygroupid"][0], $entries[0]["objectsid"][0]);
            } else {
                $entries[0]["memberof"][]="CN=Domain Users,CN=Users,".$this->_base_dn;
            }
        }

        $entries[0]["memberof"]["count"]++;
        return ($entries);
    }

    /**
    * Encode a password for transmission over LDAP
    *
    * @param string $password The password to encode
    * @return string
    */
    protected function encode_password($password){
        $password="\"".$password."\"";
        $encoded="";
        for ($i=0; $i <strlen($password); $i++){ $encoded.="{$password{$i}}\000"; }
        return ($encoded);
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
    protected function ldap_slashes($str){
        return preg_replace('/([\x00-\x1F\*\(\)\\\\])/e',
                            '"\\\\\".join("",unpack("H2","$1"))',
                            $str);
    }

    /**
    * Select a random domain controller from your domain controller array
    *
    * @return string
    */
    protected function random_controller(){
        mt_srand(doubleval(microtime()) * 100000000); // For older PHP versions
        return ($this->_domain_controllers[array_rand($this->_domain_controllers)]);
    }

    /**
    * Account control options
    *
    * @param array $options The options to convert to int
    * @return int
    */
    protected function account_control($options){
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
        return ($val);
    }

    /**
    * Take an LDAP query and return the nice names, without all the LDAP prefixes (eg. CN, DN)
    *
    * @param array $groups
    * @return array
    */
    protected function nice_names($groups){

        $group_array=array();
        for ($i=0; $i<$groups["count"]; $i++){ // For each group
            $line=$groups[$i];

            if (strlen($line)>0){
                // More presumptions, they're all prefixed with CN=
                // so we ditch the first three characters and the group
                // name goes up to the first comma
                $bits=explode(",",$line);
                $group_array[]=substr($bits[0],3,(strlen($bits[0])-3));
            }
        }
        return ($group_array);
    }

    /**
    * Delete a distinguished name from Active Directory
    * You should never need to call this yourself, just use the wrapper functions user_delete and contact_delete
    *
    * @param string $dn The distinguished name to delete
    * @return bool
    */
    protected function dn_delete($dn){
        $result=ldap_delete($this->_conn, $dn);
        if ($result!=true){ return (false); }
        return (true);
    }

    /**
    * Convert a boolean value to a string
    * You should never need to call this yourself
    *
    * @param bool $bool Boolean value
    * @return string
    */
    protected function bool2str($bool) {
        return ($bool) ? 'TRUE' : 'FALSE';
    }

    /**
    * Convert 8bit characters e.g. accented characters to UTF8 encoded characters
    */
    protected function encode8bit(&$item, $key) {
        $encode = false;
        if (is_string($item)) {
            for ($i=0; $i<strlen($item); $i++) {
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

/**
* adLDAP Exception Handler
*
* Exceptions of this type are thrown on bind failure or when SSL is required but not configured
* Example:
* try {
*   $adldap = new adLDAP();
* }
* catch (adLDAPException $e) {
*   echo $e;
*   exit();
* }
*/
class adLDAPException extends Exception {}
