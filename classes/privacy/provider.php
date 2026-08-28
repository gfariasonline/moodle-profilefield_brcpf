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
 * Privacy API implementation for the profilefield_cpf plugin.
 *
 * @package   profilefield_cpf
 * @copyright  2026 Thiago Serrao
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace profilefield_cpf\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;

/**
 * Privacy provider for CPF values stored in the core user profile subsystem.
 *
 * @package   profilefield_cpf
 * @copyright  2026 Thiago Serrao
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Returns metadata about CPF values stored by this plugin.
     *
     * @param collection $collection The metadata collection.
     * @return collection The updated metadata collection.
     */
    public static function get_metadata(collection $collection): collection {
        return $collection->add_database_table('user_info_data', [
            'userid' => 'privacy:metadata:cpf:userid',
            'fieldid' => 'privacy:metadata:cpf:fieldid',
            'data' => 'privacy:metadata:cpf:data',
            'dataformat' => 'privacy:metadata:cpf:dataformat',
        ], 'privacy:metadata:cpf:tableexplanation');
    }

    /**
     * Returns user contexts containing CPF values for the specified user.
     *
     * @param int $userid The user ID.
     * @return contextlist The matching user contexts.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT ctx.id
                  FROM {user_info_data} uda
                  JOIN {user_info_field} uif ON uda.fieldid = uif.id
                  JOIN {context} ctx ON ctx.instanceid = uda.userid
                       AND ctx.contextlevel = :contextlevel
                 WHERE uda.userid = :userid
                   AND uif.datatype = :datatype";
        $params = [
            'userid' => $userid,
            'contextlevel' => CONTEXT_USER,
            'datatype' => 'cpf',
        ];
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Adds users with CPF values in the supplied context to the user list.
     *
     * @param userlist $userlist The user list.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_user) {
            return;
        }

        $sql = "SELECT uda.userid
                  FROM {user_info_data} uda
                  JOIN {user_info_field} uif ON uda.fieldid = uif.id
                 WHERE uda.userid = :userid
                   AND uif.datatype = :datatype";
        $userlist->add_from_sql('userid', $sql, [
            'userid' => $context->instanceid,
            'datatype' => 'cpf',
        ]);
    }

    /**
     * Exports CPF data for the approved user contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        $user = $contextlist->get_user();
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_USER || $context->instanceid != $user->id) {
                continue;
            }

            foreach (static::get_records($user->id) as $record) {
                \core_privacy\local\request\writer::with_context($context)->export_data([
                    get_string('pluginname', 'profilefield_cpf'),
                ], (object)[
                    'data' => $record->data,
                ]);
            }
        }
    }

    /**
     * Deletes CPF data for all users in a user context.
     *
     * @param \context $context The context to clean.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        if ($context->contextlevel === CONTEXT_USER) {
            static::delete_data($context->instanceid);
        }
    }

    /**
     * Deletes CPF data for users in an approved user list.
     *
     * @param approved_userlist $userlist The approved user list.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $context = $userlist->get_context();
        if ($context instanceof \context_user) {
            static::delete_data($context->instanceid);
        }
    }

    /**
     * Deletes CPF data for an approved user context list.
     *
     * @param approved_contextlist $contextlist The approved contexts.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        $user = $contextlist->get_user();
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel === CONTEXT_USER && $context->instanceid == $user->id) {
                static::delete_data($context->instanceid);
            }
        }
    }

    /**
     * Deletes all CPF field data for a user.
     *
     * @param int $userid The user ID.
     */
    protected static function delete_data($userid) {
        global $DB;

        $DB->delete_records_select(
            'user_info_data',
            "fieldid IN (SELECT id FROM {user_info_field} WHERE datatype = :datatype)
             AND userid = :userid",
            ['userid' => $userid, 'datatype' => 'cpf']
        );
    }

    /**
     * Returns CPF records for a user.
     *
     * @param int $userid The user ID.
     * @return array The matching records.
     */
    protected static function get_records($userid) {
        global $DB;

        $sql = "SELECT uda.*
                  FROM {user_info_data} uda
                  JOIN {user_info_field} uif ON uda.fieldid = uif.id
                 WHERE uda.userid = :userid
                   AND uif.datatype = :datatype";
        return $DB->get_records_sql($sql, ['userid' => $userid, 'datatype' => 'cpf']);
    }
}
