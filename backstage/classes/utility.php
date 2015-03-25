<?php
/**
 * Design pattern: -
 */
Class Utility
{

	/**
	 * Class constructor
	 */
	private function __construct()
	{
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
	function dateFormat($timestmp= null, $format= '')
	{
		global $timestamp, $language, $months, $days;

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
	}
	function dateFormatCallback($matches)
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
	}


	/*
		generates an alphabetic index
		TODO:
		if showDigits=1 displays digits links such as '... X Y Z 0-9'
		TODO: use parse_str and http_build_query
	*/
	function alphaIndex($showDigits= 0)
	{
		$alphaIndex= '';
		define(URI, QUERY_STRING?preg_replace("/(?:&|&amp;)*index=\w/i",'',URI):URI);
		$alphabet= str_split('abcdefghijklmnopqrstuvwxyz');
		foreach($alphabet as $index)
			$alphaIndex.= '<a href="'.url(URI.(QUERY_STRING?'&amp;':'?')."index=$index")."\">$index</a>";
		return '<div><div id="indexAlphaWrapper"><div id="indexAlpha">'.$alphaIndex.'</div></div><br class="clear" /></div>';
	}

	/*
		$totalItems is the $mysqli->num_rows of the query displaying all items.
		returns a div containing all the links to available pages
		For DB limits (according to current page) use $DBlimits.
	*/
	function pagination($totalItems, $itemsPerPage)
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
	}
	/*
		paginanation with '...' to skip pages when too many
		//TODO: merge with the above pagination function
	*/
	function pagination2($totalItems, $itemsPerPage)
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
	}
}
?>