<?php

/**
 * If you create a special page type (like 'article') you won't need to use this router.
 */
function themeRouter($url)
{
    $page = null;

    switch(true)
    {
        // Example of routing:
        // en/cocktails/letter/b.html will lead to cocktails.php page letter=d as a get parameter.
        //     case preg_match('~en/cocktails/letter/(.*)~', $url, $m):
        //     case preg_match('~fr/cocktails/lettre/(.*)~', $url, $m):
        //         $page = 'cocktails';
        //         Userdata::setGet('letter', $m[1]);
        //         break;
    }

    return $page;
}

?>