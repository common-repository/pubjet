<?php

namespace triboon\pubjet\includes\enums;

// Exit if accessed directly
defined('ABSPATH') || exit;

class EnumPostMetakeys {
    const ReportageId         = 'pubjet_reportage_id';
    const ReportageContentUrl = 'pubjet_reportage_content_url';
    const PanelData           = 'pubjet_reportage_panel_data';
    const NoFollow            = 'pubjet_nofollow';
    const Source              = 'pubjet_reporatage_source';
    const WithoutTriboonTag   = 'pubjet_without_triboon_tag';
    const ManualApprove       = 'pubjet_manual_approve';
    const FailedSyncUrl       = 'pubjet_failed_sync_url';
}