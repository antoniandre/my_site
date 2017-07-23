<?php
/**
 * Design pattern: singleton
 *
 * @example
 * $where= $db->where("column_name1 ='value1'");
 * $where->and("column_name2='value2'")
 *       ->or("column_name3 LIKE 'value3'", $where->or("column_name4<=4"), $where->or("column_name5<=5"), $where->or("column_name6<=6"))
 *       ->and("column_name7='value7'", $where->or("column_name8<=8", $where->and("column_name9='value9'", $where->and("column_name10 IN (1,2,3,4,5)"))));
 *
 * @example
 * $q->select('article_tags', $q->count('tag'));
 * $w = $q->where()->col('tag')->eq(2)->and()->col('article')->eq(1);
 * $count = $q->run()->loadResult();
 *
 * Will produce:
 * column_name1 ='value1'
 * AND column_name2='value2'
 * OR (column_name3 LIKE 'value3' OR column_name4<=4 OR column_name5<=5 OR column_name6<=6)
 * AND (column_name7='value7' OR (column_name8<=8 AND column_name9='value9' AND column_name10 IN (1,2,3,4,5))))
 **/
Class Where extends DatabaseEntity
{
	private static $instance = null;
	private $where;

	/**
	 * Class constructor.
	 */
	protected function __construct($args = [])
	{
		parent::__construct();
		$this->tempPieces = [];
		if (count($args))
		{
			$this->tempPieces = $this->gatherArgs($args);
			$this->where = implode(' ', $this->tempPieces);
		}
	}

	/**
	 * Get the only instance of this class.
	 *
	 * @param string $condition: the where condition or just a part of it.
	 * @return the only instance of this class.
	 */
	public static function getInstance($condition = null)
	{
		// If where is initiated with 1 E.g. $q->where(1); then add it to the array of tempPieces.
		if ($condition === [1] && isset(self::$instance)) self::$instance->tempPieces[] = 1;
		if (!isset(self::$instance)) self::$instance = new self($condition);
		return self::$instance;
	}

	/**
	 * Intercept any call to a method to redispatch to the proper method.
	 * This is used to allow a call to a reserved-keyword-method like or() and and()
	 * whereas you can't define this method.
	 *
	 * @param string $method: the name of the method that was initially called.
	 * @param array $args: the parameters to provide to the method that was initially called.
	 * @return the current Where instance.
	 */
    function __call($method, $args)
    {
    	$return= null;

        switch ($method)
        {
        	case 'or':
        	case 'and':
        		$method = "_$method";
	        	break;
        	case 'run':
        		return Query::getInstance()->run();
	        	break;
        	default:
	        	break;
        }
		if (!method_exists($this, $method)) Cerror::getInstance()->add('Mysqli '.__CLASS__.'::'.ucfirst(__FUNCTION__).'(): method "'.__CLASS__."::$method()\" does not exist.");
        else return call_user_func_array(array(self::$instance, $method), $args);
    }

	/**
	 * Get where.
	 *
	 * @return string the processed where.
	 */
	public function get()
	{
		return $this->where;
	}

	/**
	 * Supposed to be named and() but can't redefine a reserved keyword.
	 * Secure the mysqli AND command with the given arguments.
	 *
	 * @param mixed func_get_args(): array or string.
	 * @return Where: the current instance.
	 *
	 * Usage:
     * $q= $db->query();
     * $q->select('pages', [$q->col('page'), $q->concat($q->col('url_en'), ' && ', $q->col('url_fr'))->as('concat')]);
     * $w= $q->where();
     * $w->and($w->col('page')->eq("sitemap"), $w->col('url_fr')->eq("accueil"));
     *
     * 3 possible syntaxes for where clause with conditions noted '_COND_':
	 * 1.    $w->and(_COND_, _COND_, _COND_);
     * 2.    $w->_COND_->and(_COND_)->and(_COND_);
     * 3.    $w->and()->_COND_->and()->_COND_->and()->_COND_;
     *
	 */
	public function _and()
	{
		$args = $this->gatherArgs(func_get_args());
		$this->tempPieces[] = (count($args) > 1) ? (' ('.implode(" AND ", $args).')') : (' AND '.(isset($args[0]) ? $args[0] : ''));
		$this->where = implode("\n", $this->tempPieces);
		return $this;
	}

	/**
	 * Supposed to be named or() but can't redefine a reserved keyword.
	 * Secure the mysqli OR command with the given arguments.
	 *
	 * @param mixed func_get_args(): array or string.
	 * @return Where: the current instance.
	 *
	 * Usage:
     * $q= $db->query();
     * $q->select('pages', [$q->col('page'), $q->concat($q->col('url_en'), ' && ', $q->col('url_fr'))->as('concat')]);
     * $w= $q->where();
     * $w->or($w->col('page')->eq("sitemap"), $w->col('page')->eq("home"));
     *
     * 3 possible syntaxes for where clause with conditions noted '_COND_':
	 * 1.    $w->or(_COND_, _COND_, _COND_);
     * 2.    $w->_COND_->or(_COND_)->or(_COND_);
     * 3.    $w->or()->_COND_->or()->_COND_->or()->_COND_;
     *
	 */
	public function _or()
	{
		$args = $this->gatherArgs(func_get_args());
		$this->tempPieces[] = (count($args) > 1) ? (' ('.implode(" OR ", $args).')') : (' OR '.(isset($args[0]) ? $args[0] : ''));
		$this->where = implode("\n", $this->tempPieces);
		return $this;
	}

	/**
	 * Secure the mysqli IN command with the given arguments.
	 *
	 * @param array func_get_args(): array of strings.
	 * @return Where: the current instance.
	 *
	 * Usage:
     * $q= $db->query()->select('pages', '*');
     * $q->where()->col('page')->in("sitemap", "home");
	 */
	public function in()
	{
		$currIndex = count($this->tempPieces)-1;
		$this->tempPieces[$currIndex] .= ' IN ('.implode(', ', $this->gatherArgs(func_get_args())).')';
		$this->where = implode("\nAND ", $this->tempPieces);
		return $this;
	}

	/**/
	public function eq($rightHandArg)
	{
		$currIndex = count($this->tempPieces)-1;

		if ($currIndex < 0) return $this->abort(ucfirst(__FUNCTION__).'(): You are trying to compare nothing on the left hand.');

		$this->tempPieces[$currIndex] .= ' = '.$this->gatherArgs(func_get_args())[0];
		$this->where = implode("\n", $this->tempPieces);
		return $this;
	}

	/**/
	public function lt($rightHandArg)
	{
		$currIndex = count($this->tempPieces)-1;
		if ($currIndex< 0) return $this->abort(ucfirst(__FUNCTION__).'(): You are trying to compare nothing on the left hand.');
		else $this->tempPieces[$currIndex] .= ' < '.$this->gatherArgs(func_get_args())[0];
		$this->where = implode("\n", $this->tempPieces);
		return $this;
	}

	/**/
	public function lte($rightHandArg)
	{
		$currIndex = count($this->tempPieces)-1;
		if ($currIndex< 0) return $this->abort(ucfirst(__FUNCTION__).'(): You are trying to compare nothing on the left hand.');
		else $this->tempPieces[$currIndex] .= ' <= '.$this->gatherArgs(func_get_args())[0];
		$this->where = implode("\n", $this->tempPieces);
		return $this;
	}

	/**/
	public function gt($rightHandArg)
	{
		$currIndex = count($this->tempPieces)-1;
		if ($currIndex< 0) return $this->abort(ucfirst(__FUNCTION__).'(): You are trying to compare nothing on the left hand.');
		else $this->tempPieces[$currIndex] .= ' > '.$this->gatherArgs(func_get_args())[0];
		$this->where = implode("\n", $this->tempPieces);
		return $this;
	}

	/**
	 * Greater Than or Equal.
	 * @param  [type] $rightHandArg [description]
	 * @return [type]               [description]
	 */
	public function gte($rightHandArg)
	{
		$currIndex = count($this->tempPieces)-1;
		if ($currIndex< 0) return $this->abort(ucfirst(__FUNCTION__).'(): You are trying to compare nothing on the left hand.');
		else $this->tempPieces[$currIndex] .= ' >= '.$this->gatherArgs(func_get_args())[0];
		$this->where = implode("\n", $this->tempPieces);
		return $this;
	}

	/**
	 * ne = Not Equal.
	 * @param  [type] $rightHandArg [description]
	 * @return [type]               [description]
	 */
	public function ne($rightHandArg)
	{
		$currIndex = count($this->tempPieces)-1;
		if ($currIndex < 0) return $this->abort(ucfirst(__FUNCTION__).'(): You are trying to compare nothing on the left hand.');
		else $this->tempPieces[$currIndex] .= ' <> '.$this->gatherArgs(func_get_args())[0];
		$this->where = implode("\n", $this->tempPieces);
		return $this;
	}
	// Different of sth. Alias for ne() method.
	public function dif($rightHandArg)
	{
		return $this->ne($rightHandArg);
	}

	/**
	 * between a date range.
	 * @param  [type] $start [description]
	 * @param  [type] $end   [description]
	 * @return [type]        [description]
	 */
	public function between($start, $end)
	{
		$start = !$start ? '0000-00-00' : $start;
		$end = !$end || 'NOW()' ? 'NOW()' : "'$end'";
		$currIndex = count($this->tempPieces)-1;
		if ($currIndex < 0) return $this->abort(ucfirst(__FUNCTION__).'(): You are trying to get a date range on no column.');
		else $this->tempPieces[$currIndex] .= " BETWEEN '$start' AND $end";
		$this->where = implode("\n", $this->tempPieces);
		return $this;
	}

	/**
	 * Secure the mysqli CONCAT command with the given arguments.
	 *
	 * @param array func_get_args(): the arguments to concat.
	 * @return Where: The current where instance.
	 */
	public function concat()
	{
		// PHP5.6+
		// parent::{__FUNCTION__}(...func_get_args());
		// PHP5.5-
		call_user_func_array(['parent', __FUNCTION__], func_get_args());
		$this->where = implode("\nAND ", $this->tempPieces);
		return $this;
	}

	/**
	 * Prepares a mysqli COUNT command and stores it in the $this->tempPieces.
	 *
	 * @param  string $string: what you want to count.
	 * @return Where: The current where instance.
	 */
	public function _count($string)
	{
		parent::{__FUNCTION__}(func_get_arg(0));
		$this->where = implode("\nAND ", $this->tempPieces);
		return $this;
	}

	/**
	 * Tells the query this word is not a string but a column name.
	 *
	 * @param string $column: the column name.
	 * @return Where: The current where instance.
	 */
	public function col($column)
	{
		parent::{__FUNCTION__}(func_get_arg(0));
		$this->where = implode("\n", $this->tempPieces);
		return $this;
	}

	/**
	 * Tells the query this word is not a string but a column name prefixed by a table name.
	 * E.g. 'articles.id'
	 *
	 * @param string $column: the column name.
	 * @param string $table: the table name.
	 * @return The current Query instance.
	 */
	public function colIn($column, $table)
	{
		// PHP5.6+
		// parent::{__FUNCTION__}(...func_get_args());
		// PHP5.5-
		return call_user_func_array(['parent', __FUNCTION__], func_get_args());
	}

	/**/
	protected function lower($string)
	{
		parent::{__FUNCTION__}(func_get_arg(0));
		$this->where = implode("\nAND ", $this->tempPieces);
		return $this;
	}

	/**/
	protected function upper($string)
	{
		parent::{__FUNCTION__}(func_get_arg(0));
		$this->where = implode("\nAND ", $this->tempPieces);
		return $this;
	}

	/**
	 * Check the fields we want to select.
	 *
	 * @param  string $field.
	 * @return string: secured field string.
	 */
	protected function checkField($field)
	{
		return parent::{__FUNCTION__}(func_get_arg(0));
	}

	/**
	 * Gather function args: if args are instanceOf Query, they are treated previously and
	 * appended to the $this->fields attribute. So This function get the treated args from this array
	 * and return all the params in the right order.
	 * If a param is non-database-entity-object (Query or Where) it is treated as a simple string.
	 *
	 * Ex:
	 * $q->select('pages', [
     *                         $q->concat($q->col('id'), $q->col('page'), ': ', $q->col('url_en'))->as('c'),
     *                         'value',
     *                         $q->count('*')->as('cd')
     *                     ]);
     *
     * @param  array $args: the arguments raw array to treat.
     * @return array: All the args in the right order.
     */
	protected function gatherArgs($args)
	{
		return parent::gatherArgs($args);
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

	/**
	 * "Destroy" (actually only reset) the current where.
	 * @return void.
	 */
	public function kill()
	{
		$this->where = '';
		$this->tempPieces = [];
	}
}
?>