<?php
/**
 *
 * LDAP Advance. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Dana Pierce
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace LazyMod\KerberosSSO\migrations;

use phpbb\db\migration\migration;

class kerberosSSO_1_0_0 extends migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'users', 'user_fullname');
	}

	static public function depends_on()
	{
		return array('phpbb\db\migration\data\v320\v320rc2');
	}


	public function update_data()
	{
		return array(
			
			array('config.add', array('kerberosSSO_version', '1.0.0')),
			array('config.add', array('kerberosSSO_displayName', 'cn')),
			array('config.add', array('kerberosSSO_adminnotify', '5')),
			array('config.add', array('kerberosSSO_server1',  '')),
			array('config.add', array('kerberosSSO_server2',  '')),
			array('config.add', array('kerberosSSO_port',  '3268')),
			array('config.add', array('kerberosSSO_user',  '')),
			array('config.add', array('kerberosSSO_password',  '')),
			array('config.add', array('kerberosSSO_email',  '')),
			array('config.add', array('kerberosSSO_base_dn',  '')),
			array('config.add', array('kerberosSSO_separator',  '\\')),
			array('config.add', array('kerberosSSO_uid',  'samAccountName')),
			array('config.add', array('kerberosSSO_user_filter',  '')),
			array('config.add', array('kerberosSSO_city',  'l')),
			array('config.add', array('kerberosSSO_state',  'st')),
			array('config.add', array('kerberosSSO_country',  'co')),
			array('config.add', array('kerberosSSO_department',  'department')),
			array('config.add', array('kerberosSSO_hidestate',  'AUTH_USER')),

			
			array('config.add', array('kerberosSSO_goodbye', 0)),

            // Add a parent module (ACP_DEMO_TITLE) to the Extensions tab (ACP_CAT_DOT_MODS)
            array('module.add', array(
                'acp',
                'ACP_CAT_DOT_MODS',
                'Kerberos SSO'
            )),

            // Add our main_module to the parent module (ACP_DEMO_TITLE)
            array('module.add', array(
                'acp',
                'Kerberos SSO',
                array(
                    'module_basename'       => '\LazyMod\KerberosSSO\acp\main_module',
                    'modes'                 => array('settings'),
                ),
            )),
		);
	}

	public function update_schema()
	{
		return array(
			'add_columns' => array(
				$this->table_prefix . 'users'			=> array(
					'user_fullname'				=> array('VCHAR:255', NULL),
					'user_department'			=> array('VCHAR:255', NULL),
					'user_city'					=> array('VCHAR:255', NULL),
					'user_state'				=> array('VCHAR:255', NULL),
					'user_country'				=> array('VCHAR:255', NULL),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns'	=> array(
				$this->table_prefix . 'users'			=> array(
					'user_fullname',
					'user_department',
					'user_city',
					'user_state',
					'user_country',
				),
			),
		);
	}
}
