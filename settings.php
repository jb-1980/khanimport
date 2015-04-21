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
 * khanimport report settings.
 *
 * @package    report_khanimport
 * @copyright  2015 Joseph Gilgen <gilgenlabs@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$settings->add(new admin_setting_heading('khanimport_header',
                                         get_string('headerconfig', 'report_khanimport'),
                                         get_string('descconfig', 'report_khanimport')));

$settings->add(new admin_setting_configtext('khanimport/consumer_key',
                                                get_string('consumerkey', 'report_khanimport'),
                                                get_string('descconsumerkey', 'report_khanimport'),
                                                null));
$settings->add(new admin_setting_configtext('khanimport/consumer_secret',
                                                get_string('consumersecret', 'report_khanimport'),
                                                get_string('descconsumersecret', 'report_khanimport'),
                                                null));
