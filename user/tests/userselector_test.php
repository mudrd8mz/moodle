<?php
// This file is part of Moodle - https://moodle.org/
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
 * Provides {@link core_user_selector_testcase} class.
 *
 * @package     core_user
 * @category    test
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/user/selector/lib.php');
require_once($CFG->dirroot.'/user/tests/fixtures/testable_user_selector.php');

/**
 * Tests for the implementation of {@link user_selector_base} class.
 *
 * @copyright 2018 David Mudrák <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_user_selector_testcase extends advanced_testcase {

    /** @var stdClass Student user record. */
    protected $student;

    /** @var stdClass Teacher user record. */
    protected $teacher;

    /** @var stdClass Manager user record. */
    protected $manager;

    /** @var stdClass Test course. */
    protected $course;

    /** @var context Test course context. */
    protected $coursecontext;

    /** @var stdClass Student role record. */
    protected $studentrole;

    /** @var stdClass Teacher role record. */
    protected $teacherrole;

    /** @var stdClass Manager role record. */
    protected $managerrole;

    /**
     * Reset the environment for the tests.
     */
    protected function setUp() {
        global $CFG, $DB;

        $CFG->showuseridentity = 'idnumber,country,city';
        $CFG->hiddenuserfields = 'country,city';

        $this->student = $this->getDataGenerator()->create_user();
        $this->teacher = $this->getDataGenerator()->create_user();
        $this->manager = $this->getDataGenerator()->create_user();

        $this->course = $this->getDataGenerator()->create_course();
        $this->coursecontext = context_course::instance($this->course->id);

        $this->teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $this->studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->managerrole = $DB->get_record('role', array('shortname' => 'manager'));

        role_assign($this->studentrole->id, $this->student->id, $this->coursecontext->id);
        role_assign($this->teacherrole->id, $this->teacher->id, $this->coursecontext->id);
        role_assign($this->managerrole->id, $this->manager->id, SYSCONTEXTID);
    }

    /**
     * No identity fields are not shown to student user (no permission to view identity fields).
     */
    public function test_hidden_siteidentity_fields_no_access() {

        $this->resetAfterTest();
        $this->setUser($this->student);

        $selector = new testable_user_selector('test');

        foreach ($selector->find_users('') as $found) {
            foreach ($found as $user) {
                $this->assertObjectNotHasAttribute('idnumber', $user);
                $this->assertObjectNotHasAttribute('country', $user);
                $this->assertObjectNotHasAttribute('city', $user);
            }
        }
    }

    /**
     * Teacher can see students' identity fields only within the course.
     */
    public function test_hidden_siteidentity_fields_course_only_access() {

        $this->resetAfterTest();
        $this->setUser($this->teacher);

        $systemselector = new testable_user_selector('test');
        $courseselector = new testable_user_selector('test', ['accesscontext' => $this->coursecontext]);

        foreach ($systemselector->find_users('') as $found) {
            foreach ($found as $user) {
                $this->assertObjectNotHasAttribute('idnumber', $user);
                $this->assertObjectNotHasAttribute('country', $user);
                $this->assertObjectNotHasAttribute('city', $user);
            }
        }

        foreach ($courseselector->find_users('') as $found) {
            foreach ($found as $user) {
                $this->assertObjectHasAttribute('idnumber', $user);
                $this->assertObjectHasAttribute('country', $user);
                $this->assertObjectHasAttribute('city', $user);
            }
        }
    }

    /**
     * Teacher can be prevented from seeing students' identity fields even within the course.
     */
    public function test_hidden_siteidentity_fields_course_prevented_access() {

        $this->resetAfterTest();
        $this->setUser($this->teacher);

        assign_capability('moodle/course:viewhiddenuserfields', CAP_PREVENT, $this->teacherrole->id, $this->coursecontext->id);

        $courseselector = new testable_user_selector('test', ['accesscontext' => $this->coursecontext]);

        foreach ($courseselector->find_users('') as $found) {
            foreach ($found as $user) {
                $this->assertObjectHasAttribute('idnumber', $user);
                $this->assertObjectNotHasAttribute('country', $user);
                $this->assertObjectNotHasAttribute('city', $user);
            }
        }
    }

    /**
     * Manager can see students' identity fields anywhere.
     */
    public function test_hidden_siteidentity_fields_anywhere_access() {

        $this->resetAfterTest();
        $this->setUser($this->manager);

        $systemselector = new testable_user_selector('test');
        $courseselector = new testable_user_selector('test', ['accesscontext' => $this->coursecontext]);

        foreach ($systemselector->find_users('') as $found) {
            foreach ($found as $user) {
                $this->assertObjectHasAttribute('idnumber', $user);
                $this->assertObjectHasAttribute('country', $user);
                $this->assertObjectHasAttribute('city', $user);
            }
        }

        foreach ($courseselector->find_users('') as $found) {
            foreach ($found as $user) {
                $this->assertObjectHasAttribute('idnumber', $user);
                $this->assertObjectHasAttribute('country', $user);
                $this->assertObjectHasAttribute('city', $user);
            }
        }
    }

    /**
     * Manager can be prevented from seeing hidden fields outside the course.
     */
    public function test_hidden_siteidentity_fields_schismatic_access() {

        $this->resetAfterTest();
        $this->setUser($this->manager);

        // Revoke the capability to see hidden user fields outside the course.
        // Note that inside the course, the manager can still see the hidden identifiers as this is currently
        // controlled by a separate capability for legacy reasons. This is counter-intuitive behaviour and is
        // likely to be fixed in MDL-51630.
        assign_capability('moodle/user:viewhiddendetails', CAP_PREVENT, $this->managerrole->id, SYSCONTEXTID, true);

        $systemselector = new testable_user_selector('test');
        $courseselector = new testable_user_selector('test', ['accesscontext' => $this->coursecontext]);

        foreach ($systemselector->find_users('') as $found) {
            foreach ($found as $user) {
                $this->assertObjectHasAttribute('idnumber', $user);
                $this->assertObjectNotHasAttribute('country', $user);
                $this->assertObjectNotHasAttribute('city', $user);
            }
        }

        foreach ($courseselector->find_users('') as $found) {
            foreach ($found as $user) {
                $this->assertObjectHasAttribute('idnumber', $user);
                $this->assertObjectHasAttribute('country', $user);
                $this->assertObjectHasAttribute('city', $user);
            }
        }
    }

    /**
     * Two capabilities must be currently set to prevent manager from seeing hidden fields.
     */
    public function test_hidden_siteidentity_fields_hard_to_prevent_access() {

        $this->resetAfterTest();
        $this->setUser($this->manager);

        assign_capability('moodle/user:viewhiddendetails', CAP_PREVENT, $this->managerrole->id, SYSCONTEXTID, true);
        assign_capability('moodle/course:viewhiddenuserfields', CAP_PREVENT, $this->managerrole->id, SYSCONTEXTID, true);

        $systemselector = new testable_user_selector('test');
        $courseselector = new testable_user_selector('test', ['accesscontext' => $this->coursecontext]);

        foreach ($systemselector->find_users('') as $found) {
            foreach ($found as $user) {
                $this->assertObjectHasAttribute('idnumber', $user);
                $this->assertObjectNotHasAttribute('country', $user);
                $this->assertObjectNotHasAttribute('city', $user);
            }
        }

        foreach ($courseselector->find_users('') as $found) {
            foreach ($found as $user) {
                $this->assertObjectHasAttribute('idnumber', $user);
                $this->assertObjectNotHasAttribute('country', $user);
                $this->assertObjectNotHasAttribute('city', $user);
            }
        }
    }

    /**
     * For legacy reasons, user selectors supported ability to override $CFG->showuseridentity.
     *
     * However, this was found as violating the principle of respecting site privacy settings. So the feature has been
     * dropped in Moodle 3.6.
     */
    public function test_hidden_siteidentity_fields_explicit_extrafields() {

        $this->resetAfterTest();
        $this->setUser($this->manager);

        $implicitselector = new testable_user_selector('test');
        $explicitselector = new testable_user_selector('test', ['extrafields' => ['email', 'department']]);

        foreach ($implicitselector->find_users('') as $found) {
            foreach ($found as $user) {
                $this->assertObjectHasAttribute('idnumber', $user);
                $this->assertObjectHasAttribute('country', $user);
                $this->assertObjectHasAttribute('city', $user);
                $this->assertObjectNotHasAttribute('email', $user);
                $this->assertObjectNotHasAttribute('department', $user);
            }
        }

        foreach ($explicitselector->find_users('') as $found) {
            foreach ($found as $user) {
                $this->assertObjectNotHasAttribute('idnumber', $user);
                $this->assertObjectNotHasAttribute('country', $user);
                $this->assertObjectNotHasAttribute('city', $user);
                $this->assertObjectHasAttribute('email', $user);
                $this->assertObjectHasAttribute('department', $user);
            }
        }
    }
}
