<?php
/**
 * Encryption class to encrypt and decrypt things securely.
 *
 * Example of use:
 * $member = new StdClass();
 * $member->id = 141;
 * $member->mail = 'disadb1@gmail.com';
 * echo $encrypted = urlencode(base64_encode(encrypt(json_encode($member))));
 * echo "\n".print_r(decrypt(base64_decode(urldecode($encrypted))),1);
 * echo "\n".$url = url("links/members/become-member.php?activate=$encrypted");
 * die;
 */

class Encryption
{
	private static $instance = null;
	private $passwordSalt;

	/**
	 * Class constructor.
	 */
	public function __construct()
	{
		$settings     = Settings::get();
		$passwordSalt = $settings->passwordSalt;
	}

	/**
	 * Get the only instance of this class.
	 *
	 * @return Encryption object: the only instance of this class.
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance)) self::$instance = new self;
		return self::$instance;
	}

	/**
	 * if secure the string is encoded in a way it cannot be decrypted ever again.

	 * @param  string  $string the string to encrypt.
	 * @param  boolean $secure whether to create a decryptable or undecryptable hash.
	 * @return string: the encrypted string.
	 */
	public static function encrypt($string, $secure = false)
	{
		// BCRYPT encoding. As you can parameter the cost, it is that many times longer to crack so better than md5.
		// BCRYPT will always be 60 characters.
		return  $secure ? password_hash($string, PASSWORD_BCRYPT, ['cost' => 10])
					    : mcrypt_encrypt(MCRYPT_RIJNDAEL_256, self::getInstance()->passwordSalt, $string, MCRYPT_MODE_ECB);
	}

	/**
	 * Decrypt the encrypted string if not secure.
	 *
	 * @param  string $string: the string to decrypt.
	 * @return string: the decrypted string.
	 */
	public static function decrypt($string)
	{
		return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, self::getInstance()->passwordSalt, $string, MCRYPT_MODE_ECB);
	}

	/**
	 * Verify in case of secure encryption only.
	 *
	 * @param  string $clearStr:  The clear string to compare against encrypted and endecryptable hash.
	 * @param  string $cryptedStr: a 60 chars hash created with password_hash() function. (Should start with $2y$).
	 * @return boolean: true if matching, false otherwise.
	 */
	public static function verify($clearStr, $cryptedStr)
	{
		return password_verify($clearStr, $cryptedStr);
	}
}
?>