<?php
//======================= VARS ========================//
//=====================================================//


//===================== INCLUDES ======================//
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
handlePostedData();

$tpl = new Template('.');
$tpl->set_file("$page->page-page", "backstage/templates/$page->page.html");
$tpl->set_var(['SELF' => SELF,
			   'cancelText' => text(17),
			   'sendText' => text(24),
			   'messageText' => text(25),
			   'lastNameText' => text(26),
			   'firstNameText' => text(27),
			   'emailText' => text(28),
			   'introText' => text(29),
			   'messagePlaceholder' => text(30),
			   'activateSwitchText' => text(31),
			   'captchaSrc' => url('images/captcha/'),
			   'captchaText' => text(36)
			  ]);
$content = $tpl->parse('display', "$page->page-page");
//============================================ end of MAIN =============================================//
//======================================================================================================//



//======================================================================================================//
//=========================================== FUNCTIONS ================================================//
function handlePostedData()
{
	global $gets;

	if (!count((array)$gets) && !count((array)$posts)) return false;// If no posted data do not go further.

	if (isset($gets->captcha) && $gets->captcha === 'passed')
	{
		$object = new StdClass();
		$object->error = 0;
		$object->message = '';
		$patterns= array('message' => '[^<>(){}\[\]\\\]+',
						 'email' => '[a-z0-9-_.]+@[a-z0-9-_.]',
						 'firstname' => '[^<>(){}\[\]\\|.,;:?/`\~@#$%^&*()+=0-9\x22]+',// \x22 = '"'
						 'lastname' => '[^<>(){}\[\]\\|.,;:?/`\~!@#$%^&*()+=0-9\x22]+');
		foreach ($patterns as $postName => $pattern)
		{
			if (!preg_match("~$pattern~i", $gets->$postName))
			{
				$object->error = 1;
				$object->message = textf(32, $postName);
				break;
			}
		}

		if (!$object->error)
		{
			$headers = "From: $gets->email\n"
					 ."Reply-To: $gets->email\n"
					 ."Content-Type: text/html; charset=\"utf-8\"\n"
					 ."Content-Transfer-Encoding: 8bit";
		    $message ='<html>
			 				<head>
								<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
							</head>
							<body>
								<table>
									<tr><th>Last name</th><td>'.text($gets->lastname).'</td></tr>
									<tr><th>First name</th><td>'.text($gets->firstname).'</td></tr>
									<tr><th>Email</th><td>'.$gets->email.'</td></tr>
									<tr><th style="vertical-align:top;"><br />Message</th><td><br />'.stripslashes(text($gets->message)).'</td></tr>
								</table>
							</body>
						</html>';

			$sent = mail('antoniandre.web@gmail.com', textf(33, $settings->siteUrl, $gets->firstname, $gets->lastname), $message, $headers);
			$object->error = !$sent;
			$object->message = text($sent? 34 : 35);
		}

		die(json_encode($object));
	}
}
//========================================== end of FUNCTIONS ==========================================//
//======================================================================================================//
?>