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
 * My Grades Report.
 *
 * @package   report_mygrades
 * @author    David Bezemer <david.bezemer@uplearning.nl>
 * @credits   Based on original work block_mygrades by Karen Holland, Mei Jin, Jiajia Chen
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require('lib.php');

global $PAGE, $COURSE, $DB;
$PAGE->set_url(new moodle_url('/report/khanimport/splash.php'));
$PAGE->set_pagelayout('report');
$PAGE->set_context(context_course::instance($COURSE->id));
$PAGE->navigation->add(get_string('pluginname', 'report_khanimport'), $PAGE->url);
$PAGE->set_heading($COURSE->fullname);
$PAGE->set_title(get_string('pluginname', 'report_khanimport'));
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('dataTables', 'report_khanimport');

require_login();


echo $OUTPUT->header();

$userid = optional_param('userid', 0, PARAM_INT);   // user id

if (empty($userid)) {
	$userid = $USER->id;
	$usercontext = context_user::instance($userid, MUST_EXIST);
} else {
	$usercontext = context_user::instance($userid, MUST_EXIST);
}
$tokens = $_GET;
print_object($tokens);
$khanapi = 
$keys = 


echo $OUTPUT->container_start('info');
echo $OUTPUT->container_end();
echo $OUTPUT->footer();
echo "<script>$('#grades').dataTable({'aaSorting': []});</script>";
