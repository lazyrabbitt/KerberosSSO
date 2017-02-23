<?php

namespace LazyMod\KerberosSSO\acp;

class main_module
{
    public $u_action;
    public $tpl_name;
    public $page_title;
	
    public function main($id, $mode)
    {
        global $user, $template, $request, $db, $config;
	
		$this->db = $db;
		$this->config = $config;

        $this->tpl_name = 'acp_KerberoSSO';
        $this->page_title = "Kerberos SSO LDAP Re-Check";
		
		//$lang->add_lang('kerberossso_acp', 'LazyMod/KerberosSSO');
		$user->add_lang_ext('LazyMod\KerberosSSO', 'kerberossso_acp');

        add_form_key('acme_demo_settings');

        if ($request->is_set_post('submit'))
        {
			$sql ='SELECT *
					FROM ' . USERS_TABLE . "
					WHERE user_type <> 2";
			$result = $this->db->sql_query($sql);

			while ($row = $this->db->sql_fetchrow($result))
			{
				$username = $row['username'];
				list($kerberosSSO_result, $ldap) = $this->ldaprecheck(htmlspecialchars_decode($this->config['kerberosSSO_server1']), $username);
				
				if ($kerberosSSO_result === false || !is_array($kerberosSSO_result))
				{
					
					if($this->config['kerberosSSO_server2'])
					{
						list($kerberosSSO_result, $ldap) = $this->ldaprecheck(htmlspecialchars_decode($this->config['kerberosSSO_server2']), $username);
						
						if ($kerberosSSO_result === false  || !is_array($kerberosSSO_result))
						{
							trigger_error($user->lang('KERBEROSSSO_NO_SERVER_CONNECTION2') . ' (' . $this->config['kerberosSSO_server2'] . '-' . $username . ') ' . adm_back_link($this->u_action));
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
						trigger_error($user->lang('KERBEROSSSO_NO_SERVER_CONNECTION1') . ' (' . $this->config['kerberosSSO_server1'] . '-' . $username . ') ' . adm_back_link($this->u_action));
					}
				}

				if (is_array($kerberosSSO_result) && sizeof($kerberosSSO_result) > 1)
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
				}
			}
			$this->db->sql_freeresult($result);
			trigger_error($user->lang('KERBEROSSSO_SETTING_SAVED') . adm_back_link($this->u_action));
        }

        $template->assign_vars(array(
            'KERBEROSSSO_GOODBYE' 		=> $config['kerberosSSO_goodbye'],
            'U_ACTION'          		=> $this->u_action,
        ));
    }
	
	private function ldaprecheck($ldapadress, $username)
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
		
		@ldap_close($ldap);
		
		return array($result, $ldap);
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