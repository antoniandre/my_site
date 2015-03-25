<?php
/**
 * Database Model.
 * Design pattern: singleton.
 */
include __DIR__.'/../classes/database.query.php';

Class Database
{
	const connectionError= '<p>Un probleme de connection &agrave; la base de donn&eacute;es est survenu.<br />Ce probl&egrave;me temporaire sera r&eacute;solu d&egrave;s que possible aupr&egrave;s de notre h&eacute;bergeur.<br />Merci de votre compr&eacute;hension.</p><hr /><p>A database connection error occured.<br />We are already working on solving this problem with our host.<br />Thank you for your understanding.</p>';
	const minimumDbSqlFile= 'backstage/install/minimum-db.sql';
	private static $instance= null;
	private $mysqli;

	/**
	 * Class constructor.
	 */
	private function __construct()
	{
		$this->mysqli= null;
		$this->connect();
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
	 *	Establish the database connection once for all and set charset. Set an error if failed.
	 */
	private function connect()
	{
		global $settings;

		$this->mysqli= IS_LOCAL? new mysqli($settings->localDBhost, $settings->localUser, $settings->localPassword)
							   : new mysqli($settings->DBhost, $settings->DBuser, $settings->DBpassword);

		if ($this->mysqli->connect_errno)
		{
			Error::getInstance()->add("Database connection failed: {$this->mysqli->connect_error}.", 'MYSQLI');
			die(self::connectionError);
		}
		else
		{
			$this->mysqli->select_db(IS_LOCAL? $settings->localDBname : $settings->DBname);

			// Unknown database.
			if ($this->mysqli->errno === 1049) $this->createDB(IS_LOCAL? $settings->localDBname : $settings->DBname);
			$this->mysqli->set_charset('utf8');
		}
	}

	/**
	 * Create the minimum required database from a backup file if DB not found.
	 * @param  string $dbName: the database name.
	 * @return void
	 */
	private function createDB($dbName)
	{
		if (is_file(__DIR__.'/../../'.self::minimumDbSqlFile))
		{
			$result= $this->mysqli->query($q= "CREATE DATABASE `$dbName`");
			if (!$result) $this->setError(__FUNCTION__, $q);
			else
			{
				$this->mysqli->select_db($dbName);
				// First set the DB charset to utf8 before importing utf8-encoded file into DB (for accents and special chars).
				$this->mysqli->set_charset('utf8');
				$result= $this->mysqli->multi_query($q= file_get_contents(__DIR__.'/../../'.self::minimumDbSqlFile));
				if (!$result) $this->setError(__FUNCTION__, $q);
				else
				{
					// Wait 1s for the sql query to be finished.
					header('Refresh:1');
					exit;
				}
			}
		}
	}

	/**
	 * Close the database connection.
	 *
	 * @return void
	 */
	public function close()
	{
		$this->mysqli->close();
	}

	/**
	 * Create the Query object.
	 *
	 * @return a Query object.
	 */
	public function query()
	{
		return Query::getInstance($this->mysqli);
	}

	/**
	 * Create a table.
	 *
	 * @param string $table
	 * @param array $fields
	 * @return void
	 *
	 * Example of use:
     * $db= database::getInstance();
     * $db->create('pages', array('page' => array('varchar', 80, true, false, true, null, 'The real page name in site folders'),
     *                            'path' => array('varchar', 255, false, false, true, 'pages/', 'The page path in site folders'),
     *                            'urlEn' => array('varchar', 255, false, false, true, null, 'The URL to access the page when rewrite engine is on'),
     *                            'urlFr' => array('varchar', 255, false, false, true, null, 'The URL to access the page when rewrite engine is on'),
     *                            'titleEn' => array('varchar', 255, false, false, true, null, 'The page title'),
     *                            'titleFr' => array('varchar', 255, false, false, true, null, 'The page title'),
     *                            'metaDescEn' => array('text', 0, false, false, true, null, 'The page meta description En'),
     *                            'metaDescFr' => array('text', 0, false, false, true, null, 'The page meta description Fr'),
     *                            'metaKeyEn' => array('text', 0, false, false, true, null, 'The page meta keywords En'),
     *                            'metaKeyFr' => array('text', 0, false, false, true, null, 'The page meta keywords Fr'),
     *                            'parent' => array('varchar', 0, false, false, true, null, 'The parent real page name in site folders, for the breadcrumbs.')));
	 */
	public function create($table, $fields= array())
	{
		$fieldOutput= '';
		$primary= '';
		$i= 0;
		foreach ($fields as $column => $fieldSettings)
		{
			$settings= $fieldSettings[0]
					  .($fieldSettings[1]? "($fieldSettings[1])" : ($fieldSettings[0]== 'varchar' ? '(255)' : ''))
					  .($fieldSettings[3]? ' AUTO_INCREMENT' : '')
					  .($fieldSettings[4]? ' NOT NULL' : '')
					  .($fieldSettings[5]? " DEFAULT \"$fieldSettings[5]\"" : '')
					  .($fieldSettings[6]? " COMMENT \"$fieldSettings[6]\"" : '');
			if ($fieldSettings[2]) $primary= $column;

			$fieldOutput.= ($i? ',' : '')."\n`$column` $settings";
			$i++;
		}
		if ($primary) $primary= ",\nPRIMARY KEY (`$primary`)";

		$result= $this->mysqli->query($q= "CREATE TABLE `$table` ($fieldOutput$primary)");
		if (!$result) $this->setError(__FUNCTION__, $q);
		// return ;
	}

	/**
	 * Alter a table
	 *
	 * @todo: ALTER TABLE `pages` ADD `article` INT NULL DEFAULT NULL COMMENT 'Article id if any' AFTER `aliases`;
	 * @param string $table
	 * @param array $fields
	 * @return void
	 *
	 * Example of use:
     * $db= database::getInstance();
	 * $db->alter('pages', array('path' => array('varchar', 255, false, false, true, 'pages/', 'The page path in site folders'),
	 *                           'parent' => array('varchar', 0, false, false, true, 'home', 'The parent real page name in site folders, for the breadcrumbs.')));
	 */
	public function alter($table, $fields= array())
	{
		$fieldOutput= '';
		//$primary= '';
		$i= 0;
		foreach ($fields as $column => $fieldSettings)
		{
			// `id` int(11) NOT NULL AUTO_INCREMENT
			// 				(type,  maxlength= 0, primary= false, auto-increment= false, not-null= false, default= null, desc= '')
			// 'id' => array('int', 11,           true,           true,                  true)
			$settings= $fieldSettings[0]
					  .($fieldSettings[1]? "($fieldSettings[1])" : ($fieldSettings[0]== 'varchar' ? '(255)' : ''))
					  .($fieldSettings[3]? ' AUTO_INCREMENT' : '')
					  .($fieldSettings[4]? ' NOT NULL' : '')
					  .($fieldSettings[5]? " DEFAULT \"$fieldSettings[5]\"" : '')
					  .($fieldSettings[6]? " COMMENT \"$fieldSettings[6]\"" : '');
			//if ($fieldSettings[2]) $primary= $column;

			$fieldOutput.= ($i? ',' : '')."\nCHANGE  `$column` `$column` $settings";
			$i++;
		}
		//if ($primary) $primary= ",\nPRIMARY KEY (`$primary`)";

		$result= $this->mysqli->query($q= "ALTER TABLE `$table` $fieldOutput");
		if (!$result) $this->setError(__FUNCTION__, $q);
	}

	/**/
	public function rename($table, $newName)
	{
		$result= $this->mysqli->query($q= "RENAME TABLE  `$table` TO `$newName`");
		if (!$result) $this->setError(__FUNCTION__, $q);
	}

/*
TODO: make functions for:
CREATE DATABASE  `my_site2` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
DROP DATABASE  `my_site` ;*/

	/**
	 * Set an error if the query failed
	 *
	 * @param string $function: the name of the function that triggered the error
	 * @param string $query: the original query
	 * @return void
	 */
	public function setError($function, $query)
	{
		Error::getInstance()->add(ucfirst($function)." function: {$this->mysqli->error}\n  SQL = \"$query\".", 'MYSQLI');
	}

	/**
	 * Private clone method to prevent cloning of the instance of the
	 * *Singleton* instance.
	 *
	 * @return void
	 */
	private function __clone()
	{
	}
}
?>