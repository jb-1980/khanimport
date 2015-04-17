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

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/gradelib.php');
require_once(dirname(__FILE__) . '/lib.php');


/**
 * The form for editing the activity duration settings throughout a course.
 *
 * @copyright 2014 Joseph Gilgen
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_khanimport_form extends moodleform {

    public function definition() {
        global $CFG, $COURSE, $DB;
        $mform = $this->_form;
        // Context instance of the course.
        $coursecontext = context_course::instance($COURSE->id);

        // Check if user has capability to upgrade/manage grades.
        $readonlygrades = !has_capability('moodle/grade:manage', $coursecontext);

        // Fetching Gradebook items.
        $gradeitems = grade_item::fetch_all(array('courseid' => $COURSE->id));

        // Course module will be always fetched,
        // so lenghth will always be 1 if no gread item is fetched.
        if (is_array($gradeitems) && (count($gradeitems) >1)) {
            usort($gradeitems, 'report_khanimport_sort_array_by_sortorder');

            // Section to display Khan Academy Items.
            $mform->addElement('header', 'khanacademyitems',
                    get_string('khanacademyitems', 'report_khanimport'));
            $mform->setExpanded('khanacademyitems', False);
            // Looping through all grade items.
            $this->add_checkbox_controller(1);
            foreach ($gradeitems as $gradeitem) {
                // Skip course and category grade items.
                if ($gradeitem->itemtype == "course" or $gradeitem->itemtype == "category") {
                    continue;
                }
                // Skip items without an idnumber (assumes only Khan Skills have them)
                // TODO find a way to make a Khan Skill item easily distinguishable
                if(!$gradeitem->idnumber){
                    continue;
                }
                
                $mform->addElement('advcheckbox', "skills[{$gradeitem->idnumber}]", $gradeitem->itemname  , null, array('group' => 1));

            }
        }
        // Section to display students.
        $mform->addElement('header', 'students',
                get_string('students', 'report_khanimport'));
        $mform->setExpanded('students', False);
        $enroled_users = get_enrolled_users($coursecontext, $withcapability = '', $groupid = 0, $userfields = 'u.id,u.firstname,u.lastname,u.email', $orderby = 'lastname',$limitfrom = 0, $limitnum = 0, $onlyactive = true);
        $roleid = $DB->get_record('role',array('shortname'=>'student'))->id;
        // Looping through each user
        $this->add_checkbox_controller(2);
        foreach($enroled_users as $enroled_user){
            if(user_has_role_assignment($enroled_user->id, $roleid, $contextid = $coursecontext->id)){
                $mform->addElement('advcheckbox', "student[{$enroled_user->email}~{$enroled_user->id}]", $enroled_user->firstname.' '.$enroled_user->lastname, null, array('group' => 2));
            }
        }
        $this->add_action_buttons(True,get_string('submit','report_khanimport'));
        
    }
}
