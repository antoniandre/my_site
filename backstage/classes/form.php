<?php
/**
 * Form Model.
 *
 * Contains necessary methods to generate a form, display it, validate fields.
 */
Class Form
{
	const existingElements = ['select', 'text', 'checkbox', 'radio', 'textarea', 'hidden', 'email', 'phone', 'wrapper', 'header', 'paragraph'];
	const existingValidations = ['required' => ['pattern' => '~.+~', 'message' => 'This field is required.'],
								 'alpha' => ['pattern' => '~^[a-z]+$~i', 'message' => 'This field must contain alphabetic chars only.'],
								 'alpha+' => ['pattern' => '~^[a-z]+$~i', 'message' => 'This field must contain alphabetic chars only.'],
								 'alphanum' => ['pattern' => '~^[a-z0-9._ -]+$~i', 'message' => 'This field must contain alphanumeric chars only.'],
								 'alphanum+' => ['pattern' => '~^[a-z0-9._ -]+$~i', 'message' => 'This field must contain alphanumeric chars only.'],
								 'num' => ['pattern' => '~^[0-9]+$~', 'message' => 'This field must contain numeric chars only.'],
								 'num+' => ['pattern' => '~^[0-9.,]+$~', 'message' => 'This field must contain numeric chars only.'],
								 'phone' => ['pattern' => '~^[0-9+ ()-]+$~', 'message' => 'This field must contain phone numbers only.'],
								 'mail' => ['pattern' => '~^[a-z0-9]+$~i', 'message' => 'This field must contain alphabetic chars only.']];
	static private $idCounter = 0;
	static private $elementId = 1;
	private $id;
	public $method;// Custom class.
	public $action;// Custom action.
	public $class;// Custom class.
	public $captcha;// Captcha presence.
	private $elements = [];
	private $buttons = [];
	private $validElements = [];

	/**
	 * Class constructor.
	 *
	 * @param array $options: optional settings for the form generation.
	 *                        Possible pairs:
	 *                          method => (string) POST, GET
	 *							action => (string) the script path.
	 *							class => (string) a possible class to apply on the form.
	 */
	public function __construct($options = [])
	{
		$this->id = self::$idCounter+1;
		$this->method = isset($option['method']) ? $option['method'] : 'POST';
		$this->action = isset($option['action']) ? $option['action'] : url('SELF');
		$this->class = isset($option['class']) ? $option['class'] : null;
	}

	/**
	 * Add a form element to the form.
	 *
	 * @see  existingElements const.
	 * @see  existingValidations const.
	 * @param string $type: the type of element you want to add to the form.
	 *        possible values: select, text, checkbox, radio, textarea, hidden, email, phone, and more to come.
	 * @param array $attributes: an indexed array (attr_name=>attr_value) of html attributes to apply to the form element.
	 *        possible values depending on the element type:
	 *            name
	 *            value
	 *            placeholder
	 *            id
	 *            class
	 *            cols
	 *            rows
	 *            maxlength
	 *            disabled
	 *            multiple
	 *            etc.
	 * @param array $options: an indexed array of extra options that may apply to the form element.
	 *        possible pairs:
	 *            default => (string/number)
	 *            label => (string)
	 *            labelPosition => (string) before, after
	 *            validation => (string/array) see the existingValidations constant.
	 *
	 * Usage:
	 * $form->addElement('text',
	 *                   ['name' => 'text[en]', 'value' => ''],
	 *                   ['validation' => 'required', 'label' => 'Text En']);
	 * $form->addElement('text',
	 *                   ['name' => 'text[fr]', 'value' => ''],
	 *                   ['validation' => 'required', 'label' => 'Text Fr', 'labelPosition' => 'after']);
     *
     * Element Object Example:
     * stdClass Object
     * (
     *     [type] => text
     *     [attributes] => stdClass Object
     *         (
     *             [name] => text[fr]
     *             [value] => 
     *             [placeholder] => Text Fr
     *         )
     * 
     *     [options] => stdClass Object
     *         (
     *             [validation] => required
     *         )
     * 
     *     [userdata] => 
     *     [message] => This field is required.
     *     [error] => 1
     * )
     **/
	public function addElement($type, $attributes = [], $options = [])
	{
		if (!is_string($type)) Error::getInstance()->add(__CLASS__.'::'.ucfirst(__FUNCTION__).'(): You must provide the type of the element you want to add in the form.', 'MISSING DATA', true);
		elseif (!in_array($type, self::existingElements)) Error::getInstance()->add(__CLASS__.'::'.ucfirst(__FUNCTION__).'(): You must select an existing element type among: '.implode(', ', self::existingElements).'.', 'WRONG DATA', true);
		elseif (in_array($type, ['wrapper', 'header', 'paragraph']))
		{
			switch ($type)
			{
				case 'wrapper':
					$this->elements[] = (object)['type' => $type, 'numberElements' => $attributes, 'class' => $options];
					break;
				case 'header':
					$this->elements[] = (object)['type' => $type, 'level' => $attributes, 'text' => $options];
					break;
				case 'paragraph':
					$this->elements[] = (object)['type' => $type, 'text' => $attributes];
					break;
				default:
					break;
			}
		}
		elseif (!is_array($attributes) || !count($attributes)) Error::getInstance()->add(__CLASS__.'::'.ucfirst(__FUNCTION__).'(): You must provide an array of attributes containing element name and other html attributes.', 'MISSING DATA', true);
		elseif (!isset($attributes['name'])) Error::getInstance()->add(__CLASS__.'::'.ucfirst(__FUNCTION__)."(): You must provide a name for the $type form element.", 'MISSING DATA');
		else
		{
			switch ($type)
			{
				case 'text':
					break;
				default:
					break;
			}
			 // 'name' => $name,
			 // 'label' => $label,
			 // 'value' => $value,
			 // 'validation' => $validation,
			 // 'attr' => $attr];
			$this->elements[] = (object)['type' => $type, 'attributes' => (object)$attributes, 'options' => (object)$options];
		}
	}

	/**
	 * addButton function.
	 * Add a button to the form.
	 *
	 * @param string $type: The type of the button to add.
	 *                      Possible buttons: 'validate', 'cancel'
	 * @param string $label: The label of the button.
	 * @return void
	 */
	public function addButton($type, $label)
	{
		if (!$label) return Error::getInstance()->add(__CLASS__.'::'.ucfirst(__FUNCTION__).'(): You must provide a label for the button you want to add to the form.', 'MISSING DATA', true);
		switch ($type)
		{
		 	case 'validate':
		 		$this->buttons[] = (object)['type' => 'submit', 'class' => $type, 'label' => $label, 'name' => 'submit'];
		 		break;
		 	case 'cancel':
		 		$this->buttons[] = (object)['type' => 'reset', 'class' => $type, 'label' => $label];
		 		break;
		 	// We do not want an unknown button to be appended.
		 	default:
				Error::getInstance()->add(__CLASS__.'::'.ucfirst(__FUNCTION__).'(): The type of the button you want to add to the form is unknown.', 'WRONG DATA', true);
		 		break;
		 }
	}

	/**
	 * render function.
	 * Render the generated form and return the full HTML.
	 *
	 * @return (string) generated form HTML.
	 */
	public function render()
	{
		$tpl = new Template(__DIR__.'/../templates/');
		$tpl->set_file(['form-tpl' => 'form.html',
						'form-element-tpl' => 'form-elements.html']);
		$tpl->set_block('form-tpl', 'rowBlock', 'theRowBlock');
		$tpl->set_block('rowBlock', 'elementBlock', 'theElementBlock');
		foreach (self::existingElements as $existingElement) if ($existingElement !== 'wrapper')
		{
			$tpl->set_block('form-element-tpl', $existingElement.'Block', 'the'.ucfirst($existingElement).'Block');
		}
		$tpl->set_block('form-element-tpl', 'labelBlock', 'theLabelBlock');
		$tpl->set_block('form-tpl', 'buttonBlock', 'theButtonBlock');

		$tpl->set_var(['formId' => "form$this->id",
					   'method' => $this->method,
					   'action' => $this->action,
					   'class' => $this->class ? " class=\"$this->class\"" : '']);

		$wrapFollowingElmts = 0;
		$wrapperClass = '';
		$rowSpan = 0;
		//========================= ELEMENTS RENDERING =========================//
		foreach ($this->elements as $element)
		{
			$tpl->set_var(['wrapperBegin' => $wrapFollowingElmts && $wrapperClass ? "<div class=\"$wrapperClass\">" : '',
						   'wrapperEnd' => $wrapFollowingElmts && $wrapFollowingElmts == 1 ? '</div>' : '',
						   'rowClass' => isset($element->options->rowClass) ? " {$element->options->rowClass}" : '']);

			// If element is a wrapper, skip the current lap setting the $wrapFollowingElmts var for next loop lap.
			if ($element->type === 'wrapper')
			{
				$wrapFollowingElmts = $element->numberElements;
				$wrapperClass = $element->class;
				continue;
			}
			
			// First lap within a wrapper element.
			if ($wrapFollowingElmts && $wrapperClass) $wrapperClass = '';

			if ($wrapFollowingElmts) $wrapFollowingElmts--;
			// dbg($element->type, $rowSpan);


			$this->renderElement($element, $tpl);
			$this->renderLabel($element, $tpl);


			// $tpl->parse('theElementBlock', 'elementBlock', false);
			// $tpl->parse('theRowBlock', 'rowBlock', true);

			$tpl->parse('theElementBlock', 'elementBlock', $rowSpan);
			$rowSpan = isset($element->options->rowSpan) && $element->options->rowSpan > 1 ? $element->options->rowSpan : $rowSpan;
			if ($rowSpan) $rowSpan--;
			if (!$rowSpan) $tpl->parse('theRowBlock', 'rowBlock', !($rowSpan));
		}
		//======================================================================//

		//========================== BUTTONS RENDERING =========================//
		foreach ($this->buttons as $k => $button)
		{
			$tpl->set_var(['btnText' => $button->label,
						   'btnClass' => $button->class,
						   'btnType' => $button->type,
						   'btnName' => isset($button->name) && $button->name ? " name=\"form{$this->id}[$button->name]\"" : '',
						   'btnValue' => isset($button->value) && $button->value ? " value=\"$button->value\"" : '']);
			$tpl->parse('theButtonBlock', 'buttonBlock', true);
		}
		//======================================================================//

		return $tpl->parse('display', 'form-tpl');
	}

	/**
	 * renderElement function.
	 * Used internally to separate each form element render.
	 *
	 * @see addElement() for a description of the element object.
	 * @param Object $element: the form element object to render.
	 * @param Object $tpl: the current page $tpl var to render the element in.
	 * @return void
	 */
	private function renderElement($element, $tpl)
	{
		$attrHtml = '';
		$name = '';// Html name attribute of the form element.
		$id = '';// Html id attribute of the form element.

		// Element name.
		// Convert subdata names from 'name[subname][subsubname]' to '[name][subname][subsubname]', or 'name' to '[name]'.
		if (isset($element->attributes->name))
		{
			$name = $this->convertName2subname($element->attributes->name);
			$id = text($name, ['formats' => ['sef']]).((int)self::$elementId++);
		}

		// Element HTML attributes.
		// Exclude some specific attribute to individually treat them later.
		if (isset($element->attributes))
		{
			foreach ($element->attributes as $attrName => $attrValue) if (!in_array($attrName, ['type', 'name', 'id', 'value']))
			{
				$attrHtml .= " $attrName=\"$attrValue\"";
			}
		}

		$tpl->set_var(['name' => $name,
				       'id' => $id,
				       'class' => isset($element->attributes->class) ? " {$element->attributes->class}" : '',
				       'validation' => isset($element->options->validation) ? implode(' ', (array)$element->options->validation) : null,
				       'state' => isset($element->error) && $element->error ? ' invalid' : (isset($element->userdata) ? ' valid' : ''),// Validation state.
				       'attr' => $attrHtml,
				       'value' => isset($element->userdata) ? stripslashes($element->userdata)
				       			  : (isset($element->attributes->value) ? $element->attributes->value : '')]);

		switch ($element->type)
		{
		 	case 'paragraph':
			 	$tpl->set_var(['text' => $element->text]);
		 		break;
		 	case 'header':
		 		$tpl->set_var(['level' => (int)$element->level, 'text' => $element->text]);
		 		break;
		 	case 'select':
		 		$tpl->set_block('selectBlock', 'optionBlock', 'theOptionBlock');
		 		foreach ($element->options->options as $value => $label)
		 		{
					$tpl->set_var(['value' => $value,
								   'label' => $label,
								   'selected' => isset($element->userdata) && $element->userdata == $value ? 'selected="selected"' : '']);
		 			$tpl->parse('theOptionBlock', 'optionBlock', true);
		 		}
		 		break;
		 	default:
		 		break;
		}

		// Reinject form element html into the form template.
		$tpl->set_var(['element' => $tpl->parse('the'.ucfirst($element->type).'Block', $element->type.'Block', false)]);
	}

	/**
	 * renderLabel function.
	 * Used internally to separate each form element label render.
	 *
	 * @param Object $element: the form element object (containing the label text) to add the label to.
	 * @param Object $tpl: the current page $tpl var to render the element label in.
	 * @return void
	 */
	private function renderLabel($element, $tpl)
	{
		if (isset($element->options->label) && $element->options->label)
		{
			$labelAfter = isset($element->options->labelPosition) && $element->options->labelPosition === 'after';
			$tpl->set_var(['label' => $element->options->label]);
			$labelHtml = $tpl->parse('theLabelBlock', 'labelBlock', false);
			$tpl->set_var(['labelBefore' => $labelAfter ? '' : $labelHtml,
						   'labelAfter' => $labelAfter ? $labelHtml : '']);
		}
		else $tpl->set_var(['labelBefore' => '', 'labelAfter' => '']);
	}

	/**
	 * checkPostedData check if there is any post for the current form, if so call validate function
	 * otherwise do nothing.
	 *
	 * @return array: the form posts object.
	 */
	public function checkPostedData()
	{
		// If no posted data do not go further.
		return ($posts = $this->getPostedData()) ? $this->validate($posts) : false;
	}

	/**
	 * getPostedData check if there is any post for the current form and return the posts object or null if unset.
	 *
	 * @return array: the form posts object.
	 */
	public function getPostedData()
	{
		$posts = Userdata::$post;
		return $posts && isset($posts->{"form$this->id"}) ? $posts->{"form$this->id"} : null;
	}

	/**
	 * validate function: check each user post and save it into the appropriate form element object.
	 * Used to - and convenient to - check ALL the fields!
	 *
	 * @return StdClass object: 
	 */
	public function validate()
	{
		// Init few vars to keep track of the validation result.
		$countFillableElements = 0;
		$countFilledElements = 0;
		$countInvalidElements = 0;

		// Loop through all the element that have a set name (html attr)
		foreach ($this->elements as $k => $element)
		{
			if (isset($element->attributes->name))
			{
				$countFillableElements++;

				// Divide name like form1[text][en] into ['form1','text','en'] to check if user post is set.
				$elementNameParts = explode('[', str_replace(']', '', $element->attributes->name));

				// Now look into the posts to find user data: construct the path in posts from the provided name.
				$userDataFromPath = $this->getPostedData();
				foreach($elementNameParts as $key)
				{
					if (isset($userDataFromPath->$key)) $userDataFromPath = $userDataFromPath->$key;
					else
					{
						$userDataFromPath = null;
						break;// if the path we look into in posts does not exist break the current loop.
					}
				}

				// The path is found in posted data, now validate the field.
				if (isset($userDataFromPath))
				{
					// Save the posted user data into the form element object.
					$this->elements[$k]->userdata = $userDataFromPath;
					$countFilledElements++;

					// If element has a validation set then call the checkElementValidations() method.
					// The element is valid if no validation is set.
					$isValid = isset($this->elements[$k]->options->validation) ? $this->checkElementValidations($this->elements[$k]) : true;
					$this->elements[$k]->error = !$isValid;
					
					if (!$isValid) $countInvalidElements++;

					// Save only valid elements in a private array for easier later access!
					else $this->validElements[] = $element->attributes->name;
				}
			}
		}

		// Call a potential validate function if it is set in calling file.
		if (function_exists('validate')) validate($this);

		return (object)['fillable' => $countFillableElements, 'filled' => $countFilledElements, 'invalid' => $countInvalidElements];
	}

	/**
	 * INTERNAL checkElementValidations function called by checkPostedData() to check each validation set for a given element.
	 *
	 * @param  Object $element: the Object of the element to check. See addElement() to know more about the expected object.
	 * @return boolean: true if valid or false otherwise.
	 */
	private function checkElementValidations($element)
	{
		$return = true;
		// Loop through the multiple element validations.
		foreach ((array)$element->options->validation as $validation)
		{
			// Only perform a validation if it is a known one!
			if (array_key_exists($validation, self::existingValidations))
			{
				// Perform a preg_match on user posted data with the pattern set in the definition of the validation.
				// See the existingValidations constant.
				$return = preg_match(self::existingValidations[$validation]['pattern'], (string)$element->userdata);
				if (!$return)
				{
					// If the preg_match fails, save the resulting error message in the element object.
					$element->message = self::existingValidations[$validation]['message'];
					break;// keep only the first message then break the loop.
				}
			}
			/*// May be used if a specific validation is required.
			switch ($validation)
			{
				case 'required':
				case 'alpha':
				case 'alphanum':
				default:
					// This cases are already handled by above preg_match.
					break;
			}*/
			// dbg($element, $return);
		}
		return $return;
	}

	/**
	 * EXTERNAL checkElements function convenient to check the validations result of a given element only (from html name)
	 * or an array of specific elements and return the detailed/summup result.
	 *
	 * @param array/string $elementNames: an array of names of elements to check.
	 * @param boolean $details: whether to return a detailed or summup result.
	 * @return array/boolean: the detailed or summup result depending on $details param:
	 *     - an array of results indexed by element names if $details is set to true
	 *         like [elementName => status, elementName => status, ...]
	 *     - a boolean set to true if all the provided elements are valid and false if at least one is invalid.
	 */
	public function checkElements($elementNames = [], $details = false)
	{
		$return = $details ? [] : 0;

		if (count((array)$elementNames))
		{
			foreach ((array)$elementNames as $name)
			{
				if ($details) $return[$name] = in_array($name, $this->validElements);
				elseif (in_array($name, $this->validElements)) $return++;
			}
			if ($details && count((array)$elementNames) == 1) $return = (boolean)$return[$name];
		}

		return $details ? $return : $return == count((array)$elementNames);
	}

	/**
	 * checkElement shortcut function for checkElements().
	 *
	 * @param string $elementNames: the name of the element to check.
	 * @param boolean $details: whether to return a detailed or summup result.
	 * @return array/boolean: the detailed or summup result depending on $details param:
	 *     - an array of results indexed by element names if $details is set to true
	 *         like [elementName => status, elementName => status, ...]
	 *     - a boolean set to true if all the provided elements are valid and false if at least one is invalid.
	 */
	public function checkElement($elementName, $details = false)
	{
		return checkElements([$elementName], $details);
	}

	/**
	 * convertName2subname converts subdata names from 'name[subname][subsubname]' to '[name][subname][subsubname]', or 'name' to '[name]'.
	 *
	 *  @param  string $name: the name to convert
	 * @return string: the converted name
	 */
	private function convertName2subname($name)
	{
		$nameOut = '';
		foreach (explode('[', str_replace(']', '', $name)) as $bit) $nameOut .= "[$bit]";
		return $nameOut;
	}
}
?>