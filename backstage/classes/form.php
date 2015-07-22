<?php
/**
 * Form Model.
 *
 * Contains necessary methods to generate a form, display it, validate fields.
 */
Class Form
{
	const existingElements = ['select', 'text', 'checkbox', 'radio', 'textarea', 'hidden', 'email', 'phone', 'wrapper', 'header', 'paragraph'];
	static private $idCounter = 0;
	static private $elementId = 1;
	private $id;
	public $method;// Custom class.
	public $action;// Custom action.
	public $class;// Custom class.
	public $captcha;// Captcha presence.
	private $elements = [];
	private $buttons = [];

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
	 *            validation => (string/array) required, alpha, alphanum, num, mail
	 *
	 * Usage:
	 * $form->addElement('text',
	 *                   ['name' => 'text[en]', 'value' => ''],
	 *                   ['validation' => 'required', 'label' => 'Text En']);
	 * $form->addElement('text',
	 *                   ['name' => 'text[fr]', 'value' => ''],
	 *                   ['validation' => 'required', 'label' => 'Text Fr', 'labelPosition' => 'after']);
	 */
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
					# code...
					break;
				default:
					# code...
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
		// @TODO: implement.
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
		 		break;
		 }
	}

	/**
	 * render function.
	 * Render the generated form and return the full HTML.
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
	 * @param Object $element:
	 * @param Object $tpl:
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
			foreach (explode('[', str_replace(']', '', $element->attributes->name)) as $bit) $name .= "[$bit]";
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
				       'validation' => isset($element->options->validation) ? $element->options->validation : null,
				       'state' => '',// Validation state.
				       'attr' => $attrHtml,
				       'value' => isset($element->attributes->value) ? $element->attributes->value : '']);

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
					$tpl->set_var(['value' => $value, 'label' => $label]);
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
	 * @param Object $element:
	 * @param Object $tpl:
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

	/**/
	public function validate()
	{
		// @TODO: implement.
	}

	/**/
	public function checkPostedData()
	{
		$posts = Userdata::$post;
		if ($posts) validate($this);
		return $posts->{"form$this->id"};
	}

	/**/
	public function getPostedData()
	{
		$posts = Userdata::$post;
		return $posts->{"form$this->id"};
	}
}
?>