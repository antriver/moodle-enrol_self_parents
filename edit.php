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
 * Adds new instance of enrol_self_parents to specified course
 * or edits current instance.
 *
 * @package    enrol_self_parents
 * @copyright  2010 Petr Skoda  {@link http://skodak.org}, 2015 Anthony Kuske <www.anthonykuske.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once('edit_form.php');

$courseid   = required_param('courseid', PARAM_INT);
$instanceid = optional_param('id', 0, PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);
require_capability('enrol/self_parents:config', $context);

$PAGE->set_url('/enrol/self_parents/edit.php', array('courseid' => $course->id, 'id' => $instanceid));
$PAGE->set_pagelayout('admin');

$return = new moodle_url('/enrol/instances.php', array('id' => $course->id));
if (!enrol_is_enabled('self_parents')) {
    redirect($return);
}

/** @var enrol_self_parents_plugin $plugin */
$plugin = enrol_get_plugin('self_parents');

if ($instanceid) {
    $instance = $DB->get_record(
        'enrol',
        array(
            'courseid' => $course->id,
            'enrol' => 'self_parents',
            'id' => $instanceid
        ),
        '*',
        MUST_EXIST
    );
} else {
    require_capability('moodle/course:enrolconfig', $context);
    // No instance yet, we have to add new instance.
    navigation_node::override_active_url(new moodle_url('/enrol/instances.php', array('id' => $course->id)));

    $instance = (object)$plugin->get_instance_defaults();
    $instance->id       = null;
    $instance->courseid = $course->id;
    $instance->status   = ENROL_INSTANCE_ENABLED; // Do not use default for automatically created instances here.
}

// Merge these two settings to one value for the single selection element.
if ($instance->notifyall and $instance->expirynotify) {
    $instance->expirynotify = 2;
}
unset($instance->notifyall);

$mform = new enrol_self_parents_edit_form(null, array($instance, $plugin, $context));

if ($mform->is_cancelled()) {
    redirect($return);

} else if ($data = $mform->get_data()) {
    if ($data->expirynotify == 2) {
        $data->expirynotify = 1;
        $data->notifyall = 1;
    } else {
        $data->notifyall = 0;
    }
    if (!$data->expirynotify) {
        // Keep previous/default value of disabled expirythreshold option.
        $data->expirythreshold = $instance->expirythreshold;
    }
    if (!isset($data->customint6)) {
        // Add previous value of newenrols if disabled.
        $data->customint6 = $instance->customint6;
    }

    if ($instance->id) {
        $reset = ($instance->status != $data->status);

        $instance->status         = $data->status;
        $instance->name           = $data->name;
        $instance->password       = $data->password;
        $instance->customint1     = $data->customint1;
        $instance->customint2     = $data->customint2;
        $instance->customint3     = $data->customint3;
        $instance->customint4     = $data->customint4;
        $instance->customint5     = $data->customint5;
        $instance->customint6     = $data->customint6;

        // customint7 was used by SSIS for bus requirements.
        // Old data still exists so not repurposing
        // $instance->customint7     = $data->customint7;

        // customint8: Parent can enrol children?
        $instance->customint8     = $data->customint8;

        // customchar1: Role for parents
        $instance->customchar1     = $data->customchar1;

        // Parents can unenrol
        $instance->customchar2     = $data->customchar2;

        // Parents count toward max enroled limit
        $instance->customchar3     = $data->customchar3;

        $instance->customtext1    = $data->customtext1;
        $instance->customtext2    = $data->customtext2;

        $instance->roleid         = $data->roleid;
        $instance->enrolperiod    = $data->enrolperiod;
        $instance->expirynotify   = $data->expirynotify;
        $instance->notifyall      = $data->notifyall;
        $instance->expirythreshold = $data->expirythreshold;
        $instance->enrolstartdate = $data->enrolstartdate;
        $instance->enrolenddate   = $data->enrolenddate;
        $instance->timemodified   = time();
        $DB->update_record('enrol', $instance);

        if ($reset) {
            $context->mark_dirty();
        }

    } else {
        $fields = array(
            'status'          => $data->status,
            'name'            => $data->name,
            'password'        => $data->password,
            'customint1'      => $data->customint1,
            'customint2'      => $data->customint2,
            'customint3'      => $data->customint3,
            'customint4'      => $data->customint4,
            'customint5'      => $data->customint5,
            'customint6'      => $data->customint6,
            // 'customint7'      => $data->customint7,
            'customint8'      => $data->customint8,
            'customchar1'     => $data->customchar1,
            'customchar2'     => $data->customchar2,
            'customchar3'     => $data->customchar3,
            'customtext1'     => $data->customtext1,
            'customtext2'     => $data->customtext2,
            'roleid'          => $data->roleid,
            'enrolperiod'     => $data->enrolperiod,
            'expirynotify'    => $data->expirynotify,
            'notifyall'       => $data->notifyall,
            'expirythreshold' => $data->expirythreshold,
            'enrolstartdate'  => $data->enrolstartdate,
            'enrolenddate'    => $data->enrolenddate);
        $plugin->add_instance($course, $fields);
    }

    redirect($return);
}

$PAGE->set_heading($course->fullname);
$PAGE->set_title(get_string('pluginname', 'enrol_self_parents'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'enrol_self_parents'));
$mform->display();
echo $OUTPUT->footer();
