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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/lib/grade/grade_item.php');
/**
 * This function extends the navigation with the report items
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 */
function report_khanimport_extend_navigation_course($navigation, $course, $context) {
    global $CFG, $OUTPUT;
    if (has_capability('report/khanimport:view', $context)) {
        $url = new moodle_url('/report/khanimport/index.php', array('id' => $course->id));
        $navigation->add(get_string( 'khanimport', 'report_khanimport' ),
                $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
    }
}

require($CFG->libdir.'/oauthlib.php');
class khanacademy extends oauth_helper {
    /**
     * Request token for authentication
     * This is the first step to use OAuth, it will return oauth_token and oauth_token_secret
     * @return array
     */
    public function request_token() {
        global $CFG;
        $this->sign_secret = $this->consumer_secret.'&';
        $params = $this->prepare_oauth_parameters($this->request_token_api, array(), 'GET');
        $url = $this->request_token_api;
        if (!empty($params)) {
            $url .= (stripos($url, '?') !== false) ? '&' : '?';
            $url .= http_build_query($params, '', '&');
        }
        return redirect($url);
    }
    
    public function parse_params($params,$exclude=array()){
        ksort($params);
        $total = array();
        foreach($params as $param=>$value){
            if(in_array($param,$exclude)){
                continue;
            }
            if(is_array($value)){
                if(!empty($value)){
                    sort($value);
                    foreach($value as $k=>$v){
                        //print_object($v);
                        $total[] = $param.'='.rawurlencode($v);
                    }
                }
            } else{
                $total[] = $param.'='.rawurlencode($value);
            }
        }
        return $total;
    }
    /**
     * Build parameters list:
     *    oauth_consumer_key="0685bd9184jfhq22",
     *    oauth_nonce="4572616e48616d6d65724c61686176",
     *    oauth_token="ad180jjd733klru7",
     *    oauth_signature_method="HMAC-SHA1",
     *    oauth_signature="wOJIO9A2W5mFwDgiDvZbTSMK%2FPY%3D",
     *    oauth_timestamp="137131200",
     *    oauth_version="1.0"
     *    oauth_verifier="1.0"
     * @param array $param
     * @return string
     */
    function get_signable_parameters($params){
        $total = $this->parse_params($params,$exclude=array('oauth_signature'));
        return implode('&', $total);
    }
    
    public function setup_oauth_http_header($params) {
        $total = $this->parse_params($params);
        $str = implode(', ', $total);
        $str = 'Authorization: OAuth '.$str;
        $this->http->setHeader('Expect:');
        $this->http->setHeader($str);
    }
    
    /**
     * Request oauth protected resources
     * @param string $method
     * @param string $url
     * @param string $token
     * @param string $secret
     */
    public function request($method, $url, $params = array(), $token = '', $secret = '') {
        if (empty($token)) {
            $token = $this->access_token;
        }
        if (empty($secret)) {
            $secret = $this->access_token_secret;
        }

        // to access protected resource, sign_secret will always be consumer_secret+token_secret
        $this->sign_secret = $this->consumer_secret.'&'.$secret;
        
        $oauth_params = $this->prepare_oauth_parameters($url, array('oauth_token'=>$token) + $params, $method);
        $this->setup_oauth_http_header($oauth_params);
        $url_params = $this->parse_params($params);
        
        $url .= (stripos($url, '?') !== false) ? '&' : '?';
        $url .= implode('&',$url_params);
        $content = call_user_func_array(array($this->http, 'get'), array($url,array(),$this->http_options));
        // reset http header and options to prepare for the next request
        $this->http->resetHeader();
        // return request return value
        return $content;
    }
    
    public function get_many_exercises($params){
        $out = array();
        $tmp = array('exercises'=>array(),'email'=>$params['email']);
        $exer = True;
        while($exer){
            $s='';
            $tmp['exercises'] = array();
            foreach($params['exercises'] as $exercise){
                $t=$s.'&exercises='.$exercise;
                if(strlen($t)<500){
                    $s.='&exercises='.$exercise;
                    $tmp['exercises'][]=$exercise;
                } else{
                    break;
                }
            }
            $params['exercises'] = array_diff($params['exercises'],$tmp['exercises']);
            $api_url = 'http://www.khanacademy.org/api/v1/user/exercises';
            $request = json_decode($this->request('GET',$api_url,$tmp));
            $out = array_merge($out,$request);
            if(empty($params['exercises'])){
                $exer = False;
            }
        }

        return $out;
    }
}

/**
 * update grade item in grade book
 * @param int $userid the id of the user whose grade we are updating
 * @param float $finalgrade the float for the grade we are updating
 * @param int $courseid the id of the course
 * @param str $idnumber the idnumber of the grade_item
 * @return bool result of the update
 */
function report_khanimport_update_grade($userid, $finalgrade, $courseid, $idnumber){
    $gradeitem = new grade_item(array('courseid'=>$courseid,'idnumber'=>$idnumber));
     return $gradeitem->update_raw_grade($userid, $finalgrade);
}
function report_khanimport_sort_array_by_sortorder($item1, $item2) {
    if (!$item1->sortorder || !$item2->sortorder) {
        return 0;
    }
    if ($item1->sortorder == $item2->sortorder) {
        return 0;
    }
    return ($item1->sortorder > $item2->sortorder) ? 1 : -1;
}


