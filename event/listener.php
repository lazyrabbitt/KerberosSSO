<?php
/**
 *
 * LDAP Advance. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Dana Pierce
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace LazyMod\KerberosSSO\event;

/**
 * @ignore
 */
use phpbb\controller\helper;
use phpbb\request\request_interface;
use phpbb\user;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * LDAP Advance Event listener.
 */
class listener implements EventSubscriberInterface
{

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\language\language */
	protected $lang;

	/** @var \phpbb\pages\operators\page */
	protected $page_operator;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var string phpbb_root_path */
	protected $phpbb_root_path;

	/** @var string phpEx */
	protected $php_ext;

	/**
	* Constructor
	*
	* @param \phpbb\auth\auth            $auth            Authentication object
	* @param \phpbb\controller\helper    $helper          Controller helper object
	* @param \phpbb\language\language    $lang            Language object
	* @param \phpbb\pages\operators\page $page_operator   Pages operator object
	* @param \phpbb\template\template    $template        Template object
	* @param \phpbb\user                 $user            User object
	* @param string                      $phpbb_root_path phpbb_root_path
	* @param string                      $php_ext         phpEx
	* @access public
	*/
	public function __construct(\phpbb\auth\auth $auth, \phpbb\controller\helper $helper, \phpbb\language\language $lang, \phpbb\pages\operators\page $page_operator, \phpbb\template\template $template, \phpbb\user $user, $phpbb_root_path, $php_ext)
	{
		global $config, $db, $profile_cache;
		
		$this->auth = $auth;
		$this->helper = $helper;
		$this->lang = $lang;
		$this->page_operator = $page_operator;
		$this->template = $template;
		$this->user = $user;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
		$this->config = $config;
		$this->db = $db;
		$this->profile_cache = $profile_cache;
	}

	/**
	* Assign functions defined in this class to event listeners in the core
	*
	* @return array
	* @static
	* @access public
	*/
	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup'						=> 'load_language_on_setup',
			'core.modify_username_string'   		=> 'modify_username_string',
			'acp_users_overview_options_append'   	=> 'add_additional_fields',
			'core.acp_users_display_overview' 		=> 'add_additional_fields',
			'core.search_modify_tpl_ary'   			=> 'add_topic_variables',
			'core.viewtopic_modify_post_row'		=> 'add_topic_variables',
			'core.memberlist_view_profile'			=> 'change_username',
			'core.memberlist_prepare_profile_data'	=> 'profile_username_change',
			'core.viewtopic_modify_post_row'		=> 'add_field_to_topic',
		);
	}

	/**
	* Load common board rules language files during user setup
	*
	* @param \phpbb\event\data $event The event object
	* @return void
	* @access public
	*/
	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'LazyMod/KerberosSSO',
			'lang_set' => 'kerberosSSO_common',
		);
		$event['lang_set_ext'] = $lang_set_ext;
		
		$this->user->add_lang_ext('LazyMod/KerberosSSO', 'kerberosSSO_acp');
		
	}
	
	public function add_field_to_topic($event){
		 $postrow = $event['post_row'];
		 $poster_id = $event['poster_id'];
	 
		$sql = 'SELECT user_department, user_city, user_state, user_country
			FROM ' . USERS_TABLE . "
			WHERE user_id = '" . $poster_id . "'";
		$result = $this->db->sql_query($sql, 600);
		
		while ($row = $this->db->sql_fetchrow($result))
		{
			$postrow = array_merge($postrow, array(
				'USER_DEPARTMENT' => $row['user_department'],
				'USER_CITY' => $row['user_city'],
				'USER_STATE' => $row['user_state'],
				'USER_COUNTRY' => $row['user_country'],
			));
			
			$this->template->assign_vars(array(
				'HIDESTATE'					=> ($this->config['kerberosSSO_hidestate'] == "Yes") ? true: false,			
			));
			
		}
		$event['post_row'] = $postrow;
	}

	public function profile_username_change($event)
	{		 
		if($this->config['auth_method'] == "kerberossso")
		{
			 $data = $event['data'];
			 
			 $userfull = $data['user_fullname'];
			 
			 $this->template->assign_vars(array(
			'L_CONTACT_USER'	=> $userfull,
			'CONTACT_USER'	=> $userfull,
			'L_VIEWING_PROFILE' => $userfull,
			'VIEWING_PROFILE' => $userfull,
			));
		}
	}

	public function add_additional_fields($event)
	{
		 $this->user->add_lang_ext('LazyMod/KerberosSSO', 'kerberosSSO_common');
		 
		 $user_row = $event['user_row'];
		 
		 $this->template->assign_vars(array(
		 	'USER_FULLNAME'				=> $user_row['user_fullname'],
			'USER_DEPARTMENT'			=> $user_row['user_department'],
			'USER_CITY'					=> $user_row['user_city'],
			'USER_STATE'				=> $user_row['user_state'],
			'USER_COUNTRY'				=> $user_row['user_country'],
		));
	}
	
	public function add_topic_variables($event)
	{	
		
		if($this->config['auth_method'] == "kerberossso")
		{
			$poster_id = $event['poster_id'];
			
			$this->template->assign_vars(array(
				'POSTER_DEPARTMENT'		=> get_username_string('text', $poster_id, "department", "#fff"),
				'POSTER_CITY'			=> get_username_string('text', $poster_id, "city", "#fff"),
				'POSTER_STATE'			=> get_username_string('text', $poster_id, "state", "#fff"),
				'POSTER_COUNTRY'		=> get_username_string('text', $poster_id, "country", "#fff"),
			));
		}
	}
	
	public function change_username($event)
	{	
		if($this->config['auth_method'] == "kerberossso")
		{
			$member = $event['member'];
			
			$sql = 'SELECT user_fullname
				FROM ' . USERS_TABLE . "
				WHERE user_id = '" . $member['user_id'] . "'";
			$result = $this->db->sql_query($sql, 600);
			$rowset = array();
			
			while ($row = $this->db->sql_fetchrow($result))
			{
				$member['username'] = $row['user_fullname'];
			}
			
			$event['member'] = $member;
			$this->db->sql_freeresult($result);
		}
	}

	public function modify_username_string($event)
	{
		if($this->config['auth_method'] == "kerberossso")
		{
			$user_id 			= $event['user_id'];
			$username 			= $event['username'];
			$mode 				= $event['mode'];
			$username_colour 	= $event['username_colour'];
			$guest_username = false;
			$custom_profile_url = false;
			
			static $_profile_cache;
			global $phpbb_dispatcher;

			// Pull the users table based on the user_id that comes over with the function call.
			$sql = 'SELECT *
				FROM ' . USERS_TABLE . "
				WHERE user_id = '" . $user_id . "'";
			$result = $this->db->sql_query($sql, 600);
			
			while ($row = $this->db->sql_fetchrow($result))
			{
				// This modification checks if the mode is anything other than username and replaces the $username value with the full name value.
				if($username == $row['username'] && $mode <> "username"){ $username = $row['user_fullname']; }
				
				// These checks are only on a new mode called text, and a call to the function replacing the username with a static name.  Basically the name
				// of the field that is being looked up.  This is so that we can easily use this information on the template for each user's forum post.
				if($mode == "text")
				{
					if($username == "department" && $row['user_department'] <> ""){ $username = $row['user_department']; }
					if($username == "department" && $row['user_department'] == ""){ return ""; }
					
					if($username == "city" && $row['user_city'] <> ""){ $username = $row['user_city']; }
					if($username == "city" && $row['user_city'] == ""){ return ""; }
					
					if($username == "state" && $row['user_state'] <> ""){ $username = $row['user_state']; } 
					if($username == "state" && $row['user_state'] == null){ return ""; } 
					
					if($username == "country" && $row['user_country'] <> ""){ $username = $row['user_country']; }
					if($username == "country" && $row['user_country'] == ""){ return ""; }
				}
			}
			$this->db->sql_freeresult($result);


			// We cache some common variables we need within this function
			if (empty($_profile_cache))
			{
				$_profile_cache['base_url'] = append_sid("{$this->phpbb_root_path}memberlist.{$this->php_ext}", 'mode=viewprofile&amp;u={USER_ID}');
				$_profile_cache['tpl_noprofile'] = '<span class="username">{USERNAME}</span>';
				$_profile_cache['tpl_text'] = '<span>{USERNAME}</span>';
				$_profile_cache['tpl_noprofile_colour'] = '<span style="color: {USERNAME_COLOUR};" class="username-coloured">{USERNAME}</span>';
				$_profile_cache['tpl_profile'] = '<a href="{PROFILE_URL}" style="color: {USERNAME_COLOUR};" class="username">{USERNAME}</a>';
				$_profile_cache['tpl_profile_colour'] = '<a href="{PROFILE_URL}" style="color: {USERNAME_COLOUR};" class="username-coloured">{USERNAME}</a>';
			}

			global $user, $auth;

			// This switch makes sure we only run code required for the mode
			switch ($mode)
			{
				case 'full':
				case 'no_profile':
				case 'colour':

					// Build correct username colour
					$username_colour = ($username_colour) ? $username_colour : '';  // Since the $username_colour, if exists, comes over with the # we nolonger need to readd it like the original function.

					// Return colour
					if ($mode == 'colour')
					{
						$username_string = $username_colour;
						break;
					}

				// no break;

				case 'username':

					// Build correct username
					if ($guest_username === false)
					{
						$username = ($username) ? $username : $user->lang['GUEST'];
					}
					else
					{
						$username = ($user_id && $user_id != ANONYMOUS) ? $username : ((!empty($guest_username)) ? $guest_username : $user->lang['GUEST']);
					}

					// Return username
					if ($mode == 'username')
					{
						$username_string = $username;
						break;
					}

				// no break;

				case 'profile':

					// Build correct profile url - only show if not anonymous and permission to view profile if registered user
					// For anonymous the link leads to a login page.
					if ($user_id && $user_id != ANONYMOUS && ($user->data['user_id'] == ANONYMOUS || $auth->acl_get('u_viewprofile')))
					{
						$profile_url = ($custom_profile_url !== false) ? $custom_profile_url . '&amp;u=' . (int) $user_id : str_replace(array('={USER_ID}', '=%7BUSER_ID%7D'), '=' . (int) $user_id, $_profile_cache['base_url']);
					}
					else
					{
						$profile_url = '';
					}

					// Return profile
					if ($mode == 'profile')
					{
						$username_string = $profile_url;
						break;
					}

				// no break;
			}

			if (!isset($username_string))
			{
				if (($mode == 'full' && !$profile_url) || $mode == 'no_profile')
				{
					$username_string = str_replace(array('{USERNAME_COLOUR}', '{USERNAME}'), array($username_colour, $username), (!$username_colour) ? $_profile_cache['tpl_noprofile'] : $_profile_cache['tpl_noprofile_colour']);
				}
				else if ($mode == 'text') {
					$username_string = str_replace('{USERNAME}', $username, $_profile_cache['tpl_text']);
				}
				else
				{
					$username_string = str_replace(array('{PROFILE_URL}', '{USERNAME_COLOUR}', '{USERNAME}'), array($profile_url, $username_colour, $username), (!$username_colour) ? $_profile_cache['tpl_profile'] : $_profile_cache['tpl_profile_colour']);
				}
			}

			$event['username_string'] = $username_string;
		} 
		return $username_string;
	}

}
