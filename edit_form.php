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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/cohort/lib.php');

class enrol_self_parents_edit_form extends moodleform
{

    public function definition() {

        global $DB;

        $mform = $this->_form;

        list($instance, $plugin, $context) = $this->_customdata;

        $mform->addElement('header', 'header', get_string('pluginname', 'enrol_self_parents'));

        $nameattribs = array('size' => '20', 'maxlength' => '255');
        $mform->addElement('text', 'name', get_string('custominstancename', 'enrol'), $nameattribs);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'server');

        $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'),
                         ENROL_INSTANCE_DISABLED => get_string('no'));
        $mform->addElement('select', 'status', get_string('status', 'enrol_self_parents'), $options);
        $mform->addHelpButton('status', 'status', 'enrol_self_parents');

        $options = array(1 => get_string('yes'), 0 => get_string('no'));
        $mform->addElement('select', 'customint6', get_string('newenrols', 'enrol_self_parents'), $options);
        $mform->addHelpButton('customint6', 'newenrols', 'enrol_self_parents');
        $mform->disabledIf('customint6', 'status', 'eq', ENROL_INSTANCE_DISABLED);

        $passattribs = array('size' => '20', 'maxlength' => '50');
        $mform->addElement('passwordunmask', 'password', get_string('password', 'enrol_self_parents'), $passattribs);
        $mform->addHelpButton('password', 'password', 'enrol_self_parents');
        if (empty($instance->id) and $plugin->get_config('requirepassword')) {
            $mform->addRule('password', get_string('required'), 'required', null, 'client');
        }
        $mform->addRule('password', get_string('maximumchars', '', 50), 'maxlength', 50, 'server');

        $options = array(1 => get_string('yes'),
                         0 => get_string('no'));
        $mform->addElement('select', 'customint1', get_string('groupkey', 'enrol_self_parents'), $options);
        $mform->addHelpButton('customint1', 'groupkey', 'enrol_self_parents');

        $roles = $this->extend_assignable_roles($context, $instance->roleid);
        $mform->addElement('select', 'roleid', get_string('role', 'enrol_self_parents'), $roles);

        $mform->addElement('duration', 'enrolperiod', get_string('enrolperiod', 'enrol_self_parents'), array('optional' => true, 'defaultunit' => 86400));
        $mform->addHelpButton('enrolperiod', 'enrolperiod', 'enrol_self_parents');

        $options = array(0 => get_string('no'), 1 => get_string('expirynotifyenroller', 'core_enrol'), 2 => get_string('expirynotifyall', 'core_enrol'));
        $mform->addElement('select', 'expirynotify', get_string('expirynotify', 'core_enrol'), $options);
        $mform->addHelpButton('expirynotify', 'expirynotify', 'core_enrol');

        $mform->addElement('duration', 'expirythreshold', get_string('expirythreshold', 'core_enrol'), array('optional' => false, 'defaultunit' => 86400));
        $mform->addHelpButton('expirythreshold', 'expirythreshold', 'core_enrol');
        $mform->disabledIf('expirythreshold', 'expirynotify', 'eq', 0);

        $mform->addElement('date_time_selector', 'enrolstartdate', get_string('enrolstartdate', 'enrol_self_parents'), array('optional' => true));
        $mform->setDefault('enrolstartdate', 0);
        $mform->addHelpButton('enrolstartdate', 'enrolstartdate', 'enrol_self_parents');

        $mform->addElement('date_time_selector', 'enrolenddate', get_string('enrolenddate', 'enrol_self_parents'), array('optional' => true));
        $mform->setDefault('enrolenddate', 0);
        $mform->addHelpButton('enrolenddate', 'enrolenddate', 'enrol_self_parents');

        $options = array(0 => get_string('never'),
                 1800 * 3600 * 24 => get_string('numdays', '', 1800),
                 1000 * 3600 * 24 => get_string('numdays', '', 1000),
                 365 * 3600 * 24 => get_string('numdays', '', 365),
                 180 * 3600 * 24 => get_string('numdays', '', 180),
                 150 * 3600 * 24 => get_string('numdays', '', 150),
                 120 * 3600 * 24 => get_string('numdays', '', 120),
                 90 * 3600 * 24 => get_string('numdays', '', 90),
                 60 * 3600 * 24 => get_string('numdays', '', 60),
                 30 * 3600 * 24 => get_string('numdays', '', 30),
                 21 * 3600 * 24 => get_string('numdays', '', 21),
                 14 * 3600 * 24 => get_string('numdays', '', 14),
                 7 * 3600 * 24 => get_string('numdays', '', 7));
        $mform->addElement('select', 'customint2', get_string('longtimenosee', 'enrol_self_parents'), $options);
        $mform->addHelpButton('customint2', 'longtimenosee', 'enrol_self_parents');

        $mform->addElement('text', 'customint3', get_string('maxenrolled', 'enrol_self_parents'));
        $mform->addHelpButton('customint3', 'maxenrolled', 'enrol_self_parents');
        $mform->setType('customint3', PARAM_INT);

        $cohorts = array(0 => get_string('no'));
        $allcohorts = cohort_get_available_cohorts($context, 0, 0, 0);
        if ($instance->customint5 && !isset($allcohorts[$instance->customint5]) &&
                ($c = $DB->get_record('cohort', array('id' => $instance->customint5), 'id, name, idnumber, contextid, visible', IGNORE_MISSING))) {
            // Current cohort was not found because current user can not see it. Still keep it.
            $allcohorts[$instance->customint5] = $c;
        }
        foreach ($allcohorts as $c) {
            $cohorts[$c->id] = format_string($c->name, true, array('context' => context::instance_by_id($c->contextid)));
            if ($c->idnumber) {
                $cohorts[$c->id] .= ' ['.s($c->idnumber).']';
            }
        }
        if ($instance->customint5 && !isset($allcohorts[$instance->customint5])) {
            // Somebody deleted a cohort, better keep the wrong value so that random ppl can not enrol.
            $cohorts[$instance->customint5] = get_string('unknowncohort', 'cohort', $instance->customint5);
        }
        if (count($cohorts) > 1) {
            $mform->addElement('select', 'customint5', get_string('cohortonly', 'enrol_self_parents'), $cohorts);
            $mform->addHelpButton('customint5', 'cohortonly', 'enrol_self_parents');
        } else {
            $mform->addElement('hidden', 'customint5');
            $mform->setType('customint5', PARAM_INT);
            $mform->setConstant('customint5', 0);
        }

        $mform->addElement('advcheckbox', 'customint4', get_string('sendcoursewelcomemessage', 'enrol_self_parents'));
        $mform->addHelpButton('customint4', 'sendcoursewelcomemessage', 'enrol_self_parents');

        $mform->addElement('textarea', 'customtext1', get_string('customwelcomemessage', 'enrol_self_parents'), array('cols' => '60', 'rows' => '8'));
        $mform->addHelpButton('customtext1', 'customwelcomemessage', 'enrol_self_parents');

        // customint8: Parents can enrol children?
        $mform->addElement('advcheckbox', 'customint8', get_string('parents_can_enrol_checkbox', 'enrol_self_parents'));

        // customchar2: Parents can unenrol children?
        $mform->addElement('advcheckbox', 'customchar2', get_string('parents_can_unenrol_checkbox', 'enrol_self_parents'));

        // customchar1: Role for parents
        $roles = $this->extend_assignable_roles($context, $instance->customchar1);
        $select = $mform->addElement('select', 'customchar1', get_string('parent_role', 'enrol_self_parents'), $roles);
        $select->setSelected($instance->customchar1);

        // customchar3: Parents count towards max enroled limit
        $mform->addElement('advcheckbox', 'customchar3', get_string('parents_counted_in_maxenrolled_checkbox', 'enrol_self_parents'));

        // customtext2: A yes/no question to be asked when a parent enrols a child
        $mform->addElement('text', 'customtext2', get_string('childenrolmentquestion', 'enrol_self_parents'));
        $mform->setType('customtext2', PARAM_RAW);
        $mform->addHelpButton('customtext2', 'childenrolmentquestion', 'enrol_self_parents');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        if (enrol_accessing_via_instance($instance)) {
            $mform->addElement('static', 'selfwarn', get_string('instanceeditselfwarning', 'core_enrol'), get_string('instanceeditselfwarningtext', 'core_enrol'));
        }

        $this->add_action_buttons(true, ($instance->id ? null : get_string('addinstance', 'enrol')));

        $this->set_data($instance);
    }

    public function validation($data, $files) {

        global $DB, $CFG;
        $errors = parent::validation($data, $files);

        list($instance, $plugin, $context) = $this->_customdata;
        $checkpassword = false;

        if ($instance->id) {
            if ($data['status'] == ENROL_INSTANCE_ENABLED) {
                if ($instance->password !== $data['password']) {
                    $checkpassword = true;
                }
            }
        } else {
            if ($data['status'] == ENROL_INSTANCE_ENABLED) {
                $checkpassword = true;
            }
        }

        if ($checkpassword) {
            $require = $plugin->get_config('requirepassword');
            $policy  = $plugin->get_config('usepasswordpolicy');
            if ($require and trim($data['password']) === '') {
                $errors['password'] = get_string('required');
            } else if ($policy) {
                $errmsg = ''; // prevent eclipse warning
                if (!check_password_policy($data['password'], $errmsg)) {
                    $errors['password'] = $errmsg;
                }
            }
        }

        if ($data['status'] == ENROL_INSTANCE_ENABLED) {
            if (!empty($data['enrolenddate']) and $data['enrolenddate'] < $data['enrolstartdate']) {
                $errors['enrolenddate'] = get_string('enrolenddaterror', 'enrol_self_parents');
            }
        }

        if ($data['expirynotify'] > 0 and $data['expirythreshold'] < 86400) {
            $errors['expirythreshold'] = get_string('errorthresholdlow', 'core_enrol');
        }

        return $errors;
    }

    /**
     * Gets a list of roles that this user can assign for the course as the default for self-enrolment.
     *
     * @param context $context the context.
     * @param integer $defaultrole the id of the role that is set as the default for self-parent-enrolment
     * @return array index is the role id, value is the role name
     */
    public function extend_assignable_roles($context, $defaultrole) {

        global $DB;

        $roles = get_assignable_roles($context, ROLENAME_BOTH);
        if (!isset($roles[$defaultrole])) {
            if ($role = $DB->get_record('role', array('id' => $defaultrole))) {
                $roles[$defaultrole] = role_get_name($role, $context, ROLENAME_BOTH);
            }
        }
        return $roles;
    }
}
