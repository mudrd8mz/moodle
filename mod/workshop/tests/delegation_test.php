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
 * Provides the mod_workshop_delegation_testcase
 *
 * @package     mod_workshop
 * @subpackage  delegation
 * @category    phpunit
 * @copyright   2015 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/workshop/locallib.php');
require_once(__DIR__ . '/fixtures/testable.php');


/**
 * Test cases for the workshop delegation functionality
 */
class mod_workshop_delegation_testcase extends advanced_testcase {

    /** workshop instance emulation */
    protected $workshop;

    /** setup testing environment */
    protected function setUp() {
        parent::setUp();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $workshop = $this->getDataGenerator()->create_module('workshop', array('course' => $course));
        $cm = get_coursemodule_from_instance('workshop', $workshop->id, $course->id, false, MUST_EXIST);
        $this->workshop = new testable_workshop($workshop, $cm, $course);
    }

    protected function tearDown() {
        $this->workshop = null;
        parent::tearDown();
    }

    /**
     * Test basic mechanics of the method call delegations.
     */
    public function test_basic_mechanics() {
        $this->resetAfterTest(true);

        // The method 'sometimes' is not implemented by any subplugin.
        // Nothing happens - just make sure the test goes on.
        $this->assertFalse(method_exists('testable_workshopsubplugin_delegate_foo', 'sometimes'));
        $this->assertFalse(method_exists('testable_workshopsubplugin_delegate_bar', 'sometimes'));
        $this->workshop->delegate->sometimes();

        // The method 'somewhere' is implemented by one subplugin.
        // Make sure it is delegated to it.
        $this->assertFalse(method_exists('testable_workshopsubplugin_delegate_foo', 'somewhere'));
        $this->assertTrue(method_exists('testable_workshopsubplugin_delegate_bar', 'somewhere'));
        $a = (object)array('results' => array());
        $this->workshop->delegate->somewhere($a);
        $this->assertEquals(1, count($a->results));
        $this->assertTrue(in_array('bar', $a->results));

        // The method 'somehow' is implemented by two subplugins.
        // Make sure it is delegated to both.
        $this->assertTrue(method_exists('testable_workshopsubplugin_delegate_foo', 'somehow'));
        $this->assertTrue(method_exists('testable_workshopsubplugin_delegate_bar', 'somehow'));
        $b = (object)array('results' => array());
        $this->workshop->delegate->somehow($b);
        $this->assertEquals(2, count($b->results));
        $this->assertTrue(in_array('foo', $b->results));
        $this->assertTrue(in_array('bar', $b->results));
    }

    /**
     * Test that passing parameters to delegated methods works.
     */
    public function test_parameters() {
        $this->resetAfterTest(true);

        $a = (object)array('workshopname' => 'Initial', 'crosscheck' => 0, 'results' => array());
        $b = 42;

        $this->workshop->delegate->something($a, $b);

        // Make sure the delegate had access to the workshop instance.
        $this->assertTrue($a->workshopname === $this->workshop->name);
        // Make sure the delegate had both parameters passed correctly.
        $this->assertTrue($a->crosscheck == $b);
        // Make sure it was delegated to the only subplugin that implements the 'something' method.
        $this->assertEquals(1, count($a->results));
        $this->assertTrue(in_array('foo', $a->results));
    }
}
