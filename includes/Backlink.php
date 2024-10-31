<?php

namespace triboon\pubjet\includes;

defined('ABSPATH') || exit;

use triboon\pubjet\includes\enums\EnumBacklinkStatus;
use triboon\pubjet\includes\traits\Utils;

class Backlink extends Singleton {

    use Utils;

    /**
     * @return void
     */
    public function init() {
        // Create backlink
        add_action('pubjet_create_backlink', [$this, 'createBacklink'], 15);
    }

    /**
     * @return void
     */
    public function createBacklink($backlink_json_data) {
        try {
            /**
             * The pubjet_should_create_backlink filter.
             *
             * @since 1.0.0
             */
            if (!apply_filters('pubjet_should_create_backlink', true, $backlink_json_data)) {
                $this->error(pubjet__('error-occured'));
            }

            $duration = pubjet_isset_value($backlink_json_data->duration, 1); // Duration

            $new_backlink_args = [
                'backlink_id'  => sanitize_text_field($backlink_json_data->id),
                'text'         => sanitize_text_field($backlink_json_data->title),
                'url'          => sanitize_text_field($backlink_json_data->url),
                'status'       => EnumBacklinkStatus::Publish,
                'position'     => sanitize_text_field($backlink_json_data->position),
                'data'         => maybe_serialize($backlink_json_data),
                'created_at'   => pubjet_now_myql(),
                'publish_date' => pubjet_now_myql(),
                'expired_at'   => date('Y-m-d H:i:s', strtotime('+' . $duration . ' month', strtotime(pubjet_now_myql()))), // Default 1 month
            ];

            // Schedule publish date
            $prefered_publish_date = pubjet_isset_value($backlink_json_data->prefered_publish_date);
            if ($prefered_publish_date) {
                $prefered_publish_date_mysql = pubjet_find_mysql_date_by_request_date($prefered_publish_date);
                if (strtotime($prefered_publish_date_mysql) > pubjet_now_ts()) {
                    $new_backlink_args['status']       = EnumBacklinkStatus::Future;
                    $new_backlink_args['publish_date'] = $prefered_publish_date_mysql;
                    $new_backlink_args['expired_at']   = date('Y-m-d H:i:s', strtotime('+' . $duration . ' month', strtotime($prefered_publish_date_mysql)));
                }
            }

            // Expire data
            if ($expire_date = pubjet_isset_value($backlink_json_data->expire_date)) {
                $expire_date_mysql = pubjet_find_mysql_date_by_request_date($expire_date);
                if ($expire_date_mysql) {
                    $new_backlink_args['expired_at'] = $expire_date_mysql;
                }
            }

            /**
             * The pubjet_new_backlink_args filter.
             *
             * @since 1.0.0
             */
            $new_backlink_args = apply_filters('pubjet_new_backlink_args', $new_backlink_args, $backlink_json_data);

            /**
             * The pubjet_before_create_backlink action.
             *
             * @since 1.0.0
             */
            do_action('pubjet_before_create_backlink', $backlink_json_data);

            pubjet_log('====== Create Backlink ======');
            pubjet_log($backlink_json_data);
            pubjet_log($new_backlink_args);

            $new_backlink_id = pubjet_db()->backlinks->insert($new_backlink_args);

            pubjet_log('Backlink row id:' . $new_backlink_id);
            pubjet_log('====== End Creating ======');

            // Publish on the server
            if ($new_backlink_args['status'] == EnumBacklinkStatus::Publish && $new_backlink_id) {
                pubjet_publish_backlink_request($backlink_json_data->id);
            }

            /**
             * The pubjet_after_create_backlink action.
             *
             * @since 1.0.0
             */
            do_action('pubjet_after_create_backlink', $new_backlink_id, $backlink_json_data);

            $this->success([
                               'id'     => $new_backlink_id,
                               'status' => $new_backlink_args['status'],
                           ]);
        } catch (\Exception $ex) {
            $this->error($ex->getMessage(), 500);
        }
    }

}