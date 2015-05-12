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
 * Self enrol plugin implementation.
 *
 * @package    enrol_self_parents
 * @copyright  2010 Petr Skoda  {@link http://skodak.org}, 2015 Anthony Kuske <www.anthonykuske.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class enrol_self_parents_enrol_form extends moodleform {
    protected $instance;
    protected $toomany = false;

    /**
     * Overriding this function to get unique form id for multiple self enrolments.
     *
     * @return string form identifier
     */
    protected function get_form_identifier() {
        $formid = $this->_customdata->id.'_'.get_class($this);
        return $formid;
    }

    /**
     * The self+parent enrolment form shown on the /enrol page for a course
     */
    public function definition() {
        global $CFG, $DB, $USER, $PAGE;

        $mform = $this->_form;
        $instance = $this->_customdata;
        $this->instance = $instance;
        $plugin = enrol_get_plugin('self_parents');

        $heading = $plugin->get_instance_name($instance);

        $mform->addElement('header', 'selfparentsheader', get_string('enrolmeheader', 'enrol_self_parents'));

        // Enrollments are limited to a certain cohort
        // We bypassed the cohort check in the lib/can_self_enrol function so do it here
        if ($instance->customint5) {
            $mustBeInCohort = $DB->get_record('cohort', array('id'=>$instance->customint5));
            if (!$mustBeInCohort) {
                throw new \Exception("The required cohort {$instance->customint5} for self_parents instance {$instance->id} does not exist.");
            }

            // Require cohort functions
            require_once $CFG->dirroot . '/cohort/lib.php';
        } else {
            $mustBeInCohort = false;
        }

        // TODO: This is not tested with a passworded enrolment. It might not work
        if ($instance->password) {
            // Change the id of self enrolment key input as there can be multiple self enrolment methods.
            $mform->addElement('passwordunmask', 'enrolpassword', get_string('password', 'enrol_self_parents'),
                    array('id' => 'enrolpassword_'.$instance->id));
        }


        /**
         * Can current user self enrol?
         */
        if ($plugin->user_is_enrolled($USER->id, $instance->id)) {

            // Curent user already enrolled
            $mform->addElement(
                'static',
                'alreadyenroled',
                '',
                get_string('alreadyenroled', 'enrol_self_parents')
            );

        } elseif ($mustBeInCohort && !cohort_is_member($mustBeInCohort->id, $USER->id)) {

            // Current user not in required cohort
            $mform->addElement(
                'static',
                'cohortnonmemberinfo',
                '',
                get_string('cohortnonmemberinfo', 'enrol_self_parents', $mustBeInCohort->name)
            );

        } else {

            // Current user can self enrol
            $mform->addElement(
                'submit',
                'enrolmesubmit',
                get_string('enrolmebutton', 'enrol_self_parents')
            );

        }


        /**
         * Can a parent enrol their child?
         */
        if ($instance->customint8) {

            // Check if they have children
            if ($children = $plugin->get_users_children($USER->id)) {

                $mform->addElement('header', 'enrolchildheader', get_string('enrolchildheader', 'enrol_self_parents'));
                $mform->addElement('html', '<div class="helptext">' . get_string('enrolchilddesc', 'enrol_self_parents') . '</div>');

                $showBusWarning = false;

                $options = array();
                foreach($children as $child) {

                    $name = $child->firstname.' '.$child->lastname;

                    $dataCheckbox = '';
                    if ($instance->customtext2) {
                        // Custom checkbox
                        $dataCheckbox = '<span style="margin-left:50px;"> ' . $instance->customtext2 . '</i> ';
                            // Not selcting a choice by default, so the user has to click, thus confirming the choice.
                            $dataCheckbox .= '<label>Yes <input type="radio" value="1" name="customtext2['. $child->userid . ']" /></label>';
                            $dataCheckbox .= '<label>No <input type="radio" value="0" name="customtext2['. $child->userid . ']" /></label>';
                        $dataCheckbox .= '</span>';
                    }

                    if ($plugin->user_is_enrolled($child->userid, $instance->id)) {

                        // Child is already enrolled
                        $str = '<span class="text-success">' . get_string('child_is_enroled', 'enrol_self_parents') . '</span>';
                        $mform->addElement(
                            'checkbox',
                            "enrolchilduserids[{$child->userid}]",
                            $name,
                            $str,
                            array(
                                'disabled' => 'disabled',
                                'class' => 'enrolchildcheckbox',
                                'data-userid' => $child->userid
                            )
                        );

                    } else if ($mustBeInCohort && !cohort_is_member($mustBeInCohort->id, $child->userid)) {

                        // Child isn't in the required cohort
                        $str = '<span class="text-danger">' . get_string('childcohortnonmemberinfo', 'enrol_self_parents') . '</span>';
                        $mform->addElement(
                            'checkbox',
                            "enrolchilduserids[{$child->userid}]",
                            $name,
                            $str,
                            array(
                                'disabled' => 'disabled',
                                'class' => 'enrolchildcheckbox',
                                'data-userid' => $child->userid
                            )
                        );

                    } else {

                        // Child can be enrolled
                        $str = $dataCheckbox;
                        $mform->addElement(
                            'checkbox',
                            "enrolchilduserids[{$child->userid}]",
                            $name,
                            $str,
                            array(
                                'class'=>'enrolchildcheckbox',
                                'data-userid' => $child->userid
                            )
                        );

                    }

                }


                // Enrol my child button
                // A .dnet-disabled class instead of the disabled attribute is used so click events can be bound to the button
                $mform->addElement(
                    'submit',
                    'enrolchildsubmit',
                    get_string('enrolchildbutton', 'enrol_self_parents'),
                    array(
                        'class' => 'dnet-disabled'
                    )
                );
                $PAGE->requires->jquery();
                $submitjs = '<script>';

                    $submitjs .= '

                    // Disable the submit button until a child is ticked to enrol
                    $(document).on("change", "input.enrolchildcheckbox", function() {
                        var count = $("input.enrolchildcheckbox:checked").length;
                        if (count > 0) {
                            $("#id_enrolchildsubmit").removeClass("dnet-disabled");
                        } else {
                            $("#id_enrolchildsubmit").addClass("dnet-disabled");
                        }
                    });

                    $(document).on("click", "#id_enrolchildsubmit", function(e) {
                        if ($(this).hasClass("dnet-disabled")) {
                            alert("Please tick at least one child to enrol.");
                            return false;
                        }';

                        if ($instance->customtext2) {

                            $submitjs .= '
                            // Make sure the custom checkbox has been selected for each user being enroled
                            $(".enrolchildcheckbox:checked").each(function() {

                                var userID = $(this).attr("data-userid");

                                if ( $(\'input[name="customtext2[\'+ userID + \']"]:checked\').length < 1) {

                                    alert("' . get_string('childenrolmentquestion_error', 'enrol_self_parents') . '");
                                    e.preventDefault();
                                }
                            });
                            ';
                        }

                    $submitjs .= '
                    });
                    ';

                $submitjs .= '</script>';
                $mform->addElement('html', $submitjs);

            }
        }

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $instance->courseid);

        $mform->addElement('hidden', 'instance');
        $mform->setType('instance', PARAM_INT);
        $mform->setDefault('instance', $instance->id);
    }

    public function validation($data, $files) {
        global $DB, $CFG;

        $errors = parent::validation($data, $files);
        $instance = $this->instance;

        if ($this->toomany) {
            $errors['notice'] = get_string('error');
            return $errors;
        }

        if ($instance->password) {
            if ($data['enrolpassword'] !== $instance->password) {
                if ($instance->customint1) {
                    $groups = $DB->get_records('groups', array('courseid'=>$instance->courseid), 'id ASC', 'id, enrolmentkey');
                    $found = false;
                    foreach ($groups as $group) {
                        if (empty($group->enrolmentkey)) {
                            continue;
                        }
                        if ($group->enrolmentkey === $data['enrolpassword']) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        // We can not hint because there are probably multiple passwords.
                        $errors['enrolpassword'] = get_string('passwordinvalid', 'enrol_self_parents');
                    }

                } else {
                    $plugin = enrol_get_plugin('self_parents');
                    if ($plugin->get_config('showhint')) {
                        $hint = core_text::substr($instance->password, 0, 1);
                        $errors['enrolpassword'] = get_string('passwordinvalidhint', 'enrol_self_parents', $hint);
                    } else {
                        $errors['enrolpassword'] = get_string('passwordinvalid', 'enrol_self_parents');
                    }
                }
            }
        }

        return $errors;
    }
}
