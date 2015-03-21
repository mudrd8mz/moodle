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
 * Defines the mod_workshop_delegator class
 *
 * @package     mod_workshop
 * @subpackage  delegation
 * @copyright   2015 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Implements the $workshop->delegate functionality
 *
 * Calls to methods in this class are delegated to the workshop subplugins that
 * define them. See {@link self::register_delegates()} for more info on how
 * subplugins are supposed to use this feature.
 *
 * @copyright 2015 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_workshop_delegator {

    /** @var workshop */
    protected $workshop;

    /** @var null|array of {@link mod_workshop_delegate} subclasses */
    protected $delegates = null;

    /**
     * Called at view.php right before the output starts
     *
     * @param moodle_page $page the current page
     */
    public function view_page_init(moodle_page $page) {
        $this->delegate('view_page_init', array($page));
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
        $this->delegate('view_page_end');
    }

    /**
     * Called at submission.php right before the output starts
     *
     * @param moodle_page $page the current page
     * @param stdClass $submission the submission record
     */
    public function submission_page_init(moodle_page $page, $submission) {
        $this->delegate('submission_page_init', array($page, $submission));
    }

    /**
     * Called at top of submission.php
     *
     * @param stdClass $submission
     */
    public function submission_page_start($submission) {
        $this->delegate('submission_page_start', array($submission));
    }

    /**
     * Called at submission.php right before the page footer is displayed
     *
     * @param stdClass $submission
     */
    public function submission_page_end($submission) {
        $this->delegate('submission_page_end', array($submission));
    }

    /**
     * Called at submission.php right before rendering the given workshop assessment
     *
     * @param stdClass $submission the assessed submission
     * @param workshop_assessment $assessment as returned by {@link workshop::prepare_assessment()}
     */
    public function submission_prepare_assessment($submission, workshop_assessment $assessment) {
        $this->delegate('submission_prepare_assessment', array($submission, $assessment));
    }

    /**
     * Called by workshop_submission_form::definition()
     *
     * @param workshop_submission_form $form
     * @param MoodleQuickForm $mform the underlying quick form instance
     * @param array $customdata custom data passed to the form
     */
    public function submission_form_definition(workshop_submission_form $form, MoodleQuickForm $mform, array $customdata) {
        $this->delegate('submission_form_definition', array($form, $mform, $customdata));
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
        $this->delegate('submission_form_validation', array($handler, $data, $files));
    }

    /**
     * Called at submission.php after the submission record is updated in the database
     *
     * @param stdClass $data the submission form data
     */
    public function submission_form_process($data) {
        $this->delegate('submission_form_process', array($data));
    }

    /**
     * Called at assessment.php right before the output starts
     *
     * @param moodle_page $page the current page
     * @param stdClass $assessment the displayed assessment's record
     * @param stdClass $submission the displayed assessed submission's record
     */
    public function assessment_page_init(moodle_page $page, $assessment, $submission) {
        $this->delegate('assessment_page_init', array($page, $assessment, $submission));
    }

    /**
     * Called at assessment.php right after the workshop title heading
     *
     * @param stdClass $assessment the displayed assessment's record
     * @param stdClass $submission the displayed assessed submission's record
     */
    public function assessment_page_start($assessment, $submission) {
        $this->delegate('assessment_page_start', array($assessment, $submission));
    }

    /**
     * Called at assessment.php right before the footer is echoed
     *
     * @param stdClass $assessment the displayed assessment's record
     * @param stdClass $submission the displayed assessed submission's record
     */
    public function assessment_page_end($assessment, $submission) {
        $this->delegate('assessment_page_end', array($assessment, $submission));
    }

    /**
     * Instantiate the delegator
     *
     * This is supposed to be use from the {@link workshop} class constructor
     * only.
     *
     * @param workshop $workshop the workshop instance to attach to
     */
    public function __construct(workshop $workshop) {
        $this->workshop = $workshop;
    }

    /**
     * Register all delegates provided by workshop subplugins
     *
     * To implement the workshop delegation feature, the subplugin has to
     * define class <workshopsubtype>_<subname>_delegate, typically in a file
     * classes/delegate.php. Additionally, this class has to be a subclass of
     * the {@link mod_workshop_delegate} class.
     */
    protected function register_delegates() {

        $this->delegates = array();

        foreach (core_component::get_subplugins('mod_workshop') as $subplugintype => $unused) {
            $candidates = core_component::get_plugin_list_with_class($subplugintype, 'delegate');

            foreach ($candidates as $subplugin => $class) {
                $parents = class_parents($class);

                if (isset($parents['mod_workshop_delegate'])) {
                    $this->delegates[] = new $class($this->workshop);
                } else {
                    debugging('The class '.$class.' is supposed to be a subclass of the mod_workshop_delegate', DEBUG_DEVELOPER);
                }
            }
        }
    }

    /**
     * Delegates (dispatches) the given method to registered subplugins
     *
     * @param string $methodname the called method name
     * @param array $params optional parameters to be passed
     */
    protected function delegate($methodname, array $params = array()) {

        if ($this->delegates === null) {
            $this->register_delegates();
        }

        foreach ($this->delegates as $delegate) {
            if (method_exists($delegate, $methodname)) {
                call_user_func_array(array($delegate, $methodname), $params);
            }
        }
    }
}
