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
 * Implementaton of the quizaccess_seb_autologin plugin.
 *
 * @package   quizaccess_seb_autologin
 * @author    ETH Zurich (moodle@id.ethz.ch)
 * @copyright 2024 ETH Zurich
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_quiz\local\access_rule_base;
use mod_quiz\quiz_attempt;
use mod_quiz\quiz_settings;
use quizaccess_seb\seb_access_manager;

/**
 * A rule requiring SEB Server connection.
 *
 * @copyright  2022 ETH Zurich
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_seb_autologin extends access_rule_base {

    /**
     * Return an appropriately configured instance of this rule, if it is applicable
     * to the given quiz, otherwise return null.
     *
     * @param quiz_settings $quizobj information about the quiz in question.
     * @param int $timenow the time that should be considered as 'now'.
     * @param bool $canignoretimelimits whether the current user is exempt from
     *      time limits by the mod/quiz:ignoretimelimits capability.
     * @return access_rule_base|null the rule, if applicable, else null.
     */
    public static function make(quiz_settings $quizobj, $timenow, $canignoretimelimits) {

        if (empty($quizobj->get_quiz()->sebautologinenabled)) {
            return null;
        }
        return new self($quizobj, $timenow);
    }

    /**
     * Add any fields that this rule requires to the quiz settings form.
     *
     * @param mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param MoodleQuickForm $mform the wrapped MoodleQuickForm.
     */
    public static function add_settings_form_fields(mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {
        global $DB;
        $quizid = $ineditmode = $quizform->get_instance();
        $displaydwnloadbutton = [];
        $mform->addElement('header', 'seb_autologinheader', get_string('pluginname', 'quizaccess_seb_autologin'));
        $mform->addElement('selectyesno', 'sebautologinenabled', get_string('enableseb_autologin', 'quizaccess_seb_autologin'));
        $mform->setType('sebautologinenabled', PARAM_INT);
        $mform->disabledif ('sebautologinenabled', 'seb_requiresafeexambrowser', 'in', [0, 2, 4]);

    }

    /**
     * Information, such as might be shown on the quiz view page, relating to this restriction.
     * There is no obligation to return anything. If it is not appropriate to tell students
     * about this rule, then just return ''.
     *
     * @return mixed a message, or array of messages, explaining the restriction
     *         (may be '' if no message is appropriate).
     */
    public function description() {
        global $CFG, $DB, $USER, $PAGE, $SESSION, $OUTPUT;

        $quizid = $this->quizobj->get_quizid();
        $cmid = $this->quizobj->get_cmid();
        $return = '';
        $seblink = new moodle_url('/mod/quiz/accessrule/seb/config.php',
                                    ['cmid' => $cmid]);
        is_https() ? $seblink->set_scheme('sebs') : $seblink->set_scheme('seb');
        // Autologin area (only non admins).
        if (!has_capability('moodle/site:config', context_system::instance(), $USER->id) &&
            !is_siteadmin($USER->id)) {
            // Delete previous keys.
            delete_user_key('quizaccess_seb_autologin', $USER->id);
            // Create a new key.
            $iprestriction = getremoteaddr();
            $validuntil = time() + 900; // Expires in 15 mins.
            $key = create_user_key('quizaccess_seb_autologin', $USER->id, $cmid, $iprestriction, $validuntil);
            $params = ['id' => $cmid, 'userid' => $USER->id, 'key' => $key, 'urltogo' => $seblink];
            $autologinurl = new moodle_url('/mod/quiz/accessrule/sebserver/sebclientautologin.php?',
                                        $params);
            is_https() ? $autologinurl->set_scheme('sebs') : $autologinurl->set_scheme('seb');
            $return .= '
                    <script type="text/javascript" charset="utf-8">
                    window.onload = function(event) {
                        var els = document.querySelectorAll("a[href=\'' . $seblink->out() . '\']");
                        for (var i = 0, l = els.length; i < l; i++) {
                            var el = els[i];
                            el.setAttribute("href", "'. $autologinurl->out(false) . '");
                            el.addEventListener("click", function() { this.setAttribute("disabled", true); });
                        }
                    };
                    </script>
            ';
        }
        return $return;

    }
    /**
     * Save any submitted settings when the quiz settings form is submitted.
     *
     * @param stdClass $quiz the data from the quiz form, including $quiz->id
     *      which is the id of the quiz being saved.
     */
    public static function save_settings($quiz) {
        global $DB;
        if (!isset($quiz->sebautologinenabled)) {
            $quiz->sebautologinenabled = 0;
        }
        $rec = $DB->get_record('quizaccess_seb_autologin', ['sebautologinquizid' => $quiz->id]);
        if (!$rec) {
            $record = new stdClass();
            $record->sebautologinquizid = $quiz->id;
            $record->sebautologinenabled = $quiz->sebautologinenabled;
            $DB->insert_record('quizaccess_seb_autologin', $record);
        } else {
            $record = new stdClass();
            $record->id = $rec->id;
            $record->autologinquizid = $quiz->id;
            $record->sebautologinenabled = $quiz->sebautologinenabled;
            $DB->update_record('quizaccess_seb_autologin', $record);
        }
    }

    /**
     * Delete any rule-specific settings when the quiz is deleted.
     *
     * @param stdClass $quiz the data from the database, including $quiz->id
     *      which is the id of the quiz being deleted.
     */
    public static function delete_settings($quiz) {
        global $DB;
        $DB->delete_records('quizaccess_seb_autologin', ['sebautologinquizid' => $quiz->id]);
    }

    /**
     * Return the bits of SQL needed to load all the settings from all the access
     * plugins in one DB query.
     *
     * @param int $quizid the id of the quiz we are loading settings for. This
     *     can also be accessed as quiz.id in the SQL. (quiz is a table alisas for {quiz}.)
     * @return array with three elements:
     *     1. fields: any fields to add to the select list. These should be alised
     *        if neccessary so that the field name starts the name of the plugin.
     *     2. joins: any joins (should probably be LEFT JOINS) with other tables that
     *        are needed.
     *     3. params: array of placeholder values that are needed by the SQL. You must
     *        used named placeholders, and the placeholder names should start with the
     *        plugin name, to avoid collisions.
     */
    public static function get_settings_sql($quizid): array {

        return [
            'seb_autologin.id as seb_autologinid, seb_autologin.sebautologinquizid as sebautologinquizid,
             seb_autologin.sebautologinenabled as sebautologinenabled',
            'LEFT JOIN {quizaccess_seb_autologin} seb_autologin ON seb_autologin.sebautologinquizid = quiz.id',
            [],
        ];
    }
}
