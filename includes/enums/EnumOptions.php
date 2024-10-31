<?php

namespace triboon\pubjet\includes\enums;

// Exit if accessed directly
defined('ABSPATH') || exit;

class EnumOptions {
    const Token                   = 'token';
    const DefaultCategory         = 'defaultCategory';
    const DebugMode               = 'debug';
    const LastCheckingMissedPosts = 'lastCheckingMissedPosts';
    const ActivationVersion       = 'activationVersion';
    const CopyrightStatus         = 'copyrightStatus';
    const UninstallCleanup        = 'uninstallCleanup';
    const Nofollow                = 'nofollow';
    const AlignCenterImages       = 'alignCenterImages';
    const LastCategoriesSyncTime  = 'lastCategoriesSyncTime';
    const PricingPlans            = 'pricingPlans';
    const Settings                = 'pubjet_settings';
}