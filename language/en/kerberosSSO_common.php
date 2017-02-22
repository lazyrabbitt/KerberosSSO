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
			'USER_FULLNAME'				=> $user['user_fullname'],
			'USER_DEPARTMENT'			=> $user['user_department'],
			'USER_CITY'					=> $user['user_city'],
			'USER_STATE'				=> $user['user_state'],
			'USER_COUNTRY'				=> $user['user_country'],
			'USER_FULLNAME_TEXT'		=> 'Full Name from LDAP2',
			'USER_DEPARTMENT_TEXT'		=> 'Department from LDAP',
			'USER_CITY_TEXT'			=> 'City from LDAP',
			'USER_STATE_TEXT'			=> 'State from LDAP',
			'USER_COUNTRY_TEXT'			=> 'Country from LDAP',
));
