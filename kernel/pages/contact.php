<?php
//======================= VARS ========================//
//=====================================================//


//===================== INCLUDES ======================//
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
$form = new Form(['class' => 'contact-form']);
$form->addElement('header', [], ['level' => 2, 'text' => text(29)]);
$form->addElement('text',
                  ['name'        => 'lastName',
                   'value'       => '',
                   'placeholder' => text(26),
                   'required'    => 'required',
                   'pattern'     => '[a-zéäëïöüàèìòùâêîôûçñœæ A-ZÉÄËÏÖÜÀÈÌÒÙÂÊÎÔÛÇÑŒÆ\'-]+'],
                  ['validation'  => ['required', 'alpha+'], 'label' =>  text(26)]);
$form->addElement('text',
                  ['name'        => 'firstName',
                   'placeholder' => text(27),
                   'required'    => 'required',
                   'pattern'     => '[a-zéäëïöüàèìòùâêîôûçñœæ A-ZÉÄËÏÖÜÀÈÌÒÙÂÊÎÔÛÇÑŒÆ\'-]+'],
                  ['validation'  => ['required', 'alpha+'], 'label' =>  text(27)]);
$form->addElement('email',
                  ['name'        => 'email',
                   'placeholder' => text(28),
                   'required'    => 'required'],
                  ['validation'  => 'required', 'label' => text(28)]);
$form->addElement('textarea',
                  ['name'        => 'message',
                   'placeholder' => text(30),
                   'cols'        => 50,
                   'rows'        => 10,
                   'required'    => 'required',
                   'pattern'     => '[^<>(){}\[\]\\]+'],
                  ['validation'  => ['required', 'alphanum+'],
                   'label'       => text(25)]);
$form->addRobotCheck(text(95));
$form->addButton('cancel', text(17));
$form->addButton('validate', text(18));

$form->validate('validateContact');
$page->setContent($form->render())->render();
//============================================ end of MAIN =============================================//
//======================================================================================================//



//======================================================================================================//
//=========================================== FUNCTIONS ================================================//
function validateContact()
{
	if (!Userdata::is_set_any()) return false;// If no posted data do not go further.

	$gets = Userdata::get();
	if (isset($gets->captcha) && $gets->captcha === 'passed')
	{
		$object = new StdClass();
		$object->error = 0;
		$object->message = '';
		$patterns = array('message' => '[^<>(){}\[\]\\\]+',
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

			$sent = mail('disadb@gmail.com', textf(33, $settings->siteUrl, $gets->firstname, $gets->lastname), $message, $headers);
			$object->error = !$sent;
			$object->message = text($sent? 34 : 35);
		}

		die(json_encode($object));
	}
}
//========================================== end of FUNCTIONS ==========================================//
//======================================================================================================//
?>