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
 * Self enrolment plugin - allows parents to remove their child from an enrolment.
 *
 * @package    enrol_self_parents
 * @copyright  2010 Petr Skoda  {@link http://skodak.org}, 2015 Anthony Kuske <www.anthonykuske.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$enrolid = required_param('enrolid', PARAM_INT);
$childuserid = required_param('childuserid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$instance = $DB->get_record('enrol', array('id'=>$enrolid, 'enrol'=>'self_parents'), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$instance->courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

// Does enrolment instance allow parents to unenrol children?
if (!$instance->customchar2) {
    throw new \Exception(get_string('unenrol_not_allowed', 'enrol_self_parents'));
}

//require_login();
require_login($course);

$plugin = enrol_get_plugin('self_parents');

// Require that user is a child of the current user
$children = $plugin->get_users_children($USER->id);
if (!isset($children[$childuserid])) {
	die("That's not your child.");
} else {
	$child = $children[$childuserid];
}


// Security defined inside following function.
$PAGE->set_url('/enrol/self_parents/unenrolchild.php', array('enrolid'=>$instance->id, 'childuserid'=>$childuserid));
$PAGE->set_title($course->fullname);
$PAGE->set_heading($course->fullname);

if ($confirm and confirm_sesskey()) {
    $plugin->unenrol_user($instance, $childuserid);
    //add_to_log($course->id, 'course', 'unenrol', '../enrol/users.php?id='.$course->id, $course->id);
    redirect(new moodle_url('/enrol/index.php?', array('id'=>$course->id)));
}

echo $OUTPUT->header();
$yesurl = new moodle_url($PAGE->url, array('confirm'=>1, 'sesskey'=>sesskey()));
$nourl = new moodle_url('/enrol/index.php?', array('id'=>$course->id));

$messgeVars = (object)array(
    'course' => format_string($course->fullname),
    'firstname' => $child->firstname,
    'lastname' => $child->lastname
);
$message = get_string('unenrolchildconfirm', 'enrol_self_parents', $messgeVars);

echo $OUTPUT->confirm($message, $yesurl, $nourl);
echo $OUTPUT->footer();
