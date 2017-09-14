<?php

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