<?php
//======================= VARS ========================//
$isArchive = isset($_GET['archive']) && $_GET['archive'];
$jsonFile =  ROOT.'backstage/pages/todo-list.json';
$archiveJsonFile =  ROOT.'backstage/pages/todo-list-archive.json';
//=====================================================//


//===================== INCLUDES ======================//
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
$tpl = new Template();
$tpl->set_file("$page->page-page", "templates/$page->page.html");
$tpl->set_block("$page->page-page", 'tableHeader', 'theTableHeader');

$jsonTree = json_decode(file_get_contents($isArchive ? $archiveJsonFile: $jsonFile));
$headers = $jsonTree->headers;
$rows = $jsonTree->rows;
if (isset($_POST['rows']))
{
    $object = new StdClass();
    $object->error = false;
    $object->message = '';
    // JSON file in which will save all the rows that are not supposed to be archived/unarchived
    $file4Rows = $isArchive ? $archiveJsonFile : $jsonFile;

    // In case user clicked on a row to archive/unarchive it
    if (isset($_POST['archive']) && $_POST['archive'])
    {
        // JSON file in which will save the clicked row to archive/unarchive depending if current page is archive or not
        $file4clickedRow = $isArchive ? $jsonFile : $archiveJsonFile;
        // die('saving '.($isArchive? 'unarchived' : 'archived').' row in file '.$file4clickedRow);
        $jsonTree4clickedRow = json_decode(file_get_contents($file4clickedRow));
        if ($row= json_decode($_POST['archive'])) $isArchive ? array_unshift($jsonTree4clickedRow->rows, $row) : array_push($jsonTree4clickedRow->rows, $row);

        $object->error = !file_put_contents($file4clickedRow, json_encode($jsonTree4clickedRow));
        $object->message = $object->error ? 'An error occured while trying to save the file.' : ('This row was '.($isArchive? 'unarchived' : 'archived').' successfully.');

        // If error here, die to not go further to prevent losing the clicked row
        if ($object->error) die(json_encode($object));
    }

    // In all cases, save all the rows that are not supposed to be archived/unarchived
    // die("\n".'Now saving all other rows in file '.$file4Rows);
    $jsonTree->rows = json_decode($_POST['rows']);
    $object->error = !file_put_contents($file4Rows, json_encode($jsonTree));

    // If failed while archiving/unarchiving died before reaching this line,
    // if failed while saving not clicked rows die giving the error message,
    // if succeeded and task was archiving/unarchiving message is retrieved from $object->message set above,
    // if succeeded and task was only saving message will just be 'file saved successfully'.
    $object->message = $object->error ? ('An error occured while trying to save rows in the '.($isArchive ? '' : 'archive ').'file.')
                     : ($object->message ? $object->message : 'The file was saved successfully.');
    die(json_encode($object));
}


// <title>Chiffrages <?php echo $isArchive? 'archivés' : 'en cours' ?\></title>

// Don't affect $headers with possible deletions made on $headersTmp
$headersTmp = $headers;
foreach ($headersTmp as $cellNum => $cell)
{
    // if colspan is defined, skip the following header cell by unsetting it
    if (isset($cell->colspan)) unset($headersTmp[$cellNum+1]);
    if (!isset($headersTmp[$cellNum])) continue;

    $tpl->set_var([
                    'cellWidth' => $cell->width,
                    'cellColspan' => isset($cell->colspan) ? " colspan=\"$cell->colspan\"" : '',
                    'cellIcon' => isset($cell->icon) ? "<span class=\"$cell->icon\"></span><br />" : '',
                    'cellText' => $cell->text
                  ]);
    $tpl->parse('theTableHeader', 'tableHeader', true);
}

$tpl->set_var([
                'archiveClass' => $isArchive ? 'archive' : '',
                'tableHeaderTitle' => 'Taches '.($isArchive ? 'archivées' : 'en cours'),
                'selfLink' => $isArchive ? url('self') : url('self', ['archive' => 1]),
                'seeTasksClass' => $isArchive ? 'return-left' : 'redo',
                'seeTasksText' => 'Taches '.($isArchive ? 'en cours' : 'archivées'),
                'tableRows' => renderTableRows($headers, $rows).renderTableRows($headers, $rows, 3)
              ]);

$content = $tpl->parse('display', "$page->page-page");
//============================================ end of MAIN =============================================//
//======================================================================================================//



//============================================== FUNCTIONS =============================================//
/**
 * @param int $rowType: 1=header, 2=row from json, 3= empty row
 */
function renderTableRows($headers, $rows, $rowType = 2)
{
    global $isArchive;

    $tr = '';
    if (!count($rows))// In case there is no registered row at all
    {
        $rows = array($headers);
        if ($rowType == 2) return '';
    }
    if ($rowType == 3) $rows= array($rows[0]);// Keep only one row to get the template for the last hidden empty row (used for js clone())
    foreach ($rows as $numRow => $row)
    {
        $td = '';
        $profit = 0;// Percentage
        $devtu = 0;// Theoretical
        $devtu_f = 0;// Final
        $completion = 0;

        foreach ($row as $cellNum => $cell)
        {
            $tdClass = isset($headers[$cellNum]->class) ? $headers[$cellNum]->class : '';

            $cell = $rowType== 3? '' : $cell;
            $width = isset($headers[$cellNum]->width) ? ' style="width:'.$headers[$cellNum]->width.'"' : '';

            $td .= "<td class=\"{$headers[$cellNum]->type} $tdClass\" $width><div>";
            if ($tdClass == 'tasks') $td .= renderTaskCell($cell);
            else switch ($headers[$cellNum]->type)
            {
                case 'text':
                    $td .= '<input type="text" value="'.$cell.'"/>';
                    break;
                case 'range':
                    $val = $rowType == 3 ? '' : (int)$cell;
                    $text = $rowType == 3 ? '-' : $cell;
                    $td .= '<input type="range" value="'.$val.'" min="'.$headers[$cellNum]->range->min.'" max="'.$headers[$cellNum]->range->max.'" step="'.$headers[$cellNum]->range->step.'"/><span>'.$text.'</span>';
                    break;
                case 'number':
                    $td.= '<input type="number" min="'.$headers[$cellNum]->number->min.'" value="'.$cell.'" step="'.$headers[$cellNum]->number->step.'"/>';
                    break;
                case 'date':
                    $cell = !$cell && $headers[$cellNum]->default == 'today' ? date('Y-m-d') : $cell;
                    $td .= '<input type="date" value="'.$cell.'"/>';
                    break;
                case 'textarea':
                default:
                    $td .= "<textarea>$cell</textarea>";
                    break;
                case 'select':
                    $options = $headers[$cellNum]->select->options;
                    $select = '';
                    foreach ($options as $val => $opt)
                    {
                        $select .= '<option value="'.$val.'"'.($cell == $val ? ' selected="selected"' : '').'>'.$opt.'</option>';
                    }
                    $td .= "<select>$select</select>";
                    break;
            }
            $td .= '</div></td>';

            if ($tdClass == 'devtu') $devtu = floatval($cell);
            if ($tdClass == 'devtu_f') $devtu_f = floatval($cell);
            elseif ($tdClass == 'completion') $completion = intval($cell);

        }

        if ($completion == 100 && $devtu && $devtu_f) $profit = round((1-$devtu_f/$devtu)*100);

        $tr .= "<tr>
                    <td style=\"width:10px\" class=\"noContent handle".($profit ? ' profit i-tag' : '')."\" data-profit=\"".($profit> 0 ? '+'.$profit : $profit)."%\">
                        <div>
                            <span class=\"handle\"></span>
                            <input type=\"checkbox\" class=\"toggle\"/>
                            <label class=\"i-minus\" title=\"Masquer\"></label>
                            <button class=\"archive i-".($isArchive ? 'unarchive' : 'archive')."\" title=\"".($isArchive? 'Désarchiver' : 'Archiver')."\"/></button>
                        </div>
                    </td>
                    $td
                </tr>";
    }
    if ($rowType == 3) $tr .= "<tr class=\"hidden\">
                                <td style=\"width:10px\" class=\"noContent handle\">
                                    <div>
                                        <span class=\"handle\"></span>
                                        <input type=\"checkbox\" class=\"toggle\"/>
                                        <label class=\"i-minus\" title=\"Masquer\"></label>
                                        <button class=\"archive i-".($isArchive? 'unarchive' : 'archive')."\" title=\"".($isArchive? 'Désarchiver' : 'Archiver')."\"></button>
                                    </div>
                                </td>
                                $td
                            </tr>";
    return $tr;
}

function renderTaskCell($tasks)
{
    if (!$tasks)
    {
        $task = new StdClass();
        $task->done = 0;
        $task->text = '';
        $task->charge = '';
        $tasks = array($task);
    }

    $tasksHTML = '<div class="tasks">';
    foreach ($tasks as $k => $task)
    {
        if (isset($task->subject)) $subject = $task->subject;
        elseif (isset($task->charge)) $tasksHTML .= createTask($task->done, $task->text, $task->charge);
        else $tasksHTML .= createTitle($task->text);
    }
    $tasksHTML .= createTask(null, null, null, 1).'</div>';

    $subjectHTML = '<div class="subject">
                        <input type="checkbox"'.(isset($subject) ? ' checked="checked"' : '').'/>
                        <div>
                            <label class="i-info"></label>
                            <input type="text" value="'.(isset($subject) ? $subject : '').'"/>
                        </div>
                   </div>';

    return $subjectHTML.$tasksHTML;
}

function createTitle($text = '')
{
    return "<div class=\"title\">
                <span class=\"handle\"></span>
                <textarea>$text</textarea>
            </div>";
}

function createTask($done = 0, $text = '', $charge = '', $hidden = 0)
{
    return "<div".($hidden? ' class="hidden"' : '').">
                <span class=\"handle\"></span>
                <input type=\"checkbox\"".($done? ' checked="checked"' : '')." data-value=\"".(float)$done."\"/>
                <label></label>
                <textarea>$text</textarea>
                <input type=\"number\" min=\"0\" step=\"0.1\" value=\"$charge\"/>
            </div>";
}
//=====================================================================================================//
?>