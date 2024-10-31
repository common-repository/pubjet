<?php

namespace triboon\pubjet\includes\enums;

// Exit if accessed directly
defined('ABSPATH') || exit;

class EnumOldOptions {
    const Token                   = 'pubjet_token';
    const DefaultCategory         = 'pubjet_default_category';
    const DebugMode               = 'pubjet_debug_mode';
    const LastCheckingMissedPosts = 'pubjet_last_checking_missed_post';
    const ActivationVersion       = 'pubjet_activation_version';
    const CopyrightStatus         = 'pubjet_copyright_status';
    const UninstallCleanup        = 'pubjet_uninstall_cleanup';
    const Nofollow                = 'pubjet_nofollow';
    const AlignCenterImages       = 'pubjet_align_center_images';
    const LastCategoriesSyncTime  = 'pubjet_last_categories_sync_time';
    const Settings                = 'pubjet_settings';
}