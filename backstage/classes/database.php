<?php
/**
 * Database Model.
 * Design pattern: singleton.
 *
 * @todo: create functions for:
 *        CREATE DATABASE  `my_site2` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
 *        DROP DATABASE  `my_site` ;
 * @dependencies: Settings.
 **/
include __DIR__.'/../classes/database.query.php';

Class Database
{
	const connectionError = '<p>Un probleme de connection &agrave; la base de donn&eacute;es est survenu.<br />Ce probl&egrave;me temporaire sera r&eacute;solu d&egrave;s que possible aupr&egrave;s de notre h&eacute;bergeur.<br />Merci de votre compr&eacute;hension.</p><hr />'
						   .'<p>A database connection error occured.<br />We are already working on solving this problem with our host.<br />Thank you for your understanding.</p>';
	const minimumDbSqlFile = 'backstage/install/minimum-db.sql';
	private static $instance = null;
	private $mysqli;

	/**
	 * Class constructor.
	 */
	private function __construct()
	{
		$this->mysqli = null;
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
	 * Establish the database connection once for all and set charset. Set an error if failed.
	 *
	 * @return void.
	 */
	private function connect()
	{
		$settings = Settings::get();

		$this->mysqli = IS_LOCAL ? new mysqli($settings->localDBhost, $settings->localUser, $settings->localPassword)
							     : new mysqli($settings->DBhost, $settings->DBuser, $settings->DBpassword);

		if ($this->mysqli->connect_errno)
		{
			Error::getInstance()->add("Database connection failed: {$this->mysqli->connect_error}.", 'MYSQLI');
			die(self::connectionError);
		}
		else
		{
			$dbName = IS_LOCAL ? $settings->localDBname : $settings->DBname;
			$this->mysqli->select_db($dbName);

			// Unknown database.
			if ($this->mysqli->errno === 1049) $this->createDB($dbName);

			// Database is empty.
			elseif (!$this->mysqli->query("SHOW TABLES IN `$dbName`")->num_rows) $this->populateDB($dbName);

			// In all cases set encoding.
			$this->mysqli->set_charset('utf8');
		}
	}

	/**
	 * Create the database then call the private method populateDB() to fill it up.
	 *
	 * @param  string $dbName: the database name.
	 * @return void.
	 */
	private function createDB($dbName)
	{
		if (is_file(__DIR__.'/../../'.self::minimumDbSqlFile))
		{
			$result = $this->mysqli->query($q = "CREATE DATABASE `$dbName`");
			if (!$result) $this->setError(__FUNCTION__, $q);
			else $this->populateDB($dbName);
		}
	}

	/**
	 * Create the minimum required database data from a backup file if DB is empty.
	 *
	 * @param string $dbName: the database name.
	 * @return void.
	 */
	private function populateDB($dbName)
	{
		if (is_file(__DIR__.'/../../'.self::minimumDbSqlFile))
		{
			$this->mysqli->select_db($dbName);
			// First set the DB charset to utf8 before importing utf8-encoded file into DB (for accents and special chars).
			$this->mysqli->set_charset('utf8');
			$result = $this->mysqli->multi_query($q = file_get_contents(__DIR__.'/../../'.self::minimumDbSqlFile));
			if (!$result) $this->setError(__FUNCTION__, $q);
			else
			{
				// Wait 1s for the sql query to be finished.
				header('Refresh:1');
				exit;
			}
		}
	}

	/**
	 * Close the database connection.
	 *
	 * @return void.
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
	 * @param string $table: the table name to create.
	 * @param array $columns: indexed array of columns to add in table (pairs of column_name => column_attributes).
	 *                       Expected column attributes:
	 *                       data type, max length, primary, auto increment, not null, default value, column description
	 * @return Boolean: true if success false otherwise.
	 *
	 * Example of use:
     * $db = database::getInstance();
     * $db->create('pages', ['page' => ['varchar', 80, true, false, true, null, 'The real page name in site folders'],
     *                       'path' => ['varchar', 255, false, false, true, 'pages/', 'The page path in site folders'],
     *                       'urlEn' => ['varchar', 255, false, false, true, null, 'The URL to access the page when rewrite engine is on'],
     *                       'urlFr' => ['varchar', 255, false, false, true, null, 'The URL to access the page when rewrite engine is on'],
     *                       'titleEn' => ['varchar', 255, false, false, true, null, 'The page title'],
     *                       'titleFr' => ['varchar', 255, false, false, true, null, 'The page title'],
     *                       'metaDescEn' => ['text', 0, false, false, true, null, 'The page meta description En'],
     *                       'metaDescFr' => ['text', 0, false, false, true, null, 'The page meta description Fr'],
     *                       'metaKeyEn' => ['text', 0, false, false, true, null, 'The page meta keywords En'],
     *                       'metaKeyFr' => ['text', 0, false, false, true, null, 'The page meta keywords Fr'],
     *                       'parent' => array('varchar', 0, false, false, true, null, 'The parent real page name in site folders, for the breadcrumbs.']);
	 */
	public function create($table, $columns = [])
	{
		$columnsString = $this->getSettingsString($columns);

		$result = $this->mysqli->query($q = "CREATE TABLE `$table` ($columnsString)");
		if (!$result) return $this->setError(__FUNCTION__, $q);

		return true;
	}

	/**
	 * Alter a table.
	 *
	 * @todo: ALTER TABLE `pages` ADD `article` INT NULL DEFAULT NULL COMMENT 'Article id if any' AFTER `aliases`;
	 * @todo: ALTER TABLE `picture_likes` ADD `article` SMALLINT UNSIGNED NOT NULL ;
	 * @todo: ALTER TABLE `pages` DROP COLUMN `article`;
	 * @todo: ALTER TABLE `newsletter_subscribers` ADD INDEX( `email`);
	 * @todo: ALTER TABLE `texts` auto_increment = 45;
	 *
	 * @param string $table: the table name to alter.
	 * @param array $columns: indexed array of columns (columns) to alter.
	 * @return Boolean: true if success false otherwise.
	 *
	 * Example of use:
     * $db = database::getInstance();
	 * $db->alter('pages', ['path' => ['varchar', 255, false, false, true, 'pages/', 'The page path in site folders'],
	 *                      'parent' => ['varchar', 0, false, false, true, 'home', 'The parent real page name in site folders, for the breadcrumbs.']]);
	 */
	public function alter($table, $columns = [])
	{
		$columnsString = $this->getSettingsString($columnSettings);

		$result = $this->mysqli->query($q = "ALTER TABLE `$table` $columnsString");
		if (!$result) return $this->setError(__FUNCTION__, $q);

		return true;
	}

	/**
	 * Get the settings.
	 *
	 * @see create(), alter().
	 * @param array $columns: indexed array of columns (columns) to add in table or alter
	 *                       (pairs of column_name => column_attributes).
	 * @param boolean $alter: Add the keyword 'CHANGE' if the string is for an alter statement.
	 * @return string: the generated string for the create or alter table statement.
	 */
	private function getSettingsString($columns, $alter = false)
	{
		$fieldOutput = '';
		$primary = '';
		$i = 0;
		foreach ($columns as $column => $columnSettings)
		{
			if ($columnSettings[2])
			{
				// Make sure there is only one Primary key.
				if ($primary)
				{
					Error::getInstance()->add(ucfirst(__FUNCTION__)." function: You cannot create a table with multiple primary keys (\"$primary\", \"{$columnSettings[2]}\").", 'MYSQLI');
					return;
				}
				$primary = $column;
			}

			// `id` int(11) NOT NULL AUTO_INCREMENT
			// 				(type,  maxlength= 0, primary= false, auto-increment= false, not-null= false, default= null, desc= '')
			// 'id' => array('int', 11,           true,           true,                  true)
			$settings = $columnSettings[0]
						.($columnSettings[1] ? "($columnSettings[1])" : ($columnSettings[0]== 'varchar' ? '(255)' : ''))
						.($columnSettings[3] ? ' AUTO_INCREMENT' : '')
						.($columnSettings[4] ? ' NOT NULL' : '')
						.($columnSettings[2] ? ' PRIMARY KEY' : '')
						.($columnSettings[5] ? " DEFAULT \"$columnSettings[5]\"" : '')
						.($columnSettings[6] ? " COMMENT \"$columnSettings[6]\"" : '');

			$columnOutput .= ($i ? ',' : '')."\n".($alter ? 'CHANGE ' : '')."`$column` $settings";
			$i++;
		}

		return $columnOutput;
	}

	/**
	 * Rename a table.
	 *
	 * @param string $table: the original table name.
	 * @param string $newName: the new table name.
	 * @return void.
	 */
	public function rename($table, $newName)
	{
		$result = $this->mysqli->query($q = "RENAME TABLE  `$table` TO `$newName`");
		if (!$result) $this->setError(__FUNCTION__, $q);
	}

	/**
	 * Set an error if the query failed
	 *
	 * @param string $function: the name of the function that triggered the error
	 * @param string $query: the original query
	 * @return false
	 */
	public function setError($function, $query)
	{
		Error::getInstance()->add(ucfirst($function)." function: {$this->mysqli->error}\n  SQL = \"$query\".", 'MYSQLI');

		return false;
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
?>