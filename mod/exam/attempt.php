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
 * Prints a particular instance of exam
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_exam
 * @copyright  2014 Pinky Sharma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/exam/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // exam instance ID - it should be named as the first character of the module

if ($id) {
    $cm         = get_coursemodule_from_id('exam', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $exam  = $DB->get_record('exam', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $exam  = $DB->get_record('exam', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $exam->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('exam', $exam->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Log this request.
$params = array(
    'objectid' => $exam->id,
    'relateduserid' => $USER->id,
    'context' => $context

);
$event = \mod_exam\event\exam_attempted::create($params);
//$event->add_record_snapshot('exam_grades', $attemptobj->get_attempt());
$event->trigger();


if (!$qzcm = get_coursemodule_from_instance('quiz', $exam->quizid, $course->id)) {
    print_error('invalidcoursemodule');
}
$quizobj = quiz::create($qzcm->instance, $USER->id);

/// Print the page header
$PAGE->set_url('/mod/exam/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($exam->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// If no questions have been set up yet redirect to edit.php or display an error.
if (!$quizobj->has_questions()) {
    if ($quizobj->has_capability('mod/quiz:manage')) {
        redirect($quizobj->edit_url());
    } else {
        print_error('cannotstartnoquestions', 'quiz', $quizobj->view_url());
    }
}
// Create an object to manage all the other (non-roles) access rules.
$timenow = time();
$accessmanager = $quizobj->get_access_manager($timenow);
if ($quizobj->is_preview_user()) {
    $accessmanager->current_attempt_finished();
}

// Check capabilities.
/*if (!$quizobj->is_preview_user()) {
    $quizobj->require_capability('mod/quiz:attempt');
}  */
// Check capabilities.
if(!has_capability('mod/exam:attempt', $context) && !has_capability('mod/exam:preview', $context) ){
    print_error('donothatvepermissiontoattempt', 'exam');
}
// Quiz Json object 
$quizjson = exam_get_examdata($exam,$quizobj);

$PAGE->requires->jquery();
$PAGE->requires->js('/mod/exam/js/slickQuiz.js');

// Output starts here
echo $OUTPUT->header();
echo html_writer::start_tag('div', array('id' => 'slickQuiz'));
    echo html_writer::start_tag('div', array('id' => 'exam_navblock', 'class' => 'navblock'));
        echo html_writer::start_tag('div', array('class' => 'content'));
            echo html_writer::tag('div', "", array('class' => 'qn_buttons multipages'));
        echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
    echo html_writer::tag('h1', '<!-- where the quiz name goes -->', array('class' => 'quizName'));
    echo html_writer::start_tag('div', array('class' => 'quizArea'));
        echo html_writer::start_tag('div', array('class' => 'quizHeader'));
            // Where the quiz main copy goes
            echo html_writer::tag('a', get_string('getstarted', 'exam'), array('class' => 'button startQuiz'));
        echo html_writer::end_tag('div');
        // Where the quiz gets built
    echo html_writer::end_tag('div');
    echo html_writer::start_tag('div', array('class' => 'quizResults'));
        echo html_writer::start_tag('h3', array('class' => 'quizScore'));
            echo get_string('youscored', 'exam');
            echo html_writer::tag('span', '<!-- where the quiz score goes -->');
        echo html_writer::end_tag('h3');
        echo html_writer::start_tag('h3', array('class' => 'quizLevel'));
            echo html_writer::tag('strong', 'Ranking : ');
            echo html_writer::tag('span', '<!-- where the quiz ranking level goes -->');
        echo html_writer::end_tag('h3');
        echo html_writer::start_tag('div', array('class' => 'quizResultsCopy'));
            echo "<!-- where the quiz result copy goes -->";
        echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
echo html_writer::end_tag('div');
// Finish the page
?>
<script>
    var quizJSON = '<?php echo $quizjson;?>';
    //var questionMode = '<?php echo $quizobj->get_quiz()->preferredbehaviour?>' ? '<?php echo $quizobj->get_quiz()->preferredbehaviour?>' : 'deferredfeedback';
    var questionMode = 'deferredfeedback';
    $(function () {
        $('#slickQuiz').slickQuiz({json: quizJSON, questionPerPage : <?php echo $exam->questionperpage;?>, questionMode : questionMode});
    });
</script>
<?php
echo $OUTPUT->footer();
?>


