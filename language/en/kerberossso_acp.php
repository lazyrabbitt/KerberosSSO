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

$lang = array_merge($lang, array(
	'LDAPADV'							=> 'KERBEROS SSO WITH MULTIPLE SERVER LDAP LOOKUP AND BACKUP LDAP BIND',
	'LDAPADV_EXTRA'						=> '<br><hr>**Note**<br><br>If you enter two server names, both will be verified for functionality.  If only one is working, the verification will fail.<br>&nbsp;<hr>',
	'LDAPADV_INCORRECT_USER_PASSWORD'	=> 'Binding to LDAP server failed with specified user/password. (Server #1)',
	'LDAPADV_NO_EMAIL'					=> 'The specified email attribute does not exist. (Server #1)',
	'LDAPADV_NO_IDENTITY'				=> 'Could not find a login identity for %s. (Server #1)',
	'LDAPADV_NO_SERVER_CONNECTION'		=> 'Could not connect to LDAP server. (Server #1)',
	'LDAPADV_SEARCH_FAILED'				=> 'An error occurred while searching the LDAP directory. (Server #1)',
	'LDAPADV_NO_SERVER_CONNECTION2'		=> 'Could not connect to LDAP server. (Server #2)',
	'LDAPADV_SEARCH_FAILED2'			=> 'An error occurred while searching the LDAP directory. (Server #2)',
	'LDAPADV_INCORRECT_USER_PASSWORD2'	=> 'Binding to LDAP server failed with specified user/password. (Server #2)',
	'LDAPADV_NO_EMAIL2'					=> 'The specified email attribute does not exist. (Server #2)',
	'LDAPADV_NO_IDENTITY2'				=> 'Could not find a login identity for %s. (Server #2)',
	'LDAPADV_PORT_EXPLAIN'				=> 'Optionally you can specify a port which should be used to connect to the LDAP server instead of the default port <samp>389</samp>. Use port <samp>3268</samp> for Global Catalog searches.',
	'LDAPADV_ADMIN'			   			=> 'GROUP ID for LDAP server outage notifications',
	'LDAPADV_ADMIN_EXPLAIN'				=> 'Set this to the groupid that should receive notifications when the primary LDAP server is not responding and the system makes the backup LDAP server primary, e.g. <samp>5</samp> for the Administrators group.',
	'LDAPADV_DN_EXPLAIN'				=> 'This is the Distinguished Name, locating the user information, e.g. <samp>o=My Company,c=US</samp>.  If using the Global Catalog port, leave this field blank to search the entire catalog.',
	'LDAPADV_DISPLAYNAME'				=> 'LDAP Full Name attribute',
	'LDAPADV_DISPLAYNAME_EXPLAIN'		=> 'Fill in to copy this field to be used in the profile of the user, e.g. <samp>cn</samp>.',
	'LDAPADV_DEPARTMENT'				=> 'LDAP Department attribute',
	'LDAPADV_DEPARTMENT_EXPLAIN'		=> 'Fill in to copy this field to be used in the profile of the user, e.g. <samp>department</samp>.',
	'LDAPADV_CITY'						=> 'LDAP City attribute',
	'LDAPADV_CITY_EXPLAIN'				=> 'Fill in to copy this field to be used in the profile of the user, e.g. <samp>l</samp>.',
	'LDAPADV_STATE'		   				=> 'LDAP State attribute',
	'LDAPADV_STATE_EXPLAIN'				=> 'Fill in to copy this field to be used in the profile of the user, e.g. <samp>st</samp>.',
	'LDAPADV_SSO'			   			=> 'Single Sign-On Seperator',
	'LDAPADV_SSO_EXPLAIN'				=> 'Set this to the seperator value for the $_SERVER("REMOTE_USER") variable.  Note that this is server specific, e.g. <samp>\</samp> for IIS or <samp>@</samp> for Apache.',
	'LDAPADV_COUNTRY'		   			=> 'LDAP Country atribute',
	'LDAPADV_COUNTRY_EXPLAIN'			=> 'Fill in to copy this field to be used in the profile of the user, e.g. <samp>co</samp>.',
	'LDAPADV_SERVER'					=> 'LDAP server name',
	'LDAPADV_SERVER_EXPLAIN'			=> 'If using LDAP this is the hostname or IP address of the LDAP server. Alternatively you can specify an URL like ldap://hostname:port/',
	'LDAPADV_SERVER2'					=> 'LDAP server name (backup)',
	'LDAPADV_SERVER2_EXPLAIN'			=> 'If using LDAP this is the hostname or IP address of the LDAP server. Alternatively you can specify an URL like ldap://hostname:port/',
));
