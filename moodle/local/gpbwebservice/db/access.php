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
 * Plugin capabilities for the plugintype_pluginname plugin.
 *
 * @package   plugintype_pluginname
 * @copyright Year, You Name <your@email.address>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$capabilities = [
    // Ability to use the plugin.
    'local/gpb_webservice:useplugininstance' => [
        'riskbitmask' => 0,
        'captype' => 'write',
        //CONTEXT_SYSTEM -- the whole site
        //CONTEXT_USER -- another user
        //CONTEXT_COURSECAT -- a course category
        //CONTEXT_COURSE -- a course
        //CONTEXT_MODULE -- an activity module
        //CONTEXT_BLOCK 
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
           'guest' => CAP_ALLOW,
           'student' => CAP_ALLOW,
           'teacher' => CAP_ALLOW,
           'editingteacher' => CAP_ALLOW,
           'coursecreator' => CAP_ALLOW,
           'admin' => CAP_ALLOW
        ],
    ],
];