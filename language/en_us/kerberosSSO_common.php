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
			'USER_FULLNAME_TEXT'		=> 'Full Name from LDAP',
			'USER_DEPARTMENT_TEXT'		=> 'Department from LDAP',
			'USER_CITY_TEXT'			=> 'City from LDAP',
			'USER_STATE_TEXT'			=> 'State from LDAP',
			'USER_COUNTRY_TEXT'			=> 'Country from LDAP',
			'KERBEROSSO_WRONG_PW'		=> 'Invalid Password Attempt',
			'TOPIC_LOCATION_TEXT'			=> 'Location',
			'TOPIC_USER_DEPARTMENT_TEXT'	=> 'Department',

));
