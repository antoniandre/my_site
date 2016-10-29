<?php
/**
 * Webservice class.
 * How to use:
 * 1 - create a file in webservices/ folder with a name that will be the webservice id
 * 2 - write 3 functions to cover all the steps of the webservice call: distantCode($data), beforeConsume(), afterConsume($data).
 * 3 - include the webservice class and instanciate it with your webservice id (name of the file)
 *
 * Example of use:
 *    includeClass('webservice');
 *    new Webservice('send-article-to-live');
 *    function distantCode($data)
 *    {
 *        // do something with the given received $data sent from localhost.
 *    }
 *
 *    function beforeConsume()
 *    {
 *        $data = prepareData();
 *        return [$data, 'post'];
 *    }
 *
 *    function afterConsume($data)
 *    {
 *        $messageType = 'info';
 *        if (strpos($data, 'SUCCESS') === 0) $messageType = 'success';
 *        if (strpos($data, 'ERROR') === 0) $messageType = 'error';
 *
 *        new Message('Distant page said:'.$data, $messageType, $messageType, 'header', true);
 *    }
 */

class Webservice
{
	private static $instance = null;
	private $distantUrl = '';
	// private $distantUrl = 'http://travel.dev';
	private $exchangePassword = 'be$tKeyword∑ver-Yop!';


	/**
	 * Class constructor.
	 */
	public function __construct($wsID = null)
	{
		$settings = Settings::get();
		$this->distantUrl = $settings->siteUrl;

		if (strpos($_SERVER['QUERY_STRING'], 'ws=') !== false) $this->runDistant();
		elseif ($wsID) $this->consume($wsID);

		return $this;
	}

    /**
     * Check that the password received with the webservice request is valid.
     * If the passsword is invalid don't do the expected action and ignore the call as if it was a standard page visit.
     * You don't want to tell people with bad intentions that the webservice exists but the hash is incorrect.
     *
     * @return boolean: true if correct, false otherwise.
     */
	public function checkPass()
	{
		parse_str($_SERVER['QUERY_STRING'], $queryParts);
		$ws = urldecode(base64_decode($queryParts['ws']));

		list($ws, $token) = array_pad(explode('~~', $ws), 2, null);

		return $token && strlen($token) === 60 && Encryption::verify($this->exchangePassword, $token) ? $ws : false;
	}

	/**
	 * Consume action equals to:
	 * 1 - connect to distant (passing data or not)
	 * 2 - run code on distant (providing that the hash is correct)
	 * 3 - fetch the return of distant onto local
     *
	 * @param string $wsId: The webservice id you want to run.
	 * @return [type]           [description]
	 */
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


	/**
     * Emmit the code from live site if token is matching.
     * Then die a message to be returned to the caller - on localhost.
     *
     * @return void.
     */
	public function runDistant()
	{
		$posts = Userdata::getWithHtml('post');//!\ Be careful what you do!
		$data = isset($posts->data) ? json_decode(stripslashes(urldecode($posts->data))) : null;
        $returnMessage = 'No return message provided by the webservice.';

		if (($wsID = $this->checkPass()) && includeOnceWebservice($wsID)) $returnMessage = (string)distantCode($data);
        die($returnMessage);
	}
}
?>