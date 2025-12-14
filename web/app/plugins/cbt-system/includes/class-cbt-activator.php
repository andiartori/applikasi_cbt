<?php
/**
 * Plugin activation and deactivation
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class CBT_activator
{
    /**
     * Run on plugin activation
     */
    public static function activate()
    {
        //Create database tables
        CBT_Database::create_tables();

        //Create custom roles
        CBT_ROLES::create_roles();

        //Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Run on plugin deactivation
     */

    public static function deactivate()
    {
        //Flush rewrite rules (Note: this dont delete tables on deactivation)
        flush_rewrite_rules();
    }

    /**
     * Run on plugin uninstall (optional - create uninstall.php)
     */

    public static function uninstall()
    {
        //Remove database tables
        CBT_Database::drop_tables();

        //Remove custom roles
        CBT_ROLES::remove_roles();
    }

}