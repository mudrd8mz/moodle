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
 * Defines the mod_workshop_delegate class
 *
 * @package     mod_workshop
 * @subpackage  delegation
 * @copyright   2015 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Base class for all workshop delegates
 *
 * A delegate is a class provided by the workshop subplugin. Subplugins can
 * affect various parts of the workshop interface and functionality by
 * implementing the corresponding delegated call.
 *
 * @copyright 2015 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_workshop_delegate {

    /** @var workshop */
    protected $workshop;

    /**
     * Instantiate the delegate
     *
     * @param workshop $workshop the workshop instance to attach to
     */
    public function __construct(workshop $workshop) {
        $this->workshop = $workshop;
    }

    /**
     * Called at view.php right before the output starts
     *
     * @param moodle_page $page the current page
     */
    public function view_page_init(moodle_page $page) {
    }

    /**
     * Called at view.php right after the workshop heading is printed
     */
    public function view_page_start() {
        $this->delegate('view_page_start');
    }

    /**
     * Called at view.php right before the page footer
     */
    public function view_page_end() {
    }

    /**
     * Called at submission.php right before the output starts
     *
     * @param moodle_page $page the current page
     * @param stdClass $submission the submission record
     */
    public function submission_page_init(moodle_page $page, $submission) {
    }

    /**
     * Called at top of submission.php
     *
     * @param stdClass $submission
     */
    public function submission_page_start($submission) {
    }

    /**
     * Called at submission.php right before the page footer is displayed
     *
     * @param stdClass $submission
     */
    public function submission_page_end($submission) {
    }

    /**
     * Called at submission.php right before rendering the given workshop assessment
     *
     * @param stdClass $submission the assessed submission
     * @param workshop_assessment $assessment as returned by {@link workshop::prepare_assessment()}
     */
    public function submission_prepare_assessment($submission, workshop_assessment $assessment) {
    }

    /**
     * Called by workshop_submission_form::definition()
     *
     * @param workshop_submission_form $form
     * @param MoodleQuickForm $mform the underlying quick form instance
     * @param array $customdata custom data passed to the form
     */
    public function submission_form_definition(workshop_submission_form $form, MoodleQuickForm $mform, array $customdata) {
    }

    /**
     * Called by workshop_submission_form::validation()
     *
     * If the validation for a field 'foobar' should fail, set the value
     * $handler->errors['foobar'] to the descriptive validation error.
     *
     * @param stdClass $handler used to pass the validation errors back
     * @param array $data the submitted form data
     * @param array $files the submitted form files
     */
    public function submission_form_validation(stdClass $handler, array $data, array $files) {
    }

    /**
     * Called at submission.php after the submission record is updated in the database
     *
     * @param stdClass $data the submission form data
     */
    public function submission_form_process($data) {
    }

    /**
     * Called at assessment.php right before the output starts
     *
     * @param moodle_page $page the current page
     * @param stdClass $assessment the displayed assessment's record
     * @param stdClass $submission the displayed assessed submission's record
     */
    public function assessment_page_init(moodle_page $page, $assessment, $submission) {
    }

    /**
     * Called at assessment.php right after the workshop title heading
     *
     * @param stdClass $assessment the displayed assessment's record
     * @param stdClass $submission the displayed assessed submission's record
     */
    public function assessment_page_start($assessment, $submission) {
    }

    /**
     * Called at assessment.php right before the footer is echoed
     *
     * @param stdClass $assessment the displayed assessment's record
     * @param stdClass $submission the displayed assessed submission's record
     */
    public function assessment_page_end($assessment, $submission) {
    }
}
