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
 * @package    mod_exam
 * @subpackage backup-moodle2
 * @copyright 2014 Pinky Sharma
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_exam_activity_task
 */

/**
 * Structure step to restore one exam activity
 */
class restore_exam_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('exam', '/activity/exam');
        if ($userinfo) {
            $paths[] = new restore_path_element('exam_grade', '/activity/exam/grades/grade');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_exam($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $oldcourseid = $data->course;//save original course id
        $oldquizid = $data->quizid; //save original quiz id
        $data->course = $this->get_courseid();
        
        // In same couse mapped quiz id missing
        // This is for course restore
        $data->quizid = $this->get_mappingid('quiz', $data->quizid); 
        // Workround for module restor in same course
        // In other course quizid blank if dependent quiz not restored
        if(empty($data->quizid) && ($oldcourseid==$data->course)){
            $data->quizid = $oldquizid;
        }

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // insert the exam record
        $newitemid = $DB->insert_record('exam', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
        
    }


    protected function process_exam_grade($data) {
        global $DB;

        $data = (object)$data;

        $data->examid = $this->get_new_parentid('exam');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->attempttime = $this->apply_date_offset($data->attempttime);

        $newitemid = $DB->insert_record('exam_grades', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder)
    }

    protected function after_execute() {
        // Add exam related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_exam', 'intro', null);
    }
}
