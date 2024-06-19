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

// Require_login is not needed here.
// phpcs:disable moodle.Files.RequireLogin.Missing

require_once(__DIR__ . '/../../../../../../config.php');

@header("X-LMS-USER-ID: $USER->id");
@header("X-LMS-USER-USERNAME: $USER->username");
@header("X-LMS-USER-EMAIL: $USER->email");
@header("X-LMS-USER-IDNUMBER: $USER->idnumber");
echo $USER->id;
