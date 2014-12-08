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
 * The main exam configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_exam
 * @copyright  2014 Pinky Sharma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/exam/locallib.php');
/**
 * Module instance settings form
 */
class mod_exam_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {

        $mform = $this->_form;
        global $COURSE;
        
        //-------------------------------------------------------------------------------
        // Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('examname', 'exam'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
       
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'examname', 'exam');
        

        // Adding the standard "intro" and "introformat" fields
        $this->add_intro_editor();

        //-------------------------------------------------------------------------------
        // Adding the rest of exam settings, spreeading all them into this fieldset
        // or adding more fieldsets ('header' elements) if needed for better logic
        
        
        $quizoptions = exam_quiz_list();
        if(empty($quizoptions)){
            $quizoptions = "";
            
            //Quiz link
            $strobj = new stdClass();
            $strobj->cid = $COURSE->id;
            $strobj->section =  optional_param('section', 0, PARAM_INT);
            $mform->addElement('static', 'description', "", get_string('noquiz', 'exam', $strobj));    

        }  

        $attempt =0;
        if(!empty($this->_instance)){
        	$attempt = exam_attempted($this->_instance);
        }
        
        if($attempt>0){
            $mform->addElement('select', 'quizid', get_string('selectquiz', 'exam'),$quizoptions,'disabled');
        }else{
            $mform->addElement('select', 'quizid', get_string('selectquiz', 'exam'),$quizoptions);
            $mform->addRule('quizid', get_string('error'), 'required',null, 'client');
        }       
        $mform->addHelpButton('quizid', 'selectquiz', 'exam');
        
       
        // Number of questions on each page
        $questionperpage = array('0' => get_string('neverallononepage', 'quiz'));
        $questionperpage[1] = get_string('everyquestion', 'quiz');
        for ($i = 2; $i <= 50; $i++) {
            $questionperpage[$i] = get_string('everynquestions', 'quiz', $i);
        }
        $mform->addElement('select', 'questionperpage', get_string('questionperpage', 'exam'),
                $questionperpage);
        $mform->setDefault('questionperpage', 10);
        
        
        
        $mform->addElement('header', 'examfieldset', get_string('gradeset', 'exam'));
        // Number of attempts.
        $attemptoptions = array('0' => get_string('unlimited'));
        for ($i = 1; $i <= 10; $i++) {
            $attemptoptions[$i] = $i;
        }
        $mform->addElement('select', 'attempts', get_string('attemptsallowed', 'quiz'),
                $attemptoptions);
        
        // Grading method.
        $gradeoptions = array(1 => "Highest grade", 2 => "Average grade", 3 => "First attempt", 4 => "Last attempt");
        $mform->addElement('select', 'grademethod', get_string('grademethod', 'quiz'),
                $gradeoptions);
        $mform->addHelpButton('grademethod', 'grademethod', 'quiz');
        $mform->setDefault('grademethod', 1);
        $mform->disabledIf('grademethod', 'attempts', 'eq', 1);
        
        // Maximum points.
        $mform->addElement('text', 'grade', get_string('maxpoint', 'exam'));
        $mform->setType('grade', PARAM_INT);
        $mform->setDefault('grade', 100);

        //-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();
        //-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();
    }
    
    
     public function data_preprocessing(&$toform) {
        if (isset($toform['grade'])) {
            // Convert to a real number, so we don't get 0.0000.
            $toform['grade'] = $toform['grade'] + 0;
        }        
    }
}
