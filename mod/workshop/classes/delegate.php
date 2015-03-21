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
 * @copyright 2015 David Mudrak <david@moodle.com> @license
 * http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
}
