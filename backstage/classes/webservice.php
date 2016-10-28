<?php
/**
 * Webservice class.
 */

class Webservice
{
	private static $instance = null;
	// private $distantUrl = $settings->siteUrl;
	private $distantUrl = 'http://travel.dev';
	private $exchangePassword = 'be$tKeyword∑ver-Yop!';


	/**
	 * Class constructor.
	 */
	public function __construct($wsID = null)
	{
		if (strpos($_SERVER['QUERY_STRING'], 'ws=') !== false) $this->runDistant();
		elseif ($wsID) $this->consume($wsID);

		return $this;
	}

	public function checkPass()
	{
		parse_str($_SERVER['QUERY_STRING'], $queryParts);
		$ws = urldecode(base64_decode($queryParts['ws']));

		list($ws, $token) = array_pad(explode('~~', $ws), 2, null);

		return $token && strlen($token) === 60 && Encryption::verify($this->exchangePassword, $token) ? $ws : false;
	}

	/**
	 * Consume =
	 * 1 - connect to distant (passing data or not)
	 * 2 - run code on distant (providing that the hash is correct)
	 * 3 - fetch the return of distant onto local
	 * @param  [type] $ws       [description]
	 * @param  [type] $jsonData [description]
	 * @param  [type] $method   [description]
	 * @return [type]           [description]
	 */
	/*public function consume($ws, $jsonData = null, $method = null)
	{
		if ($token && $ws = $this->checkPass($token))
		{
			$posts = Userdata::get();

		    $settings = Settings::get();
		    $encToken = Encryption::encrypt($this->exchangePassword, true);

		    // create curl resource.
		    $ch = curl_init();

		    // set url
		    curl_setopt($ch, CURLOPT_URL, $settings->siteUrl."/en/?ws=".base64_encode(urlencode("$ws~~$encToken")));

		    if ($data && $method === 'post')
		    {
		    	curl_setopt($ch,CURLOPT_POST, 1);
		    	curl_setopt($ch,CURLOPT_POSTFIELDS, 'data='.urlencode($jsonData));
		    }

		    //return the transfer as a string.
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		    // $output contains the output string.
		    $output = curl_exec($ch);

		    // close curl resource to free up system resources
		    curl_close($ch);

			if (includeOnceWebservice($ws)) afterConsume($output);
		}
	}*/
	public function consume($wsID)
	{
		// Include the dedicated file and run the beforeConsume() function.
		if (includeWebservice($wsID))
		{
			list($data, $method) = beforeConsume();

		    $settings = Settings::get();
		    $encToken = Encryption::encrypt($this->exchangePassword, true);

		    // Create curl resource.
		    $ch = curl_init();

		    // Set url to the WS call giving a hash.
		    curl_setopt($ch, CURLOPT_URL, "$this->distantUrl/en/?ws=".base64_encode(urlencode("$wsID~~$encToken")));

		    if ($data && $method === 'post')
		    {
		    	curl_setopt($ch, CURLOPT_POST, 1);
		    	// Better to provide a string rather than an array: http://stackoverflow.com/a/2138534/2012407.
		    	curl_setopt($ch, CURLOPT_POSTFIELDS, 'data='.urlencode(json_encode($data)));
		    }

		    // Return the transfer as a string.
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		    // $output contains the output string.
		    $output = curl_exec($ch);

		    // Close curl resource to free up system resources.
		    curl_close($ch);

			afterConsume($output);
		}
	}


	// Emmit the code if token is matching.
	public function runDistant()
	{
		$posts = Userdata::getWithHtml('post');//!\ Be careful what you do!
		$data = isset($posts->data) ? json_decode(stripslashes(urldecode($posts->data))) : null;

		if (($wsID = $this->checkPass()) && includeOnceWebservice($wsID)) distantCode($data);
	}
}
?>