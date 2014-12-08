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
 * This file contains the backup structure for the exam module
 *
 * This is the "graphical" structure of the exam module:
 *
 *                  exam -------------->---------------|--------------
 *               (CL,pk->id)                           |     
 *                     |                               |
 *                     |                         exam_grades   
 *                     |                  (UL, pk->id,fk->examid)    
 *                                        
 **/

/**
 * Define the complete exam structure for backup, with file and id annotations
 */
class backup_exam_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $exam = new backup_nested_element('exam', array('id'), array(
            'course','name', 'quizid', 'intro', 'introformat', 'timecreated',
            'timemodified', 'grade', 'attempts', 'grademethod',
            'marksperquestion', 'questionperpage'));


        // The exam_grades table
        // Grouped by a grades element this is relational to the exam and user.
        $grades = new backup_nested_element('grades');
        $grade = new backup_nested_element('grade', array('id'), array(
            'userid','grade', 'attempttime'));
        


        // Build the tree
        $exam->add_child($grades);
        $grades->add_child($grade);

        // Define sources
        $exam->set_source_table('exam', array('id' => backup::VAR_ACTIVITYID));

        // All the rest of elements only happen if we are including user info
        if ($userinfo) {
            $grade->set_source_table('exam_grades', array('examid'=>backup::VAR_PARENTID));
        }

        $exam->annotate_ids('quiz', 'quizid');
        // Define id annotations
        $grade->annotate_ids('user', 'userid');

        // Define file annotations
        $exam->annotate_files('mod_exam', 'intro', null); // This file area hasn't itemid

        // Return the root element (exam), wrapped into standard activity structure
        return $this->prepare_activity_structure($exam);
    }
}
