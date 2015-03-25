<?php
/**
 * DatabaseEntity Model.
 * Abstract class to group common functions.
 * Children classes: Query, Where.
 **/
abstract Class DatabaseEntity
{
	// Array of secured fields for insert/update/select requests,
	// or Temp array to calculate the where string.
	protected $tempPieces;

	/**
	 * Class constructor.
	 */
	protected function __construct()
	{
		$this->tempPieces= [];
	}

	/**
	 * Get the only instance of the calling singleton class.
	 * Make sure the children classes will comply to a singleton pattern.
	 *
	 * @return $this: the only instance of that class.
	 */
	public static function getInstance(){}

	/**
	 * Secure the mysqli CONCAT command with the given arguments.
	 *
	 * @param array func_get_args(): the arguments to concat.
	 * @return $this: The current object instance.
	 */
	protected function concat()
	{
		$this->tempPieces[]= 'CONCAT('.implode(', ', $this->gatherArgs(func_get_args())).')';
		return $this;
	}

	/**
	 * Secure the mysqli COUNT command with the given arguments.
	 *
	 * @param mixed $string: the argument to count.
	 *                       Expects only one param: an instance of children class or a string.
	 * @return $this: The current object instance.
	 */
	public function _count($string)
	{
		$this->tempPieces[]= 'COUNT('.($string== '*'? '*' : $this->gatherArgs(func_get_args())[0]).')';
		return $this;
	}

	/**
	 * Secure a mysqli column name given in param.
	 *
	 * @param string $column: the column name to secure.
	 * @return $this: The current object instance.
	 */
	public function col($column)
	{
		$this->tempPieces[]= "`$column`";
		return $this;
	}

	/**
	 * Secure the mysqli LOWER command with the given arguments.
	 *
	 * @param mixed $string: the argument to apply lowercase on.
	 *                       Expects only one param: an instance of children class or a string.
	 * @return $this: The current object instance.
	 */
	protected function lower($string)
	{
		$this->tempPieces[]= 'LOWER('.implode('', $this->gatherArgs(func_get_arg(0))).')';
		return $this;
	}

	/**
	 * Secure the mysqli CONCAT command with the given arguments.
	 *
	 * @param mixed $string: the argument to apply uppercase on.
	 *                       Expects only one param: an instance of children class or a string.
	 * @return $this: The current object instance.
	 */
	protected function upper($string)
	{
		$this->tempPieces[]= 'UPPER('.implode('', $this->gatherArgs(func_get_arg(0))).')';
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
		$field= preg_replace('~^\((.*)\)$~', '$1', $field);// Remove extra parenthesis if any.

		// Handles simple alphanumeric, backquoted or not
		if (preg_match('~^`?(\w+)`?$~i', $field, $matches)) $field= "`$matches[1]`";
		else return $this->abort(ucfirst(__FUNCTION__)."(): The field syntax \"$field\" is not recognized.");
		return $field;
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
		$return= array();
		$treatedArgs= 0;
		$i= 0;
		$currIndex= count($this->tempPieces);

		// First count arguments that are already stored in the $this->tempPieces array.
		foreach ($args as $arg) if (is_object($arg) && get_class($arg) === get_called_class()) $treatedArgs++;

		// Foreach argument to concat, if the arg is a Where instance, it means it has been treated previously
		// So retrieve it in the $this->tempPieces array unset it at its original currIndex in the array.
		foreach ($args as $numArg => $arg)
		{
			if (is_object($arg) && get_class($arg) === get_called_class())
			{
				$return[]= $this->tempPieces[$currIndex-$treatedArgs+$i];
				unset($this->tempPieces[$currIndex-$treatedArgs+$i]);
				$i++;
			}
			// If the argument is not an instance of Where then it is treated as a simple string.
			else $return[]= "'$arg'";
		}
		$this->tempPieces= array_values($this->tempPieces);// Reindex the array after unsetting some keys.

		return $return;
	}
}
?>