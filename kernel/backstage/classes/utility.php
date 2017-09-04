<?php
/**
 * Design pattern: singleton.
 *
 * @todo: implement whole class.
 */
Class Utility
{
	private static $instance = null;

	/**
	 * Class constructor.
	 */
	private function __construct()
	{
	}

	/**
	 * Get the only instance of this class.
	 *
	 * @return User object: the only instance of this class.
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance)) self::$instance = new self;
		return self::$instance;
	}

	public static function generateCommentSystem($orderColumn, $orderDirection)
	{
		global $likes;

		$db       = database::getInstance();
		$page     = Page::getCurrent();
		$language = Language::getCurrent();
		$return   = '';

		$tpl = newPageTpl('comments');
		$tpl->set_block($page->page, 'commentBlock', 'theCommentBlock');

		$form = new Form(['class' => 'leaveComment']);
		$form->addElement('paragraph',
						  ['class' => 'intro floatLeft'],
						  ['text' => text(88).' ', 'rowSpan' => 2]);// Leave a comment.
		$form->addElement('radio',
		                  ['name' => 'gender'],
		                  ['validation' => 'required', 'inline' => true, 'options' => ['female' => text(90), 'male' => text(91)], 'rowClass' => 'floatLeft']);
		$form->addElement('textarea',
		                  ['name' => 'comment', 'placeholder' => text(89), 'cols' => 70, 'rows' => 4],
		                  ['validation' => 'required', 'rowClass' => 'clear']);
		$form->addElement('text',
		                  ['name' => 'firstName', 'placeholder' => text(27)],
		                  ['validation' => 'required', 'rowClass' => 'floatLeft'/*, 'rowSpan' => 2*/]);
		/*$form->addElement('email',
		                  ['name' => 'email', 'placeholder' => text('Email : restera invisible sur le site')],
		                  ['validation' => 'required']);*/

        $form->addRobotCheck(text(95));
		$form->addButton('validate', text(18));
		$form->validate(function($result, $form)
		{
			$return = false;
			$db = database::getInstance();
			$q = $db->query();
			$page = Page::getCurrent();
			$comment = $form->getPostedData('comment');

			// Do not perform the insertion in db if page found in DB.
			$q->select('comments', [$q->col('comment'), $q->col('created')]);
			$w = $q->where();
			$w->col('published')->eq(1)
			  ->and($w->col('page')->eq($page->isArticle() ? $page->article->id : $page->id))
			  ->and($w->col('comment')->eq($comment));

			$isInDB = $q->run()->info()->numRows;

			if ($isInDB)
			{
				new Message(nl2br(text(92)), 'info', 'info', 'header');
				$form->unsetPostedData('comment', false);
			}
			else
			{
				$q->insert('users', ['login' => $form->getPostedData('firstName'),
									 'firstName' => $form->getPostedData('firstName'),
									 'email' => $form->getPostedData('email'),
									 'type' => 3,
									 'gender' => $form->getPostedData('gender')]);
				$q->run();
				if ($userId = $q->info()->insertId)
				{
					$q->insert('comments', ['page' => $page->isArticle() ? $page->article->id : $page->id,
											'author' => $userId,
											'comment' => $comment]);
					$q->run();

					if ($q->info()->affectedRows)
					{
						new Message(text(93), 'valid', 'success', 'header');// Your comment was saved successfully.
						$return = true;

						$subject = textf(94, ucfirst($form->getPostedData('firstName')), $page->getTitle());
					    $message = '<html>
						 				<head>
											<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
										</head>
										<body>
											<p>'.$form->getPostedData('firstName').' (email: '.$form->getPostedData('email').') a laissé un commentaire sur l\'article <a href="'.url($page->getUrl()).'">'.$page->getTitle().'</a> :</p>
											<p>'.nl2br(stripslashes($comment)).'</p>
										</body>
									</html>';
						self::mailAdmin($subject, $message);
					}
					else new Message(text(84), 'error', 'error', 'header');// There was a pb.
				}
				else new Message(text(84), 'error', 'error', 'header');// There was a pb.
			}

			return $return;
		});

		$tpl->set_var(['leaveCommentForm' => $form->render()]);

		$q = $db->query();
		$q->select('comments',
				   [$q->colIn('id', 'comments'),
				    $q->col('page'),
				    $q->col("comment"),
				    $q->colIn('created', 'comments'),
				    $q->colIn('firstName', 'users')->as('author'),
				    $q->col('gender'),
				    $q->col('published')])
		  ->relate('comments.author', 'users.id');
		$w = $q->where();
		$w->col('published')->eq(1)->and($w->col('page')->eq($page->isArticle() ? $page->article->id : $page->id));
		if ($orderColumn && $orderDirection) $q->orderBy($orderColumn, $orderDirection);
		$comments = $q->run()
		              ->loadObjects();

		if (count($comments)) foreach ($comments as $k => $comment)
		{
			$created = new DateTime($comment->created);
			$tpl->set_var(['id' => $comment->id,
						   'comment' => nl2br(ucfirst($comment->comment)),
						   'gender' => $comment->gender,
						   'dataLikes' => isset($likes["c$comment->id"]['likes']) ? intval($likes["c$comment->id"]['likes']) : 0,
						   'dataLiked' => isset($likes["c$comment->id"]['liked']) ? intval($likes["c$comment->id"]['liked']) : 0,
						   'createdByOn'=> text(21,
						   					[
						   					    'contexts' => 'article',
						   						'formats' =>
						   						[
						   							'sprintf' =>
						   							[
				   										ucfirst($comment->author),
													  	$created->format($language == 'fr' ? 'd/m/Y' : 'Y-m-d'),
													 	$created->format($language == 'fr' ? 'H\hi' : 'H:i')
													]
												]
											])
						   ]);
			$tpl->parse('theCommentBlock', 'commentBlock', true);
		}

		$return = $tpl->parse('display', $page->page);

		return $return;
	}

	/**
	 * Send a mail to the administrator.
	 *
	 * @param  String $subject: the object of the mail to send.
	 * @param  String $message: the email contents to send.
	 * @param  String $params: to provide extra params if needed.
	 * @return boolean: true if sent correctly false otherwise.
	 */
	public static function mailAdmin($subject, $message, $params = [])
	{
		$settings = Settings::get();

		$headers = "From: $settings->adminEmail\n"
				  ."Reply-To: $settings->adminEmail\n"
				  ."Content-Type: text/html; charset=\"utf-8\"\n"
				  ."Content-Transfer-Encoding: 8bit";

		return mail($settings->adminEmail, "[$settings->siteName] $subject", $message, $headers);
	}

	/**
	 * Send a mail to a user.
	 *
	 * @param  String $email: the user mail.
	 * @param  String $subject: the object of the mail to send.
	 * @param  String $message: the email contents to send.
	 * @param  String $params: to provide extra params if needed.
	 * @return boolean: true if sent correctly false otherwise.
	 */
	public static function mailUser($email, $subject, $message, $params = [])
	{
		$settings = Settings::get();

		$headers = "From: $settings->adminEmail\n"
				  ."Reply-To: $settings->adminEmail\n"
				  ."Content-Type: text/html; charset=\"utf-8\"\n"
				  ."Content-Transfer-Encoding: 8bit";

		$sent = mail($email, $subject, $message, $headers);
	}

	public static function human_filesize($filePath, $decimals = 2) {
	  $sz = 'BKMGTP';
	  $filesize = filesize($filePath);
	  $factor = floor((strlen($filesize) - 1) / 3);
	  return sprintf("%.{$decimals}f", $filesize / pow(1024, $factor)) . @$sz[$factor];
	}

	/*
		Format a date.
		@param (time) $timestamp: the timestamp to convert to date string. use current timestamp if none.
		@param (string) $format: the format to apply to the timestamp. use current language default format if none.
		-> in $format, use "{day}" or {Day} to get the day translated. if no uppercase, keep only 3 letters.
					   use "{month}" or {Month} to get the month translated. if no uppercase, keep only 3 letters.
		@return: the formatted date.

		use: dateFormat(null,'{Day} d {Month} Y');
	*/
	/*function dateFormat($timestmp= null, $format= '')
	{
		global $timestamp, $months, $days;
		$language = Language::getCurrent();

		$timestamp= !$timestmp? time(): $timestmp;

		if (!$format)
		{
			$date= $language!='En'? date('j ',$timestamp).getTexts(utf8_encode(substr($months[date('n',$timestamp)],0,3)))
								  : date('jS M',$timestamp);
			$date= $date.date($language!= 'Fr'?' Y, hia':' Y, H\hi', $timestamp);
		}
		elseif (preg_match('/{(.*?)}/', $format))
		{
			$newDateFormat= preg_replace_callback('/{(.*?)}/', 'dateFormatCallback', $format);
			$date= date($newDateFormat, $timestamp);
		}
		else $date= date($format, $timestamp);

		return $date;
	}*/
	/*function dateFormatCallback($matches)
	{
		global $timestamp;

		$days= array(855,856,857,858,859,860,861);//array of text id in DB
		$months= array('never used',862,863,864,865,866,867,868,869,870,871,872,873);//array of text id in DB (862 to 873)
		switch($matches[1])
		{
			case 'day': $return= substr(getTexts($days[date('w',$timestamp)],0),0,3);break;
			case 'Day': $return= getTexts($days[date('w',$timestamp)]);break;
			case 'month': $return= substr(getTexts($months[date('n',$timestamp)],0),0,3);break;
			case 'Month': $return= getTexts($months[date('n',$timestamp)]);break;
		}

		//backslash each letter to prevent the date function from converting the new day and month
		$return= '\\'.implode('\\',str_split($return));
		//if (ctype_upper($matches[1]{0}))//check if first letter is uppercase

		return $return;
	}*/


	/*
		Generates an alphabetic index.
	*/
	public static function alphaIndex($showDigits = false, $enableLetters= [])
	{
		$page       = Page::getCurrent();
		$urlBase    = str_replace('.html', '', url($page->page));
		$alphaIndex = '';
		$alphabet   = str_split('abcdefghijklmnopqrstuvwxyz');
		if ($showDigits) $alphabet[] = '#0-9';

		foreach($alphabet as $letter)
		{
			if (!count($enableLetters) || array_key_exists($letter, $enableLetters))
                $alphaIndex.= '<a href="' . "$urlBase/" . text(100) . "/$letter.html\">$letter</a>";
			else $alphaIndex .= "<span>$letter</span>";
		}

	    return '<div class="index-alpha">'.$alphaIndex.'</div>';
	}

	/*
		$totalItems is the $mysqli->num_rows of the query displaying all items.
		returns a div containing all the links to available pages
		For DB limits (according to current page) use $DBlimits.
	*/
	/*function pagination($totalItems, $itemsPerPage)
	{
		global $gets;
		$GLOBALS['DBlimits']= $pagination= '';
		$currentPage= isset($gets->page)?$gets->page:1;
		$totalPages= ceil($totalItems/$itemsPerPage);

		if ($totalPages>1)
		{
			$GLOBALS['DBlimits']= 'LIMIT '.($itemsPerPage*($currentPage-1)).",$itemsPerPage";
			$pagination= '<div class="pagination"><span>'.getTexts(773).'&nbsp;</span><div>';
			$vars= $_SERVER['QUERY_STRING']?preg_replace('/(?:^page=[1-9]*&?)|(?:&page=[1-9]*)/i','',$_SERVER['QUERY_STRING']):'';
			$baseUrl= SELF."?$vars";
			for ($i=1;$i<=$totalPages;$i++)
			{
				$page= ($vars?'&amp;':'')."page=$i";
				$pagination.= $currentPage==$i?"<strong>$i</strong> ":('<a href="'.url($baseUrl.$page)."\">$i</a> ");
			}
			$pagination.= '</div></div><br class="clear" />';
		}
		return $pagination;
	}*/

	/*
		paginanation with '...' to skip pages when too many
		//TODO: merge with the above pagination function
	*/
	/*function pagination2($totalItems, $itemsPerPage)
	{
		global $gets;

		$GLOBALS['DBlimits']= $links= $pagination= '';
		$currentPage= isset($gets->page)?$gets->page:1;
		$totalPages= ceil($totalItems/$itemsPerPage);

		if ($totalPages> 1)
		{
			$GLOBALS['DBlimits']= 'LIMIT '.($itemsPerPage*($currentPage-1)).",$itemsPerPage";
			$visibleLinks= array(1,2,3,$currentPage-5,$currentPage-4,$currentPage-3,$currentPage-2,$currentPage-1,
								 $currentPage,$currentPage+1,$currentPage+2,$currentPage+3,$currentPage+4,$currentPage+5,
								 $totalPages-2,$totalPages-1,$totalPages);
			$visibleLinks= array_unique($visibleLinks);
			for ($i=1; $i<=$totalPages; $i++) if (in_array($i,$visibleLinks))
			{
				$links.= $currentPage==$i?"<strong>$i</strong> ":"<a href=\"liste-des-monuments.php?page=$i\">$i</a>"
						.($i<$totalPages && !in_array($i+1,$visibleLinks)?'<strong class="empty">...</strong>':'');
			}
			$pagination= '<div class="pagination">'.implode(' ',$links).'</div><br class="clear" />';
		}
		return $pagination;
	}*/
}
?>