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
 * Internal library of functions for module exam
 *
 * All the newmodule specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_exam
 * @copyright  2014 Pinky Sharma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Get the quizjson object from given
 * exam instance and quiz object
 *
 * @since Moodle 2.7
 * @param object $exam instance of exam
 * @param object $quiz instance of quiz
 * @return stdClass Subscription record from DB
 * @throws moodle_exception for an invalid id
 */
function exam_get_examdata($exam,$quizobj) {
    global $DB;
    $quizjson = array();
    $questions = array();

    $cache = cache::make('mod_exam', 'examdata');
    $quizjson = $cache->get($exam->id);
    if (empty($quizjson)) {
        $quizobj->preload_questions();
        $quizobj->load_questions();
        
       /* $info = array ("exam" => $exam->id, "name" => $quizobj->get_quiz()->name,
        "main" => "Add some heading for your quiz", "results" => $exam->grade);*/
        $info = array ("exam" => $exam->id, "name" => "",
        "main" => "", "results" => $exam->grade);
        foreach ($quizobj->get_questions() as $questiondata) {
            $options = array();
            if ($questiondata->qtype == 'multichoice') {  
                foreach($questiondata->options->answers as $ans) { 
                    $correct = false;
                    if($ans->fraction > 0) { 
                        $correct = true;
                    }
                    $answer = exam_formate_text($questiondata,$ans->answer,$ans->answerformat,'question', 'answer', $ans->id);
                    $options[] = array("option" => $answer, "correct" => $correct);
                }        
                $questiontext = exam_formate_text($questiondata, $questiondata->questiontext, $questiondata->questiontextformat,'question', 'questiontext', $questiondata->id);
                $questions[] = array("q" => $questiontext ,"a" => $options,
                "correct" => $questiondata->options->correctfeedback ? $questiondata->options->correctfeedback : "Your answer is correct.",
                "incorrect" => $questiondata->options->incorrectfeedback ? $questiondata->options->incorrectfeedback: "Your answer is incorrect.");
            }
        }
        $qjson = array("info" => $info, "questions" => $questions);
        $quizjson = addslashes(json_encode($qjson));
        // cache the data.
        $cache->set($exam->id, $quizjson);
    }
    return $quizjson;
}

/*
function exam_user_attempts($examid, $userid = null) {
   global $USER, $CFG ,$DB;
   if(!$userid){
       $userid = $USER->id;
   }
   $attempts= $DB->count_records('exam_grades',array('examid'=>$examid, 'userid'=>$userid));
   return $attempts;
}*/

 function exam_attempted($examid) {
   global $CFG ,$DB;

   $attempts= $DB->count_records('exam_grades',array('examid'=>$examid));
   return $attempts;
}
/**
 * Return list of all quiz
 *
 * @param array $things
 * @return object
 */
function exam_quiz_list(int $courseid=null) {
   global $COURSE, $CFG ,$DB;
   $quizs= $DB->get_records_menu('quiz',array('course'=>$COURSE->id),null,'id,name');
   return $quizs;
}



/**
 * @return array int => lang string the options for calculating the quiz grade
 *      from the individual attempt grades.
 */
/*function quiz_get_grading_options() {
    return array(
        QUIZ_GRADEHIGHEST => get_string('gradehighest', 'quiz'),
        QUIZ_GRADEAVERAGE => get_string('gradeaverage', 'quiz'),
        QUIZ_ATTEMPTFIRST => get_string('attemptfirst', 'quiz'),
        QUIZ_ATTEMPTLAST  => get_string('attemptlast', 'quiz')
    );
}*/
