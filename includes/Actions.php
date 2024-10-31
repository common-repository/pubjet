<?php

namespace triboon\pubjet\includes;

use triboon\pubjet\includes\enums\EnumActions;
use triboon\pubjet\includes\enums\EnumBacklinkStatus;
use triboon\pubjet\includes\enums\EnumOptions;
use triboon\pubjet\includes\enums\EnumPostTypes;
use triboon\pubjet\includes\enums\EnumTransients;
use triboon\pubjet\includes\traits\Utils;
use triboon\pubjet\includes\widgets\Backlinks;

defined('ABSPATH') || exit;

class Actions extends Singleton {

    use Utils;

    /**
     * @return void
     */
    public function init() {
        add_action("admin_menu", [$this, "registerMenu"], 15);
        add_action("admin_footer", [$this, "adminFooterScripts"], 15);
        add_action("wp_head", [$this, "publishMissedSchedulePosts"], 15);
        add_action("wp_footer", [$this, "addScriptToReportage"], 15);
        add_action("admin_head", [$this, "pluginFont"], 15);
        add_action("wp_head", [$this, "alignReportageImagesCenter"], 15);
        add_action('created_term', [$this, 'createCategory'], 15, 5);
        add_action('delete_term', [$this, 'deleteCategory'], 15, 4);
        add_action('pubjet_new_reportage', [$this, 'reportageCustomFields'], 15, 2);
        add_action('upgrader_process_complete', [$this, 'syncCategoriesAfterUpdate'], 15, 2);
        add_action('init', [$this, 'checkAndSendVersion'], 15);
        add_action('init', [$this, 'publishFutureBacklinks'], 25);
        // Change Reportage Author
        add_action('pubjet_new_reportage', [$this, 'changeReportageAuthor'], 15, 2);
        // Create database tables
        add_action('admin_init', [$this, 'createDbTables'], 15);
        // Register Widgets
        add_action('widgets_init', [$this, 'registerWidgets'], 15);
        // Elementor Widgets
        add_action('elementor/widgets/widgets_registered', [$this, 'registerElementorWidgets'], 15);
        // Process reportage by query string
        add_action('init', [$this, 'createReportageByActionQueryString'], 15);
        add_action('init', [$this, 'createBacklinkByActionQueryString'], 15);
        add_action('init', [$this, 'showPluginStatus'], 15);
        add_action('pubjet_create_reportage', [$this, 'processCreateReportage'], 15);
    }

    /**
     * @return void
     */
    public function showPluginStatus() {
        $action = $this->get('action');
        if (EnumActions::PubjetStatus !== $action) {
            return;
        }
        $this->success(pubjet_plugin_status());
    }

    /**
     * @return void
     */
    public function createBacklinkByActionQueryString() {
        $action = $this->get('action');
        if (EnumActions::CreateBacklink !== $action) {
            return;
        }
        // Check token
        $check_token = pubjet_is_request_token_valid();
        if (is_wp_error($check_token)) {
            $this->error($check_token->get_error_message(), 403);
        }
        try {
            // Get backlink data
            $backlink_data = file_get_contents("php://input");
            $backlink_data = json_decode($backlink_data);
            if (empty($backlink_data)) {
                $this->error(pubjet__('missing-params'), 400);
            }
            /**
             * Hooked [Backlink, 'createBacklink'] - 15
             *
             * @since 4.0.0
             */
            do_action('pubjet_create_backlink', $backlink_data);
        } catch (\Exception $ex) {
            $this->error($ex->getMessage(), 500);
        }
    }

    /**
     * @return void
     */
    public function createReportageByActionQueryString() {
        $action = $this->get('action');
        if (EnumActions::CreateReportage !== $action) {
            return;
        }
        // Check token
        $check_token = pubjet_is_request_token_valid();
        if (is_wp_error($check_token)) {
            $this->error($check_token->get_error_message(), 403);
        }
        try {
            // Get reportage data
            $reportage_data = file_get_contents("php://input");
            $reportage_data = json_decode($reportage_data);
            if (empty($reportage_data)) {
                $this->error(pubjet__('missing-params'), 400);
            }
            /**
             * Hooked [Actions, 'processCreateReportage'] - 15
             *
             * @since 4.0.0
             */
            do_action('pubjet_create_reportage', $reportage_data);
        } catch (\Exception $ex) {
            $this->error($ex->getMessage(), 500);
        }
    }

    /**
     * @return void
     * @since 4.0.0
     */
    public function processCreateReportage($reportage) {
        $wp_post_id = ReportagePost::insert($reportage);

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

        if (!empty($reportage->wp_post_id)) {
            // Update
            pubjet_log('Post updated successfully. Post ID: ' . $reportage->wp_post_id);
        } else {
            // Insert
            pubjet_log('Post created successfully. New Post ID: ' . $wp_post_id);
        }

        $reportage_post = get_post($wp_post_id);

        // Success
        $this->success([
                           'postId'      => $wp_post_id,
                           'postStatus'  => $reportage_post ? $reportage_post->post_status : 'Unknown',
                           'reportageId' => $reportage->id,
                       ]);
    }

    /**
     * @return void
     */
    public function registerElementorWidgets() {
        /**
         * The pubjet_elementor_widgets_instances filter.
         *
         * @since 1.0.0
         */
        $instances = apply_filters('pubjet_elementor_widgets_instances', [
            new \triboon\pubjet\includes\elementor\Backlinks(),
        ]);
        foreach ($instances as $instance) {
            \Elementor\Plugin::instance()->widgets_manager->register_widget_type($instance);
        }
    }

    /**
     * @return void
     */
    public function registerWidgets() {
        /**
         * The pubjet_widgets_classes filter.
         *
         * @since 1.0.0
         */
        $instances = apply_filters('pubjet_widgets_instances', [
            new Backlinks(),
        ]);
        foreach ($instances as $instance) {
            register_widget($instance);
        }
    }

    /**
     * @return void
     */
    public function createDbTables() {
        /**
         * The pubjet_database_tables filter.
         *
         * @since 1.0.0
         */
        $tables = DBLoader::getInstance()->getTables();
        foreach ($tables as $table) {
            $table->createTable();
        }
    }

    /**
     * @return void
     */
    public function changeReportageAuthor($reportage_post_id, $reportage) {
        global $pubjet_settings;
        $status    = pubjet_isset_value($pubjet_settings['repauthor']['status']);
        $author_id = pubjet_isset_value($pubjet_settings['repauthor']['authorId']);
        if (!$author_id || !$status) {
            return;
        }
        $reportage_post              = get_post($reportage_post_id);
        $reportage_post->post_author = $author_id;
        wp_update_post($reportage_post);
    }

    /**
     * @return void
     */
    public function publishFutureBacklinks() {
        // بررسی اگر transient وجود دارد یا نه
        if (false === get_transient(EnumTransients::PublishFutureBacklinks)) {
            // ارسال ورژن افزونه به API
            $futures_backlinks = pubjet_db()->backlinks->findFutures();
            pubjet_log($futures_backlinks);
            if ($futures_backlinks && is_array($futures_backlinks)) {
                foreach ($futures_backlinks as $row_item) {
                    // Notify Triboon
                    pubjet_publish_backlink_request($row_item->backlink_id);
                    // Update Database
                    pubjet_db()->backlinks->update($row_item->id, [
                        'status' => EnumBacklinkStatus::Publish,
                    ]);
                }
            }
            // تنظیم transient برای 1 دقیقه
            set_transient(EnumTransients::PublishFutureBacklinks, true, 60);
        }
    }

    /**
     * @return void
     */
    public function checkAndSendVersion() {
        // بررسی اگر transient وجود دارد یا نه
        if (false === get_transient('pubjet_daily_plugin_status_check')) {
            // ارسال ورژن افزونه به API
            pubjet_send_plugin_status_to_api('active');
            // تنظیم transient برای 24 ساعت
            set_transient('pubjet_daily_plugin_status_check', true, DAY_IN_SECONDS);
        }
    }

    /**
     * @return void
     */
    public function syncCategoriesAfterUpdate($upgrader_object, $options) {
        if ($options['action'] == 'update' && $options['type'] == 'plugin' && isset($options['plugins'])) {
            foreach ($options['plugins'] as $plugin) {
                if ($plugin == PUBJET_PLUGIN_BASE) {
                    pubjet_sync_categories();
                    break;
                }
            }
        }
    }

    /**
     * @return void
     */
    public function reportageCustomFields($post_id, $reportage_data) {
        global $pubjet_settings;
        $status = pubjet_isset_value($pubjet_settings['metakeys']['status']);
        if (!$status) {
            return;
        }
        $items = pubjet_isset_value($pubjet_settings['metakeys']['items']);
        if (!$items || !is_array($items)) {
            return;
        }
        foreach ($items as $item) {
            if (empty(trim($item['name']))) {
                continue;
            }
            update_post_meta($post_id, $item['name'], $item['value']);
        }
    }

    /**
     * @return void
     */
    public function createCategory($term_id, $tt_id, $taxonomy, $args) {
        if ('category' !== $taxonomy) {
            return;
        }
        $term = get_term_by('term_id', $term_id, $taxonomy);
        if (!$term) {
            return;
        }
        pubjet_sync_categories();
    }

    /**
     * @return void
     */
    public function deleteCategory($term, $tt_id, $taxonomy, $deleted_term) {
        if ('category' !== $taxonomy) {
            return;
        }
        pubjet_sync_categories();
    }

    /**
     * @return void
     */
    public function alignReportageImagesCenter() {
        global $pubjet_settings;
        if (!is_singular('post')) {
            return;
        }
        global $post;
        if ($post->post_type !== EnumPostTypes::Post || !pubjet_is_reportage($post->ID)) {
            return;
        }
        $status = pubjet_isset_value($pubjet_settings[EnumOptions::AlignCenterImages]);
        if (!$status) {
            return;
        }
        ?>
        <style>
            .pubjet-reportage img {
                display: block !important;
                margin-left: auto !important;
                margin-right: auto !important;
            }
        </style>
        <?php
    }

    /**
     * @return void
     */
    public function pluginFont() {
        ?>
        <style>
            @font-face {
                font-family: 'Vazirmatn';
                src: url(<?php echo PUBJET_ASSETS_URL; ?>/fonts/Vazirmatn[wght].woff2) format('woff2 supports variations'),
                url(<?php echo PUBJET_ASSETS_URL; ?>/fonts/Vazirmatn[wght].woff2) format('woff2-variations');
                font-weight: 100 900;
                font-style: normal;
                font-display: swap;
            }

            .mF0ELrBPqJ9R6bt1N3mw *,
            .pubjet-notice *,
            .ant-tooltip-inner,
            .ant-select-dropdown div,
            .ant-modal *,
            .ant-popover *,
            .ant-message *,
            #pubjet-reportage-data *,
            #pubjet-reportage-options *,
            #pubjet-page-settings-content noscript {
                font-family: 'Vazirmatn';
            }
        </style>
        <?php
    }

    /**
     * @return void
     */
    public function publishMissedSchedulePosts() {
        global $pubjet_settings;

        $last_check = pubjet_isset_value($pubjet_settings[EnumOptions::LastCheckingMissedPosts]);

        if (pubjet_now_ts() - $last_check < 60) {
            return;
        }

        pubjet_update_setting(EnumOptions::LastCheckingMissedPosts, pubjet_now_ts());

        wp_remote_post(home_url('/wp-json/pubjet/v1/check-missed-reportage'), [
            'headers'     => [
                'Authorization' => pubjet_token(),
                'Content-Type'  => 'application/json',
            ],
            'data_format' => 'body',
            'method'      => 'POST',
            'body'        => json_encode([]),
        ]);
    }

    /**
     * @return void
     */
    public function addScriptToReportage() {

        if (!is_singular() || !pubjet_is_reportage(get_the_ID())) {
            return;
        }

        ?>
        <style>
            body .pubjet-copyright p {
                display: inline-flex !important;
                align-items: center !important;
                background: #eee !important;
                padding: 12px 16px !important;
                border-radius: 8px !important;
                margin: 12px 0 !important;
                font-size: 14px !important;
            }

            body .pubjet-copyright p img {
                width: 64px !important;
                margin: 0 8px !important;
            }
        </style>
        <?php
    }

    /**
     * @return void
     */
    public function adminFooterScripts() {
        global $wpdb;

        if (get_current_screen()->id != 'edit-post') {
            return;
        }
        $posts      = "SELECT COUNT(*) FROM {$wpdb->posts} as posts JOIN {$wpdb->postmeta} as meta ON meta.post_id = posts.ID where posts.post_type = 'post' AND posts.post_status IN ('publish' , 'future' ,'draft') AND meta.meta_key = 'pubjet_reportage_id' ";
        $count_post = $wpdb->get_var($posts);
        ?>
        <script>
            jQuery(document).ready(function ($) {
                jQuery(".subsubsub").append("<li class='reportages'><a href='edit.php?post_type=post&reportage=true'> | <?php echo pubjet__('reportage'); ?> <span class='count'>(<?= intval($count_post) ?>)</span></a></li>")
            });
        </script>
        <?php
    }

    /**
     * @return void
     */
    public function registerMenu() {
        add_menu_page(
            pubjet__('pubjet'),
            pubjet__('pubjet'),
            'manage_options',
            'pubjet_settings',
            [$this, 'pubjetSettingsPageCallback'],
            PUBJET_IMAGES_URL . 'pubjet-icon.svg',
            100
        );
    }

    /**
     * @return void
     */
    public function pubjetSettingsPageCallback() {
        pubjet_template('settings');
    }


}