<?php

namespace triboon\pubjet\includes\elementor;

// Exit if accessed directly
defined('ABSPATH') || exit;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

class Backlinks extends Widget_Base {

    /**
     * @return string
     */
    public function get_name() {
        return 'pubjet';
    }

    /**
     * @return string
     */
    public function get_title() {
        return pubjet__('backlinks');
    }

    /**
     * @return string
     */
    public function get_icon() {
        return 'eicon eicon-editor-link';
    }

    /**
     * @return array|string[]
     */
    public function get_categories() {
        return [''];
    }

    /**
     * Register widget properties
     *
     * @since 1.0.0
     */
    protected function register_controls() {
        $this->start_controls_section(
            'general_settings_section',
            [
                'label' => pubjet__('general'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ],
        );
        $options = [
            'all' => pubjet__('all-backlinks'),
        ];
        foreach (pubjet_find_backlink_positions() as $key) {
            $options[$key] = pubjet__($key);
        }
        $this->add_control(
            'position',
            [
                'name'    => 'position',
                'label'   => pubjet__('backlinks-position'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'all',
                'options' => $options,
            ],
        );
        $this->add_control(
            'dlayout',
            [
                'name'    => 'dlayout',
                'label'   => pubjet__('style'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'horizontal',
                'options' => [
                    'horizontal' => pubjet__('horizontal'),
                    'vertical'   => pubjet__('vertical'),
                ],
            ],
        );
        $this->end_controls_section();
    }

    /**
     * @return string
     * @since 1.0
     */
    protected function render() {
        $settings                        = $this->get_settings_for_display();
        $settings['elementor_widget_id'] = $this->get_id();
        $settings['elementor_preview']   = \Elementor\Plugin::$instance->editor->is_edit_mode();
        echo \triboon\pubjet\includes\Shortcodes::getInstance()->renderBacklinks([
                                                                                     'position' => $settings['position'],
                                                                                     'style'    => $settings['dlayout'],
                                                                                 ]);
    }

}