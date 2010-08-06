## modActiveDirectory

This is an ActiveDirectory integration for MODx Revolution.

## Installation

Simply install via Package Management in MODx Revolution.

From there, you'll need to setup some settings:

* activedirectory.account_suffix : The account suffix for your domain. Usually in @forest.domain format.
* activedirectory.domain_controllers : A comma-separated list of domain controllers. Specifiy multiple controllers if you would like the class to balance the LDAP queries.

## ActiveDirectory Group Synchronization

modActiveDirectory will automatically grab all the ActiveDirectory groups a user belongs to, and then search for any MODx UserGroups with matching names. If found, the user will be added to those groups.

If you'd like to disable this, set the activedirectory.autoadd_adgroups System Setting to 0.

modActiveDirectory also allows you to specify a comma-separated list of MODx UserGroup names to automatically add the User to. This can be set in the activedirectory.autoadd_usergroups setting.

