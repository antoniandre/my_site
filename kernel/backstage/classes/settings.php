<?php
/**
 * Settings Model.
 * Design pattern: singleton.
 **/

class Settings
{
	const CONFIG_DIR       = ROOT.'kernel/backstage/config';
	const CONFIG_FILE      = 'config.ini';// Main settings. Can be overriden by other .ini files.
	const THEME_DIR        = ROOT.'themes';
	const THEME_CONFIG_DIR = 'config';// From current theme root. E.g. themes/my-awesome-site/.

	private static $instance = null;

	// The settings object, extracted from the multiple configuration ".ini" files.
	private $settings = null;
	private $theme    = null;

	/**
	 * Class constructor.
	 */
	private function __construct()
	{
		$this->getActiveTheme();

		$settings = $this->fetchSettings(self::CONFIG_DIR);
		$themeSettings = $this->fetchSettings(self::THEME_DIR.'/'.$this->theme.'/'.self::THEME_CONFIG_DIR);

		// Override the $settings array with theme configs if any.
		if ($themeSettings) $settings = array_merge($settings, $themeSettings);

	    // Convert to object.
	    $this->settings = (object)$settings;

	    $this->settings = $this->addVarsToSettings($this->settings);

		$_SERVER['SERVER_ADMIN'] = $this->settings->adminEmail;
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

	private function getActiveTheme()
	{
		$this->theme = trim(file_get_contents(self::THEME_DIR . '/active'));
		return $this;
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
	private function fetchSettings($src)
	{
		$settings = null;
		$file     = $src.'/'.self::CONFIG_FILE;

		if (is_file($file))
		{
			// First retrieve global settings in file named as defined in CONFIG_FILE const.
			$settings = parse_ini_file($file/*, process_sections= true*/);

			// Then retrieve possible env-specific ini files -- in CONFIG_DIR -- to overwrite vars in global settings.
			if (is_dir($src)) foreach(scandir($src) as $iniFile)
			{
				// only take care of .ini and .ini.lnk
				if ((strpos($iniFile, '.ini') !== false || strpos($iniFile, '.ini.lnk') !== false) && $iniFile !== self::CONFIG_FILE)
				{
					$iniFile = $src."/$iniFile";
					if (strpos($iniFile, '.ini.lnk') !== false)
					{
						// Get the content of the .lnk to extract the real target. readlink() only work for symlinks.
						$lnkData = file_get_contents($iniFile);
						$target = preg_replace('@^.*\00([A-Z]:)(?:\\\\.*?\\\\\\\\.*?\00|[\00\\\\])(.*?)\00.*$@s', '$1\\\\$2', $lnkData);
						if (!is_file($target)) continue;
						$iniFile = $target;
					}
					// Override the $settings array with specific configs if any.
					$settings = array_merge($settings, parse_ini_file($iniFile/*, process_sections = true*/));
				}
			}
		}

	    return $settings;
	}

	private function addVarsToSettings($settings)
	{
		// Multiple localhost addresses can be set in the config.ini file.
		define('IS_LOCAL', in_array($_SERVER['SERVER_NAME'], $settings->localHosts));

	    // This var has to be used in templates when the rewrite engine is ON.
	    $settings->root = (IS_LOCAL ? $settings->rootLocal : $settings->siteUrl).'/';
	    $settings->rewriteEngine = isset($settings->rewriteEngine) && $settings->rewriteEngine;
	    $settings->theme = $this->theme;

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