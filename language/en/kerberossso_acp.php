<?php
/**
 *
 * Kerberos SSO with LDAP Lookup. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Dana Pierce
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

global $db;

$arr = array(
	'KERBEROSSSO_SERVER1SET' 	=> 'kerberosSSO_server1', 
	'KERBEROSSSO_SERVER2SET' 	=> 'kerberosSSO_server2', 
	'KERBEROSSSO_VERSIONSET' 	=> 'kerberosSSO_version', 
	'KERBEROSSSO_AUTHTYPESET' 	=> 'auth_method',
	'KERBEROSSSO_USERSET' 		=> 'kerberosSSO_user',
);

foreach ($arr as $key => $val)
{
	$sql ='SELECT config_value FROM ' . CONFIG_TABLE . " WHERE config_name = '" . $val . "'";
	$result = $db->sql_query($sql);
	$row = $db->sql_fetchrow($result);
	$lang = array_merge($lang, array(
		"$key" 	=> $row['config_value'],
	));
	$db->sql_freeresult($result);
}



$lang = array_merge($lang, array(
	'KERBEROSSSO'							=> 'KERBEROS SSO WITH MULTIPLE SERVER LDAP LOOKUP AND BACKUP LDAP BIND',
	'KERBEROSSSO_EXTRA'						=> '', //'<br><hr>**Note**<br><br>If you enter two server names, both will be verified for functionality.  If only one is working, the verification will fail.<br>&nbsp;<hr>',
	'KERBEROSSSO_INCORRECT_USER_PASSWORD1'	=> 'Binding to LDAP server failed with specified user/password. (Server #1)',
	'KERBEROSSSO_NO_EMAIL1'					=> 'The specified email attribute does not exist. (Server #1)',
	'KERBEROSSSO_NO_IDENTITY1'				=> 'Could not find a login identity for %s. (Server #1)',
	'KERBEROSSSO_NO_SERVER_CONNECTION1'		=> 'Could not connect to LDAP server. (Server #1)',
	'KERBEROSSSO_SEARCH_FAILED1'			=> 'An error occurred while searching the LDAP directory. (Server #1)',
	'KERBEROSSSO_NO_SERVER_CONNECTION2'		=> 'Could not connect to LDAP server. (Server #2)',
	'KERBEROSSSO_SEARCH_FAILED2'			=> 'An error occurred while searching the LDAP directory. (Server #2)',
	'KERBEROSSSO_INCORRECT_USER_PASSWORD2'	=> 'Binding to LDAP server failed with specified user/password. (Server #2)',
	'KERBEROSSSO_NO_EMAIL2'					=> 'The specified email attribute does not exist. (Server #2)',
	'KERBEROSSSO_NO_IDENTITY2'				=> 'Could not find a login identity for %s. (Server #2)',
	'KERBEROSSSO_PORT'						=> 'LDAP server port',
	'KERBEROSSSO_PORT_EXPLAIN'				=> 'Optionally you can specify a port which should be used to connect to the LDAP server instead of the default port <samp>389</samp>. Use port <samp>3268</samp> for Global Catalog searches.',
	'KERBEROSSSO_ADMIN'			   			=> 'GROUP ID for LDAP server outage notifications',
	'KERBEROSSSO_ADMIN_EXPLAIN'				=> 'Set this to the groupid that should receive notifications when the primary LDAP server is not responding and the system makes the backup LDAP server primary, e.g. <samp>5</samp> for the Administrators group.',
	'KERBEROSSSO_DN'						=> 'LDAP base <var>dn</var>',
	'KERBEROSSSO_DN_EXPLAIN'				=> 'This is the Distinguished Name, locating the user information, e.g. <samp>o=My Company,c=US</samp>.  If using the Global Catalog port, leave this field blank to search the entire catalog.',
	'KERBEROSSSO_DISPLAYNAME'				=> 'LDAP Full Name attribute',
	'KERBEROSSSO_DISPLAYNAME_EXPLAIN'		=> 'Fill in to copy this field to be used in the profile of the user, e.g. <samp>cn</samp>.',
	'KERBEROSSSO_DEPARTMENT'				=> 'LDAP Department attribute',
	'KERBEROSSSO_DEPARTMENT_EXPLAIN'		=> 'Fill in to copy this field to be used in the profile of the user, e.g. <samp>department</samp>.',
	'KERBEROSSSO_CITY'						=> 'LDAP City attribute',
	'KERBEROSSSO_CITY_EXPLAIN'				=> 'Fill in to copy this field to be used in the profile of the user, e.g. <samp>l</samp>.',
	'KERBEROSSSO_STATE'		   				=> 'LDAP State attribute',
	'KERBEROSSSO_STATE_EXPLAIN'				=> 'Fill in to copy this field to be used in the profile of the user, e.g. <samp>st</samp>.',
	'KERBEROSSSO_SSO'			   			=> 'Global PHP Variable Separator',
	'KERBEROSSSO_SSO_EXPLAIN'				=> 'Set this to the separator value for the PHP Global Variable.<br />Note that this is server specific, e.g. <samp>\</samp> for IIS or <samp>@</samp> for Apache.',
	'KERBEROSSSO_COUNTRY'		   			=> 'LDAP Country atribute',
	'KERBEROSSSO_COUNTRY_EXPLAIN'			=> 'Fill in to copy this field to be used in the profile of the user, e.g. <samp>co</samp>.',
	'KERBEROSSSO_SERVER1'					=> 'LDAP server name',
	'KERBEROSSSO_SERVER1_EXPLAIN'			=> 'If using LDAP this is the hostname or IP address of the LDAP server. Alternatively you can specify an URL like ldap://hostname:port/',
	'KERBEROSSSO_SERVER2'					=> 'LDAP server name (backup)',
	'KERBEROSSSO_SERVER2_EXPLAIN'			=> 'If using LDAP this is the hostname or IP address of the LDAP server. Alternatively you can specify an URL like ldap://hostname:port/',
	'KERBEROSSSO_EMAIL'						=> 'LDAP email attribute',
	'KERBEROSSSO_EMAIL_EXPLAIN'				=> 'Set this to the name of your user entry email attribute (if one exists) in order to automatically set the email address for new users. Leaving this empty results in empty email address for users who log in for the first time.',
	'KERBEROSSSO_PASSWORD'					=> 'LDAP password',
	'KERBEROSSSO_PASSWORD_EXPLAIN'			=> 'Leave blank to use anonymous binding, otherwise fill in the password for the above user. Required for Active Directory Servers.<br /><em><strong>Warning:</strong> This password will be stored as plain text in the database, visible to everybody who can access your database or who can view this configuration page.</em>',
	'KERBEROSSSO_UID'						=> 'LDAP <var>uid</var>',
	'KERBEROSSSO_UID_EXPLAIN'				=> 'This is the key under which to search for a given login identity, e.g. <var>uid</var>, <var>sn</var>, etc.',
	'KERBEROSSSO_USER'						=> 'LDAP user <var>dn</var>',
	'KERBEROSSSO_USER_EXPLAIN'				=> 'Leave blank to use anonymous binding. If filled in phpBB uses the specified distinguished name on login attempts to find the correct user, e.g. <samp>uid=Username,ou=MyUnit,o=MyCompany,c=US</samp>. Required for Active Directory Servers.',
	'KERBEROSSSO_USER_FILTER'				=> 'LDAP user filter',
	'KERBEROSSSO_USER_FILTER_EXPLAIN'		=> 'Optionally you can further limit the searched objects with additional filters. For example <samp>objectClass=posixGroup</samp> would result in the use of <samp>(&amp;(uid=$username)(objectClass=posixGroup))</samp>',

	'KERBEROSSSO_TITLE'        				=> 'Kerberos SSO Module',
	'KERBEROSSSO_LDAPLOOKUP'   				=> 'Settings',
	'KERBEROSSSO_GOODBYE'      				=> 'Kerberos SSO LDAP Update',
	'KERBEROSSSO_SETTING_SAVED' 			=> 'LDAP Updates have successfully taken place!',

	'KERBEROSSSO_STRING'      				=> 'Global PHP Variable',
	'KERBEROSSSO_STRING_EXPLAIN' 			=> 'Different auth methods will populate different global variables in PHP.  Select one of the common ones.',	
	'KERBEROSSSO_REMOTE_USER'      			=> 'REMOTE_USER',
	'KERBEROSSSO_AUTH_USER' 				=> 'AUTH_USER',
	'KERBEROSSSO_LOGON_USER' 				=> 'LOGON_USER',
	

));
