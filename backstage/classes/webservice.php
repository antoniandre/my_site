<?php
/**
 * Webservice class.
 */

class Webservice
{
	private static $instance = null;
	private $exchangePassword = 'be$tKeyword∑ver-Yop!';

	/**
	 * Class constructor.
	 */
	public function __construct($token = null)
	{
		if ($token && $ws = $this->checkPass($token)) $this->emit($ws);

		return $this;
	}

	public function checkPass($ws)
	{
		list($ws, $token) = array_pad(explode('~~', $ws), 2, null);

		return $token && strlen($token) === 60 && Encryption::verify($this->exchangePassword, $token) ? $ws : false;
	}

	public function emit($ws)
	{dbgd($ws);
		if (includeOnceWebservice($ws)) emit();
	}

	public function consume($ws)
	{
		$posts = Userdata::get();

	    $settings = Settings::get();
	    $encToken = Encryption::encrypt($this->exchangePassword, true);

	    // create curl resource.
	    $ch = curl_init();

	    // set url
	    curl_setopt($ch, CURLOPT_URL, $settings->siteUrl."/en/?ws=".base64_encode(urlencode("$ws~~$encToken")));

	    //return the transfer as a string.
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	    // $output contains the output string.
	    $output = curl_exec($ch);

	    // close curl resource to free up system resources
	    curl_close($ch);

		if (includeOnceWebservice($ws)) afterConsume($output);
	}
	public function receive($ws)
	{
		$this->consume($ws);
	}
}
?>