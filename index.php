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
 * Script to let users import grades for grade items throughout a course.
 *
 * @package   local_flexdates_mod_duration
 * @copyright 2014 Joseph Gilgen
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once(dirname(__FILE__) . '/form.php');

$id = required_param('id', PARAM_INT); // Course id.
$update = optional_param('update',0,PARAM_BOOL);
$oauth_token = optional_param('oauth_token',null,PARAM_RAW);
$oauth_token_secret = optional_param('oauth_token_secret',null,PARAM_RAW);
$oauth_verifier = optional_param('oauth_verifier',null,PARAM_RAW);
// Should be a valid course id.
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_login($course);

// Setup page.
$PAGE->set_url('/report/khanimport/index.php', array('id'=>$id));
$PAGE->set_pagelayout('admin');

// Check permissions.
$coursecontext = context_course::instance($course->id);
require_capability('report/khanimport:view', $coursecontext);
$consumer_obj = get_config('khanimport');
$args = array(
    'api_root'=>'http://www.khanacademy.org/',
    'oauth_consumer_key'=>$consumer_obj->consumer_key,
    'oauth_consumer_secret'=>$consumer_obj->consumer_secret,
    'request_token_api'=>'http://www.khanacademy.org/api/auth/request_token',
    'access_token_api'=>'http://www.khanacademy.org/api/auth/access_token',
    'oauth_callback'=>"{$CFG->wwwroot}/report/khanimport/index.php?id={$id}"
);
$khanacademy = new khanacademy($args);
if(!$tokens = $DB->get_record('report_khanimport',array('userid'=>$USER->id))){
    if($oauth_token and $oauth_token_secret and $oauth_verifier){
        $keys = $khanacademy->get_access_token($oauth_token, $oauth_token_secret, $oauth_verifier);
        $data = new stdClass;
        $data->userid = $USER->id;
        $data->oauthtoken = $keys['oauth_token'];
        $data->oauthsecret = $keys['oauth_token_secret'];
        $data->timestamp = time();
        $DB->insert_record('report_khanimport',$data);
    } else {
        $khanacademy->request_token();
    }
} else{
    if(time() - $tokens->timestamp > 1209600 or $update){
        if($oauth_token and $oauth_token_secret and $oauth_verifier){
        $keys = $khanacademy->get_access_token($oauth_token, $oauth_token_secret, $oauth_verifier);
        $data = new stdClass;
        $data->id = $tokens->id;
        $data->userid = $tokens->userid;
        $data->oauthtoken = $keys['oauth_token'];
        $data->oauthsecret = $keys['oauth_token_secret'];
        $data->timestamp = time();
        $DB->update_record('report_khanimport',$data);
        } else {
            $khanacademy->request_token();
        }
    } else{
        $khanacademy->set_access_token($tokens->oauthtoken,$tokens->oauthsecret);
    }
}

// Creating form instance, passed course id as parameter to action url.
$baseurl = new moodle_url('/report/khanimport/index.php', array('id' => $id));
$mform = new report_khanimport_form($baseurl);

$returnurl = new moodle_url('/course/view.php', array('id' => $id));
if ($mform->is_cancelled()) {
    // Redirect to course view page if form is cancelled.
    redirect($returnurl);
} else if ($data = $mform->get_data()) {
    $params = array('exercises'=>array());
    // if skills have been selected, get data just for those skills,
    // else get data for all skills in KA
    if(array_search(1,$data->skills) !== False){
        foreach($data->skills as $skill=>$value){
            if($value){
                $params['exercises'][] = $skill;
            }
        }
    }
    foreach($data->student as $student=>$selected){
        if($selected){
            $student_info = explode('~',$student);
            $params['email']=$student_info[0];
            $content = $khanacademy->get_many_exercises($params);
            //$c = $khanacademy->request('GET',$api_url,$params);
            //$content = json_decode($c);
        } else{
            continue;
        }
        
        $requester = json_decode($khanacademy->request('GET','http://www.khanacademy.org/api/v1/user'));
        //print_object($requester);
        //print_object($content);
        // If user doesn't have requester as coach, khan academy will return content
        // of the requester. This prevents us from updating grades with bad info
        if($requester->key_email == $content[0]->user){
            $content = null;
            continue;
        }
        foreach($content as $index=>$skillmodel){
            $exercise = $skillmodel->exercise;
            if(in_array($exercise,array_keys($data->skills))){
                $skill_level = $skillmodel->exercise_progress->level;
                $finalgrade = array(
                  'unstarted'=>false,
                  'practiced'=>85,
                  'mastery1'=>90,
                  'mastery2'=>95,
                  'mastery3'=>100)[$skill_level];
                report_khanimport_update_grade($student_info[1], $finalgrade, $id, $exercise);
            }
        }
        $content = null;
        
    }
    rebuild_course_cache($course->id);
    redirect($returnurl);
} else {
    $PAGE->set_title($course->shortname .': '. get_string('khanimport', 'report_khanimport'));
    $PAGE->set_heading($course->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->heading(format_string($course->fullname));
    $mform->display();
    echo $OUTPUT->footer();
}

