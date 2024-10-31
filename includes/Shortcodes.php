<?php

namespace triboon\pubjet\includes;

use triboon\pubjet\includes\enums\EnumBacklinkPosition;

defined('ABSPATH') || exit;

class Shortcodes extends Singleton {

    /**
     * @return void
     */
    public function init() {
        add_shortcode('pubjet_backlinks', [$this, 'renderBacklinks']);
    }

    /**
     * @return string
     */
    public function renderBacklinks($shortcode_atts) {
        $shortcode_atts = pubjet_parse_args($shortcode_atts, [
            'position' => EnumBacklinkPosition::HeaderAll,
            'style'    => 'vertical',
        ]);
        $backlinks      = pubjet_db()->backlinks->findActives($shortcode_atts['position']);
        if (!$backlinks) {
            return '';
        }
        ob_start();
        ?>
        <style>
            .pubjet-backlinks a {
                text-decoration: none !important;
            }

            .pubjet-backlinks--horizontal a:not(:first-child) {
                margin-<?php echo is_rtl() ? 'left' : 'right' ?>: 5px;
            }

            .pubjet-backlinks--vertical a:not(:last-child) {
                display: flex;
                flex-direction: column;
                margin-bottom: 8px;
            }
        </style>
        <div class="pubjet-backlinks pubjet-backlinks--<?php echo esc_attr($shortcode_atts['style']); ?>">
            <?php
            foreach ($backlinks as $backlink_row) {
                ?>
                <a href="<?php echo esc_url($backlink_row->url); ?>"
                   class="pubjet-backlinks__item" <?php echo $backlink_row->nofollow ? 'rel="nofollow"' : ''; ?>>
                    <?php echo esc_html($backlink_row->text); ?>
                </a>
                <?php
            }
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

}