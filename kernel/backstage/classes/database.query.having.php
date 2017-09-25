<?php
/**
 * Design pattern: singleton.
 * The Having class works the exact same way as the Where class and benefits from the same methods.
 *
 * @example 1
 * $having = $db->having("column_name1 ='value1'");
 * $having->and("column_name2='value2'")
 *        ->or("column_name3 LIKE 'value3'", $having->or("column_name4<=4"), $having->or("column_name5<=5"), $having->or("column_name6<=6"))
 *        ->and("column_name7='value7'", $having->or("column_name8<=8", $having->and("column_name9='value9'", $having->and("column_name10 IN (1,2,3,4,5)"))));
 *
 * Will produce:
 * column_name1 ='value1'
 * AND column_name2='value2'
 * OR (column_name3 LIKE 'value3' OR column_name4<=4 OR column_name5<=5 OR column_name6<=6)
 * AND (column_name7='value7' OR (column_name8<=8 AND column_name9='value9' AND column_name10 IN (1,2,3,4,5))))
 *
 * @example 2
 * $q->select('article_tags', $q->count('tag'));
 * $h = $q->having()->col('tag')->eq(2)->and()->col('article')->eq(1);
 * $count = $q->run()->loadResult();
 **/
class Having extends Where
{
	protected static $instance = null;
	// protected $string;// The having generated string.


	/**
	 * Get the only instance of this class.
	 *
	 * @param string $condition: the having condition or just a part of it.
	 * @return the only instance of this class.
	 */
	public static function getInstance($condition = null)
	{
		// If having is initiated with 1 E.g. $q->having(1); then add it to the array of tempPieces.
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
	 * @return the current having instance.
	 */
    function __call($method, $args)
    {
    	$return = $this;

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

        if (!method_exists($this, $method)) Query::getInstance()->abort('Mysqli '.__CLASS__.": method \"$method()\" does not exist.");
        else $return = call_user_func_array(array(self::$instance, $method), $args);

        return $return;
    }

}
?>