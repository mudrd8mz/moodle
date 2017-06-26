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
 * Calendar lib unit tests
 *
 * @package    core_calendar
 * @copyright  2013 Dan Poltawski <dan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/calendar/lib.php');

/**
 * Unit tests for calendar lib
 *
 * @package    core_calendar
 * @copyright  2013 Dan Poltawski <dan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_calendar_lib_testcase extends advanced_testcase {

    protected function setUp() {
        $this->resetAfterTest(true);
    }

    public function test_calendar_get_course_cached() {
        // Setup some test courses.
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        // Load courses into cache.
        $coursecache = null;
        calendar_get_course_cached($coursecache, $course1->id);
        calendar_get_course_cached($coursecache, $course2->id);
        calendar_get_course_cached($coursecache, $course3->id);

        // Verify the cache.
        $this->assertArrayHasKey($course1->id, $coursecache);
        $cachedcourse1 = $coursecache[$course1->id];
        $this->assertEquals($course1->id, $cachedcourse1->id);
        $this->assertEquals($course1->shortname, $cachedcourse1->shortname);
        $this->assertEquals($course1->fullname, $cachedcourse1->fullname);

        $this->assertArrayHasKey($course2->id, $coursecache);
        $cachedcourse2 = $coursecache[$course2->id];
        $this->assertEquals($course2->id, $cachedcourse2->id);
        $this->assertEquals($course2->shortname, $cachedcourse2->shortname);
        $this->assertEquals($course2->fullname, $cachedcourse2->fullname);

        $this->assertArrayHasKey($course3->id, $coursecache);
        $cachedcourse3 = $coursecache[$course3->id];
        $this->assertEquals($course3->id, $cachedcourse3->id);
        $this->assertEquals($course3->shortname, $cachedcourse3->shortname);
        $this->assertEquals($course3->fullname, $cachedcourse3->fullname);
    }

    /**
     * Test calendar cron with a working subscription URL.
     */
    public function test_calendar_cron_working_url() {
        global $CFG;
        require_once($CFG->dirroot . '/lib/cronlib.php');

        // ICal URL from external test repo.
        $subscriptionurl = $this->getExternalTestFileUrl('/ical.ics');

        $subscription = new stdClass();
        $subscription->eventtype = 'site';
        $subscription->name = 'test';
        $subscription->url = $subscriptionurl;
        $subscription->pollinterval = 86400;
        $subscription->lastupdated = 0;
        calendar_add_subscription($subscription);

        $this->expectOutputRegex('/Events imported: .* Events updated:/');
        calendar_cron();
    }

    /**
     * Test calendar cron with a broken subscription URL.
     */
    public function test_calendar_cron_broken_url() {
        global $CFG;
        require_once($CFG->dirroot . '/lib/cronlib.php');

        $subscription = new stdClass();
        $subscription->eventtype = 'site';
        $subscription->name = 'test';
        $subscription->url = 'brokenurl';
        $subscription->pollinterval = 86400;
        $subscription->lastupdated = 0;
        calendar_add_subscription($subscription);

        $this->expectOutputRegex('/Error updating calendar subscription: The given iCal URL is invalid/');
        calendar_cron();
    }

    /**
     * Test the calendar_get_events() function only returns activity
     * events that are enabled.
     */
    public function test_calendar_get_events_with_disabled_module() {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $events = [[
                        'name' => 'Start of assignment',
                        'description' => '',
                        'format' => 1,
                        'courseid' => $course->id,
                        'groupid' => 0,
                        'userid' => 2,
                        'modulename' => 'assign',
                        'instance' => 1,
                        'eventtype' => 'due',
                        'timestart' => time(),
                        'timeduration' => 86400,
                        'visible' => 1
                    ], [
                        'name' => 'Start of lesson',
                        'description' => '',
                        'format' => 1,
                        'courseid' => $course->id,
                        'groupid' => 0,
                        'userid' => 2,
                        'modulename' => 'lesson',
                        'instance' => 1,
                        'eventtype' => 'end',
                        'timestart' => time(),
                        'timeduration' => 86400,
                        'visible' => 1
                    ]
                ];

        foreach ($events as $event) {
            calendar_event::create($event, false);
        }

        $timestart = time() - 60;
        $timeend = time() + 60;

        // Get all events.
        $events = calendar_get_events($timestart, $timeend, true, 0, true);
        $this->assertCount(2, $events);

        // Disable the lesson module.
        $modulerecord = $DB->get_record('modules', ['name' => 'lesson']);
        $modulerecord->visible = 0;
        $DB->update_record('modules', $modulerecord);

        // Check that we only return the assign event.
        $events = calendar_get_events($timestart, $timeend, true, 0, true);
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertEquals('assign', $event->modulename);
    }

    /**
     * Tests for the behaviour of {@link calendar_export_all_day_event_boundaries()}.
     */
    public function test_calendar_export_all_day_event_boundaries() {

        // Prepare the test user.
        $generator = $this->getDataGenerator();
        $user = $generator->create_user(['timezone' => 'Australia/Perth']);

        // Let the user create an event starting on 27 June 2017 at 6am her time (which is 26 June 22:00 UTC).
        $event = (object)[
            'timestart' => make_timestamp(2017, 6, 27, 6, 0, 0, $user->timezone),
            'userid' => $user->id,
        ];

        // Get the start and end date to be exported to the iCal.
        list($start, $end) = calendar_export_all_day_event_boundaries($event);

        // Assert that the all day event starts on 27 June and ends on 28 June.
        $this->assertEquals(20170627, $start);
        $this->assertEquals(20170628, $end);

        // Now let the user create an event starting on 27 June 2017 at 4:30pm her time (27 June 08:30 UTC).
        $event = (object)[
            'timestart' => make_timestamp(2017, 6, 27, 16, 30, 0, $user->timezone),
            'userid' => $user->id,
        ];

        list($start, $end) = calendar_export_all_day_event_boundaries($event);
        $this->assertEquals(20170627, $start);
        $this->assertEquals(20170628, $end);

        // Check that exporting of an event with no known author falls back to interpreting
        // the timestamp according to the server default timezone.
        $this->setTimezone('Europe/Prague');
        $event = (object)[
            'timestart' => make_timestamp(2016, 12, 31, 23, 30, 0, 'Etc/GMT'),
            'userid' => 0,
        ];

        list($start, $end) = calendar_export_all_day_event_boundaries($event);
        $this->assertEquals(20170101, $start);
        $this->assertEquals(20170102, $end);
    }
}
