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
 * Helper class for the cli/util.php script.
 *
 * @package     tool_installaddon
 * @subpackage  cli
 * @copyright   2014 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class tool_installaddon_cli_util {

    /** @var string */
    protected $jobid;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->jobid = 'cli_'.md5(rand().uniqid('cli_', true));
    }

    /**
     * Write the text to the given output stream.
     *
     * @param string $text text to be printed
     * @param resource $stream the output stream
     */
    public function write($text = '', $stream = STDOUT) {
        fwrite($stream, $text);
    }

    /**
     * Write a line to the given output stream.
     *
     * @param string $text text to be printed
     * @param resource $stream the output stream
     */
    public function writeln($text, $stream = STDOUT) {
        $this->write($text.PHP_EOL, $stream);
    }

    /**
     * Create a temporary directory for this instance.
     *
     * @param string $name the name of the directory
     * @uses exit
     * @return string the full path to created directory
     */
    public function temp_dir($name = 'temp') {
        $dir = make_temp_directory('tool_installaddon/'.$this->jobid.'/'.$name);
        if (empty($dir)) {
            $this->writeln("Unable to create temp directory.", STDERR);
            exit(1);
        }
        return $dir;
    }

    /**
     * Return the name of the root directory in the plugin package.
     *
     * Valid plugin ZIP package has just one root directory with all the files in it.
     * Some packages, such as those downloaded from Github, have their own
     * format for the name of the root directory. It the must be renamed to the
     * plugin name expected by Moodle.
     *
     * @param string $zipfilepath full path to the ZIP package
     * @param string $workdir full path to the temporary working directory we can use
     * @return string|bool the name or false
     */
    public function plugin_zip_root($zipfilepath, $workdir) {
        global $CFG;
        require_once($CFG->libdir.'/filelib.php');

        $fp = get_file_packer('application/zip');
        $files = $fp->list_files($zipfilepath);

        if (empty($files)) {
            return false;
        }

        $rootdirname = null;

        foreach ($files as $file) {
            // Valid plugin ZIP package has just one root directory with all
            // files in it.
            $pathnameitems = explode('/', $file->pathname);

            if (empty($pathnameitems)) {
                return false;
            }

            // Set the expected name of the root directory in the first
            // iteration of the loop.
            if ($rootdirname === null) {
                $rootdirname = $pathnameitems[0];
            }

            // Require the same root directory for all files in the ZIP
            // package.
            if ($rootdirname !== $pathnameitems[0]) {
                return false;
            }
        }

        return $rootdirname;
    }
}
