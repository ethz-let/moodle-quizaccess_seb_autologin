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
 * Auto-login end-point, a user can be fully authenticated in the site providing a valid key.
 *
 * @package    quizaccess_seb_autologin
 * @copyright  2024 ETH Zurich (moodle@id.ethz.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 // No login check is expected here because it related to login key.
 // @codingStandardsIgnoreLine
require_once('../../../../config.php');

$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$key = required_param('key', PARAM_ALPHANUMEXT);
$urltogo = optional_param('urltogo', $CFG->wwwroot, PARAM_RAW);
$urltogo = $urltogo ?: $CFG->wwwroot;
$context = context_system::instance();
$PAGE->set_context($context);

// Check if the user is already logged-in.
if (isloggedin() && !isguestuser()) {
    delete_user_key( 'quizaccess_seb_autologin', $userid, $id);
    if ($USER->id == $userid) {
        // 302 might not work for POST requests, 303 is ignored by obsolete clients.
        @header($_SERVER['SERVER_PROTOCOL'] . ' 303 See Other');
        @header('Location: ' . $urltogo);
    } else {
        throw new moodle_exception('alreadyloggedin', 'error', '', format_string(fullname($USER)));
    }
}

if (!$CFG->enablewebservices) {
    throw new moodle_exception('enablewsdescription', 'webservice');
}
if (!is_https()) {
    throw new moodle_exception('httpsrequired', 'tool_mobile');
}
if (has_capability('moodle/site:config', context_system::instance(), $userid) ||
    is_siteadmin($userid)) {
    throw new moodle_exception('autologinnotallowedtoadmins', 'tool_mobile');
}
// Validate the key.
$key = validate_user_key($key, 'quizaccess_seb_autologin', $id);

// Double check key belong to user.
if ($key->userid != $userid) {
    throw new moodle_exception('Login key does not belong to the current user.
                                Either download the config file manually, or reload the exam page again.');
}

// Key validated, now require an active user: not guest, not suspended.
$user = core_user::get_user($key->userid, '*', MUST_EXIST);
core_user::require_active_user($user, true, true);

// Do the user log-in.
if (!$user = get_complete_user_data('id', $user->id)) {
    throw new moodle_exception('cannotfinduser', '', '', $user->id);
}

complete_user_login($user);
\core\session\manager::apply_concurrent_login_limit($user->id, session_id());
// Leave the key to expire on its own due to headless call twice.
// 302 might not work for POST requests, 303 is ignored by obsolete clients.
@header($_SERVER['SERVER_PROTOCOL'] . ' 303 See Other');
@header('Location: '.$urltogo);
