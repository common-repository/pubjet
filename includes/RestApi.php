<?php

namespace triboon\pubjet\includes;

use DateTime;
use DateTimeZone;
use triboon\pubjet\includes\enums\EnumOptions;
use triboon\pubjet\includes\traits\Utils;

defined('ABSPATH') || exit;

class RestApi extends Singleton {

    use Utils;

    /**
     * @return void
     */
    public function init() {
        add_action('rest_api_init', [$this, 'registerRoutes'], 15);
    }

    /**
     * @return void
     */
    public function registerRoutes() {
        $this->registerRoute('reportage', 'createReportage', ['POST']);
        $this->registerRoute('reportage/(?P<reportageId>\d+)', 'updateReportage', ['PUT']);
        $this->registerRoute('reportage/(?P<reportageId>\d+)', 'findReportage', ['GET']);
        $this->registerRoute('reportage/(?P<reportageId>\d+)', 'deleteReportage', ['DELETE']);
        $this->registerRoute('backlink', 'createBacklink', ['POST']);
        $this->registerRoute('backlink/(?P<backlinkId>\d+)', 'findBacklink', ['GET']);
        $this->registerRoute('version', 'getPluginVersion', ['GET']);
        $this->registerRoute('status', 'getPluginStatus', ['GET']);
        $this->registerRoute('copyright/(?P<reportageId>\d+)/(?P<status>show|hide)', 'toggleCopyright', ['POST', 'PATCH']);
        $this->registerRoute('site/info', 'findSiteInfo', ['GET']);
        $this->registerRoute('site/tags', 'findSiteTags', ['GET']);
        $this->registerRoute('site/categories', 'findSiteCategories', ['GET']);
        $this->registerRoute('check-missed-reportage', 'checkMissedReportage', ['POST']);
    }

    /**
     * @return void
     */
    public function findSiteCategories() {
        $this->success(pubjet_find_wp_categories(0, false));
    }

    /**
     * @return void
     */
    public function findSiteTags(\WP_REST_Request $request) {
        $tags = pubjet_find_wp_tags();
        $this->success($tags);
    }

    /**
     * @return void
     */
    public function checkMissedReportage(\WP_REST_Request $request) {
        $this->finishRequest();

        global $wpdb;

        $dt = new DateTime(date('Y-m-d H:i:s e'));
        $dt->setTimezone(new DateTimeZone(wp_timezone_string()));
        $now = $dt->format('Y-m-d H:i:s');

        $sql    = $wpdb->prepare("SELECT `ID` FROM $wpdb->posts WHERE `post_type` = %s AND post_status='future' AND post_date_gmt < %s", PUBJET_POST_TYPE, $now);
        $result = $wpdb->get_results($sql);

        if (!$result) {
            return;
        }

        foreach ($result as $post) {
            if (!pubjet_is_reportage($post->ID)) {
                continue; // Just publish reportage post
            }
            wp_publish_post($post->ID);
        }
    }

    /**
     * @return void
     */
    public function findReportage(\WP_REST_Request $request) {
        $reportage_id = $request->get_param('reportageId');
        pubjet_log('===== Get Reportage Post =====');
        $reportage_post_id = pubjet_find_post_id_by_reportage_id($reportage_id);
        $reportage_post    = get_post($reportage_post_id);
        if (!$reportage_post_id || empty($reportage_post)) {
            $this->error(pubjet__('post-not-found'), 404);
        }
        $this->success([
                           'postId'    => $reportage_post->ID,
                           'postTitle' => $reportage_post->post_title,
                           'postUrl'   => get_permalink($reportage_post->ID),
                       ]);
    }

    /**
     * @return void
     */
    public function deleteReportage(\WP_REST_Request $request) {
        $reportage_id = $request->get_param('reportageId');
        pubjet_log("==== Delete Reportage Post ====");
        pubjet_log($reportage_id);
        $reportage_post_id = pubjet_find_post_id_by_reportage_id(pubjet_isset_value($reportage_id));
        pubjet_log('Post: ' . $reportage_post_id);

        if (empty($reportage_post_id)) {
            $this->error(['error' => pubjet__('rep-not-found'),], 401);
        }

        $post = get_post($reportage_post_id);
        if ($post->post_type !== pubjet_post_type()) {
            $this->error(['error' => pubjet__('rep-not-found')], 401);
        }

        $reportage_post = get_post($reportage_post_id);
        $result         = wp_delete_post($reportage_post_id, true);
        if (is_wp_error($result)) {
            $this->error($result->get_error_message(), 500);
        }

        $this->success([
                           'postId'      => absint($reportage_post_id),
                           'postTitle'   => $reportage_post->post_title,
                           'reportageId' => absint(pubjet_isset_value($reportage_id)),
                       ]);
    }

    /**
     * @return void
     */
    public function findSiteInfo(\WP_REST_Request $request) {
        /**
         * The pubjet_siteinfo filter.
         *
         * @since 1.0.0
         */
        $result = apply_filters('pubjet_siteinfo', [
            'title'          => get_bloginfo('name'),
            'description'    => get_bloginfo('description'),
            'site_url'       => get_bloginfo('wpurl'),
            'site_version'   => get_bloginfo('version'),
            'pubjet_version' => PUBJ()->getVersion(),
        ]);
        $this->success($result);
    }

    /**
     * @return void
     */
    public function toggleCopyright(\WP_REST_Request $request) {
        pubjet_log('==== Change Copyright Status ====');

        $reportage_post_id = pubjet_find_post_id_by_reportage_id($request->get_param('reportageId'));
        if (empty($reportage_post_id)) {
            $this->error(pubjet__('post-not-found'), 404);
        }

        $new_status = $request->get_param('status');
        if ('hide' === $new_status) {
            // Hide copyright
            pubjet_update_setting(EnumOptions::CopyrightStatus, 'hide');
        } else {
            // Show copyright
            pubjet_update_setting(EnumOptions::CopyrightStatus, '');
        }

        $this->success([
                           'postId'      => $reportage_post_id,
                           'reportageId' => $request->get_param('reportageId'),
                           'status'      => $new_status,
                       ]);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return void
     */
    public function getPluginStatus(\WP_REST_Request $request) {
        $this->success(pubjet_plugin_status());
    }

    /**
     * @return void
     */
    public function getPluginVersion(\WP_REST_Request $request) {
        $this->success(['version' => PUBJ()->getVersion(),]);
    }

    /**
     * @return void
     */
    public function updateReportage(\WP_REST_Request $request) {
        try {
            $reportage_id      = $request->get_param('reportageId');
            $reportage_post_id = pubjet_find_post_id_by_reportage_id(pubjet_isset_value($reportage_id));
            pubjet_log("====== Update Reportage Post ======");
            pubjet_log('Reportage ID: ' . $reportage_id);
            pubjet_log('Reportage Post ID: ' . $reportage_post_id);

            if (empty($reportage_post_id)) {
                $this->error(pubjet__('rep-not-found'), 404);
            }

            $post = get_post($reportage_post_id);
            if ($post->post_type !== pubjet_post_type()) {
                $this->error(pubjet__('rep-not-found'), 404);
            }

            $reportage_post        = get_post($reportage_post_id);
            $reportage             = (object)$request->get_json_params();
            $reportage->wp_post_id = $reportage_post->ID;
            pubjet_log($reportage);

            $wp_post_id = ReportagePost::update($reportage);

            if (!$wp_post_id || is_wp_error($wp_post_id)) {
                if (is_wp_error($wp_post_id)) {
                    pubjet_log('Error: ' . $wp_post_id->get_error_message());
                }
                $sentry_error = is_wp_error($wp_post_id) ? $wp_post_id->get_error_message() : 'خطای نامشخصی در فرایند ثبت نوشته رپورتاژ رخ داده است.';
                pubjet_log_sentry($sentry_error, [
                    'reportage_id'    => pubjet_isset_value($reportage->id),
                    'reportage_title' => pubjet_isset_value($reportage->title),
                ]);
                $this->error($sentry_error, 400);
            }

            pubjet_log('====== Post updated successfully. ======');
            // Success
            $this->success(['postId' => $wp_post_id, 'reportageId' => $reportage->id,]);
        } catch (\Exception $ex) {
            $this->error($ex->getMessage(), 500);
        }
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return void
     */
    public function findBacklink(\WP_REST_Request $request) {
        $backlink_id = $request->get_param('backlinkId');
        pubjet_log('===== Get Backlink Details =====');
        $backlink_row = pubjet_find_backlink_by_id($backlink_id);
        if (!$backlink_row || empty($backlink_row)) {
            $this->error(pubjet__('backlink-not-found'), 404);
        }
        $this->success($backlink_row);
    }

    /**
     * @return void
     */
    public function createBacklink(\WP_REST_Request $request) {
        $backlink_json_data = (object)$request->get_json_params();
        /**
         * The pubjet_create_backlink action.
         *
         * @hooked [Backlink, 'createBacklink'] - 15
         *
         * @since 1.0.0
         */
        do_action('pubjet_create_backlink', $backlink_json_data);
    }

    /**
     * @return void
     */
    public function createReportage(\WP_REST_Request $request) {
        try {
            $reportage = (object)$request->get_json_params();
            pubjet_log($reportage);
            /**
             * Hooked [Actions, 'processCreateReportage'] - 15
             *
             * @since 4.0.0
             */
            do_action('pubjet_create_reportage', $reportage);
        } catch (\Exception $ex) {
            $this->error($ex->getMessage(), 400);
        }
    }

    /**
     * @param $namespace
     * @param $callback
     * @param $method
     * @param $args
     *
     * @return void
     */
    public function registerRoute($namespace, $callback, $method = \WP_REST_Server::READABLE, $args = null) {
        register_rest_route('pubjet/v1', "/$namespace", [
            'methods'             => $method,
            'callback'            => [$this, $callback],
            'permission_callback' => [$this, 'permissonCallback'],
        ]);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return boolean
     */
    public function permissonCallback(\WP_REST_Request $request) {
        if (!$this->isTokenValid($request)) {
            wp_send_json_error(pubjet__('invalid-token'), 401);
        }
        return true;
    }

    /**
     * @return bool
     */
    public function isTokenValid(\WP_REST_Request $request) {
        if (pubjet_is_dev_mode()) {
            return true;
        }
        if (empty(pubjet_token())) {
            return false;
        }
        $authorization_token = $request->get_header('authorization');
        $authorization_token = is_array($authorization_token) ? reset($authorization_token) : $authorization_token;
        return pubjet_token() == $authorization_token;
    }

    /**
     * @return void
     */
    public function finishRequest() {
        ignore_user_abort(true);

        if (!headers_sent()) {
            header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
            header('Cache-Control: no-cache, must-revalidate, max-age=0');
        }

        if (PHP_VERSION_ID >= 70016 && function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else if (function_exists('litespeed_finish_request')) {
            litespeed_finish_request();
        } else {
            ob_start();
            $size = ob_get_length();
            header("Content-Length: $size");
            header("Connection: close");
            ob_end_flush();
            ob_flush();
            flush();
        }

    }

}