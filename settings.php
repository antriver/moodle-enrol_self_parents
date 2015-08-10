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
 * Self enrolment plugin settings and presets.
 *
 * @package    enrol_self_parents
 * @copyright  2010 Petr Skoda  {@link http://skodak.org}, 2015 Anthony Kuske <www.anthonykuske.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    /**
     * General settings
     */
    $settings->add(new admin_setting_heading('enrol_self_parents_settings', '', get_string('pluginname_desc', 'enrol_self_parents')));

    $settings->add(new admin_setting_configcheckbox(
        'enrol_self_parents/requirepassword',
        get_string('requirepassword', 'enrol_self_parents'),
        get_string('requirepassword_desc', 'enrol_self_parents'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'enrol_self_parents/usepasswordpolicy',
        get_string('usepasswordpolicy', 'enrol_self_parents'),
        get_string('usepasswordpolicy_desc', 'enrol_self_parents'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'enrol_self_parents/showhint',
        get_string('showhint', 'enrol_self_parents'),
        get_string('showhint_desc', 'enrol_self_parents'),
        0
    ));

    // Note: let's reuse the ext sync constants and strings here, internally it is very similar,
    //       it describes what should happend when users are not supposed to be enerolled any more.
    $options = array(
        ENROL_EXT_REMOVED_KEEP           => get_string('extremovedkeep', 'enrol'),
        ENROL_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'enrol'),
        ENROL_EXT_REMOVED_UNENROL        => get_string('extremovedunenrol', 'enrol'),
    );
    $settings->add(new admin_setting_configselect(
        'enrol_self_parents/expiredaction',
        get_string('expiredaction', 'enrol_self_parents'),
        get_string('expiredaction_help', 'enrol_self_parents'),
        ENROL_EXT_REMOVED_KEEP,
        $options
    ));

    $options = array();
    for ($i = 0; $i < 24; $i++) {
        $options[$i] = $i;
    }
    $settings->add(new admin_setting_configselect('enrol_self_parents/expirynotifyhour', get_string('expirynotifyhour', 'core_enrol'), '', 6, $options));

    /**
     * Enrol instance defaults
     */
    $settings->add(new admin_setting_heading(
        'enrol_self_parents_defaults',
        get_string('enrolinstancedefaults', 'admin'),
        get_string('enrolinstancedefaults_desc', 'admin')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'enrol_self_parents/defaultenrol',
        get_string('defaultenrol', 'enrol'),
        get_string('defaultenrol_desc', 'enrol'),
        0
    ));

    $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'),
                     ENROL_INSTANCE_DISABLED => get_string('no'));
    $settings->add(new admin_setting_configselect(
        'enrol_self_parents/status',
        get_string('status', 'enrol_self_parents'),
        get_string('status_desc', 'enrol_self_parents'),
        ENROL_INSTANCE_DISABLED,
        $options
    ));

    $options = array(1  => get_string('yes'), 0 => get_string('no'));
    $settings->add(new admin_setting_configselect(
        'enrol_self_parents/newenrols',
        get_string('newenrols', 'enrol_self_parents'),
        get_string('newenrols_desc', 'enrol_self_parents'),
        1,
        $options
    ));

    $options = array(1  => get_string('yes'),
                     0 => get_string('no'));
    $settings->add(new admin_setting_configselect(
        'enrol_self_parents/groupkey',
        get_string('groupkey', 'enrol_self_parents'),
        get_string('groupkey_desc', 'enrol_self_parents'),
        0,
        $options
    ));

    if (!during_initial_install()) {
        $options = get_default_enrol_roles(context_system::instance());
        $student = get_archetype_roles('student');
        $student = reset($student);
        $settings->add(new admin_setting_configselect(
            'enrol_self_parents/roleid',
            get_string('defaultrole', 'enrol_self_parents'),
            get_string('defaultrole_desc', 'enrol_self_parents'),
            $student->id,
            $options
        ));
    }

    $settings->add(new admin_setting_configduration(
        'enrol_self_parents/enrolperiod',
        get_string('enrolperiod', 'enrol_self_parents'),
        get_string('enrolperiod_desc', 'enrol_self_parents'),
        0
    ));

    $options = array(0 => get_string('no'), 1 => get_string('expirynotifyenroller', 'core_enrol'), 2 => get_string('expirynotifyall', 'core_enrol'));
    $settings->add(new admin_setting_configselect(
        'enrol_self_parents/expirynotify',
        get_string('expirynotify', 'core_enrol'),
        get_string('expirynotify_help', 'core_enrol'),
        0,
        $options
    ));

    $settings->add(new admin_setting_configduration(
        'enrol_self_parents/expirythreshold',
        get_string('expirythreshold', 'core_enrol'),
        get_string('expirythreshold_help', 'core_enrol'),
        86400,
        86400
    ));

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
    $settings->add(new admin_setting_configselect(
        'enrol_self_parents/longtimenosee',
        get_string('longtimenosee', 'enrol_self_parents'),
        get_string('longtimenosee_help', 'enrol_self_parents'),
        0,
        $options
    ));

    $settings->add(new admin_setting_configtext(
        'enrol_self_parents/maxenrolled',
        get_string('maxenrolled', 'enrol_self_parents'),
        get_string('maxenrolled_help', 'enrol_self_parents'),
        0,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'enrol_self_parents/sendcoursewelcomemessage',
        get_string('sendcoursewelcomemessage', 'enrol_self_parents'),
        get_string('sendcoursewelcomemessage_help', 'enrol_self_parents'),
        1
    ));


    // customint8: Default value for parents can enrol children
    $settings->add(new admin_setting_configcheckbox(
        'enrol_self_parents/defaultparentscanenrol',
        get_string('parents_can_enrol_checkbox', 'enrol_self_parents'),
        '',
        1
    ));

    // customchar2: Default value for parents can enrol children
    $settings->add(new admin_setting_configcheckbox(
        'enrol_self_parents/defaultparentscanunenrol',
        get_string('parents_can_unenrol_checkbox', 'enrol_self_parents'),
        '',
        1
    ));

    // customchar1 Default role for parents
    /**
     * In a default installation, roleid 6 is 'guest' so that
     * seems like a sensible default value
     */
    if (!during_initial_install()) {
        $options = get_default_enrol_roles(context_system::instance());
        $guest = get_archetype_roles('guest');
        $guest = reset($guest);
        $settings->add(new admin_setting_configselect(
            'enrol_self_parents/defaultparentrole',
            get_string('defaultparentrole', 'enrol_self_parents'),
            get_string('defaultparentrole_desc', 'enrol_self_parents'),
            $guest->id,
            $options
        ));
    }

    // customchar3: Default value for parents counted in max enrol limit
    $settings->add(new admin_setting_configcheckbox(
        'enrol_self_parents/defaultparentscountedinmaxenrolled',
        get_string('parents_counted_in_maxenrolled_checkbox', 'enrol_self_parents'),
        '',
        0
    ));


    // customtext2: Yes/no question when parent enrols child
    $settings->add(
        new admin_setting_configtext(
            'enrol_self_parents/defaultcustomtext2',
            get_string('childenrolmentquestion', 'enrol_self_parents'),
            get_string('childenrolmentquestion_help', 'enrol_self_parents'),
            ''
        )
    );
}
