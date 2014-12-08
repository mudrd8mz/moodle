<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Redirect from slickQuiz.js with grade
 *
 * @package   mod_exam 
 * @copyright 2014 Pinky Sharma
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/mod/exam/lib.php');

global $DB, $USER;

if($_POST['examid']){
    $examid = $_POST['examid'] ? $_POST['examid']: 0;
    $score = $_POST['score'] ? $_POST['score'] : 0;
    echo $examid ."   ".$score;
    $record = new stdclass();
    $record->examid = $examid;
    $record->userid = $USER->id;
    $record->grade = $score;
    $record->attempttime = time();
    $returnid = $DB->insert_record('exam_grades', $record);

    $exam = $DB->get_record('exam', array('id' => $examid));
    exam_update_grades($exam, $USER->id);
    if($returnid){
        echo "recored saved";
    }else{
        echo "Result not saved";
    }
}
//redirect('view.php?id='.$id);
