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
 * Gradebook automatically links to this page to review all grades for the module or
 * to review grades for a particular user in the module.
 *
 * @package    mod
 * @subpackage workshop
 * @copyright  2011 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

$id         = optional_param('id', 0, PARAM_INT); // course_module ID, or
$w          = optional_param('w', 0, PARAM_INT);  // workshop instance ID
$userid     = optional_param('userid', 0, PARAM_INT);  // user id

if ($id) {
    $cm         = get_coursemodule_from_id('workshop', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $workshop   = $DB->get_record('workshop', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $workshop   = $DB->get_record('workshop', array('id' => $w), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $workshop->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('workshop', $workshop->id, $course->id, false, MUST_EXIST);
}
$PAGE->set_url('/mod/workshop/grade.php', array('id'=>$cm->id));

require_login($course, false, $cm);

if (has_capability('mod/workshop:viewallassessments', get_context_instance(CONTEXT_MODULE, $cm->id))) {
    if ($userid) {
        redirect('submission.php?cmid='.$cm->id.'&userid='.$userid);
    } else {
        redirect('view.php?id='.$cm->id);
    }
} else {
    // user will view his own submission, parameter $userid is ignored
    redirect('view.php?id='.$cm->id);
}