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
 * Utility providing CLI access to plugin installer functionality
 *
 * This is mainly intended as a plumbing for other CLI tools that implement the
 * actual deployment and installation of plugins. The utility provides helpful
 * information about the plugin ZIP package and the environment that other
 * scripts can use.
 *
 * @package     tool_installaddon
 * @subpackage  cli
 * @copyright   2014 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$usage = "
Provides various information that can be used in your custom scripts dealing
with Moodle plugin installation via command line.

Usage:
    $ php util.php --component <zipfile>
    $ php util.php --dirname <zipfile>
    $ php util.php --type=<name> (--rename=<name>) (--version=<version>) --validate <zipfile>
    $ php util.php --normalize <componentname>
    $ php util.php --typeroot <plugintype>
    $ php util.php --dirroot
    $ php util.php --wwwroot
    $ php util.php --help

Options:
    --component          Print component name (frankenstyle) of the plugin package
    --dirname            Print the name of the root directory in the plugin package
    --validate           Validate the given plugin package and print results formatted as
                         level, message code and json encoded additional message, separated
                         by the tab character
    --type=<name>        Expected plugin type for the validation
    --rename=<name>      Rename the root folder of the plugin package for the validation
                         (do not rename if not specified)
    --version=<version>  Set the target Moodle version the plugin is going to be
                         installed to (uses \$CFG->version if not specified)
    --normalize          Normalize given component name, prints the plugin type and
                         the plugin name separated by the tab character
    --typeroot           Print the path to the root directory of the given plugin type,
                         relative to the Moodle root directory
    --dirroot            Print \$CFG->dirroot
    --wwwroot            Print \$CFG->wwwroot
    -h, --help           Print usage information and examples

Exit status:
    The utility exits with status 0 on expected output/behaviour. The exit status
    is set to 1 in case of error. The exit status 2 is used on unexpected input.

    The --validate mode exits with status 0 on passed validation, or with status 1
    on the validation failure.
";

$examples = "
Examples:
    Obtaining the component (frankenstyle) declared in the plugin package:
        $ php util.php --component ~/tmp/moodle-block_my_files.zip

    Obtaining the real name of the plugin directory that will be created
    after unzipping the package:
        $ php util.php --dirname ~/tmp/moodle-workshopallocation_semirandom.zip

    Validating the plugin packag
        $ php util.php --type=block --validate ~/tmp/package.zip

    Parsing the component name to get the plugin type and plugin name:
        $ TYPE=$(php util.php --normalize block_my_files | cut -f1)
        $ NAME=$(php util.php --normalize block_my_files | cut -f2)

    Getting the root directory of the given plugin type (relative):
        $ php util.php --typeroot workshopallocation

    Print the configured root directory of this Moodle installation:
        $ php util.php --dirroot
";

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../../config.php');
require_once($CFG->libdir.'/clilib.php');

list($options, $arguments) = cli_get_params(
    array(
        'component' => null,
        'dirname' => null,
        'validate' => null,
        'type' => null,
        'rename' => '',
        'version' => $CFG->version,
        'normalize' => null,
        'typeroot' => null,
        'dirroot' => null,
        'wwwroot' => null,
        'help' => null,
    ),
    array(
        'h' => 'help',
    )
);

$installer = tool_installaddon_installer::instance();
$util = new tool_installaddon_cli_util();

if ($options['component'] and !empty($arguments[0])) {
    $zipfile = $arguments[0];

    if (!is_readable($zipfile)) {
        cli_error("file not readable: $zipfile");
    }

    $workdir = $util->temp_dir();
    $detected = $installer->detect_plugin_component($zipfile, $workdir);

    if (empty($detected)) {
        cli_error("unable to detect the plugin component: $zipfile");
    } else {
        $util->writeln($detected);
    }
    exit();

} else if ($options['dirname'] and !empty($arguments[0])) {
    $zipfile = $arguments[0];

    if (!is_readable($zipfile)) {
        cli_error("file not readable: $zipfile");
    }

    $workdir = $util->temp_dir();
    $detected = $util->plugin_zip_root($zipfile, $workdir);

    if (empty($detected)) {
        cli_error("unexpected structure of the plugin package: $zipfile");
    } else {
        $util->writeln($detected);
    }
    exit();

} else if ($options['validate'] and $type = $options['type']) {
    $zipfile = $arguments[0];

    if (!is_readable($zipfile)) {
        cli_error("file not readable: $zipfile");
    }

    $contents = $util->temp_dir('contents');
    $files = $installer->extract_installfromzip_file($zipfile, $contents, $options['rename']);

    $validator = tool_installaddon_validator::instance($contents, $files);
    $validator->assert_plugin_type($type);
    $validator->assert_moodle_version($options['version']);
    $result = $validator->execute();

    foreach ($validator->get_messages() as $message) {
        $util->writeln(sprintf("%s\t%s\t%s", $message->level, $message->msgcode, json_encode($message->addinfo)));
    }

    if ($result) {
        exit();
    } else {
        exit(1);
    }

} else if ($options['normalize'] and !empty($arguments[0])) {
    list($type, $name) = core_component::normalize_component($arguments[0]);

    if ($name === null) {
        $util->writeln($type);
    } else {
        $util->writeln($type."\t".$name);
    }
    exit();

} else if ($options['typeroot'] and !empty($arguments[0])) {

    $root = $installer->get_plugintype_root($arguments[0]);

    if ($root === null) {
        cli_error("unknown plugin type");
    } else if ($root === false) {
        cli_error("plugin type root directory does not exist");
    } else {
        // Strip the leading dirroot part.
        if (strpos($root, $CFG->dirroot) !== 0) {
            cli_error("unexpected API output");
        } else {
            $root = substr($root, 1 + strlen($CFG->dirroot)) . '/';
            $util->writeln($root);
        }
    }
    exit();

} else if ($options['dirroot']) {
    $util->writeln($CFG->dirroot);
    exit();

} else if ($options['wwwroot']) {
    $util->writeln($CFG->wwwroot);
    exit();

} else if ($options['help']) {
    $util->writeln($usage.$examples);
    exit();

} else {
    cli_error($usage, 2);
}
