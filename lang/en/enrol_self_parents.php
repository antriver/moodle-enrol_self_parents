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
 * Strings for component 'enrol_self_parents', language 'en'.
 *
 * @package    enrol_self_parents
 * @copyright  2010 Petr Skoda  {@link http://skodak.org}, 2015 Anthony Kuske <www.anthonykuske.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['alreadyenroled'] = 'You are already enroled in this course.';
$string['canntenrol'] = 'Enrolment is disabled or inactive';
$string['child_is_enroled'] = 'Enroled! Use the <strong>Course Administration</strong> menu to unenrol.';
$string['cohortnonmemberinfo'] = 'Only members of cohort \'{$a}\' can self-enrol.';
$string['childcohortnonmemberinfo'] = 'Can\'t be enroled because only members of cohort \'{$a}\' can be enroled.';
$string['cohortonly'] = 'Only cohort members';
$string['cohortonly_help'] = 'Self enrolment may be restricted to members of a specified cohort only. Note that changing this setting has no effect on existing enrolments.';

$string['childenrolmentquestion'] = 'Child enrolment question';
$string['childenrolmentquestion_help'] = 'An optional yes/no question that parents will answer per-child when enroling a child. Leave blank to disable.

An example use for this is parents saying if their child will need to use the school bus if their participate in this course.';
$string['childenrolmentquestion_error'] = 'Please ensure you have answered all the questions on the page.';

$string['customwelcomemessage'] = 'Custom welcome message';
$string['customwelcomemessage_help'] = 'A custom welcome message may be added as plain text or Moodle-auto format, including HTML tags and multi-lang tags.

The following placeholders may be included in the message:

* Course name {$a->coursename}
* Link to user\'s profile page {$a->profileurl}';
$string['defaultrole'] = 'Default role assignment';
$string['defaultrole_desc'] = 'Select role which should be assigned to users during self enrolment';

$string['defaultparentrole'] = 'Default parent role';
$string['defaultparentrole_desc'] = 'Select a role which should be assigned to parents when their child enrols. (This can be changed per enrolment instance)';

$string['enrolchildheader'] = 'Enrol Your Children';
$string['enrolchilddesc'] = 'Tick which of your children you want to enrol in this activity and then click <strong>Enrol</strong>.';
$string['enrolchildbutton'] = 'Enrol';
$string['enrolchildrenlink'] = 'Enrol My Children...';

$string['enrolenddate'] = 'End date';
$string['enrolenddate_help'] = 'If enabled, users can enrol themselves until this date only.';
$string['enrolenddaterror'] = 'Enrolment end date cannot be earlier than start date';

$string['enrolmeheader'] = 'Enrol Yourself';
$string['enrolmebutton'] = 'Enrol Me';

$string['enrolperiod'] = 'Enrolment duration';
$string['enrolperiod_desc'] = 'Default length of time that the enrolment is valid. If set to zero, the enrolment duration will be unlimited by default.';
$string['enrolperiod_help'] = 'Length of time that the enrolment is valid, starting with the moment the user enrols themselves. If disabled, the enrolment duration will be unlimited.';
$string['enrolstartdate'] = 'Start date';
$string['enrolstartdate_help'] = 'If enabled, users can enrol themselves from this date onward only.';
$string['expiredaction'] = 'Enrolment expiration action';
$string['expiredaction_help'] = 'Select action to carry out when user enrolment expires. Please note that some user data and settings are purged from course during course unenrolment.';
$string['expirymessageenrollersubject'] = 'Self enrolment expiry notification';
$string['expirymessageenrollerbody'] = 'Self enrolment in the course \'{$a->course}\' will expire within the next {$a->threshold} for the following users:

{$a->users}

To extend their enrolment, go to {$a->extendurl}';
$string['expirymessageenrolledsubject'] = 'Self enrolment expiry notification';
$string['expirymessageenrolledbody'] = 'Dear {$a->user},

This is a notification that your enrolment in the course \'{$a->course}\' is due to expire on {$a->timeend}.

If you need help, please contact {$a->enroller}.';
$string['groupkey'] = 'Use group enrolment keys';
$string['groupkey_desc'] = 'Use group enrolment keys by default.';
$string['groupkey_help'] = 'In addition to restricting access to the course to only those who know the key, use of group enrolment keys means users are automatically added to groups when they enrol in the course.

Note: An enrolment key for the course must be specified in the self enrolment settings as well as group enrolment keys in the group settings.';
$string['longtimenosee'] = 'Unenrol inactive after';
$string['longtimenosee_help'] = 'If users haven\'t accessed a course for a long time, then they are automatically unenrolled. This parameter specifies that time limit.';
$string['maxenrolled'] = 'Max enrolled users';
$string['maxenrolled_help'] = 'Specifies the maximum number of users that can self enrol. 0 means no limit. Parents are NOT counted in this limit!';
$string['maxenrolledreached'] = 'Maximum number of users allowed to self-enrol was already reached.';
$string['messageprovider:expiry_notification'] = 'Self enrolment expiry notifications';
$string['newenrols'] = 'Allow new enrolments';
$string['newenrols_desc'] = 'Allow users to self enrol into new courses by default.';
$string['newenrols_help'] = 'This setting determines whether a user can enrol into this course.';
$string['nopassword'] = 'Click the \'Enrol Me\' button to join this course.';
$string['parent_role'] = 'Role assigned to parents';
$string['parents_counted_in_maxenrolled_checkbox'] = 'Parents count towards the max enroled user limit';
$string['parents_can_enrol_checkbox'] = 'Parents can enrol their children';
$string['parents_can_unenrol_checkbox'] = 'Parents can unenrol their children';
$string['password'] = 'Enrolment key';
$string['password_help'] = 'An enrolment key enables access to the course to be restricted to only those who know the key.

If the field is left blank, any user may enrol in the course.

If an enrolment key is specified, any user attempting to enrol in the course will be required to supply the key. Note that a user only needs to supply the enrolment key ONCE, when they enrol in the course.';
$string['passwordinvalid'] = 'Incorrect enrolment key, please try again';
$string['passwordinvalidhint'] = 'That enrolment key was incorrect, please try again<br />
(Here\'s a hint - it starts with \'{$a}\')';
$string['pluginname'] = 'Self and parents enrolment';
$string['pluginname_desc'] = 'The self and parents enrolment plugin works the same as the self enrolment plugin, but parents can also enrol their children into a course. When a child self-enrols into a course, the parent also becomes enroled.';
$string['requirepassword'] = 'Require enrolment key';
$string['requirepassword_desc'] = 'Require enrolment key in new courses and prevent removing of enrolment key from existing courses.';
$string['role'] = 'Default assigned role';
$string['self:config'] = 'Configure self and parents enrol instances';
$string['self:manage'] = 'Manage enrolled users';
$string['self:unenrol'] = 'Unenrol users from course';
$string['self:unenrolself'] = 'Unenrol self from the course';
$string['sendcoursewelcomemessage'] = 'Send course welcome message';
$string['sendcoursewelcomemessage_help'] = 'If enabled, users receive a welcome message via email when they self-enrol in a course.';
$string['showhint'] = 'Show hint';
$string['showhint_desc'] = 'Show first letter of the guest access key.';
$string['status'] = 'Enable existing enrolments';
$string['status_desc'] = 'Enable self enrolment method in new courses.';
$string['status_help'] = 'If disabled all existing self enrolments are suspended and new users can not enrol.';
$string['unenrol'] = 'Unenrol user';
$string['unenrolselfconfirm'] = 'Do you really want to unenrol yourself from course "{$a}"?';
$string['unenrolchildconfirm'] = 'Do you really want to unenrol {$a->firstname} {$a->lastname} from "{$a->course}"?';
$string['unenroluser'] = 'Do you really want to unenrol "{$a->user}" from course "{$a->course}"?';
$string['unenrolchildlink'] = 'Unenrol {$a->firstname} {$a->lastname}';
$string['unenrol_not_allowed'] = 'Parents are not allowed to unenrol children from this course.';
$string['usepasswordpolicy'] = 'Use password policy';
$string['usepasswordpolicy_desc'] = 'Use standard password policy for enrolment keys.';
$string['welcometocourse'] = 'Welcome to {$a}';
$string['welcometocoursetext'] = 'Welcome to {$a->coursename}!

If you have not done so already, you should edit your profile page so that we can learn more about you:

  {$a->profileurl}';
