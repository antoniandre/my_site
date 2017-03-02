<?php
/**
 * Form Model.
 *
 * Contains necessary methods to generate a form, display it, validate fields.
 *
 * Simple example of use:
 *    $newsletter = new Form(['class' => 'newsletter']);
 *    $newsletter->addElement('email',
 *    		                ['name' => 'newsletter', 'value' => '', 'placeholder' => text(80)],// Your email address.
 *    		                ['validation' => 'required', 'label' => text(81)]);// Subscribe to the newsletter.
 *    $newsletter->addButton('validate', text(85));// Button label: OK.
 *    $newsletter->validate
 *    (
 *    	function($result, $form){...}
 *    );
 *    echo $newsletter->render();
 */
Class Form
{
	const existingElements = ['select', 'text', 'checkbox', 'radio', 'textarea', 'wysiwyg', 'hidden', 'email', 'phone', 'wrapper', 'header', 'paragraph', 'upload'];
	const existingValidations = ['required' => ['pattern' => '~.+~', 'message' => 'This field is required.'],
								 'requiredIf' => ['pattern' => '~.+~', 'message' => 'This field is required if the previous option "%s" is chosen.'],
								 'alpha' => ['pattern' => '~^[a-z]+$~i', 'message' => 'This field must contain alphabetic chars only.'],
								 'alpha+' => ['pattern' => '~^[a-z]+$~i', 'message' => 'This field must contain alphabetic chars only.'],
								 'alphanum' => ['pattern' => '~^[a-z0-9._ -]+$~i', 'message' => 'This field must contain alphanumeric chars only.'],
								 'alphanum+' => ['pattern' => '~^[a-z0-9._ -]+$~i', 'message' => 'This field must contain alphanumeric chars only.'],
								 'num' => ['pattern' => '~^[0-9]+$~', 'message' => 'This field must contain numeric chars only.'],
								 'num+' => ['pattern' => '~^[0-9.,]+$~', 'message' => 'This field must contain numeric chars only.'],
								 'phone' => ['pattern' => '~^[0-9+ ()-]+$~', 'message' => 'This field must contain phone numbers only.'],
								 'email' => ['pattern' => '~^[a-z0-9_][a-z0-9._]+@[a-z0-9][a-z0-9._]{1,40}[a-z0-9]$~i', 'message' => 'This field only accept valid emails.']];
	const uploadsDir = '../uploads/';
	const uploadsDirTemp = '../uploads/temp/';
	static private $idCounter = 1;// Form id counter. incremented on each new form.
	private $elementId = 1;
	private $id;// Form id. Useful when multiple forms on a page to know which form is submitted.
	public $method;// Custom class.
	public $action;// Custom action.
	public $class;// Custom class.
	public $captcha;// Captcha presence.
	private $enctype;// Add enctype="multipart/form-data" to the form tag if an upload element is found.
	private $elements = [];

	// Store the elements indexes, indexed by element name, for conveniance and performances.
	// Only store indexes and not directly element object for the element to be up to date at any time.
	private $elementIndexesByName = [];

	private $wrappers = [];
	private $buttons = [];
    private $robotCheck = false;
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
		$this->id = isset($options['id']) ? $options['id'] : ('form'.(self::$idCounter++));
		$this->method = isset($options['method']) ? $options['method'] : 'POST';
		$this->action = isset($options['action']) ? $options['action'] : url('SELF');
		$this->class = isset($options['class']) ? $options['class'] : null;
		$this->enctype = false;

		if (Userdata::isAjax()) $this->handleAjax();
	}

	/**
	 * handle all the built-in form-related tasks executed from ajax calls.
	 *
	 * @return void.
	 */
	private function handleAjax()
	{
		handleAjax(function()
		{
			$object = null;
			$gets = Userdata::get();

			switch (true)
			{
				case isset($gets->discardUpload) && $gets->discardUpload:
					$object = $this->discardUploads($gets->discardUpload);
					break;
				case isset($gets->discardAllUploads):
					$object = $this->discardUploads();
					break;
				case isset($gets->addImagesToArticle):
					$object = $this->addImagesToArticle();
					break;
				case isset($gets->ajaxTrackProgress):
					$object = $this->ajaxTrackProgress();
					break;
				case isset($gets->removeFile):
					$object = $this->removeFile($gets->removeFile);
					break;
				case isset($gets->removeImage):
					$object = $this->removeImage($gets->removeImage);
					break;

				default:
					break;
			}

			return $object;
		});
	}

	private function ajaxTrackProgress()
	{
		$progress = $_SESSION['ajaxProgressUpdate'] ? $_SESSION['ajaxProgressUpdate'] : 0;
		if ($progress >= 100)
		{
			Userdata::_unset('session', 'ajaxProgressUpdate');
			// unset($_SESSION['ajaxProgressUpdate']);
		}

		header('Content-Type: application/json;charset=utf-8');
		header('Cache-Control: no-cache');
		die(json_encode(['progress' => $progress]));
	}

	private function removeFile($fileSrc)
	{
		$urlParts = parse_url($fileSrc);dbgd($urlParts);
		// @TODO: Do a file unlink.
		// unlink(self::uploadsDir.'/'.$urlParts['query']);
	}

	private function removeImage($imgSrc)
	{
		$urlParts = parse_url($imgSrc);

		// Split url like "images/?u=201609/b4ca2b2183a4c6b6436110ef4ec7e670_xl.jpg" into useful parts.
		preg_match('~^(u|i)=(.*?)([^/]+?)\.(\w+)$~', $urlParts['query'], $matches);
		list(, $letter, $path, $imageName, $extension) = $matches;

		// Look for a possible file size in picture name. If found, then remove all size images from server.
		$possibleSizes = ['xs', 's', 'm', 'l', 'xl', 'o'];
		$removedFiles = 0;
		if (preg_match('~(.*)_(?:'.implode('|', $possibleSizes).')~', $imageName))
		{
			$fileBaseName = $root.($letter == 'u' ? 'uploads' : 'images')."/$path{$imageName}_";
			foreach ($possibleSizes as $size)
			{
				// if (is_file($fileBaseName."$size.$extension"))
				// if (unlink(self::uploadsDir.'/'.$urlParts['query'])) $removedFiles++;
				print_r([$fileBaseName."$size.$extension", is_file($fileBaseName."$size.$extension")]);
			}
			die;
		}
		elseif (unlink(self::uploadsDir.'/'.$urlParts['query']))
		{
			echo 'ok !';
			$removedFiles++;
		}


		// @TODO: Do a file unlink.
		// unlink(self::uploadsDir.'/'.$urlParts['query']);
	}

	/**
	 * Discard provided (or all if empty) temporary uploads.
	 *
	 * @param string|array $fileNames: the file names list of files you want to delete from server temporary uploads folder.
	 *                          Leave empty to discard all.
	 * @return void.
	 */
	private function discardUploads($fileNames = [])
	{
		// Make sure given param is an array. But allow one filename string.
		if (is_string($fileNames)) $fileNames = array($fileNames);

		// If no file name given crawl temporary uploads folder and remove everything.
 		if (!$filesCount = count($fileNames))
 		{
	 		$fileNames = array_diff(scandir(self::uploadsDirTemp), ['.', '..']);
	 		$filesCount = count($fileNames);
 		}

 		// Keep track of removed files for reporting.
 		$removedFilesCount = 0;
 		foreach ($fileNames as $k => $fileName) if (unlink(self::uploadsDirTemp.$fileName)) $removedFilesCount++;

 		return ['error' => $removedFilesCount < count($fileNames), 'message' => "$removedFilesCount/$filesCount files were deleted."];
	}

	private function addImagesToArticle()
	{
		$fileNames = array_diff(scandir(self::uploadsDirTemp), ['.', '..']);
		$error     = true;
		$message   = 'No file was found in '.self::uploadsDirTemp;
		$html      = '';

		if (count($fileNames))
		{
			$output = '';
			$imagesFound = 0;
			$imagesProcessed = 0;
			$imagesToProcess = [];

			// First calculate the number of found images to treat so the ajax progress can be calculated.
			foreach ($fileNames as $fileName)
			{
				// If file is an image, move it to definitive uploads folder and rename file for security.
				if (in_array($imageExtension = pathinfo($fileName, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif']))
				{
					$imagesFound++;
					$imagesToProcess[] = $fileName;
				}
			}

			// Then loop through matching files.
			foreach ($imagesToProcess as $k => $fileName)
			{
				// If file is an image, move it to definitive uploads folder and rename file for security.
				// if (in_array($imageExtension = pathinfo($fileName, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif']))
				// {
					// Read from pic metadata and keep original picture date and time for img alt attribute.
					$exif = exif_read_data(self::uploadsDirTemp.$fileName, 'IFD0');
					$alt = $exif === false && isset($exif['DateTime']) && $exif['DateTime'] ? 'picture '.($k + 1).' (no date)'
																							: $exif['DateTime'];


					$imageNameBase = md5(date('YmdHis') . 'pw-salt');// Rename file for security.
					$yearMonth = date('Ym');// Organize pics in month folders for convenience.

					// Create folder if it does not exist.
					if (!is_dir(self::uploadsDir.$yearMonth)) mkdir(self::uploadsDir.$yearMonth);

					$originalImage = self::uploadsDir."$yearMonth/{$imageNameBase}_o.$imageExtension";

					// Move image from temporary upload folder to definitive upload folder.
					if (rename(self::uploadsDirTemp.$fileName, $originalImage))
					{
					    $newImage = self::uploadsDir."$yearMonth/{$imageNameBase}_m.$imageExtension";

					    copy($originalImage, $newImage);

						$sizes = ['m'  => 607500,// 900*900*3/4
						          'o'  => 3145728];// 2048*2048*3/4

					    // @todo: use imagemagick from the built-in php library (php extension).
					    // Convert the image to the $size resized format using Imagemagick lib.
					    exec("/usr/local/bin/convert '$originalImage' -resize $sizes[m]@ -unsharp 2x0.5+0.6+0 -quality 90 '$newImage';");
						$imagesProcessed += .5;
						updateAjaxProgress($imagesProcessed * 100 / $imagesFound);

						// Convert the image to the 'original' format (3M pixels max - width: 2000 & ratio: 4/3) using Imagemagic lib.
						exec("/usr/local/bin/convert '$originalImage' -resize $sizes[o]@ -unsharp 2x0.5+0.6+0 -quality 90 $originalImage;");
						$imagesProcessed += .5;
						updateAjaxProgress($imagesProcessed * 100 / $imagesFound);

						$url = url("images/?u=$yearMonth/{$imageNameBase}_m.$imageExtension");
						$br = $k && ($k+1) % 2 ? '<br>' : '';
						$output .= <<<HTML
						<figure class="size_m">
							<img src="$url" alt="$alt">
							<figcaption></figcaption>
						</figure>$br
HTML;
					}
				// }
			}

			$error   = $imagesProcessed < $imagesFound;
			$message = "$imagesProcessed/$imagesFound image files were processed.";
			$html    = $output;
		}

 		return ['error' => $error, 'message' => $message, 'html' => $html];
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
	 * @param array $options: an indexed array (['key' => 'value',...]) of extra options that may apply to the form element.
	 *        possible pairs:
	 *            default => (string/number): a default value to display if no value provided.
	 *            			 (array): [(string/number)"default value", (boolean)ignore_default_on_form_submit]
	 *            			          an array of string/number default value and boolean to ignore or not this default value when form is submitted. Default to false.
	 *            label => (string): The field text to display in label tag.
	 *            labelPosition => (string): before, after - the element.
	 *            validation => (string/array): if there is any validation requirements for the form element.
	 *            				@see: the existingValidations constant.
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
		$options = (object)$options;// Just for conveniance.

		// Error if no element type provided.
		if (!is_string($type))
			Error::add(__CLASS__.'::'.ucfirst(__FUNCTION__).'(): You must provide the type of the element you want to add in the form.', 'MISSING DATA', true);

		// Error if provided element type does not exist.
		elseif (!in_array($type, self::existingElements))
			Error::add(__CLASS__.'::'.ucfirst(__FUNCTION__).'(): You must select an existing element type among: '.implode(', ', self::existingElements).'.', 'WRONG DATA', true);

		// Error if no attribute is found. Except for elements: wrapper, header, paragraph.
		elseif (!in_array($type, ['wrapper', 'header', 'paragraph'])
				&& (!is_array($attributes) || !count($attributes)))
			Error::add(__CLASS__.'::'.ucfirst(__FUNCTION__)."($type): You must provide an array of attributes containing element name and other html attributes.", 'MISSING DATA', true);

		// Error if no name attribute is found. Except for elements: wrapper, header, paragraph.
		elseif (!in_array($type, ['wrapper', 'header', 'paragraph'])
				&& !isset($attributes['name']))
			Error::add(__CLASS__.'::'.ucfirst(__FUNCTION__)."($type): You must provide (through the attributes array) a name for the $type form element.", 'MISSING DATA');

		// Error if no name numberElements is found for wrapper element.
		elseif ($type === 'wrapper' && !isset($options->numberElements))
			Error::add(__CLASS__.'::'.ucfirst(__FUNCTION__)."($type): You must provide (through the options array) a number of elements to wrap: 'numberElements' => (int).", 'MISSING DATA');

		// Error if no name numberElements is found for wrapper element.
		elseif (($type === 'header' || $type === 'paragraph') && !isset($options->text))
			Error::add(__CLASS__.'::'.ucfirst(__FUNCTION__)."($type): You must provide (through the options array) a text to write: 'text' => (string).", 'MISSING DATA');

		// Error if no name numberElements is found for wrapper element.
		elseif (($type === 'header') && !isset($options->level))
			Error::add(__CLASS__.'::'.ucfirst(__FUNCTION__)."($type): You must provide (through the options array) a level for the header (h1 - h6): 'level' => (int).", 'MISSING DATA');

		// Elements for which attributes are not required:
		else
		{
			// Html name attribute of the form element.
			// Convert subdata names from 'name[subname][subsubname]' to '[name][subname][subsubname]', or 'name' to '[name]'.
			$name = isset($attributes['name']) ? $this->convertName2subname($attributes['name']) : '';

			$el = (object)['name' => $name,
						   'id' => text($name ? $name : $type, ['formats' => ['sef']]).((int)$this->elementId++),
						   'type' => $type,
						   'attributes' => (object)$attributes,
						   'options' => $options];

			switch ($type)
			{
				case 'wrapper':
					// Expects the specific 'numberElements' param.
					$el->numberElements = $options->numberElements;

					// Store the wrapper in a class var array for later use.
					$this->wrappers[$el->id] = ['wrap' => $el->numberElements, 'state' => 'unset'];
					break;
				case 'header':
					// Expects the specific 'level' and 'text' param.
					$el->level = $options->level;
					$el->text = $options->text;
					break;
				case 'paragraph':
					// Expects the specific 'text' param.
					$el->text = $options->text;
					break;
				case 'upload':
					$this->enctype = true;
					break;
				case 'email':
					if (!isset($el->options->validation)) $el->options->validation = [];
					elseif (!is_array($el->options->validation)) $el->options->validation = [$el->options->validation];
					if (!in_array('email', $el->options->validation)) $el->options->validation[] = 'email';
					break;
				case 'text':
				default:
					break;
			}

			$this->elements[] = $el;
		}
	}

    /**
     * addButton function.
     * Add a button to the form.
     *
     * @param string $type: The type of the button to add.
     *                      Possible buttons: 'validate', 'cancel'
     * @param string $label: The label of the button.
     * @param array $options: more options for the button. Like javascript toggle.
     * @return void.
     */
    public function addButton($type, $label, $options = [])
    {
        if (!$label) return Error::add(__CLASS__.'::'.ucfirst(__FUNCTION__).'(): You must provide a label for the button you want to add to the form.', 'MISSING DATA', true);

        $defaultClass = $type;
        $button = new StdClass();

        switch ($type)
        {
            case 'submit':
                $button->type = $type;
                $button->name =  isset($options['name']) ? $options['name'] : 'submit';
                break;
            case 'validate':
                $button->type = 'submit';
                $button->name = 'submit';
                break;
            case 'cancel':
                $button->type = 'reset';
                break;
            // We do not want an unknown button to be appended.
            default:
                return Error::add(__CLASS__.'::'.ucfirst(__FUNCTION__).'(): The type of the button you want to add to the form is nknown.', 'WRONG DATA', true);
                break;
        }

        $button->class = isset($options['class']) ? $options['class'] : $defaultClass;
        if (isset($options['value'])) $button->value = $options['value'];
        if (isset($options['title'])) $button->title = $options['title'];
        $button->label = $label;
        $button->options = (object)$options;

        $this->buttons[] = $button;
    }

	/**
	 * addRobotCheck function.
	 * Add a Robot Check to the form.
     * If activated, the form will refuse to submit unless user unchecks the "I'm a robot" checkbox.
     * If no robot check the form will submit every valid data.
	 *
	 * @param string $label: The label to give to the Robot Check.
	 * @return void.
	 */
	public function addRobotCheck($label = '')
	{
		if (!$label) return Error::add(__CLASS__.'::'.ucfirst(__FUNCTION__).'(): You must provide a label for the Robot check you want to add to the form.', 'MISSING DATA', true);

		$this->robotCheck = new StdClass();
        $this->robotCheck->label = $label;
	}

	/**
	 * render function.
	 * Render the generated form and return the full HTML.
	 * 1. render the form attributes,
	 * 2. render the elements (sub-template) and handle possible nested wrappers.
	 * 3. render the element labels
	 * 4. render the element error messages.
	 * 5. render form buttons.
	 *
	 * @return (string) generated form HTML.
	 */
	public function render()
	{
		$tpl = new Template(ROOT.'backstage/templates/');
		$tpl->set_file(['form-tpl' => 'form.html',
						'form-element-tpl' => 'form-elements.html']);
		$tpl->set_block('form-tpl', 'wrapperBlock', 'theWrapperBlock');
		$tpl->set_block('wrapperBlock', 'rowBlock', 'theRowBlock');
		$tpl->set_block('rowBlock', 'elementBlock', 'theElementBlock');
		foreach (self::existingElements as $existingElement) if ($existingElement !== 'wrapper')
		{
			$tpl->set_block('form-element-tpl', $existingElement.'Block', 'the'.ucfirst($existingElement).'Block');
		}
		$tpl->set_block('form-element-tpl', 'labelBlock', 'theLabelBlock');
		$tpl->set_block('form-tpl', 'robotCheck', 'theRobotCheck');
        $tpl->set_block('form-tpl', 'buttonBlock', 'theButtonBlock');
		$tpl->set_block('selectBlock', 'selectOptionBlock', 'theSelectOptionBlock');
		$tpl->set_block('uploadBlock', 'uploadItemBlock', 'theUploadItemBlock');
		$tpl->set_block('radioBlock', 'radioOptionBlock', 'theRadioOptionBlock');
		$tpl->set_block('checkboxBlock', 'checkboxOptionBlock', 'theCheckboxOptionBlock');

		$tpl->set_var(['formId' => $this->id,
					   'method' => $this->method,
					   'action' => $this->action,
					   'formClass' => $this->class ? " class=\"$this->class\"" : '',
					   'formWrapperClass' => $this->class ? " class=\"{$this->class}Wrapper\"" : '',
					   'enctype' => $this->enctype ? " enctype=\"multipart/form-data\"" : '']);

		//========================= ELEMENTS RENDERING =========================//
		$rowSpan = 0;
		foreach ($this->elements as $k => $element)
		{
			$k++;// Just start counter at 1 instead of 0.


			// Javascript conditionnal element toggles.
			$toggle = $this->getToggle($element);//!\ Do not remove assignation: used below.
			$tpl->set_var(['toggle' => $toggle]);


			$tpl->set_var(['elementType' => $element->type,
						   //!\ First clear row is important when rowSpan is active.
						   'theRowBlock' => '',

			//------------------------- Wrappers handling ----------------------//
						   'wrapperBegin' => '',// Init/reset to empty.
						   'wrapperEnd' => '']);

			// Foreach element check (among all wrappers in $wrappers array) if there is:
			// - a wrapper to open
			// - an existing open wrapper that should be closing
			// A closed wrapper is not processed.
			$closingWrappers = 0;

			//!\ Mind the passed by reference &$wrapper.
			if (count($this->wrappers)) foreach ($this->wrappers as $wrapperId => &$wrapper) if ($wrapper['state'] !== 'closed')
			{
				if ($wrapper['state'] !== 'unset') $wrapper['wrap']--;// Decrement the wrapper number of remaining elements.
				elseif ($wrapperId === "wrapper$k") $wrapper['state'] = 'opened';// create wrapper.

				if (!$wrapper['wrap'])
				{
					$wrapper['state'] = 'closed';
					$closingWrappers++;
				}
			}

			// In each element loop multiple wrappers can possibly close at the same time (nested).
			$tpl->set_var('wrapperEnd', str_repeat('</div>', $closingWrappers));

			// If the element is a wrapper then open its div tag.
			if ($element->type === 'wrapper')
			{
				$wrapperBegin = $element->attributes->class ? "<div class=\"{$element->attributes->class}\"" : '<div';
				$wrapperBegin .= $toggle.'>';
				$tpl->set_var('wrapperBegin', $wrapperBegin);
			}
			//------------------------------------------------------------------//

			else
			{
				$this->renderElement($element, $tpl);
				$this->renderLabel($element, $tpl);

				// RowSpan let the possibility to merge two or more rows.
				// It is counted like a standard html table rowspan.
				$tpl->parse('theElementBlock', 'elementBlock', $rowSpan);
				$rowSpan = isset($element->options->rowSpan) && $element->options->rowSpan > 1 ? $element->options->rowSpan : $rowSpan;
				if ($rowSpan) $rowSpan--;

				$tpl->set_var(['rowClass' => isset($element->options->rowClass) ? " {$element->options->rowClass}" : '']);

				//!\ false or true has no impact here cz clearing previous row with above $tpl->set_var(['theRowBlock' => '']);
				if (!$rowSpan) $tpl->parse('theRowBlock', 'rowBlock', false);
			}

			$tpl->parse('theWrapperBlock', 'wrapperBlock',  true);
		}
		//======================================================================//

        //======================== ROBOT CHECK RENDERING =======================//
        if ($this->robotCheck)
        {
            $tpl->set_var(['robotCheckText' => $this->robotCheck->label,
                           'yes' => text(97),
                           'no' => text(98)]);
            $tpl->parse('theRobotCheck', 'robotCheck', true);
        }
        //======================================================================//

		//========================== BUTTONS RENDERING =========================//
		foreach ($this->buttons as $k => $button)
		{
			$tpl->set_var(['btnText' => $button->label,
						   'btnClass' => $button->class,
						   'btnType' => $button->type,
						   'btnName' => isset($button->name) && $button->name ? " name=\"{$this->id}[$button->name]\"" : '',
                           'btnValue' => isset($button->value) && $button->value ? " value=\"$button->value\"" : '',
						   'btnTitle' => isset($button->title) && $button->title ? " title=\"$button->title\"" : '',
						   'toggle' => $this->getToggle($button)]);
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

		// Element HTML attributes.
		// Exclude some specific attribute to individually treat them later.
		if (isset($element->attributes))
		{
			// if there is a value html attribute remove it and place it in $element->options->default.
			if (isset($element->attributes->value)) $element->options->default = $element->attributes->value;
			unset($element->attributes->value);

			foreach ($element->attributes as $attrName => $attrValue) if (!in_array($attrName, ['type', 'name', 'id', 'value']))
			{
				$attrHtml .= " $attrName=\"$attrValue\"";
			}
		}

		if (isset($element->options->default) && is_array($element->options->default))
		{
			$element->options->ignoreDefaultOnSubmit = (bool)$element->options->default[1];
			$element->options->default = $element->options->default[0];
		}

		$tpl->set_var(['name' => $element->name,
				       'id' => $element->id,
				       'class' => isset($element->attributes->class) ? " {$element->attributes->class}" : '',
				       'validation' => isset($element->options->validation) ? implode(' ', (array)$element->options->validation) : null,
				       'errorMessage' => isset($element->error) && $element->error ? "<span class=\"error\">$element->message</span>" : '',
				       'state' => isset($element->error) && $element->error ? ' invalid' : (isset($element->userdata) ? ' valid' : ''),// Validation state.
				       'attr' => $attrHtml,
				       // $element->userdata is an object if the element is a checkbox or multiple select.
				       'value' => isset($element->userdata) && !is_object($element->userdata) && !is_array($element->userdata) ?
				       			  stripslashes($element->userdata)
				       			  : (isset($element->options->default) ? $element->options->default : '')]);

		switch ($element->type)
		{
		 	case 'paragraph':
			 	$tpl->set_var(['text' => $element->options->text, 'class' => isset($element->attributes->class) ? " class=\"{$element->attributes->class}\"" : '']);
		 		break;
		 	case 'header':
		 		$tpl->set_var(['level' => (int)$element->options->level, 'text' => $element->options->text, 'class' => isset($element->attributes->class) ? " class=\"{$element->attributes->class}\"" : '']);
		 		break;
		 	case 'select':
		 		$multiple = isset($element->options->multiple) && $element->options->multiple == true;
		 		$tpl->set_var(['the'.ucfirst($element->type).'OptionBlock' => '',
		 					   'ifMultiple' => $multiple ? '[]' : '',
		 					   'multiple' => $multiple ? ' multiple' : '']);
		 		$i = 0;
		 		foreach ($element->options->options as $value => $label)
		 		{
		 			$isSelected = (isset($element->userdata) && $element->userdata == $value)
		 						  || (!isset($element->userdata) && isset($element->options->default) && $element->options->default == $value);
					$tpl->set_var(['value' => $value,
								   'label' => $label,
								   'opt' => $i,
								   'selected' => $isSelected ? 'selected="selected"' : '']);
		 			$tpl->parse('the'.ucfirst($element->type).'OptionBlock', $element->type.'OptionBlock', true);
		 			$i++;
		 		}
		 		break;
		 	case 'radio':
		 	case 'checkbox':
		 		$inline = isset($element->options->inline) && $element->options->inline;
		 		$tpl->set_var(['the'.ucfirst($element->type).'OptionBlock' => '',
		 					   'inline' => $inline ? ' inline' : '']);

		 		// If checkbox has multiple options use array (e.g. name="checkbox[]").
		 		$multiple = $element->type === 'checkbox' && count($element->options->options) > 1;

		 		// Loop through options to generate html.
		 		$i = 0;
		 		foreach ($element->options->options as $value => $label)
		 		{
		 			$isChecked = (isset($element->userdata) && in_array($value, (array)$element->userdata))
		 						 || (!isset($element->userdata) && isset($element->options->default) && $element->options->default == $value);
					$tpl->set_var(['value' => $value,
								   'ifArray' => $multiple ? '[]' : '',
								   'br' => $inline ? '' : '<br />',
								   'label' => $label ? "<label for=\"$this->id{$element->id}opt$i\">$label</label>" : '',
								   'opt' => $i,
								   'checked' => $isChecked ? 'checked="checked"' : '']);
		 			$tpl->parse('the'.ucfirst($element->type).'OptionBlock', $element->type.'OptionBlock', true);
		 			$i++;
		 		}
		 		break;
		 	case 'upload':
		 		$tpl->set_var(['the'.ucfirst($element->type).'ItemBlock' => '',
		 					   'addImagesToArticle' => text(77),
							   'discardAll' => text(78)]);

		 		$filterFiles = ['.DS_Store'];

		 		// Append in dropzone box every files found in temporary uploads folder.
		 		foreach (array_diff(scandir(self::uploadsDirTemp), ['.', '..']) as $fileName) if (!in_array($fileName, $filterFiles))
		 		{
		 			$tpl->set_var(['fileName' => $fileName,
								   'filePath' => url("images/?u=temp/$fileName"),
								   'fileSize' => Utility::human_filesize(self::uploadsDirTemp."/$fileName"),
								   'removeFile' => text(79)]);
		 			$tpl->parse('the'.ucfirst($element->type).'ItemBlock', $element->type.'ItemBlock', true);
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
	 * getPostedData check if there is any post for the current form and return the posts object or null if unset.
	 * Example of use:
	 *     $form->getPostedData('getTexts');// You can also use this in your callback function.
	 *
	 * @param string $fieldName: one field name you want to retrieve the posted data. Leave empty to get all posted data.
	 * @param bool $acceptHtml: set tot true if you really want to unblock html contents.
	 * @return array: the form posts object.
	 */
	public function getPostedData($elementName = null, $acceptHtml = false)
	{
		$posts = $acceptHtml ? Userdata::secureVars($_POST, true, true) : Userdata::get('post');

		if (!$posts || !isset($posts->{$this->id})) return null;

		$return = null;

		if (!$elementName) $return = $posts->{$this->id};
		else
		{
			$element = $this->getElementByName($elementName);
			if (isset($element->userdata)) $return = $element->userdata;
			else
			{
				$tmpPath = $_POST[$this->id];
				foreach (explode('[', str_replace(']', '', $elementName)) as $bit)
				{
					$tmpPath = isset($tmpPath[$bit]) ? $tmpPath[$bit] : null;
				}
				$return = Userdata::secureVars($tmpPath, true, $acceptHtml);
			}
		}

		return $return;
	}

	/**
	 * unsetPostedData
	 *
	 * @param string $elementName: one field name you want to unset the posted data.
	 *                           leave empty if you want all fields to be emptied.
	 * @return void.
	 */
	public function unsetPostedData($elementName = '', $keepError = true)
	{
		if (!$elementName)
		{
			foreach ($this->elements as &$element)
			{
				if (isset($element->userdata))
				{
					unset($element->userdata);
					$element->error = 0;
				}
			}
		}
		elseif (isset($this->getElementByName($elementName)->userdata))
		{
			$element = $this->getElementByName($elementName);
			if (isset($this->validElements[$elementName])) unset($this->validElements[$elementName]);
			if (!$keepError)
			{
				unset($element->userdata);
				$element->error = 0;
			}
			else
			{
				$element->error = 1;
				$element->message = self::existingValidations['required']['message'];
				unset($element->userdata);
			}
		}
	}

	/**
	 * Inject a value in one form element.
	 * Shortcut function for injectValues().
	 *
	 * @param String $elementName: the element html name attribute.
	 * @param string $value: the value you want to set for the current element.
	 * @return void.
	 */
	public function injectValue($elementName, $value)
	{
		$this->injectValues([$elementName => $value]);
	}
	/**
	 * inject values for all the provided form elements.
	 *
	 * @param array $arrayOfElements: an array of [elementName => value] pairs.
	 * @return void.
	 */
	public function injectValues($arrayOfElements = [])
	{
		if (count($arrayOfElements)) foreach ($arrayOfElements as $elementName => $value)
		{
			$element = $this->getElementByName($elementName);
			$element->options->default = $value;
		}
	}

	/**
	 * Modify an element attributes array.
	 *
	 * @param String $elementName: the element html name attribute.
	 * @return void.
	 */
	public function modifyElementAttributes($elementName, $newAttributesArray)
	{
		if (!$elementName)
			return Error::add(__CLASS__.'::'.ucfirst(__FUNCTION__).'(): You must provide the name of the element you want to modify.', 'MISSING DATA', true);

		$this->modifyElements([$elementName => $newAttributesArray], 'attributes');
	}
	public function modifyElementOptions($elementName, $newOptionsArray)
	{
		if (!$elementName)
			return Error::add(__CLASS__.'::'.ucfirst(__FUNCTION__).'(): You must provide the name of the element you want to modify.', 'MISSING DATA', true);

		$this->modifyElements([$elementName => $newOptionsArray], 'options');
	}
	public function modifyElements($arrayOfElements = [], $what)
	{
		if (!count((array)$arrayOfElements))
			return Error::add(__CLASS__.'::'.ucfirst(__FUNCTION__).'(): You must provide an indexed array of at least 1 element to modify [\'elementName\' => [modifiedArray]].', 'WRONG DATA', true);

		if (!$what)
			return Error::add(__CLASS__.'::'.ucfirst(__FUNCTION__).'(): You must explicit the element array you want to modify: attributes or options.', 'WRONG DATA', true);

		foreach ((array)$arrayOfElements as $elementName => $newArray)
		{
			$element = $this->getElementByName($elementName);
			$element->$what = (object)array_merge((array)$element->$what, (array)$newArray);
			dbg((array)$element->$what);
		}
	}

	/**
	 * validate function: first check if there is any post for the current form, if not simply return false
	 * otherwise check each user post and save it into the appropriate form element object.
	 * Used to - and convenient to - check ALL the fields!
	 * @param function $callback: a callback function to execute once the form is submitted and passed the default validation.
	 *                            You can provide this function directly as an anonymous function, or the name of an existing
	 *                            function as a string.
	 *                            E.g: $form->validate('afterValidate'); or $form->validate(function(){...});
	 *                            This function will be passed 2 parameters:
	 *                            	$return: the object as shown in @return bellow.
	 *                            	$this: the current form Object.
	 *                            So you can declare the callback function like so: function callback($info, $form){...}.
	 *
	 * @return StdClass object / null: {fillable:$countFillableElements, filled:$countFilledElements, invalid:$countInvalidElements}
	 */
	public function validate($callback = null)
	{
		$gets = Userdata::get();

        // If no posted data do not go further.
        if (!$this->getPostedData() && !Userdata::isAjax() && !isset($gets->upload)) return false;

		// Prevent submission if robot check is enabled and user did not unckeck the 'i'm a robot' checkbox.
        if ($this->robotCheck && $this->getPostedData('robotCheck') !== 'clear')
        {
            new Message(text(96), 'error', 'error', 'header');
            return false;
        }

		// Init few vars to keep track of the validation result.
		$countFillableElements = 0;
		$countFilledElements = 0;
		$countInvalidElements = 0;

		// Loop through all the element that have a set name (html attr).
		foreach ($this->elements as $k => &$element) if (isset($element->attributes->name))
		{
			$countFillableElements++;

			// Split name like form1[text][en] into ['form1','text','en'] to check if user post is set.
			$elementNameParts = explode('[', str_replace(']', '', $element->attributes->name));

            // $this->getPostedData with last param true to accept html. Only for wysiwyg.
			if ($element->type === 'wysiwyg') $userDataFromPath = $this->getPostedData($element->attributes->name, true);
			else
			{
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
			}

			// The path is found in posted data, now validate the field.
			if ($userDataFromPath !== null)
			{
				// Save the posted user data into the form element object.
				$element->userdata = $userDataFromPath;
				$countFilledElements++;

				// If element has a validation set then call the checkElementValidations() method.
				// The element is valid if no validation is set.
				$isValid = isset($element->options->validation) ? $this->checkElementValidations($element) : true;
				$element->error = !$isValid;

				if (!$isValid) $countInvalidElements++;

				// Save only valid elements in a private array for easier later access!
				else $this->validElements[] = $element->attributes->name;
			}

			// @TODO: finish implementing upload.
			// if upload element, check uploaded file types.
			elseif ($element->type === 'upload')
			{
				if (is_object($files = Userdata::secureVars($_FILES))) $files = $files->{$this->id};

				if (!isset($element->options->accept)) $element->options->accept = ['*'];
				foreach ((array)$element->options->accept as $l => &$accept)
				{
					$accept = strtolower($accept);
					switch($accept)
					{
						case '':
						case '*':
							$accept = '*';
							break;
						case 'jpeg':
						case 'jpg':
							$accept = 'image/jpeg';
							break;
						case 'png':
						case 'gif':
						case "image/$accept":
							$accept = "image/$accept";
							break;
						default:
							Error::add(__CLASS__.'::'.ucfirst(__FUNCTION__).'(): The upload file type you want to accept was not recognized and ignored: "'.$accept.'".', 'WRONG DATA', true);
							unset($element->options->accept[$l]->accept[$l]);
							break;
					}
				}

				// Look at the $element->attributes->name into the $_FILES var to find the upload mime type of each uploaded file.
				// $files->type looks like:
				// [type] => stdClass Object
				// (
				//    [article] => stdClass Object
				//    (
				//        [upload] => stdClass Object
				//        (
				//            [0] => image/jpeg
				//            [1] => image/jpeg
				//            [2] => image/jpeg
				//        )
				//    )
				// )
				$fileTypesTmpPath = $files->type;
				$fileNamesTmpPath = $files->name;
				$fileTmpNamesTmpPath = $files->tmp_name;
				$fileErrorTmpPath = $files->error;
				$element->message = '';

				// Converting files array to object.
				foreach (explode('[', str_replace(']', '', $element->attributes->name)) as $bit) if ($bit)
				{
					$fileTypesTmpPath = $fileTypesTmpPath->$bit;
					$fileNamesTmpPath = $fileNamesTmpPath->$bit;
					$fileTmpNamesTmpPath = $fileTmpNamesTmpPath->$bit;
					$fileErrorTmpPath = $fileErrorTmpPath->$bit;
				}
				foreach ($fileErrorTmpPath as $key => $error)
				{
					$name = $fileNamesTmpPath[$key];// E.g. 'picture1.jpg'.
					$tmpName = $fileTmpNamesTmpPath[$key];// Temporary name given by PHP while transfert.
					$mimeType = $fileTypesTmpPath[$key];// E.g. 'image/jpg'.

					// Skip empty files.
					if (!$tmpName) continue;

					// If upload error is detected.
				    if ($error != UPLOAD_ERR_OK)
				    {
						$element->error = 1;
						$element->message = "Upload error. [$error] on file \"$name\".";
						$countInvalidElements++;
						break;
				    }

					// If not allowed file mime type.
					elseif (!in_array($mimeType, $element->options->accept) && !in_array('*', $element->options->accept))
					{
						$element->error = 1;
						$element->message = 'Unaccepted upload file type.';
						$countInvalidElements++;
						break;
					}

					// If upload is correct, move it into uploads directory.
					else
					{
						$safeName = md5(date('YmdHis')).basename($name);
						if (move_uploaded_file($tmpName, self::uploadsDirTemp.basename($name)))
							$element->message .= "The file \"$name\" is valid, and was successfully uploaded.\n";
						else echo "Could not move uploaded file \"$tmpName\" to \"".self::uploadsDirTemp.basename($name)."\".";
					}
				}
			}

			// if post is unset (like it might happen for select, checkbox or multiple select and radio elmts).
			else
			{
				$element->userdata = null;
				$isValid = isset($element->options->validation) ? $this->checkElementValidations($element) : true;
				$element->error = !$isValid;

				if (!$isValid) $countInvalidElements++;

				// Save only valid elements in a private array for easier later access!
				else $this->validElements[] = $element->attributes->name;
			}
		}

		$return = (object)['fillable' => $countFillableElements, 'filled' => $countFilledElements, 'invalid' => $countInvalidElements];


		// If all the posts are valid.
		if (!$countInvalidElements)
		{
			$grantClearing = true;

			// Call a potential validate function if it is set in calling file.
			if ($callback && is_string($callback))
			{
				if (is_callable($callback)) $grantClearing = $callback($return, $this);
				else
				{
					Error::add(__CLASS__.'::'.ucfirst(__FUNCTION__)."(): The given callback function \"$callback\" does not exist.", 'WRONG DATA', true);
					return null;
				}
			}
			elseif ($callback && is_callable($callback)) $grantClearing = $callback($return, $this);

			// If all the posts are valid, and unless callback function returns false, clear all the fields.
			if ($grantClearing) $this->unsetPostedData();
		}

		return $return;
	}

	/**
	 * INTERNAL checkElementValidations function called by the validate() method to check each validation set for a given element.
	 *
	 * @param  Object $element: the Object of the element to check. See addElement() to know more about the expected object.
	 * @return boolean: true if valid or false otherwise.
	 */
	private function checkElementValidations($element)
	{
		$isValid = true;

		// Loop through the multiple element validations.
		// If found, the var $condition will contain it.
		foreach ((array)$element->options->validation as $validation)
		{
			// Detect the presence of a potential condition in parenthesis.
			if (($pos = strpos($validation, '(')) !== false) list($validation, $condition) = explode('(', trim($validation, ')'));

			// Only perform a validation if it is a known one!
			if (array_key_exists($validation, self::existingValidations))
			{
				// discard the posted default value if it is untouched value and the ignoreDefaultOnSubmit is set to true.
				if (isset($element->options->default)
				    && isset($element->options->ignoreDefaultOnSubmit)
				    && !is_object($element->userdata)
				    && $element->userdata === $element->options->default)
					$element->userdata = '';

				// 'Switch' to treat specific validations.
				switch ($validation)
				{
					case 'requiredIf':
						list($leftHand, $rightHand) = explode('=', $condition);
						$referedElement = $this->getElementByName($leftHand);
						$isConditionTrue = $referedElement && isset($referedElement->userdata) && $referedElement->userdata == $rightHand;
						$isValid = !$isConditionTrue
								   // The element->userdata can be an object in case of checkbox or multiple select.
								   || ($isConditionTrue && isset($element->userdata) && is_object($element->userdata))
								   // Otherwise check the validation pattern.
								   || ($isConditionTrue && preg_match(self::existingValidations[$validation]['pattern'], (string)trim($element->userdata)));

						// Need to sprintf the message, so set an array of arguments to inject in message.
						$messageArgs = [$referedElement->options->options[$rightHand]];
						break;
					default:
						// The element->userdata can be an object in case of checkbox. if it is the case valid = true.
						// Otherwise perform a preg_match on user posted data with the pattern set in the definition of the validation.
						// See the existingValidations constant.
						$isValid = (isset($element->userdata) && is_object($element->userdata))
								   || preg_match(self::existingValidations[$validation]['pattern'], (string)trim($element->userdata));
						break;
				}

				if (!$isValid)
				{
					// If above tests fail, save the resulting error message in the element object.
					// And perform a sprintf if the array $messageArgs exists and is not empty.
					if (isset($messageArgs) && count($messageArgs))
						$element->message = sprintf(self::existingValidations[$validation]['message'], ...$messageArgs);
					else $element->message = self::existingValidations[$validation]['message'];
					break;// keep only the first message then break the loop.
				}
			}
			// dbg($element, $isValid);
		}
		return $isValid;
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
	 * @param  string $name: the name to convert
	 * @return string: the converted name
	 */
	private function convertName2subname($name)
	{
		$nameOut = '';
		foreach (explode('[', str_replace(']', '', $name)) as $bit) $nameOut .= "[$bit]";
		return $nameOut;
	}

	/**
	 * Javascript conditionnal element toggles (conditionnal show/hide).
	 *
	 * @param Object $element: the form element you want to check the toggle attributes for.
	 * @return String: the html output of the toggle attributes.
	 */
	private function getToggle($element)
	{
		$toggle = '';
		if (isset($element->options->toggle))
		{
			list($toggle, $condition) = explode('(', trim($element->options->toggle, ')'));
			$toggle = substr($toggle, 0, -2);
			list($leftHand, $rightHand) = explode('=', $condition);
			$leftHand = "{$this->id}".$this->convertName2subname($leftHand);
			$toggleEffect = $toggle && isset($element->options->toggleEffect) ? " data-toggle-effect=\"{$element->options->toggleEffect}\"" : '';
			$toggle = " data-toggle=\"$toggle\" data-toggle-cond=\"$leftHand=$rightHand\"$toggleEffect";
		}
		return $toggle;
	}

	/**
	 * getElementIndexByName.
	 * First fill the elementIndexesByName private class var array,
	 * then returns the index of the found element to work on it or false if unfound.
	 *
	 * @see $this->elementIndexesByName.
	 * @param string $name: the name of the element you want to retrieve.
	 * @return int/false: the index of the found element or false if unfound.
	 */
	private function getElementIndexByName($name)
	{
		// First time the function is called, generate an array of [elementName => index] pairs.
		// Loop through all the element that have a set name (html attr).
		if (!count($this->elementIndexesByName)) foreach ($this->elements as $k => $element)
		{
			if (isset($element->attributes->name)) $this->elementIndexesByName[$element->attributes->name] = $k;
		}

		return isset($this->elementIndexesByName[$name]) ? $this->elementIndexesByName[$name] : false;
	}

	/**
	 * getElementByName returns the found element to work on it or false if unfound.
	 *
	 * @param  string $name: the name of the element you want to retrieve.
	 * @return object/false: the object of the found element or false if unfound.
	 */
	private function getElementByName($name)
	{
		return $this->elements[$this->getElementIndexByName($name)];
	}
}
?>