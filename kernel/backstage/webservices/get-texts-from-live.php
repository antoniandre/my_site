<?php
//======================= VARS ========================//
//=====================================================//


//===================== INCLUDES ======================//
//=====================================================//


//======================================================================================================//
//========================================== FUNCTIONS =================================================//
/**
 * Called by Webservice->consume().
 * This function will be run from the live site on distant server.
 * Put here the action you want the live site to perform with the given data coming from localhost.
 *
 * @param object $data: the data from localhost you want to send to the live site for processing.
 * @return string: a status or info message to send back. You can also die directly here.
 */
function distantCode($data)
{
    $posts = Userdata::get();

    // Fetch texts from DB.
    $db = database::getInstance();
    $q = $db->query();
    $texts = $q->select('texts', '*')->run()->loadObjects();

    header('Content-Type: application/json;charset=utf-8');
    header('Cache-Control: no-cache');
    die(json_encode($texts));
}


/**
 * Called by Webservice->consume().
 * Actions to perform just before webservice consume().
 * You may remove the function or leave it empty if you don't need to send data or perform action prior consume().
 * But if you want to send data to the distant server, the function must return an array like [$data, $method].
 * The data can be anything as it will be json_encoded.
 *
 * @return array: [$data, $method].
 */
function beforeConsume(){}


/**
 * Called by Webservice->consume().
 * Actions to perform just after webservice consume().
 * You may want to use the return data from the distant server accessible through the $data param.
 * The webservice ends at this point.
 *
 * @return void.
 */
function afterConsume($data)
{
    $texts = json_decode($data);
    $db = database::getInstance();
    $q = $db->query();
    $messageType = 'error';
    $message = 'There was an error inserting in database.';

    if (count($texts)) foreach ($texts as $k => $values)
    {
        // Insert in DB with param replace = true and addslashes = true.
        $q->insert('texts', (array)$values, true, true)->run();

        // Save the inserted ID if successful.
        if ($q->info()->insertId) $idList[] = $q->info()->insertId;
    }

    // If all text insertions were successfull say it!
    if (count($idList) === count($texts))
    {
        $messageType = 'success';
        $message = 'All texts were correctly inserted in database! '.implode(', ', $idList);
    }

    new Message($message, $messageType, $messageType, 'header', true);
}
//====================================== END of FUNCTIONS ==============================================//
//======================================================================================================//
?>