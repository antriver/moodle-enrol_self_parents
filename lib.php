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
 * Self enrolment plugin.
 *
 * @package    enrol_self_parents
 * @copyright  2010 Petr Skoda  {@link http://skodak.org}, 2015 Anthony Kuske <www.anthonykuske.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Self enrolment plugin implementation.
 * @author Petr Skoda
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_self_parents_plugin extends enrol_plugin
{

    protected $lasternoller = null;
    protected $lasternollerinstanceid = 0;

    /**
     * Returns name of this enrol plugin
     * @return string
     */
    public function get_name() {

        return 'self_parents';
    }

    /**
     * Returns optional enrolment information icons.
     *
     * This is used in course list for quick overview of enrolment options.
     *
     * We are not using single instance parameter because sometimes
     * we might want to prevent icon repetition when multiple instances
     * of one type exist. One instance may also produce several icons.
     *
     * @param array $instances all enrol instances of this type in one course
     * @return array of pix_icon
     */
    public function get_info_icons(array $instances) {

        $key = false;
        $nokey = false;
        foreach ($instances as $instance) {
            if ($this->can_user_enrol($instance, false) !== true) {
                // User can not enrol himself.
                // Note that we do not check here if user is already enrolled for performance reasons -
                // such check would execute extra queries for each course in the list of courses and
                // would hide self-enrolment icons from guests.
                continue;
            }
            if ($instance->password or $instance->customint1) {
                $key = true;
            } else {
                $nokey = true;
            }
        }
        $icons = array();
        if ($nokey) {
            $icons[] = new pix_icon('withoutkey', get_string('pluginname', 'enrol_self_parents'), 'enrol_self_parents');
        }
        if ($key) {
            $icons[] = new pix_icon('withkey', get_string('pluginname', 'enrol_self_parents'), 'enrol_self_parents');
        }
        return $icons;
    }

    /**
     * Returns localised name of enrol instance
     *
     * @param stdClass $instance (null is accepted too)
     * @return string
     */
    public function get_instance_name($instance) {

        global $DB;

        if (empty($instance->name)) {
            if (!empty($instance->roleid) and $role = $DB->get_record('role', array('id' => $instance->roleid))) {
                $role = ' (' . role_get_name($role, context_course::instance($instance->courseid, IGNORE_MISSING)) . ')';
            } else {
                $role = '';
            }
            $enrol = $this->get_name();
            return get_string('pluginname', 'enrol_'.$enrol) . $role;
        } else {
            return format_string($instance->name);
        }
    }

    public function roles_protected() {

        // Users may tweak the roles later.
        return false;
    }

    public function allow_unenrol(stdClass $instance) {

        // Users with unenrol cap may unenrol other users manually manually.
        return true;
    }

    public function allow_manage(stdClass $instance) {

        // Users with manage cap may tweak period and status.
        return true;
    }

    public function show_enrolme_link(stdClass $instance) {

        if (true !== $this->can_user_enrol($instance, false)) {
            return false;
        }

        return true;
    }

    /**
     * Sets up navigation entries.
     *
     * @param stdClass $instancesnode
     * @param stdClass $instance
     * @return void
     */
    public function add_course_navigation($instancesnode, stdClass $instance) {

        global $DB, $USER;

        if ($instance->enrol !== 'self_parents') {
             throw new coding_exception('Invalid enrol instance type!');
        }

        $context = context_course::instance($instance->courseid);
        if (has_capability('enrol/self_parents:config', $context)) {
            $managelink = new moodle_url('/enrol/self_parents/edit.php', array('courseid' => $instance->courseid, 'id' => $instance->id));
            $instancesnode->add($this->get_instance_name($instance), $managelink, navigation_node::TYPE_SETTING);
        }

        /**
        * If we want parents to be able to unenrol their children from the course
        * Add links to do this in the course administration menu
        */
        if ($instance->customint8 || $instance->customchar3) {
            // Get current user's children
            $children = $this->get_users_children($USER->id);

            // Should there be a link to enrol more children?
            $showenrolmorechildrenlink = false;

            foreach ($children as $child) {
                // Is this child enrolled in the course?
                if ($this->user_is_enrolled($child->userid, $instance->id)) {
                    // Can parent unenrol?
                    if ($instance->customchar2) {
                        $str = get_string('unenrolchildlink', 'enrol_self_parents', $child);
                        $instancesnode->parent->parent->add(
                            $str,
                            "/enrol/self_parents/unenrolchild.php?enrolid={$instance->id}&childuserid={$child->userid}",
                            navigation_node::TYPE_SETTING
                        );

                        /*
                            About the parent->parent thing...
                            If we just used this to add the menu item:
                            $instancesnode->add('Testing', '#', navigation_node::TYPE_SETTING);
                            then the link would get added into a submenu for this enrolment plugin (like this http://ctrlv.in/262781)
                            So we add it to the parent's parent so it goes into the main menu (like this http://ctrlv.in/262782)
                        */

                    }

                } else if ($instance->customint8) {
                    // This child isn't enrolled, so show the link to the enrol page for this course
                    $showenrolmorechildrenlink = true;
                }
            }

            if ($showenrolmorechildrenlink) {
                $instancesnode->parent->parent->add(
                    get_string('enrolchildrenlink', 'enrol_self_parents'),
                    "/enrol/index.php?id={$instance->courseid}",
                    navigation_node::TYPE_SETTING
                );
            }
        }

    }

    /**
     * Returns edit icons for the page with list of instances
     * @param stdClass $instance
     * @return array
     */
    public function get_action_icons(stdClass $instance) {

        global $OUTPUT;

        if ($instance->enrol !== 'self_parents') {
            throw new coding_exception('invalid enrol instance!');
        }
        $context = context_course::instance($instance->courseid);

        $icons = array();

        if (has_capability('enrol/self_parents:config', $context)) {
            $editlink = new moodle_url("/enrol/self_parents/edit.php", array('courseid' => $instance->courseid, 'id' => $instance->id));
            $icons[] = $OUTPUT->action_icon($editlink, new pix_icon(
                't/edit',
                get_string('edit'),
                'core',
                array('class' => 'iconsmall')
            ));
        }

        return $icons;
    }

    /**
     * Returns link to page which may be used to add new instance of enrolment plugin in course.
     * @param int $courseid
     * @return moodle_url page url
     */
    public function get_newinstance_link($courseid) {

        $context = context_course::instance($courseid, MUST_EXIST);

        if (!has_capability('moodle/course:enrolconfig', $context) or !has_capability('enrol/self_parents:config', $context)) {
            return null;
        }
        // Multiple instances supported - different roles with different password.
        return new moodle_url('/enrol/self_parents/edit.php', array('courseid' => $courseid));
    }

    /**
     * Self enrol user to course
     *
     * @param stdClass $instance enrolment instance
     * @param stdClass $data data needed for enrolment.
     * @return bool|array true if enroled else eddor code and messege
     */
    public function enrol_self_parents(stdClass $instance, $data = null) {

        global $DB, $USER, $CFG;

        // Don't enrol user if password is not passed when required.
        if ($instance->password && !isset($data->enrolpassword)) {
            return;
        }

        $timestart = time();
        if ($instance->enrolperiod) {
            $timeend = $timestart + $instance->enrolperiod;
        } else {
            $timeend = 0;
        }

        // Deterime who to enrol
        $useridstoenrol = array();

        if (isset($data->enrolchildsubmit)) {
            // The enrol my child button was clicked
            // Enrol the given userIDs
            foreach ($data->enrolchilduserids as $userid => $null) {
                $useridstoenrol[] = $userid;
            }
        } else {
            // The enrol self button was clicked
            // Enrol the current user
            $useridstoenrol[] = $USER->id;
        }

        // Now do the enrolment
        foreach ($useridstoenrol as $useridtoenrol) {
            $this->enrol_user($instance, $useridtoenrol, $instance->roleid, $timestart, $timeend);

            // TODO: This isn't tested
            if ($instance->password and $instance->customint1 and $data->enrolpassword !== $instance->password) {
                // it must be a group enrolment, let's assign group too
                $groups = $DB->get_records('groups', array('courseid' => $instance->courseid), 'id', 'id, enrolmentkey');
                foreach ($groups as $group) {
                    if (empty($group->enrolmentkey)) {
                        continue;
                    }
                    if ($group->enrolmentkey === $data->enrolpassword) {
                        groups_add_member($group->id, $useridtoenrol);
                        break;
                    }
                }
            }

            // Send welcome message to user
            if ($instance->customint4) {
                $this->email_welcome_message($instance, $USER);
            }
        }

        // Save custom checkbox data

        // Using $_POST instead of $data because Moodle doesn't
        // put form fields into the $data object unless they were added
        // to the form using the API. The customtext2 checkbox is added manually to make it
        // appear inline next to the user.
        if ($instance->customtext2 && isset($_POST['customtext2']) && is_array($_POST['customtext2'])) {
            foreach ($_POST['customtext2'] as $userid => $value) {
                $this->set_custom_data($instance->id, $userid, $value);
            }
        }

        return $useridstoenrol;
    }

    /**
     * Creates course enrol form, checks if form submitted
     * and enrols user if necessary. It can also redirect.
     *
     * @param stdClass $instance
     * @return string html text, usually a form in a text box
     */
    public function enrol_page_hook(stdClass $instance) {

        global $CFG, $OUTPUT, $USER;

        require_once("$CFG->dirroot/enrol/self_parents/locallib.php");

        $enrolstatus = $this->can_user_enrol($instance);

        // Don't show enrolment instance form, if user can't enrol using it.
        if (true === $enrolstatus) {
            $form = new enrol_self_parents_enrol_form(null, $instance);
            $instanceid = optional_param('instance', 0, PARAM_INT);
            if ($instance->id == $instanceid) {
                // If form has been posted, do the enrolment
                if ($data = $form->get_data()) {
                    $this->enrol_self_parents($instance, $data);
                }
            }

            ob_start();
            $form->display();
            $output = ob_get_clean();
            return $OUTPUT->box($output);
        } else {
            return $OUTPUT->box($enrolstatus);
        }
    }

    /**
     * Checks if user can self enrol.
     *
     * @param stdClass $instance enrolment instance
     * @param bool $checkuserenrolment if true will check if user enrolment is inactive.
     *             used by navigation to improve performance.
     * @return bool|string true if successful, else error message or false.
     */
    public function can_user_enrol(stdClass $instance, $checkuserenrolment = true) {

        global $DB, $USER, $CFG;

        if (!$instance->customint8 && $checkuserenrolment) {
            if (isguestuser()) {
                // Can not enrol guest.
                return get_string('noguestaccess', 'enrol');
            }
            // Check if user is already enroled.
            if ($DB->get_record('user_enrolments', array('userid' => $USER->id, 'enrolid' => $instance->id))) {
                return get_string('canntenrol', 'enrol_self_parents');
            }
        }

        if ($instance->status != ENROL_INSTANCE_ENABLED) {
            return get_string('canntenrol', 'enrol_self_parents');
        }

        if ($instance->enrolstartdate != 0 and $instance->enrolstartdate > time()) {
            return get_string('canntenrol', 'enrol_self_parents');
        }

        if ($instance->enrolenddate != 0 and $instance->enrolenddate < time()) {
            return get_string('canntenrol', 'enrol_self_parents');
        }

        if (!$instance->customint6) {
            // New enrols not allowed.
            return get_string('canntenrol', 'enrol_self_parents');
        }

        // Added the if block around this because it would always check
        // even if $checkuserenrolment was false
        // TODO: Is that a bug that should be patched in Moodle?
        if (!$instance->customint8 && $checkuserenrolment) {
            if ($DB->record_exists('user_enrolments', array('userid' => $USER->id, 'enrolid' => $instance->id))) {
                return get_string('canntenrol', 'enrol_self_parents');
            }
        }

        if ($instance->customint3 > 0) {
            // Max enrol limit specified.
            $count = $DB->count_records('user_enrolments', array('enrolid' => $instance->id));

            if ($instance->customchar3) {
                // Count parents in enrol limit
                $count = $DB->count_records('user_enrolments', array('enrolid' => $instance->id));
            } else {
                // Don't count parents in enrol limit
                $q = 'select count(*) from {user_enrolments} ue
                join {enrol} enrl on enrl.id = ue.enrolid
                join {context} ctx on ctx.instanceid = enrl.courseid and ctx.contextlevel = 50
                join {role_assignments} ra on ra.userid = ue.userid and ra.contextid = ctx.id
                where ue.enrolid = ? and ra.roleid != ?';
                $count = $DB->get_field_sql($q, array(
                    $instance->id,
                    $instance->customchar1 // Parent role id
                ));

            }

            if ($count >= $instance->customint3) {
                // Bad luck, no more self enrolments here.
                return get_string('maxenrolledreached', 'enrol_self_parents');
            }
        }

        // Cohort check is now done in the form definition instead
        // To allow parents to enrol their children

        return true;
    }

    /**
     * Return information for enrolment instance containing list of parameters required
     * for enrolment, name of enrolment plugin etc.
     *
     * @param stdClass $instance enrolment instance
     * @return stdClass instance info.
     */
    public function get_enrol_info(stdClass $instance) {

        $instanceinfo = new stdClass();
        $instanceinfo->id = $instance->id;
        $instanceinfo->courseid = $instance->courseid;
        $instanceinfo->type = $this->get_name();
        $instanceinfo->name = $this->get_instance_name($instance);
        $instanceinfo->status = $this->can_user_enrol($instance);

        if ($instance->password) {
            $instanceinfo->requiredparam = new stdClass();
            $instanceinfo->requiredparam->enrolpassword = get_string('password', 'enrol_self_parents');
        }

        // If enrolment is possible and password is required then return ws function name to get more information.
        if ((true === $instanceinfo->status) && $instance->password) {
            $instanceinfo->wsfunction = 'enrol_self_parents_get_instance_info';
        }
        return $instanceinfo;
    }

    /**
     * Add new instance of enrol plugin with default settings.
     * @param stdClass $course
     * @return int id of new instance
     */
    public function add_default_instance($course) {

        $fields = $this->get_instance_defaults();

        if ($this->get_config('requirepassword')) {
            $fields['password'] = generate_password(20);
        }

        return $this->add_instance($course, $fields);
    }

    /**
     * Returns defaults for new instances.
     * @return array
     */
    public function get_instance_defaults() {

        $expirynotify = $this->get_config('expirynotify');
        if ($expirynotify == 2) {
            $expirynotify = 1;
            $notifyall = 1;
        } else {
            $notifyall = 0;
        }

        $fields = array();
        $fields['status']          = $this->get_config('status');
        $fields['roleid']          = $this->get_config('roleid');
        $fields['enrolperiod']     = $this->get_config('enrolperiod');
        $fields['expirynotify']    = $expirynotify;
        $fields['notifyall']       = $notifyall;
        $fields['expirythreshold'] = $this->get_config('expirythreshold');
        $fields['customint1']      = $this->get_config('groupkey');
        $fields['customint2']      = $this->get_config('longtimenosee');
        $fields['customint3']      = $this->get_config('maxenrolled');
        $fields['customint4']      = $this->get_config('sendcoursewelcomemessage');
        $fields['customint5']      = 0;
        $fields['customint6']      = $this->get_config('newenrols');
        // customit7 was previously used for SSIS bus requirement. But that is now done via customtext1
        $fields['customint8']      = $this->get_config('defaultparentscanenrol');
        $fields['customchar1']     = $this->get_config('defaultparentrole');
        $fields['customchar2']     = $this->get_config('defaultparentscanunenrol');
        $fields['customchar3']     = $this->get_config('defaultparentscountedinmaxenrolled');

        $fields['customtext2']     = $this->get_config('defaultcustomtext2');

        return $fields;
    }

    /**
     * Send welcome email to specified user.
     *
     * @param stdClass $instance
     * @param stdClass $user user record
     * @return void
     */
    protected function email_welcome_message($instance, $user) {

        global $CFG, $DB;

        $course = $DB->get_record('course', array('id' => $instance->courseid), '*', MUST_EXIST);
        $context = context_course::instance($course->id);

        $a = new stdClass();
        $a->coursename = format_string($course->fullname, true, array('context' => $context));
        $a->profileurl = "$CFG->wwwroot/user/view.php?id=$user->id&course=$course->id";

        if (trim($instance->customtext1) !== '') {
            $message = $instance->customtext1;
            $message = str_replace('{$a->coursename}', $a->coursename, $message);
            $message = str_replace('{$a->profileurl}', $a->profileurl, $message);
            if (strpos($message, '<') === false) {
                // Plain text only.
                $messagetext = $message;
                $messagehtml = text_to_html($messagetext, null, false, true);
            } else {
                // This is most probably the tag/newline soup known as FORMAT_MOODLE.
                $messagehtml = format_text($message, FORMAT_MOODLE, array('context' => $context, 'para' => false, 'newlines' => true, 'filter' => true));
                $messagetext = html_to_text($messagehtml);
            }
        } else {
            $messagetext = get_string('welcometocoursetext', 'enrol_self_parents', $a);
            $messagehtml = text_to_html($messagetext, null, false, true);
        }

        $subject = get_string('welcometocourse', 'enrol_self_parents', format_string($course->fullname, true, array('context' => $context)));

        $rusers = array();
        if (!empty($CFG->coursecontact)) {
            $croles = explode(',', $CFG->coursecontact);
            list($sort, $sortparams) = users_order_by_sql('u');
            // We only use the first user.
            $i = 0;
            do {
                $rusers = get_role_users(
                    $croles[$i],
                    $context,
                    true,
                    '',
                    'r.sortorder ASC, ' . $sort,
                    null,
                    '',
                    '',
                    '',
                    '',
                    $sortparams
                );
                $i++;
            } while (empty($rusers) && !empty($croles[$i]));
        }
        if ($rusers) {
            $contact = reset($rusers);
        } else {
            $contact = core_user::get_support_user();
        }

        // Directly emailing welcome message rather than using messaging.
        email_to_user($user, $contact, $subject, $messagetext, $messagehtml);
    }

    /**
     * Enrol self cron support.
     * @return void
     */
    public function cron() {

        $trace = new text_progress_trace();
        $this->sync($trace, null);
        $this->send_expiry_notifications($trace);
    }

    /**
     * Sync all meta course links.
     *
     * @param progress_trace $trace
     * @param int $courseid one course, empty mean all
     * @return int 0 means ok, 1 means error, 2 means plugin disabled
     */
    public function sync(progress_trace $trace, $courseid = null) {

        global $DB;

        if (!enrol_is_enabled('self_parents')) {
            $trace->finished();
            return 2;
        }

        // Unfortunately this may take a long time, execution can be interrupted safely here.
        core_php_time_limit::raise();
        raise_memory_limit(MEMORY_HUGE);

        $trace->output('Verifying self-enrolments...');

        $params = array('now' => time(), 'useractive' => ENROL_USER_ACTIVE, 'courselevel' => CONTEXT_COURSE);
        $coursesql = "";
        if ($courseid) {
            $coursesql = "AND e.courseid = :courseid";
            $params['courseid'] = $courseid;
        }

        // Note: the logic of self enrolment guarantees that user logged in at least once (=== u.lastaccess set)
        //       and that user accessed course at least once too (=== user_lastaccess record exists).

        // First deal with users that did not log in for a really long time - they do not have user_lastaccess records.
        $sql = "SELECT e.*, ue.userid
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON (e.id = ue.enrolid AND e.enrol = 'self' AND e.customint2 > 0)
                  JOIN {user} u ON u.id = ue.userid
                 WHERE :now - u.lastaccess > e.customint2
                       $coursesql";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $instance) {
            $userid = $instance->userid;
            unset($instance->userid);
            $this->unenrol_user($instance, $userid);
            $days = $instance->customint2 / 60 * 60 * 24;
            $trace->output("unenrolling user $userid from course $instance->courseid as they have did not log in for at least $days days", 1);
        }
        $rs->close();

        // Now unenrol from course user did not visit for a long time.
        $sql = "SELECT e.*, ue.userid
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON (e.id = ue.enrolid AND e.enrol = 'self' AND e.customint2 > 0)
                  JOIN {user_lastaccess} ul ON (ul.userid = ue.userid AND ul.courseid = e.courseid)
                 WHERE :now - ul.timeaccess > e.customint2
                       $coursesql";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $instance) {
            $userid = $instance->userid;
            unset($instance->userid);
            $this->unenrol_user($instance, $userid);
                $days = $instance->customint2 / 60 * 60 * 24;
            $trace->output("unenrolling user $userid from course $instance->courseid as they have did not access course for at least $days days", 1);
        }
        $rs->close();

        $trace->output('...user self-enrolment updates finished.');
        $trace->finished();

        $this->process_expirations($trace, $courseid);

        return 0;
    }

    /**
     * Returns the user who is responsible for self enrolments in given instance.
     *
     * Usually it is the first editing teacher - the person with "highest authority"
     * as defined by sort_by_roleassignment_authority() having 'enrol/self_parents:manage'
     * capability.
     *
     * @param int $instanceid enrolment instance id
     * @return stdClass user record
     */
    protected function get_enroller($instanceid) {

        global $DB;

        if ($this->lasternollerinstanceid == $instanceid and $this->lasternoller) {
            return $this->lasternoller;
        }

        $instance = $DB->get_record('enrol', array('id' => $instanceid, 'enrol' => $this->get_name()), '*', MUST_EXIST);
        $context = context_course::instance($instance->courseid);

        if ($users = get_enrolled_users($context, 'enrol/self_parents:manage')) {
            $users = sort_by_roleassignment_authority($users, $context);
            $this->lasternoller = reset($users);
            unset($users);
        } else {
            $this->lasternoller = parent::get_enroller($instanceid);
        }

        $this->lasternollerinstanceid = $instanceid;

        return $this->lasternoller;
    }

    /**
     * Gets an array of the user enrolment actions.
     *
     * @param course_enrolment_manager $manager
     * @param stdClass $ue A user enrolment object
     * @return array An array of user_enrolment_actions
     */
    public function get_user_enrolment_actions(course_enrolment_manager $manager, $ue) {

        $actions = array();
        $context = $manager->get_context();
        $instance = $ue->enrolmentinstance;
        $params = $manager->get_moodlepage()->url->params();
        $params['ue'] = $ue->id;
        if ($this->allow_unenrol($instance) && has_capability("enrol/self_parents:unenrol", $context)) {
            $url = new moodle_url('/enrol/unenroluser.php', $params);
            $actions[] = new user_enrolment_action(new pix_icon('t/delete', ''), get_string('unenrol', 'enrol'), $url, array('class' => 'unenrollink', 'rel' => $ue->id));
        }
        if ($this->allow_manage($instance) && has_capability("enrol/self_parents:manage", $context)) {
            $url = new moodle_url('/enrol/editenrolment.php', $params);
            $actions[] = new user_enrolment_action(new pix_icon('t/edit', ''), get_string('edit'), $url, array('class' => 'editenrollink', 'rel' => $ue->id));
        }
        return $actions;
    }

    /**
     * Restore instance and map settings.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $course
     * @param int $oldid
     */
    public function restore_instance(restore_enrolments_structure_step $step, stdClass $data, $course, $oldid) {

        global $DB;
        if ($step->get_task()->get_target() == backup::TARGET_NEW_COURSE) {
            $merge = false;
        } else {
            $merge = array(
                'courseid'   => $data->courseid,
                'enrol'      => $this->get_name(),
                'roleid'     => $data->roleid,
            );
        }
        if ($merge and $instances = $DB->get_records('enrol', $merge, 'id')) {
            $instance = reset($instances);
            $instanceid = $instance->id;
        } else {
            if (!empty($data->customint5)) {
                if (!$step->get_task()->is_samesite()) {
                    // Use some id that can not exist in order to prevent self enrolment,
                    // because we do not know what cohort it is in this site.
                    $data->customint5 = -1;
                }
            }
            $instanceid = $this->add_instance($course, (array)$data);
        }
        $step->set_mapping('enrol', $oldid, $instanceid);
    }

    /**
     * Restore user enrolment.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $instance
     * @param int $oldinstancestatus
     * @param int $userid
     */
    public function restore_user_enrolment(restore_enrolments_structure_step $step, $data, $instance, $userid, $oldinstancestatus) {

        $this->enrol_user($instance, $userid, null, $data->timestart, $data->timeend, $data->status);
    }

    /**
     * Restore role assignment.
     *
     * @param stdClass $instance
     * @param int $roleid
     * @param int $userid
     * @param int $contextid
     */
    public function restore_role_assignment($instance, $roleid, $userid, $contextid) {

        // This is necessary only because we may migrate other types to this instance,
        // we do not use component in manual or self enrol.
        role_assign($roleid, $userid, $contextid, '', 0);
    }

    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_delete_instance($instance) {

        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/self_parents:config', $context);
    }

    /**
     * Is it possible to hide/show enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {

        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/self_parents:config', $context);
    }


    // Parent enrolment additions

    /**
     * Returns people who are "mentors" of the given userID
     */
    private function get_users_parents($userid) {

        global $DB;
        $usercontexts = $DB->get_records_sql("
            SELECT
                ra.userid,
                u.username,
                u.firstname,
                u.lastname
            FROM {role_assignments} ra, {context} c, {user} u
            WHERE
                c.contextlevel = " . CONTEXT_USER . "
                AND c.instanceid = ?
                AND ra.contextid = c.id
                AND u.id = ra.userid
                ", array($userid));
        return $usercontexts;
    }

    /**
     * Returns people who are "mentees" of the given userID
     */
    public function get_users_children($userid) {

        global $DB;
        $usercontexts = $DB->get_records_sql("
            SELECT
                c.instanceid,
                c.instanceid,
                u.id AS userid,
                u.firstname,
                u.lastname
             FROM {role_assignments} ra, {context} c, {user} u
             WHERE ra.userid = ?
                  AND ra.contextid = c.id
                  AND c.instanceid = u.id
                  AND c.contextlevel = " . CONTEXT_USER, array($userid));
        return $usercontexts;
    }

    /**
     * Override the enrol_user method from enrol_plugin
     * First it just calls enrol_user in enrol_plugin, then it enrols
     * the users parents as well
     */
    public function enrol_user(stdClass $instance, $userid, $roleid = null, $timestart = 0, $timeend = 0, $status = null, $recovergrades = null) {

        // Enrol the user as normal
        parent::enrol_user($instance, $userid, $roleid, $timestart, $timeend, $status, $recovergrades);
        // That doesn't return anything so we have to assume it worked

        // Get the user's parents
        $parents = $this->get_users_parents($userid);
        foreach ($parents as $parent) {
            // Check if parent is enrolled
            if (!$this->user_is_enrolled($parent->userid, $instance->id)) {
                // Parent isn't enrolled - enrol them!
                $this->enrol_user($instance, $parent->userid, $instance->customchar1);
            }
        }
    }

    public function unenrol_user(stdClass $instance, $userid) {

        // Unenrol the user as normal
        parent::unenrol_user($instance, $userid);
        // That doesn't return anything so we have to assume it worked

        // Get the user's parents
        $parents = $this->get_users_parents($userid);
        foreach ($parents as $parent) {
            // Check if parent is enrolled
            if ($this->user_is_enrolled($parent->userid, $instance->id)) {
                // Parent is enrolled - we're going to unenrol them
                // unless they have other children who are still enrolled
                $unenrolparent = true;

                // Check if the parent still has other children who are enrolled
                $children = $this->get_users_children($parent->userid);
                foreach ($children as $child) {
                    if ($this->user_is_enrolled($child->userid, $instance->id)) {
                        // Child is still enrolled - not going to unenrol the parent
                        $unenrolparent = false;
                        break;
                    }
                }

                if ($unenrolparent) {
                    // User has no children, or all of their children are unenrolled - unenrol the parent
                    $this->unenrol_user($instance, $parent->userid);
                }

            }
        }
    }


   /**
    * Returns true or false if the given userid is enrolled in the given enrolment instance
    */
    public function user_is_enrolled($userid, $instanceid) {
        global $DB;
        return $DB->record_exists('user_enrolments', array('userid' => $userid, 'enrolid' => $instanceid));
    }

    /**
     * Custom data
     */
    public function get_custom_data($instanceid, $userid) {
        global $DB;
        try {
            $value = $DB->get_field('enrol_self_parents_data', 'customtext2_value', array('enrolid' => $instanceid, 'userid' => $userid), MUST_EXIST);
            return $value ? 1 : 0;
        } catch (Exception $e) {
            return null;
        }

    }

    public function set_custom_data($instanceid, $userid, $value) {

        global $DB;

        // Normalize value
        $value = $value ? 1 : 0;

        if ($this->get_custom_data($instanceid, $userid) !== null) {
            // Already set - UPDATE
            return $DB->set_field('enrol_self_parents_data', 'customtext2_value', $value, array('enrolid' => $instanceid, 'userid' => $userid));

        } else {
            // Not already set - INSERT
            $row = new stdClass;
            $row->enrolid = $instanceid;
            $row->userid = $userid;
            $row->customtext2_value = $value;
            return $DB->insert_record('enrol_self_parents_data', $row);
        }
    }
}
