<?php

namespace triboon\pubjet\includes;

if (!defined("ABSPATH")) exit;

use DateTime;
use DateTimeZone;
use Statickidz\GoogleTranslate;
use triboon\pubjet\includes\enums\EnumPostMetakeys;
use triboon\pubjet\includes\enums\EnumPostStatus;

class ReportagePost extends Singleton {

    /**
     * @since 1.0.0
     */
    public function __construct() {
        add_action("transition_post_status", [$this, "afterPublishReportage"], 15, 3);
    }

    public static function get_post_date($prefrred_date) {
        $dt = new DateTime($prefrred_date);
        $dt->setTimezone(new DateTimeZone(wp_timezone_string()));
        return $dt->format('Y-m-d H:i:s');
    }

    /**
     * @param $post_date
     *
     * @return string
     * @throws \Exception
     */
    public static function get_post_status($post_date) {
        $dt = new DateTime(date('Y-m-d H:i:s e'));
        $dt->setTimezone(new DateTimeZone(wp_timezone_string()));
        $current_time = $dt->format('Y-m-d H:i:s');
        return strtotime($post_date) > strtotime($current_time) ? 'future' : 'publish';
    }

    /**
     * @param $thereportage
     *
     * @return bool
     */
    public static function update($thereportage) {

        if (!isset($thereportage->wp_post_id) || !$thereportage->wp_post_id) {
            return false;
        }

        $post_id      = intval($thereportage->wp_post_id);
        $reportage_id = get_post_meta($post_id, EnumPostMetakeys::ReportageId, true);

        if (!get_post($thereportage->wp_post_id) || $reportage_id != $thereportage->id) {
            return false;
        }

        $post_date   = self::get_post_date($thereportage->preferred_publish_date);
        $post_status = self::get_post_status($post_date);

        $args = [
            'ID'          => $post_id,
            'post_type'   => pubjet_post_type(),
            'post_status' => $post_status,
            'tags_input'  => isset($thereportage->tags) && is_array($thereportage->tags) ? map_deep($thereportage->tags, 'sanitize_text_field') : [],
        ];

        if ($post_status !== 'publish') {
            $args['post_date']     = $post_date;
            $args['post_date_gmt'] = $post_date;
        }

        $reportage_url = get_post_meta($post_id, EnumPostMetakeys::ReportageContentUrl, true);
        if ($reportage_url !== $thereportage->content_file) {

            $post_content = !empty($thereportage->content_file_html) ? $thereportage->content_file_html : self::get_content_file($thereportage);
            $post_content = self::get_post_content($post_content, $thereportage->title);

            $args['post_title']   = $post_content['title'] ?? '';
            $args['post_content'] = $post_content['content'] ?? '';

            $args['meta_input'] = [
                EnumPostMetakeys::PanelData           => $thereportage,
                EnumPostMetakeys::ReportageContentUrl => $thereportage->content_file,
            ];

        }

        $update = wp_update_post($args);

        return boolval($update);
    }

    /**
     * @param $reportage
     *
     * @return bool|int|\WP_Error
     */
    public static function insert($reportage) {

        pubjet_log('================== Insert ===================');
        if ($reportage->wp_post_id = self::reportage_exists($reportage->id)) {
//            pubjet_log('==================== Updating ===================');
//            return self::update($reportage);
            return new \WP_Error('reportage-exists', 'رپورتاژ قبلا در رسانه منتشر شده است و امکان ثبت مجدد آن نیست');
        }

        $def_category = self::findReportageCategory($reportage);
        $post_content = !empty($reportage->content_file_html) ? $reportage->content_file_html : self::get_content_file($reportage);

        // Check if we have gateway error
        if (pubjet_gateway_error($post_content)) {
            return new \WP_Error('gateway-error', pubjet__('gateway-error'));
        }

        $post_content = self::get_post_content($post_content, $reportage->title);
        $post_date    = self::get_post_date($reportage->preferred_publish_date);
        $post_status  = self::get_post_status($post_date);

        $args = [
            'post_type'     => sanitize_text_field(pubjet_post_type()),
            'post_title'    => isset($post_content['title']) ? sanitize_text_field($post_content['title']) : '',
            'post_status'   => 'future' === $post_status ? $post_status : (pubjet_should_publish_reportage_manually() ? EnumPostStatus::Pending : EnumPostStatus::Publish),
            'post_content'  => $post_content['content'] ?? '',
            'post_name'     => sanitize_text_field(self::get_post_name($reportage)),
            'tags_input'    => isset($reportage->tags) && is_array($reportage->tags) ? map_deep($reportage->tags, 'sanitize_text_field') : [],
            'post_category' => (int)$def_category > 0 ? [intval($def_category)] : '',
            'meta_input'    => [
                EnumPostMetakeys::ReportageId         => intval($reportage->id),
                EnumPostMetakeys::ReportageContentUrl => sanitize_url($reportage->content_file),
                EnumPostMetakeys::PanelData           => $reportage,
                EnumPostMetakeys::Source              => 'triboon',
            ],
        ];

        if (EnumPostStatus::Pending === $args['post_status']) {
            $args['meta_input'][EnumPostMetakeys::ManualApprove] = 1;
        }

        if (!in_array($post_status, [EnumPostStatus::Publish, EnumPostStatus::Pending])) {
            $args['post_date']     = sanitize_text_field($post_date);
            $args['post_date_gmt'] = sanitize_text_field($post_date);
        }

        /**
         * The pubjet_new_reportage_post_args filter.
         *
         * @since 1.0.0
         */
        $args = apply_filters('pubjet_new_reportage_post_args', $args, $reportage);

        pubjet_log('======= Reportage =======');
        pubjet_log($reportage);
        pubjet_log('======= New Post Args =======');
        pubjet_log($args);

        $post_id = wp_insert_post($args);

        pubjet_log('======= New Post Result =======');
        pubjet_log($post_id);

        if (is_wp_error($post_id)) {
            pubjet_log_sentry(sprintf('%s: %s', 'خطا در ایجاد نوشته رپورتاژ', $post_id->get_error_message()), [
                'reportage_id'    => $reportage->id,
                'reportage_title' => $reportage->title,
            ]);
            return new \WP_Error('insert-reportage', $post_id->get_error_message());
        }

        // =================== Success ===================
        if (isset($post_content['featured_img_id'])) {
            set_post_thumbnail($post_id, intval($post_content['featured_img_id']));
        }

        // Publish without Triboon tag
        if (isset($reportage->is_publish_without_triboon_tag) && $reportage->is_publish_without_triboon_tag) {
            update_post_meta($post_id, EnumPostMetakeys::WithoutTriboonTag, true);
        }

        /**
         * The pubjet_new_reportage action.
         *
         * Hooked [Actions, 'changeReportageAuthor'] - 15
         *
         * @since 1.0.0
         */
        do_action('pubjet_new_reportage', $post_id, $reportage);

        return $post_id;
    }

    /**
     * @param $utc_datetime_str
     *
     * @return string
     * @throws \Exception
     */
    public static function get_time_format($utc_datetime_str) {
        $dt = new DateTime($utc_datetime_str, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone(wp_timezone_string()));
        return $dt->format('Y-m-d H:i:s');
    }

    public static function reportage_exists($reportage_id) {
        global $wpdb;
        $post_id = $wpdb->get_col($wpdb->prepare("SELECT `post_id` FROM {$wpdb->postmeta} where `meta_key` = %s AND `meta_value` = %s LIMIT 1", EnumPostMetakeys::ReportageId, $reportage_id));
        if (!$post_id) {
            return false;
        }
        return is_array($post_id) ? reset($post_id) : $post_id;
    }

    /**
     * @param $content
     * @param $title
     *
     * @return array
     */
    public static function get_post_content($content, $title) {
        $content = self::handle_images($content);
        pubjet_log($content);
        $post_content                    = self::nomalize_html($content['html_file']);
        $post_content                    = self::remove_repeate_headeing_title_in_content($post_content, $title);
        $post_content['featured_img_id'] = $content['featured_img_id'];
        return $post_content;
    }

    /**
     * @param $reportage
     *
     * @return string
     */
    public static function get_post_name($reportage) {
        global $pubjet_settings;
        $post_name = $reportage->title;
        // Use Google Translate service for translating post title
        $use_google_translate = pubjet_isset_value($pubjet_settings['useGoogleTranslate']);
        if ($use_google_translate) {
            $trans     = new GoogleTranslate();
            $post_name = $trans->translate('fa', 'en', $reportage->title);
            return sanitize_title_with_dashes($post_name, '', 'save');
        }
        return false;
    }

    public static function nomalize_html($post_content) {
        $post_content = preg_replace('/\s*<a/', '<a', $post_content);
        $post_content = preg_replace('/<\/a>\s*/', '</a>', $post_content);
        $post_content = str_replace("\n\r", "", $post_content);
        return str_replace("\n", "", $post_content);
    }

    public static function handle_images($html_content, $just_thumbnail = false) {
        preg_match_all('/<img[^>]+>/i', $html_content, $result);
        $featured_image_isset = false;
        $featured_image_id    = null;

        foreach ($result[0] as $img) {

            $pattern = '/<img\s+[^>]*src="([^"]+)"[^>]*>/i';
            if (preg_match($pattern, $img, $matches)) {

                $src = $matches[1];

                if ($just_thumbnail) {

                    $attach_id         = self::upload_from_url(str_replace('\\"', '', $src));
                    $featured_image_id = $attach_id;
                    $html_content      = str_replace($src, wp_get_attachment_url($attach_id), $html_content);

                    break;

                } else {
                    $attach_id = self::upload_from_url(str_replace('\\"', '', $src));

                    if ($featured_image_isset == false) {
                        $featured_image_id    = $attach_id;
                        $featured_image_isset = true;
                    }

                    $html_content = str_replace($src, wp_get_attachment_url($attach_id), $html_content);
                }


            }

        }

        return ['html_file' => $html_content, 'featured_img_id' => $featured_image_id];
    }

    public static function upload_from_url($url, $title = null) {
        require_once(ABSPATH . "/wp-load.php");
        require_once(ABSPATH . "/wp-admin/includes/image.php");
        require_once(ABSPATH . "/wp-admin/includes/file.php");
        require_once(ABSPATH . "/wp-admin/includes/media.php");

        // Download url to a temp file
        $tmp = download_url($url);
        if (is_wp_error($tmp)) return false;

        // Get the filename and extension ("photo.png" => "photo", "png")
        $filename  = pathinfo($url, PATHINFO_FILENAME);
        $extension = pathinfo($url, PATHINFO_EXTENSION);

        // An extension is required or else WordPress will reject the upload
        if (!$extension) {
            // Look up mime type, example: "/photo.png" -> "image/png"
            $mime = mime_content_type($tmp);
            $mime = is_string($mime) ? sanitize_mime_type($mime) : false;

            // Only allow certain mime types because mime types do not always end in a valid extension (see the .doc example below)
            $mime_extensions = [
                // mime_type         => extension (no period)
                'text/plain'         => 'txt',
                'text/csv'           => 'csv',
                'application/msword' => 'doc',
                'image/jpg'          => 'jpg',
                'image/jpeg'         => 'jpeg',
                'image/gif'          => 'gif',
                'image/png'          => 'png',
                'video/mp4'          => 'mp4',
            ];

            if (isset($mime_extensions[$mime])) {
                // Use the mapped extension
                $extension = $mime_extensions[$mime];
            } else {
                // Could not identify extension
                wp_delete_file($tmp);
//                @unlink($tmp);
                return false;
            }
        }

        // Upload by "sideloading": "the same way as an uploaded file is handled by media_handle_upload"
        $args = [
            'name'     => "$filename.$extension",
            'tmp_name' => $tmp,
        ];

        // Do the upload
        $attachment_id = media_handle_sideload($args, 0, $title);

        // Cleanup temp file
        wp_delete_file($tmp);

        // Error uploading
        if (is_wp_error($attachment_id)) {
            return false;
        }

        // Success, return attachment ID (int)
        return (int)$attachment_id;
    }

    public static function get_content_file($reportage) {

        //$content_file = str_replace("https://cdn.triboon.net", "https://cdn.pubjet.ir", $reportage->content_file);

        pubjet_log(':: Content File ::');
        pubjet_log($reportage->content_file);
        $response = wp_remote_get($reportage->content_file, [
            'timeout'     => 25,
            'redirection' => 5,
            'blocking'    => true,
            'sslverify'   => false,
        ]);
        $body     = wp_remote_retrieve_body($response);

        pubjet_log(':: First ::');
        pubjet_log($body);

        // Check gateway error
        if (pubjet_gateway_error($body) || empty($body)) {
            $response = wp_remote_get($reportage->content_file);
            $body     = wp_remote_retrieve_body($response);

            pubjet_log(':: Second ::');
            pubjet_log($body);
        }

        pubjet_log($body);

        //$body = str_replace("https://cdn.triboon.net", "https://cdn.pubjet.ir", $body);

        return $body;
    }

    public static function remove_repeate_headeing_title_in_content($content, $object_title) {

        preg_match('/<h1\b[^>]*>(.*?)<\/h1>/i', $content, $matches);

        foreach ($matches as $index => $matche) {
            $h1Tag = strip_tags($matche);
            if ($index == 0) {
                $object_title = empty(trim($object_title)) ? trim($h1Tag) : $object_title;
                $content      = str_replace($matche, '', $content);
            } else {
                $object_title = empty(trim($object_title)) ? trim($h1Tag) : $object_title;
                $matche2      = str_replace('<h1', '<h2', $matche);
                $matche2      = str_replace('</h1', '</h2', $matche2);
                $content      = str_replace($matche, $matche2, $content);
            }
        }

        $content = str_replace('<a', ' <a', $content);
        $content = str_replace('</a>', '</a> ', $content);

        return [
            'content' => $content,
            'title'   => $object_title,
        ];
    }

    /**
     * @param string   $new_status New post status.
     * @param string   $old_status Old post status.
     * @param \WP_Post $post       Post object.
     *
     * @return void
     */
    public function afterPublishReportage($new_status, $old_status, $post) {
        if ($post->post_type !== pubjet_post_type() || EnumPostStatus::Publish !== $new_status) {
            return;
        }
        $reportage_id = pubjet_find_reportage_id($post->ID);
        if (empty($reportage_id)) {
            return;
        }
        $result = $this->publishReportageRequest($post->ID, $reportage_id);
        if (isset($result['code']) && 200 != $result['code']) {
            update_post_meta($post->ID, EnumPostMetakeys::FailedSyncUrl, true);
        }
    }

    /**
     * @param $post_id
     * @param $reportage_id
     *
     * @return mixed
     */
    public function publishReportageRequest($post_id, $reportage_id) {
        return pubjet_publish_reportage($post_id, $reportage_id);
    }

    /**
     * @param $reportage
     *
     * @return string|integer
     */
    public static function findReportageCategory($reportage) {
        global $pubjet_settings;
        $result = false;
        if (isset($reportage->relative_category) && !empty($reportage->relative_category)) {
            $found  = get_term_by('slug', $reportage->relative_category['unique_name'], 'category');
            $result = $found ? $found->term_id : false;
        } else {
            // Find category id based on pricing plans
            if (isset($pubjet_settings['pricingPlans']) && !empty($pubjet_settings['pricingPlans'])) {
                foreach ($pubjet_settings['pricingPlans'] as $pricingPlan) {
                    if ($pricingPlan['title'] == $reportage->pricing_plan_title) {
                        $result = pubjet_isset_value($pricingPlan['category']);
                        break;
                    }
                }
            }
        }
        return $result ? $result : pubjet_isset_value($pubjet_settings['defaultCategory']);
    }

}