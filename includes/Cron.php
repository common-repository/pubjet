<?php

namespace triboon\pubjet\includes;

use triboon\pubjet\includes\enums\EnumPostMetakeys;
use triboon\pubjet\includes\enums\EnumPostTypes;

defined('ABSPATH') || exit;

class Cron extends Singleton {

    /**
     * @return void
     */
    public function init() {
        add_filter('cron_schedules', [$this, 'registerInterval'], 15);
        add_action('wp', [$this, 'registerCron'], 15);
        add_action('pubjet_sync_reportage_url', [$this, 'runSyncReportageUrl'], 15);
    }

    /**
     * @return void
     */
    public function registerInterval($schedules) {
        $schedules['every_minute'] = [
            'interval' => 60,
            'display'  => pubjet__('every-minute'),
        ];
        return $schedules;
    }

    /**
     * @return void
     */
    public function runSyncReportageUrl() {
        $args = [
            'post_type'      => EnumPostTypes::Post,
            'meta_key'       => EnumPostMetakeys::FailedSyncUrl,
            'posts_per_page' => -1,
        ];

        $posts = get_posts($args);

        foreach ($posts as $post) {
            $reportage_id = pubjet_find_reportage_id($post->ID);
            if (!$reportage_id) {
                continue;
            }
            $result = pubjet_publish_reportage($post->ID, $reportage_id);
            if (isset($result['code']) && $result['code'] == 200) {
                delete_post_meta($post->ID, EnumPostMetakeys::FailedSyncUrl);
            }
        }
    }

    /**
     * @return void
     */
    public function registerCron() {
        if (!wp_next_scheduled('pubjet_sync_reportage_url')) {
            wp_schedule_event(time(), 'every_minute', 'pubjet_sync_reportage_url');
        }
    }

}