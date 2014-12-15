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
 * Provides the unit tests class and some helper classes
 *
 * @package     tool_installaddon
 * @category    test
 * @copyright   2014 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Unit tests for the {@link tool_installaddon_cli_util} class
 *
 * @copyright 2014 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_installaddon_cli_util_testcase extends basic_testcase {

    public function test_temp_dir() {
        $util = new tool_installaddon_cli_util();
        $dir = $util->temp_dir('test');
        $this->assertTrue(is_dir($dir));
        $this->assertTrue(is_writable($dir));
    }

    public function test_plugin_zip_root() {
        $util = new tool_installaddon_cli_util();
        $workdir = $util->temp_dir('foo');
        $zip = dirname(__FILE__).'/fixtures/zips/invalidroot.zip';
        $this->assertEquals('invalid-root', $util->plugin_zip_root($zip, $workdir));

        $workdir = $util->temp_dir('bar');
        $zip = dirname(__FILE__).'/fixtures/zips/bar.zip';
        $this->assertEquals('bar', $util->plugin_zip_root($zip, $workdir));

        $workdir = $util->temp_dir('multi');
        $zip = dirname(__FILE__).'/fixtures/zips/multidir.zip';
        $this->assertSame(false, $util->plugin_zip_root($zip, $workdir));
    }
}
