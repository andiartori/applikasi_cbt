<?php
/**
 * Custom roles management
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class CBT_ROLES
{
    /**
     * Create the student role
     */
    public static function create_roles()
    {
        //Add student role with minimal capabilities
        add_role(
            'cbt_student',
            __('CBT Student', 'cbt-system'),
            array(
                'read' => true,
            )
        );

        //Add CBT capabilities to administrator
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('manage_cbt_exams');
            $admin->add_cap('manage_cbt_students');
            $admin->add_cap('view_cbt_results');
        }

        // Optionally add a Teacher role 
        add_role(
            'cbt_teacher',
            __('CBT Teacher', 'cbt-system'),
            [
                'read' => true,
                'manage_cbt_exams' => true,
                'manage_cbt_students' => true,
                'view_cbt_results' => true,
            ]
        );

        /**
         * Remove custom roles and capabilities
         */
    }

    public static function remove_roles()
    {
        //Remove student role
        remove_role('cbt_student');

        //Remove teacher role
        remove_role('cbt_teacher');

        //Remove CBT capabilities from administrator
        $admin = get_role('administrator');
        if ($admin) {
            $admin->remove_cap('manage_cbt_exams');
            $admin->remove_cap('manage_cbt_students');
            $admin->remove_cap('view_cbt_results');
        }
    }
}