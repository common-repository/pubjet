<?php

namespace triboon\pubjet\includes\widgets;

defined('ABSPATH') || exit;

class Backlinks extends \WP_Widget {

    /**
     * @since 1.0.0
     */
    public function __construct() {
        parent::__construct(
            'pubjet_backlinks',
            pubjet__('pubjet-backlinks'),
            ['description' => pubjet__('pubjet-backlinks-hints'),] // Widget options
        );
    }

    /**
     * @param $args
     * @param $instance
     *
     * @return void
     */
    public function widget($args, $instance) {
        $position = pubjet_isset_value($instance['position'], 'all');
        /**
         * The pubjet_backlinks_widget_title filter.
         *
         * @since 1.0.0
         */
        $title = apply_filters('pubjet_backlinks_widget_title', $instance['title'], $this);
        if (!empty($title)) {
            echo $args['before_title'] . esc_html($title) . $args['after_title'];
        }
        echo $args['before_widget'];
        $backlinks = pubjet_db()->backlinks->findActives($position);
        if ($backlinks) {
            ob_start();
            foreach ($backlinks as $backlink_row) {
                ?>
                <a href="<?php echo esc_url($backlink_row->url); ?>" <?php echo $backlink_row->nofollow ? 'rel="nofollow"' : ''; ?>>
                    <?php echo $backlink_row->text; ?>
                </a>
                <?php
            }
        }
        echo ob_get_clean();
        echo $args['after_widget'];
    }

    /**
     * @param $instance
     *
     * @return void
     */
    public function form($instance) {
        // Default values
        $title    = !empty($instance['title']) ? $instance['title'] : '';
        $position = !empty($instance['position']) ? $instance['position'] : '';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php echo pubjet__('widget-title'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('position')); ?>">
                <?php echo pubjet__('backlinks-position'); ?>
            </label>
            <select
                    class="widefat"
                    id="<?php echo esc_attr($this->get_field_id('position')); ?>"
                    name="<?php echo esc_attr($this->get_field_name('position')); ?>"
            >
                <option value="all" <?php selected($position, 'all'); ?>>
                    <?php echo pubjet__('all-backlinks'); ?>
                </option>
                <?php
                foreach (pubjet_find_backlink_positions() as $option) {
                    ?>
                    <option value="<?php echo esc_attr($option); ?>" <?php selected($position, $option); ?>>
                        <?php echo pubjet__($option); ?>
                    </option>
                    <?php
                }
                ?>

            </select>
        </p>
        <?php
    }

    /**
     * @param $new_instance
     * @param $old_instance
     *
     * @return array
     */
    public function update($new_instance, $old_instance) {
        $instance             = [];
        $instance['position'] = (!empty($new_instance['position'])) ? sanitize_text_field($new_instance['position']) : '';
        $instance['title']    = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        return $instance;
    }

}