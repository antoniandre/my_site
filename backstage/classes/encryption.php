<?php
/**
 * Encryption class to encrypt and decrypt things securely.
 *
 * Example of use:
 * $member= new StdClass();
 * $member->id= 141;
 * $member->mail= 'disadb1@gmail.com';
 * echo $encrypted= urlencode(base64_encode(encrypt(json_encode($member))));
 * echo "\n".print_r(decrypt(base64_decode(urldecode($encrypted))),1);
 * echo "\n".$url= url("links/members/become-member.php?activate=$encrypted");
 * die;
 */

class Encryption
{
	private static $prefixSalt = 'disadb--12345678';
	private static $suffixSalt = 'yeah';

	/**
	 * Class constructor
	 */
	public function __construct()
	{
	}

	/*
		if secure the string is encoded in a way it cannot be decrypted ever again.
	*/
	public static function encrypt($string, $secure = 0)
	{
		return  $secure ? md5($this->prefixSalt.$string.$this->suffixSalt)
					    : mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->prefixSalt, $string, MCRYPT_MODE_ECB);
	}

	public static function decrypt($string)
	{
		return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->prefixSalt, $string, MCRYPT_MODE_ECB);
	}

	public static function checkValidity($clearStr, $cryptedStr)
	{
		return encrypt($clearStr, 1) == $cryptedStr;
	}
}
?>