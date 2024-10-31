<?php
/**
 * Pubjet Uninstall
 *
 * Uninstalling Pubjet deletes pages, tables, and options.
 *
 * @package Pubjet\Uninstaller
 * @version 1.0.0
 */

use triboon\pubjet\includes\enums\EnumOptions;

global $wpdb, $wp_roles;

defined('WP_UNINSTALL_PLUGIN') || exit;

// Load Pubjet file.
include_once('pubjet.php');

global $pubjet_settings;

$status = pubjet_isset_value($pubjet_settings[EnumOptions::UninstallCleanup]);
if (!$status) {
    return;
}

// Remove any transients we've left behind
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_pubjet_%'");
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '\_site\_transient\_pubjet_%'");
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_timeout\_pubjet_%'");
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '\_site\_transient\_timeout\_pubjet_%'");

$wpdb->query("DELETE FROM $wpdb->options WHERE `option_name` LIKE '%pubjet%'");
$wpdb->query("DELETE FROM $wpdb->options WHERE `option_name` LIKE '%options_pubjet_%'");