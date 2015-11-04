<?php
/**
 * Settings Model.
 * Design pattern: singleton.
 **/

Class Settings
{
	const CONFIG_DIR = __DIR__.'/../config';
	const CONFIG_FILE = 'config.ini';
	private static $instance = null;

	// The settings object, extracted from the multiple configuration ".ini" files.
	private $settings = null;

	/**
	 * Class constructor.
	 */
	private function __construct()
	{
		$this->settings = $this->fetchSettings();
	}

	/**
	 * Get the only instance of this class.
	 *
	 * @return the only instance of this class.
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance)) self::$instance = new self;
		return self::$instance;
	}

	/**
	 * Fetch all the settings from the ini files in CONFIG_DIR and prepare some vars.
	 * First get config from CONFIG_FILE and then overwrite with any other .ini file found in CONFIG_DIR.
	 * Handles links (shortcuts) to .ini files elsewhere (for FAT32 partitions that don't handle symlinks), and MAC OSX symlinks.
	 * Used to handle multiple dev environments with specific configs.
	 * The practice is to put a lnk/symlink -- in CONFIG_DIR --  to a .ini file on the machine.
	 *
	 * @return void.
	 */
	private function fetchSettings()
	{
	    // First retrieve global settings.
	    $settings = parse_ini_file(self::CONFIG_DIR.'/'.self::CONFIG_FILE/*, process_sections= true*/);

	    // Then retrieve possible env-specific ini files -- in CONFIG_DIR -- to overwrite vars in global settings.
	    foreach(scandir(self::CONFIG_DIR) as $iniFile)
	    {
	        // only take care of .ini and .ini.lnk
	        if ((strpos($iniFile, '.ini') !== false || strpos($iniFile, '.ini.lnk') !== false) && $iniFile !== self::CONFIG_FILE)
	        {
	            $iniFile = self::CONFIG_DIR."/$iniFile";
	            if (strpos($iniFile, '.ini.lnk') !== false)
	            {
	                // Get the content of the .lnk to extract the real target. readlink() only work for symlinks.
	                $lnkData = file_get_contents($iniFile);
	                $target = preg_replace('@^.*\00([A-Z]:)(?:\\\\.*?\\\\\\\\.*?\00|[\00\\\\])(.*?)\00.*$@s', '$1\\\\$2', $lnkData);
	                if (!is_file($target)) continue;
	                $iniFile = $target;
	            }
	            // Overwrite the $settings array with specific configs if any.
	            $settings = array_merge($settings, parse_ini_file($iniFile/*, process_sections = true*/));
	        }
	    }

	    // Convert to object.
	    $settings = (object)$settings;

	    // This var has to be used in templates when the rewrite engine is ON.
	    $settings->root = (IS_LOCAL ? $settings->rootLocal : $settings->siteUrl).'/';
	    $settings->rewriteEngine = isset($settings->rewriteEngine) && $settings->rewriteEngine;
	    $_SERVER['SERVER_ADMIN'] = $settings->adminEmail;

	    return $settings;
	}

	static function get()
	{
		return self::getInstance()->settings;
	}

	/**
	 * Private clone method to prevent cloning of the instance of the Singleton.
	 *
	 * @return void.
	 */
	private function __clone()
	{
	}
}