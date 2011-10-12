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
 * Common classes used by gradingform plugintypes are defined here
 *
 * @package    core
 * @subpackage grading
 * @copyright  2011 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Grading method controller represents a plugin used in a particular area
 */
abstract class gradingform_controller {

    const DEFINITION_STATUS_WORKINPROGRESS  = 0;
    const DEFINITION_STATUS_PRIVATE         = 1;
    const DEFINITION_STATUS_PUBLIC          = 2;

    /** @var stdClass the context */
    protected $context;

    /** @var string the frankenstyle name of the component */
    protected $component;

    /** @var string the name of the gradable area */
    protected $area;

    /** @var int the id of the gradable area record */
    protected $areaid;

    /** @var stdClass|false the definition structure */
    protected $definition;

    /**
     * Do not instantinate this directly, use {@link grading_manager::get_controller()}
     *
     * @param stdClass $context the context of the form
     * @param string $component the frankenstyle name of the component
     * @param string $area the name of the gradable area
     * @param int $areaid the id of the gradable area record
     */
    public function __construct(stdClass $context, $component, $area, $areaid) {
        global $DB;

        $this->context      = $context;
        list($type, $name)  = normalize_component($component);
        $this->component    = $type.'_'.$name;
        $this->area         = $area;
        $this->areaid       = $areaid;

        $this->load_definition();
    }

    /**
     * @return stdClass controller context
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * @return string gradable component name
     */
    public function get_component() {
        return $this->component;
    }

    /**
     * @return string gradable area name
     */
    public function get_area() {
        return $this->area;
    }

    /**
     * @return int gradable area id
     */
    public function get_areaid() {
        return $this->areaid;
    }

    /**
     * Is the grading form defined and released for usage by the given user?
     *
     * @param int $foruserid the id of the user who attempts to work with the form
     * @return boolean
     */
    public function is_form_available($foruserid = null) {
        global $USER;

        if (is_null($foruserid)) {
            $foruserid = $USER->id;
        }

        if (empty($this->definition)) {
            return false;
        }

        if ($this->definition->status == self::DEFINITION_STATUS_PUBLIC) {
            return true;
        }

        if ($this->definition->status == self::DEFINITION_STATUS_PRIVATE) {
            if ($this->definition->usercreated == $foruserid) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extends the module settings navigation
     *
     * This function is called when the context for the page is an activity module with the
     * FEATURE_ADVANCED_GRADING, the user has the permission moodle/grade:managegradingforms
     * and there is an area with the active grading method set to the given plugin.
     *
     * @param settings_navigation $settingsnav {@link settings_navigation}
     * @param navigation_node $node {@link navigation_node}
     */
    public function extend_settings_navigation(settings_navigation $settingsnav, navigation_node $node=null) {
        // do not extend by default
    }

    /**
     * Returns the grading form definition structure
     *
     * @return stdClass|false definition data or false if the form is not defined yet
     */
    public function get_definition() {
        if (is_null($this->definition)) {
            $this->load_definition();
        }
        return $this->definition;
    }

    /**
     * Saves the defintion data into the database
     *
     * The implementation in this base class stores the common data into the record
     * into the {grading_definition} table. The plugins are likely to extend this
     * and save their data into own tables, too.
     *
     * @param stdClass $definition data containing values for the {grading_definition} table
     * @param int|null $usermodified optional userid of the author of the definition, defaults to the current user
     */
    public function update_definition(stdClass $definition, $usermodified = null) {
        global $DB, $USER;

        if (is_null($usermodified)) {
            $usermodified = $USER->id;
        }

        if (!empty($this->definition->id)) {
            // prepare a record to be updated
            $record = new stdClass();
            // populate it with scalar values from the passed definition structure
            foreach ($definition as $prop => $val) {
                if (is_array($val) or is_object($val)) {
                    // probably plugin's data
                    continue;
                }
                $record->{$prop} = $val;
            }
            // make sure we do not override some crucial values by accident
            if (!empty($record->id) and $record->id != $this->definition->id) {
                throw new coding_exception('Attempting to update other definition record.');
            }
            $record->id = $this->definition->id;
            unset($record->areaid);
            unset($record->method);
            unset($record->timecreated);
            // set the modification flags
            $record->timemodified = time();
            $record->usermodified = $usermodified;

            $DB->update_record('grading_definitions', $record);

        } else if ($this->definition === false) {
            // prepare a record to be inserted
            $record = new stdClass();
            // populate it with scalar values from the passed definition structure
            foreach ($definition as $prop => $val) {
                if (is_array($val) or is_object($val)) {
                    // probably plugin's data
                    continue;
                }
                $record->{$prop} = $val;
            }
            // make sure we do not override some crucial values by accident
            if (!empty($record->id)) {
                throw new coding_exception('Attempting to create a new record while there is already one existing.');
            }
            unset($record->id);
            $record->areaid       = $this->areaid;
            $record->method       = $this->get_method_name();
            $record->timecreated  = time();
            $record->usercreated  = $usermodified;
            $record->timemodified = $definition->timecreated;
            $record->usermodified = $definition->usercreated;
            $record->status       = self::DEFINITION_STATUS_WORKINPROGRESS;

            $DB->insert_record('grading_definitions', $definition);

        } else {
            throw new coding_exception('Unknown status of the cached definition record.');
        }
    }

    /**
     * Makes sure there is a form instance for the given rater grading the given item
     *
     * Plugins will probably override/extend this and load additional data of how their
     * forms are filled in one complex query.
     *
     * @todo this might actually become abstract method
     * @param int $raterid
     * @param int $itemid
     * @return stdClass newly created or existing record from {grading_instances}
     */
    public function prepare_instance($raterid, $itemid) {
        global $DB;

        if (empty($this->definition)) {
            throw new coding_exception('Attempting to prepare an instance of non-existing grading form');
        }

        $current = $DB->get_record('grading_instances', array(
            'formid'  => $this->definition->id,
            'raterid' => $raterid,
            'itemid'  => $itemid), '*', IGNORE_MISSING);

        if (empty($current)) {
            $instance = new stdClass();
            $instance->formid = $this->definition->id;
            $instance->raterid = $raterid;
            $instance->itemid = $itemid;
            $instance->timemodified = time();
            $instance->id = $DB->insert_record('grading_instances', $instance);
            return $instance;

        } else {
            return $current;
        }
    }

    /**
     * Saves non-js data and returns the gradebook grade
     */
    abstract public function save_and_get_grade($itemid, $formdata);

    /**
     * Returns html for form element
     */
    abstract public function to_html($gradingformelement);

    /**
     *
     */
    public function default_validation_error_message() {
        return '';
    }

    /**
     *
     */
    public function validate_grading_element($elementvalue, $itemid) {
        return true;
    }

    ////////////////////////////////////////////////////////////////////////////


    /**
     * Loads the form definition if it exists
     *
     * The default implementation just tries to load the record from the {grading_definitions}
     * table. The plugins are likely to override this with a more complex query that loads
     * all required data at once.
     */
    protected function load_definition() {
        global $DB;
        $this->definition = $DB->get_record('grading_definitions', array(
            'areaid' => $this->areaid,
            'method' => $this->get_method_name()), '*', IGNORE_MISSING);
    }

    /**
     * @return string the name of the grading method plugin, eg 'rubric'
     * @see PARAM_PLUGIN
     */
    protected function get_method_name() {
        if (preg_match('/^gradingform_([a-z][a-z0-9_]*[a-z0-9])_controller$/', get_class($this), $matches)) {
            return $matches[1];
        } else {
            throw new coding_exception('Invalid class name');
        }
    }
}
