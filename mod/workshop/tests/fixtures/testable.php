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
 * mod_workshop fixtures
 *
 * @package    mod_workshop
 * @category   phpunit
 * @copyright  2014 Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test subclass that makes all the protected methods we want to test public.
 */
class testable_workshop extends workshop {

    public function aggregate_submission_grades_process(array $assessments) {
        parent::aggregate_submission_grades_process($assessments);
    }

    public function aggregate_grading_grades_process(array $assessments, $timegraded = null) {
        parent::aggregate_grading_grades_process($assessments, $timegraded);
    }

    /**
     * Overrides the parent method to use the testable delegator instead of the default one.
     */
    protected function initialize_delegator() {
        $this->delegate = new testable_mod_workshop_delegator($this);
    }
}


/**
 * Testable subclass of the default workshop delegator.
 *
 * Instead of actually searching for workshop subplugins to see if they want to
 * act as delegates, this testable delegator registeres two testable delegate
 * classes to perform tests on.
 *
 * Additionally, it defines four dummy delegatable methods 'something',
 * 'somewhere', 'somehow' and 'sometimes' that are used in the tests.
 *
 * @copyright 2015 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testable_mod_workshop_delegator extends mod_workshop_delegator {

    /**
     * Delegatable method with two parameters.
     *
     * @param stdClass $param1
     * @param int $param2
     */
    public function something($param1, $param2) {
        $this->delegate('something', array($param1, $param2));
    }

    /**
     * Delegatable method with single parameter.
     *
     * @param stdClass $param
     */
    public function somewhere($param) {
        $this->delegate('somewhere', array($param));
    }

    /**
     * Delegatable method with single parameter.
     *
     * @param stdClass $param
     */
    public function somehow($param) {
        $this->delegate('somehow', array($param));
    }

    /**
     * Delegatable method with no parameter.
     */
    public function sometimes() {
        $this->delegate('sometimes');
    }

    /**
     * Overrides the parent method to use fake delegates.
     */
    protected function register_delegates() {
        $this->delegates = array(
            new testable_workshopsubplugin_delegate_foo($this->workshop),
            new testable_workshopsubplugin_delegate_bar($this->workshop),
        );
    }
}


/**
 * Base class for testable fake delegates used in this test.
 *
 * @copyright 2015 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testable_mod_workshop_delegate extends mod_workshop_delegate {

    /**
     * Example delegable method.
     *
     * @param stdClass $param1
     * @param int $param2
     */
    public function something($param1, $param2) {
    }

    /**
     * Example delegable method.
     *
     * @param stdClass $param
     */
    public function somewhere($param) {
    }

    /**
     * Example delegable method.
     *
     * @param stdClass $param
     */
    public function somehow($param) {
    }

    /**
     * Example delegable method.
     */
    public function sometimes() {
    }
}


/**
 * Fake delegate used in the tests.
 *
 * @copyright 2015 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testable_workshopsubplugin_delegate_foo extends testable_mod_workshop_delegate {

    /**
     * Records that we were called.
     *
     * @param stdClass $param
     */
    public function somehow($param) {
        $param->results[] = 'foo';
    }

    /**
     * Records that we were called and that we had access to our workshop and the second parameter.
     *
     * @param stdClass $param1
     * @param int $param2
     */
    public function something($param1, $param2) {
        $param1->workshopname = $this->workshop->name;
        $param1->crosscheck = $param2;
        $param1->results[] = 'foo';
    }
}

/**
 * Fake delegate used in the tests.
 *
 * @copyright 2015 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testable_workshopsubplugin_delegate_bar extends testable_mod_workshop_delegate {

    /**
     * Records that we were called.
     *
     * @param stdClass $param
     */
    public function somewhere($param) {
        $param->results[] = 'bar';
    }

    /**
     * Records that we were called.
     *
     * @param stdClass $param
     */
    public function somehow($param) {
        $param->results[] = 'bar';
    }
}
