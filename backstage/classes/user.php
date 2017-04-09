<?php
/**
 * User model.
 *
 * @dependencies: settings class.
 * @todo: develop this whole class.
 */
class User
{
	private static $instance = null;
	private $id;
	public $type;
	public $login;
	private $settings;
	private $ip;
	private $info;
	private $email;
	public $message;
	private $error;


	/**
	 * Class constructor.
	 *
	 * @todo: complete the code here.
	 */
	function __construct()
	{
		$settings = Settings::get();

		if (IS_LOCAL)
		{
			$this->id = 1;
			$this->type = 'member';
		}
		else
		{
			$this->id = 2;
			$this->type = 'guest';
		}
		/*
		$language = Language::getCurrent();
		$posts = Userdata::get('post');

		// First look in session to check if the user is already logged in or guest.
		//!\\ The session var 'user' is already taken by ovh. Use 'usr' to avoid conflict.
		if (isset($sessions->usr)) $this->retrieveUserFromSession($sessions);

		// Check if user attempts to log in.
		if (isset($posts->connection)) $this->login($posts->login, $posts->password);

		// Check if user attempts to log out.
		elseif (isset($posts->logout) && $this->isMember()) $this->logout();

		// So the user is a guest.
		elseif (!isset($sessions->usr)) $this->startGuestSession();

		$this->setSettings();
		if ($this->message) setMessage($this->message, $this->error? 'failure' : 'success');

		//if user prefered language is different than the active one redirect the user to his language and
		//translate url if SEO is on.
		if ($this->getSettings() && isset($this->getSettings()->language)
			&& $language!= @$this->getSettings()->language)//!\ only if pref language is different
		{
			changeLanguage($language, $this->getSettings()->language);
		}*/
	}


	/**
	 * Get the only instance of this class.
	 *
	 * @return User object: the only instance of this class.
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance)) self::$instance = new self;
		return self::$instance;
	}


	/**
	 * Retrieve user from session.
	 *
	 * @return void.
	 */
	/*private function retrieveUserFromSession($sessions)
	{
		// Decrypt the session var, unserialize it and obtain this object: $user= {id: an_id, login: "a login"}.
		// Store the user id + user login in session for stronger security (cannot guess the matching id-login pair).
		$user = unserialize(Encryption::decrypt($sessions->usr));

		// Check in database if the user userId-login pair exists.
		// "SELECT `id`, `login`, `type` FROM `users` WHERE CONCAT(`id`,LOWER(`login`))='$user->id$user->login'"
		$db= Database::getInstance();
		$q= $db->query();
		$q->select('users', [$q->col('id'), $q->col('login'), $q->col('type')]);
		$w= $q->where();
		$w->concat($w->col('id'), $w->lower($w->col('login')));
		$user= $q->run()
			     ->loadObject();
		if (is_object($user))
		{
			$this->id= $user->id;
			$this->login= $user->login;
			$this->type= $user->type;
		}
		else $this->startGuestSession();
	}*/


	/*
		try to log the user in with the provided ogin and password,
		if success the guest becomes member, the userId-login pair is stored in session and hello msg prints to screen
		if failure return error and message to the user
		if user account is not active (activation by mail) prints a msg to screen
	*/
	/*private function login($login, $password)
	{
		$settings = Settings::get();
		$pair= strtolower($login.$password);

		if (strpos($pair,"'")!==false || strpos($pair,'"')!==false) setError(744);//error: found " or '
		else $member= loadObject("SELECT `memberId`,`activated`,`visitsNumber` FROM `members`
								  WHERE LOWER(CONCAT(`login`,`password`))='$pair'");

		if (!is_object($member)) return setError(569);//did not found user in DB (invalid login or password)
		elseif (!$member->activated) return setError(getTexts(593).'<br />'.getTexts(594));//block unactiv. accompts
		else
		{
			update('members',array('lastVisit','visitsNumber'),
							 array(date('Y-m-d H:i:s'),$member->visitsNumber+1),
							 "`memberId`='$member->memberId'");
			$this->id= $member->memberId;
			$this->login= $GLOBALS['memberLogin']= $login;
			$this->type= 'member';
			$this->message= filter(getTexts(date('H')<12?561:(date('H')<$settings->eveningStartsAt?562:563)));
			$this->storeInSession();//whatever the type of user, store the userId-login pair in session
		}
	}*/


	/*
		logs the user out and change the type of user from member to guest.
		the session is not destroyed (we also need it for guest not to be redirected to intro)
	*/
	/*public function logout()
	{
		//after logout guest session is started to prevent redirection to introduction
		//!\So no session_destroy !
		$this->startGuestSession();
		$this->message= getTexts(564);
	}*/


	/*
		activate a user, sends an email redirect and log the user in
	*/
	/*public function activate($id)
	{
		$member= loadObject("SELECT * FROM `members` WHERE `memberId`='$id'");
		if (is_object($member) && !$member->activated)
		{
			update('members', 'activated', 1, "`memberId`='$id'");
			$this->login($member->login, $member->password);
			email('admin', '', filter(getTexts(268,0)), filter(getTexts(207)));
			$GLOBALS['memberLogin']= $member->login;
			//commented next line cz already showing message...! :)
			//setMessage(filter(getTexts(date('H')<12?561:(date('H')<$settings->eveningStartsAt?562:563))),'success',1);
			header('location: '.url(SELF."?welcome=>$this->login!"));
			exit;
			//reloads the page for the session to be active
		}
		else {header('location: '.url(SELF));exit;}
	}*/


	/*
		starts a Guest Session
	*/
	/*private function startGuestSession()
	{
		$guestId= loadResult('SELECT `value` FROM `misc` WHERE `var`="guestId"');
		if (!$guestId) insert('misc',array('guestId',$guestId= 1,date('Y-m-d')));
		else update('misc','value',$guestId+= 1,'var="guestId"');
		$this->id= -$guestId;
		$this->type= 'guest';
		$this->login= null;
		$this->storeInSession();//whatever the type of user, store the userId-login pair in session
	}*/


	/**
	 * Check if current user is admin or not and return a boolean.
	 * First check the IP against the admin ip list provided in the config.ini file,
	 * Then check the session.
	 *
	 * @return boolean: true if user is admin, false otherwise.
	 * @todo Complete the code. For now only check by IP.
	 */
	public function isAdmin()
	{
		$settings = Settings::get();

		// First check if the user ip is recognized as an admin IP.
		if (in_array($this->getIp(), $settings->adminIpList)) return true;
        else return false;

		// Then check the session.
		// @todo: check the session.
		// return $this->id == 1;
		// $settings = Settings::get();
		// return $this->login.$this->id== $settings->webMasterLogin.'1'?1:0;
	}


	/*
		return true if user is a member or admin
	*/
	public function isMember()
	{
		return $this->type== 'member' || $this->isAdmin() ? 1 : 0;
	}


	/*
		set lang, etc.
	*/
	/*private function setSettings()
	{
		$settings= loadResult("SELECT `settings` FROM `user_settings` WHERE `userId`='$this->id'");
		if (is_object(unserialize(@$settings))) $this->settings= unserialize($settings);
		else $this->settings= new StdClass();
	}*/


	/*
		set lang, etc.
		@param either a pair $key, $value (2 params) or an $object (1 param)
	*/
	/*public function updateSettings()
	{
		$object= new StdClass();
		if (func_num_args()==1) $object= func_get_arg(0);
		else {$a=func_get_arg(0);$object->$a= func_get_arg(1);}

		foreach(get_object_vars($object) as $k=>$v) $this->settings->$k= $v;
		insert('user_settings',array($this->id,serialize($this->settings)));
	}*/


	/*
		remove the settings given in param
		@param either a single $key (string) or array of keys (strings).
	*/
	/*public function removeSettings($keys)
	{
		foreach((array)$keys as $k) unset($this->settings->$k);
		insert('user_settings',array($this->id,serialize($this->settings)));
	}*/


	/**
	 * getId. Get the private attr ID of the current user.
	 *
	 * @return int: the id of the current user.
	 */
	public function getId()
	{
		return $this->id;
	}


	/*
		set lang, etc.
	*/
	/*public function getSettings()
	{
		return $this->settings;
	}*/


	/*
		set an error and a message.
	*/
	private function setError($msg)
	{
		$this->error= 1;
		$this->message= is_numeric($msg)?getTexts(msg):$msg;
	}


	/*
		set browser, etc.
	*/
	private function setInfo()
	{
		/*global $root;
		include $root.'backstage/functions/statistics.php';
		$this->info = getClientInfo();*/
	}


	/*
		get browser, etc.
	*/
	public function getInfo()
	{
		if (!$this->info) $this->setInfo();
		return $this->info;
	}


	/**
	 * Retrieve the real user IP.
	 */
	public function getIp()
	{
		if ($this->ip) return $this->ip;

		$ip = null;

		// Shared internet case.
		if (isset($_SERVER['HTTP_CLIENT_IP'])) $ip = $_SERVER['HTTP_CLIENT_IP'];

		// Behind proxy case.
		elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];

		// Normal IP.
		else $ip = (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null);

		$this->ip = $ip;

		return $ip;
	}


	/*
		set email address for a member.
	*/
	/*public function setEmail()
	{
		$this->email= loadResult("SELECT `email` FROM `members` WHERE `memberId`='$this->id'");
	}*/
	/*
		get email address for a member.
	*/
	public function getEmail()
	{
		if (!$this->email) $this->setEmail();
		return $this->email;
	}


	/*
		whatever the type of user, store the userId-login pair in session for stronger security.
	*/
	/*private function storeInSession()
	{
		$userSess= new StdClass();
		$userSess->id= $this->id;
		$userSess->login= $this->isMember()? $this->login : "Guest #$this->id";
		$_SESSION['usr']= Encryption::encrypt(serialize($userSess));
	}*/


	/* TODO: finish

		//----------------------------- Profile deletion ----------------------------//
		if (isset($posts->deleteProfile) && !isset($posts->yesConfirmed))
			// sets the query but does not delete unless ajax brought confirmation.
			// if not confirmed deletion query is executed in: '-- delete pending queries --' above.
			delete(array("my_bar||`user`=$user->id",
						 "my_bar_tried_cocktails||`user`=$user->id",
						 "DELETE cocktails,made_of FROM cocktails,made_of
						  WHERE `owner`=$user->id AND `cocktail`=`cocktailId`",
						 "DELETE boutique_orders,boutique_ordered_products FROM boutique_orders,boutique_ordered_products
						  WHERE `client`=$user->id AND `orderId`=`order`",
						 "members||`memberId`=$user->id"),isset($posts->ajax)?0:1);

		if (isset($posts->yesConfirmed) && isset($posts->deleteProfile))
		{
			$guestId= loadResult('SELECT `value` FROM `misc` WHERE `var`="guestId"');
			insert('misc',array('guestId',(int)$guestId+1,date('Y-m-d')));
			$user->logout();//logout the user and start a guest session instead

			//TODO: test following:
			setMessage(getTexts(634),'success',1);
			////can't set a 'messageTo$user->id' in misc DB table cz the user id is no longer set
			//header('location: '.url(URI).(QUERY_STRING?'&amp;accountDeleted=1':'?accountDeleted=1'));

			exit;
		}
		if (isset($gets->accountDeleted)) setMessage(getTexts(634),'success');
		//---------------------------------------------------------------------------//
		//-------------------------------- user mailbox -----------------------------//
		if (isset($posts->getMail) && isset($posts->ajax))
		{
			$mail= loadObject("SELECT `messageId`,`message`,`login`,`date`,`from` FROM `user_messages`
							   INNER JOIN `members` ON `memberId`=`from`
							   WHERE `user`=$user->id AND `state`=1 ORDER BY `date`");
			if ($mail->message)
			{
				update('user_messages','state',2,"`messageId`=$mail->messageId");
				$date= dateFormat(strtotime($mail->date),$language!= 'Fr' ? 'l, F dS Y \a\t h:ia'
																		  : '{Day} d {Month} Y \&\a\g\r\a\v\e\; H\hi');
				if ($mail->from== 1) $mail->login= getTexts(38);//says "from admin" instead of "from disadb"
			}
			die($mail->message?'<p class="header"><em><strong>'.getTexts(387)." $mail->login</strong> &nbsp;-&nbsp; $date</em></p><p class=\"body\">".nl2br($mail->message).'</p>':'error');
		}
		//---------------------------------------------------------------------------//
	*/
}
?>