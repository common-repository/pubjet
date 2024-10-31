<?php

namespace triboon\pubjet\includes;

use triboon\pubjet\includes\traits\Utils;

defined('ABSPATH') || exit;

class Metaboxes extends Singleton {

    use Utils;

    /**
     * @return void
     */
    public function init() {
        add_action('admin_menu', [$this, 'reportage'], 15);
    }

    /**
     * @return void
     */
    public function reportage() {
        global $post;

        $post    = false;
        $post_id = $this->get('post');
        if ($post_id) {
            $post = get_post($post_id);
        }

        /**
         * The pubjet_reportages filter.
         *
         * @since 1.0.0
         */
        $metaboxes = apply_filters('pubjet_reportages', [
            [
                'id'       => 'pubjet-reportage-data',
                'title'    => pubjet__('reportage-data'),
                'context'  => 'side',
                'callback' => function () use ($post) {
                    ?>
                    <div id="pubjet-reportage-panel-data" data-postid="<?php echo esc_attr($post->ID); ?>"></div>
                    <?php
                },
                'register' => function ($metabox) use ($post) {
                    if (!$post || !pubjet_is_reportage($post->ID)) {
                        return false;
                    }
                    return true;
                },
            ],
        ],                         $this);
        if (empty($metaboxes)) {
            return;
        }
        $metaboxes = pubjet_array($metaboxes); // Convert to array
        foreach ($metaboxes as $metabox) {

            $should_register = !isset($metabox['register']) ||
                !is_callable($metabox['register']) ||
                empty($metabox['register']) ||
                (is_callable($metabox['register']) && call_user_func($metabox['register'], $metabox));

            if (!$should_register) {
                continue;
            }

            add_meta_box(
                pubjet_isset_value($metabox['id']),
                pubjet_isset_value($metabox['title']),
                pubjet_isset_value($metabox['callback']),
                pubjet_isset_value($metabox['screen'], 'post'),
                pubjet_isset_value($metabox['context'], 'normal'),
                pubjet_isset_value($metabox['priority'], 'low')
            );
        }
    }

}