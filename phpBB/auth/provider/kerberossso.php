<?php
/**
*
* This is a modified version of the LDAP authentication that comes with the phpBB Forum Software.
* It provides for the capabilities of two LDAP servers (redundant not load sharing), 
* while also integrating Kerberos Single Sign-On capabilities for an enterprise intranet.
*
* Modifications completed by: Dana Pierce 
*
* Mod Completed: February 2017
*
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace LazyMod\KerberosSSO\phpbb\auth\provider;

/**
 * Database authentication provider for phpBB3
 * This is for authentication via the integrated user table
 */
class kerberossso extends \phpbb\auth\provider\base
{
	/**
	* phpBB passwords manager
	*
	* @var \phpbb\passwords\manager
	*/
	protected $passwords_manager;

	/**
	 * LDAP Authentication Constructor
	 *
	 * @param	\phpbb\db\driver\driver_interface		$db		Database object
	 * @param	\phpbb\config\config		$config		Config object
	 * @param	\phpbb\passwords\manager	$passwords_manager		Passwords manager object
	 * @param	\phpbb\user			$user		User object
	 */
	public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\config\config $config, \phpbb\passwords\manager $passwords_manager, \phpbb\user $user)
	{
		global $phpbb_root_path, $phpEx, $request, $auth;
		
		$this->phpbb_root_path = $phpbb_root_path;
		$this->phpEx = $phpEx;
		$this->request = $request;
		$this->db = $db;
		$this->config = $config;
		$this->passwords_manager = $passwords_manager;
		$this->user = $user;
		$this->auth = $auth;

		$this->user->add_lang_ext('LazyMod\KerberosSSO', 'kerberossso_acp');
	}

	
	public function autologin()
	{

		$result = $this->login("", "");
		if ($result['status'] == LOGIN_SUCCESS  || $result['status'] == LOGIN_SUCCESS_CREATE_PROFILE )
		{
				return $result['user_row'];		
		}
		$this->user->session_kill();
		return array();
	}

	/**
	 * {@inheritdoc}
	 */
	public function init()
	{
	
		if (!@extension_loaded('ldap'))
		{
			return $this->user->lang['LDAP_NO_LDAP_EXTENSION'];
		}
		
		// Start the checking of server 1 entered into the config of ACP.

		$this->config['kerberosSSO_port'] = (int) $this->config['kerberosSSO_port'];
		if ($this->config['kerberosSSO_port'])
		{
			$ldap = @ldap_connect($this->config['kerberosSSO_server1'], $this->config['kerberosSSO_port']);
		}
		else
		{
			$ldap = @ldap_connect($this->config['kerberosSSO_server1']);
		}

		if (!$ldap)
		{
			return $this->user->lang['KERBEROSSSO_NO_SERVER_CONNECTION1'];
		}

		@ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
		@ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

		if ($this->config['kerberosSSO_user'] || $this->config['kerberosSSO_password'])
		{
			if (!@ldap_bind($ldap, htmlspecialchars_decode($this->config['kerberosSSO_user']), htmlspecialchars_decode($this->config['kerberosSSO_password'])))
			{
				return $this->user->lang['KERBEROSSSO_INCORRECT_USER_PASSWORD1'];
			}
		}

		// LDAP_connect only checks whether the specified server is valid, so the connection might still fail
		$search = @ldap_search(
			$ldap,
			htmlspecialchars_decode($this->config['kerberosSSO_base_dn']),
			$this->kerberosSSO_user_filter($this->user->data['username']),
			(empty($this->config['kerberosSSO_email'])) ?
				array(htmlspecialchars_decode($this->config['kerberosSSO_uid'])) :
				array(htmlspecialchars_decode($this->config['kerberosSSO_uid']), htmlspecialchars_decode($this->config['kerberosSSO_email'])),
			0,
			1
		);

		if ($search === false)
		{
			return $this->user->lang['KERBEROSSSO_SEARCH_FAILED'];
		}

		$result = @ldap_get_entries($ldap, $search);

		@ldap_close($ldap);

		if (!is_array($result) || sizeof($result) < 2)
		{
			return sprintf($this->user->lang['KERBEROSSSO_NO_IDENTITY'], $this->user->data['username']);
		}

		if (!empty($this->config['kerberosSSO_email']) && !isset($result[0][htmlspecialchars_decode($this->config['kerberosSSO_email'])]))
		{
			return $this->user->lang['KERBEROSSSO_NO_EMAIL'];
		}


		// Start the checking of server 2 entered into the config of ACP.

		if($this->config['kerberosSSO_server2'])
		{
			$this->config['kerberosSSO_port'] = (int) $this->config['kerberosSSO_port'];
			if ($this->config['kerberosSSO_port'])
			{
				$ldap = @ldap_connect($this->config['kerberosSSO_server2'], $this->config['kerberosSSO_port']);
			}
			else
			{
				$ldap = @ldap_connect($this->config['kerberosSSO_server2']);
			}

			if (!$ldap)
			{
				return $this->user->lang['KERBEROSSSO_NO_SERVER_CONNECTION2'];
			}

			@ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
			@ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

			if ($this->config['kerberosSSO_user'] || $this->config['kerberosSSO_password'])
			{
				if (!@ldap_bind($ldap, htmlspecialchars_decode($this->config['kerberosSSO_user']), htmlspecialchars_decode($this->config['kerberosSSO_password'])))
				{
					return $this->user->lang['KERBEROSSSO_INCORRECT_USER_PASSWORD2'];
				}
			}

			// LDAP_connect only checks whether the specified server is valid, so the connection might still fail
			$search = @ldap_search(
				$ldap,
				htmlspecialchars_decode($this->config['kerberosSSO_base_dn']),
				$this->kerberosSSO_user_filter($this->user->data['username']),
				(empty($this->config['kerberosSSO_email'])) ?
					array(htmlspecialchars_decode($this->config['kerberosSSO_uid'])) :
					array(htmlspecialchars_decode($this->config['kerberosSSO_uid']), htmlspecialchars_decode($this->config['kerberosSSO_email'])),
				0,
				1
			);

			if ($search === false)
			{
				return $this->user->lang['KERBEROSSSO_SEARCH_FAILED2'];
			}

			$result = @ldap_get_entries($ldap, $search);

			@ldap_close($ldap);

			if (!is_array($result) || sizeof($result) < 2)
			{
				return sprintf($this->user->lang['KERBEROSSSO_NO_IDENTITY2'], $this->user->data['username']);
			}

			if (!empty($this->config['kerberosSSO_email']) && !isset($result[0][htmlspecialchars_decode($this->config['kerberosSSO_email'])]))
			{
				return $this->user->lang['KERBEROSSSO_NO_EMAIL2'];
			}
		}

		// If both servers are good return false.
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function login($username, $password)
	{

		/**
		*	The assumption here is that Apache reads the name as USERNAME @ DOMAIN and IIS reads the name as DOMAN \ USERNAME.
		*	So since there are different possibilities for seperators this if statement is included to basically swap the order of realm and username.
		*/
		switch ($this->config['kerberosSSO_separator'])
		{
			case "\\":
				list($realm, $kerbuser) = explode($this->config['kerberosSSO_separator'], $this->request->server(htmlspecialchars_decode($this->config['kerberosSSO_string']), ''));
				break;
			case "@":
			default:
				list($kerbuser, $realm) = explode($this->config['kerberosSSO_separator'], $this->request->server(htmlspecialchars_decode($this->config['kerberosSSO_string']), ''));
				break;
		}
		
		if (!@extension_loaded('ldap'))
		{
			return array(
				'status'		=> LOGIN_ERROR_EXTERNAL_AUTH,
				'error_msg'		=> 'LDAP_NO_LDAP_EXTENSION',
				'user_row'		=> array('user_id' => ANONYMOUS),
			);
		}

		if (!$username && !kerbuser)
		{
			return array(
				'status'	=> LOGIN_ERROR_USERNAME,
				'error_msg'	=> 'LOGIN_ERROR_USERNAME',
				'user_row'	=> array('user_id' => ANONYMOUS),
			);
		}
			
		if( ($kerbuser == $username && $kerbuser) || (!$username && $kerbuser) )
		{
				// Reset the $username field to match the detected kerberos username.  Keeps the rest of the function looking similar to the origianl LDAP.
				$username = $kerbuser;
				if(!password){
					// set dummy password. The Web server has authenticated the user.
					//$password = "DummyPassword-SSO";
				}
		}	
		else
		{
			
			// do not allow empty password
			if (!$password)
			{
				return array(
					'status'	=> LOGIN_ERROR_PASSWORD,
					'error_msg'	=> 'NO_PASSWORD_SUPPLIED',
					'user_row'	=> array('user_id' => ANONYMOUS),
				);
			}

			if (!$username)
			{
				return array(
					'status'	=> LOGIN_ERROR_USERNAME,
					'error_msg'	=> 'LOGIN_ERROR_USERNAME',
					'user_row'	=> array('user_id' => ANONYMOUS),
				);
			}
		}
		
		list($kerberosSSO_result, $ldap) = $this->ldapconnect(htmlspecialchars_decode($this->config['kerberosSSO_server1']), $username);
		
		if ($kerberosSSO_result === false || !is_array($kerberosSSO_result))
		{
			@ldap_close($ldap);
			if($this->config['kerberosSSO_server2'])
			{
				list($kerberosSSO_result, $ldap) = $this->ldapconnect(htmlspecialchars_decode($this->config['kerberosSSO_server2']), $username);
				
				if ($kerberosSSO_result === false  || !is_array($kerberosSSO_result))
				{
					return array(
					'status'		=> LOGIN_ERROR_EXTERNAL_AUTH,
					'error_msg'		=> 'kerberosSSO_NO_SERVER_CONNECTION',
					'user_row'		=> array('user_id' => ANONYMOUS),
					);
				} 
				else 
				{
				
					$sql1 = $this->config['kerberosSSO_server1'];
					$sql2 = $this->config['kerberosSSO_server2'];
					
					$sql ='SELECT config_value FROM ' . CONFIG_TABLE . " WHERE config_name = 'kerberosSSO_server1'";
					$result = $this->db->sql_query($sql);
					$row = $this->db->sql_fetchrow($result);
					$this->db->sql_freeresult($result);
					
					if($row['config_value'] <> $sql2) 
					{
						include_once($this->phpbb_root_path . "includes/functions_privmsgs." . $this->phpEx);
						
						// Update the SQL to swap the servers so the second one is primary	
						$sqlup = "UPDATE " . CONFIG_TABLE . " SET config_value = '" . $sql1 . "' WHERE config_name = 'kerberosSSO_server2'";
						$resultup = $this->db->sql_query($sqlup);
						$this->db->sql_freeresult($resultup);
						
						$sqlup = "UPDATE " . CONFIG_TABLE . " SET config_value = '" . $sql2 . "' WHERE config_name = 'kerberosSSO_server1'";
						$resultup = $this->db->sql_query($sqlup);
						$this->db->sql_freeresult($resultup);
						
						// Gather all variables and information then send PM to admin about LDAP server outage.
						$pmsubject = "Problem with LDAP server!";
						$message = "LDAP Server #1 (" . htmlspecialchars_decode($this->config['kerberosSSO_server1']) . ") may be offline.  LDAP Server #2 (" . htmlspecialchars_decode($this->config['kerberosSSO_server2']) . ") has become the primary.";
						$uid = $bitfield = $options = ''; // will be modified by generate_text_for_storage
						$allow_bbcode = $allow_smilies = true;
						$allow_urls = true;
						$useridpm = $this->config['kerberosSSO_adminnotify'];
						generate_text_for_storage($message, $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);
						$pm_data = array(
						'from_user_id'       => 1,
						'from_user_ip'       => "127.0.0.1",
						'from_username'      => "Administrator",
						'enable_sig'         => false,
						'enable_bbcode'      => true,
						'enable_smilies'     => true,
						'enable_urls'        => false,
						'icon_id'            => 0,
						'bbcode_bitfield'    => $bitfield,
						'bbcode_uid'         => $uid,
						'message'            => $message,
						'address_list'       => array('g' => array($useridpm => 'to')),
						);
						submit_pm('post', $pmsubject, $pm_data, false, false);
					}
				}
			}
			else
			{
				return array(
						'status'		=> LOGIN_ERROR_EXTERNAL_AUTH,
						'error_msg'		=> 'kerberosSSO_NO_SERVER_CONNECTION',
						'user_row'		=> array('user_id' => ANONYMOUS),
						);
			}
		}

		if (is_array($kerberosSSO_result) && sizeof($kerberosSSO_result) > 1)
		{
			if ($kerbuser == $username || @ldap_bind($ldap, $kerberosSSO_result[0]['dn'], htmlspecialchars_decode($password)))
			{
				$sql ='SELECT *
					FROM ' . USERS_TABLE . "
					WHERE username_clean = '" . $this->db->sql_escape(utf8_clean_string($username)) . "'";
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				if ($row)
				{
				
					// Build array to update the users table based on information from LDAP
					$sql_ary = array(
						'user_fullname'			=> $kerberosSSO_result[0][htmlspecialchars_decode($this->config['kerberosSSO_displayName'])][0],
						'user_city'				=> $kerberosSSO_result[0][htmlspecialchars_decode($this->config['kerberosSSO_city'])][0],
						'user_state'			=> $kerberosSSO_result[0][htmlspecialchars_decode($this->config['kerberosSSO_state'])][0],
						'user_country'			=> $kerberosSSO_result[0][htmlspecialchars_decode($this->config['kerberosSSO_country'])][0],
						'user_department'		=> $kerberosSSO_result[0][htmlspecialchars_decode($this->config['kerberosSSO_department'])][0],					
					);
					
					// Update users table with array built above.
					$sqlup = "UPDATE " . USERS_TABLE . "
						SET " . $this->db->sql_build_array('UPDATE', $sql_ary) . "
						WHERE username_clean = '" . $this->db->sql_escape(utf8_clean_string($username)) . "'";
					$resultup = $this->db->sql_query($sqlup);
					$this->db->sql_freeresult($resultup);

					// User inactive...
					if ($row['user_type'] == USER_INACTIVE || $row['user_type'] == USER_IGNORE)
					{
						return array(
							'status'		=> LOGIN_ERROR_ACTIVE,
							'error_msg'		=> 'ACTIVE_ERROR',
							'user_row'		=> $row,
						);
					}

					// Successful login... set user_login_attempts to zero...
					add_log('user', "Login Success", "User has successfully logged in - " . $username);
					return array(
						'status'		=> LOGIN_SUCCESS,
						'error_msg'		=> false,
						'user_row'		=> $row,
					);
				}
				else
				{
					// retrieve default group id
					$sql = 'SELECT group_id
						FROM ' . GROUPS_TABLE . "
						WHERE group_name = '" . $this->db->sql_escape('REGISTERED') . "'
							AND group_type = " . GROUP_SPECIAL;
					$result = $this->db->sql_query($sql);
					$row = $this->db->sql_fetchrow($result);
					$this->db->sql_freeresult($result);

					if (!$row)
					{
						trigger_error('NO_GROUP');
					}

					// generate user account data
					$kerberosSSO_user_row = array(
						'username'		  => $username,
						'user_password'	  => $this->passwords_manager->hash("ApacheSSO"),
						'user_email'	  => (!empty($this->config['kerberosSSO_email'])) ? utf8_htmlspecialchars($kerberosSSO_result[0][htmlspecialchars_decode($this->config['kerberosSSO_email'])][0]) : '',
						'group_id'		  => (int) $row['group_id'],
						'user_type'		  => USER_NORMAL,
						'user_ip'		  => $this->user->ip,
						'user_fullname'   => utf8_htmlspecialchars($kerberosSSO_result[0][htmlspecialchars_decode($this->config['kerberosSSO_displayName'])][0]),
						'user_city'       => utf8_htmlspecialchars($kerberosSSO_result[0][htmlspecialchars_decode($this->config['kerberosSSO_city'])][0]),
						'user_state'      => (utf8_htmlspecialchars($kerberosSSO_result[0][htmlspecialchars_decode($this->config['kerberosSSO_country'])][0]) <> "United States") ? "" : $kerberosSSO_result[0][htmlspecialchars_decode($this->config['kerberosSSO_state'])][0],
						'user_country'    => utf8_htmlspecialchars($kerberosSSO_result[0][htmlspecialchars_decode($this->config['kerberosSSO_country'])][0]),
						'user_department' => utf8_htmlspecialchars($kerberosSSO_result[0][htmlspecialchars_decode($this->config['kerberosSSO_department'])][0]),
						'user_new'		  => ($this->config['new_member_post_limit']) ? 1 : 0,
					);

					// this is the user's first login so create an empty profile
					add_log('user', "Profile created for User", "Profile has been created for - " . $username);
					
					return array(
						'status'		=> LOGIN_SUCCESS_CREATE_PROFILE,
						'error_msg'		=> false,
						'user_row'		=> $kerberosSSO_user_row,
					);
				}
			}
			else
			{
				add_log('user', "Login Failed", "Invalid password attempt for - " . $username);
				// Give status about wrong password...
				return array(
					'status'		=> LOGIN_ERROR_PASSWORD,
					'error_msg'		=> 'LOGIN_ERROR_PASSWORD',
					'user_row'		=> array('user_id' => ANONYMOUS),
				);
			}
			
		}

		@ldap_close($ldap);
		unset($kerberosSSO_result);
		unset($ldap);
		
		add_log('user', "Login Failed", "Invalid username - " . $username);
		return array(
			'status'	=> LOGIN_ERROR_USERNAME,
			'error_msg'	=> 'LOGIN_ERROR_USERNAME',
			'user_row'	=> array('user_id' => ANONYMOUS),
		);
	}


	private function ldapconnect($ldapadress, $username)
	{
		$this->config['kerberosSSO_port'] = (int) $this->config['kerberosSSO_port'];
		if ($this->config['kerberosSSO_port'])
		{
			$ldap = @ldap_connect($ldapadress, $this->config['kerberosSSO_port']);
		}
		else
		{
			$ldap = @ldap_connect($ldapadress);
		}

		if (!$ldap)
		{
			return array(false, $ldap);
		}

		@ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
		@ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

		if ($this->config['kerberosSSO_user'] || $this->config['kerberosSSO_password'])
		{
			if (!@ldap_bind($ldap, htmlspecialchars_decode($this->config['kerberosSSO_user']), htmlspecialchars_decode($this->config['kerberosSSO_password'])))
			{
				return array(false, $ldap);
			}
		}
		
		$search = @ldap_search(
			$ldap,
			htmlspecialchars_decode($this->config['kerberosSSO_base_dn']),
			$this->kerberosSSO_user_filter($username),
			array(
				htmlspecialchars_decode($this->config['kerberosSSO_uid']),
				(empty($this->config['kerberosSSO_city']) ? : htmlspecialchars_decode($this->config['kerberosSSO_city'])),
				(empty($this->config['kerberosSSO_state']) ? : htmlspecialchars_decode($this->config['kerberosSSO_state'])),
				(empty($this->config['kerberosSSO_country']) ? : htmlspecialchars_decode($this->config['kerberosSSO_country'])),
				(empty($this->config['kerberosSSO_department']) ? : htmlspecialchars_decode($this->config['kerberosSSO_department'])),
				(empty($this->config['kerberosSSO_displayName']) ? : htmlspecialchars_decode($this->config['kerberosSSO_displayName'])),
				(empty($this->config['kerberosSSO_email']) ? : htmlspecialchars_decode($this->config['kerberosSSO_email']))
			),
			0,
			1
		);
		
		if ($search === false)
		{
			return array(false, $ldap);
		}
		
		$result = @ldap_get_entries($ldap, $search);
		
		return array($result, $ldap);
	}

	/**
	 * {@inheritdoc}
	 */
	public function acp()
	{
		// These are fields required in the config table
		return array(
			'kerberosSSO_hidestate', 'kerberosSSO_server1', 'kerberosSSO_server2', 'kerberosSSO_string', 'kerberosSSO_port', 'kerberosSSO_base_dn', 'kerberosSSO_uid', 'kerberosSSO_user_filter', 'kerberosSSO_email', 'kerberosSSO_user', 'kerberosSSO_password', 'kerberosSSO_displayName', 'kerberosSSO_city', 'kerberosSSO_state', 'kerberosSSO_country', 'kerberosSSO_department','kerberosSSO_adminnotify','kerberosSSO_separator',
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_acp_template($new_config)
	{
		return array(
			'TEMPLATE_FILE'	=> '@LazyMod_KerberosSSO/auth_provider_KerberosSSO.html',
			'TEMPLATE_VARS'	=> array(
				'AUTH_KERBEROSSSO_BASE_DN'		=> $new_config['kerberosSSO_base_dn'],
				'AUTH_KERBEROSSSO_EMAIL'		=> $new_config['kerberosSSO_email'],
				'AUTH_KERBEROSSSO_PASSORD'		=> $new_config['kerberosSSO_password'] !== '' ? '********' : '',
				'AUTH_KERBEROSSSO_PORT'			=> $new_config['kerberosSSO_port'],
				'AUTH_KERBEROSSSO_SERVER1'		=> $new_config['kerberosSSO_server1'],
				'AUTH_KERBEROSSSO_SERVER2'		=> $new_config['kerberosSSO_server2'],
				'AUTH_KERBEROSSSO_UID'			=> $new_config['kerberosSSO_uid'],
				'AUTH_KERBEROSSSO_USER'			=> $new_config['kerberosSSO_user'],
				'AUTH_KERBEROSSSO_USER_FILTER'	=> $new_config['kerberosSSO_user_filter'],
				'AUTH_KERBEROSSSO_DISPLAYNAME'	=> $new_config['kerberosSSO_displayName'],
				'AUTH_KERBEROSSSO_DEPARTMENT'	=> $new_config['kerberosSSO_department'],
				'AUTH_KERBEROSSSO_CITY'    		=> $new_config['kerberosSSO_city'],
				'AUTH_KERBEROSSSO_STATE'    	=> $new_config['kerberosSSO_state'],
				'AUTH_KERBEROSSSO_COUNTRY'  	=> $new_config['kerberosSSO_country'],
				'AUTH_KERBEROSSSO_SSO'			=> $new_config['kerberosSSO_separator'],
				'AUTH_KERBEROSSSO_ADMIN'		=> $new_config['kerberosSSO_adminnotify'],
				'AUTH_KERBEROSSSO_STRING'		=> build_select(array(
									'AUTH_USER'			=> 'KERBEROSSSO_AUTH_USER',
									'LOGON_USER'		=> 'KERBEROSSSO_LOGON_USER',
									'REMOTE_USER'		=> 'KERBEROSSSO_REMOTE_USER',
								), ($this->config['kerberosSSO_string']) ? $this->config['kerberosSSO_string'] : 'AUTH_USER' ),
				'AUTH_KERBEROSSSO_HIDESTATE'	=> build_select(array(
									'Yes'				=> 'KERBEROSSSO_YES',
									'No'				=> 'KERBEROSSSO_NO',
								), isset($this->config['kerberosSSO_hidestate']) ? $this->config['kerberosSSO_hidestate'] : 'YES'),
			),
		);
	}

	/**
	 * Generates a filter string for kerberosSSO_search to find a user
	 *
	 * @param	$username	string	Username identifying the searched user
	 *
	 * @return				string	A filter string for kerberosSSO_search
	 */
	private function kerberosSSO_user_filter($username)
	{
		$filter = '(' . $this->config['kerberosSSO_uid'] . '=' . $this->kerberosSSO_escape(htmlspecialchars_decode($username)) . ')';
		if ($this->config['kerberosSSO_user_filter'])
		{
			$_filter = ($this->config['kerberosSSO_user_filter'][0] == '(' && substr($this->config['kerberosSSO_user_filter'], -1) == ')') ? $this->config['kerberosSSO_user_filter'] : "({$this->config['kerberosSSO_user_filter']})";
			$filter = "(&{$filter}{$_filter})";
		}
		return $filter;
	}

	/**
	 * Escapes an LDAP AttributeValue
	 *
	 * @param	string	$string	The string to be escaped
	 * @return	string	The escaped string
	 */
	private function kerberosSSO_escape($string)
	{
		return str_replace(array('*', '\\', '(', ')'), array('\\*', '\\\\', '\\(', '\\)'), $string);
	}
		 
}
