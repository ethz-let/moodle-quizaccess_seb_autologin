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
 * Restore code for the quizaccess_seb_autologin plugin.
 *
 * @package   quizaccess_seb_autologin
 * @author    ETH Zurich (moodle@id.ethz.ch)
 * @copyright 2022 ETH Zurich
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/quiz/backup/moodle2/restore_mod_quiz_access_subplugin.class.php');


/**
 * Provides the information to restore the seb_autologin quiz access plugin.
 *
 * If this plugin is required, a single
 * <quizaccess_seb_autologin><required>1</required></quizaccess_seb_autologin> tag
 * will be in the XML, and this needs to be written to the DB. Otherwise, nothing
 * needs to be written to the DB.
 *
 * @copyright 2024 ETH Zurich (moodle@id.ethz.ch)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_quizaccess_seb_autologin_subplugin extends restore_mod_quiz_access_subplugin {

    /**
     * Use this method to describe the XML structure required to store your
     * sub-plugin's settings for a particular quiz, and how that data is stored
     * in the database.
     */
    protected function define_quiz_subplugin_structure() {

        $paths = [];

        $elename = $this->get_namefor('');
        $elepath = $this->get_pathfor('/quizaccess_seb_autologin');
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths;
    }

    /**
     * Processes the quizaccess_seb_autologin element, if it is in the file.
     * @param array $data the data read from the XML file.
     */
    public function process_quizaccess_seb_autologin($data) {
        global $DB;
        $data = (object)$data;
        $data->seb_autologinquizid = $this->get_new_parentid('quiz');
        $DB->insert_record('quizaccess_seb_autologin', $data);
    }
}
