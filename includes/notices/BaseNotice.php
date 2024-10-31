<?php

namespace triboon\pubjet\includes\notices;

// Exit if accessed directly
defined('ABSPATH') || exit;

abstract class BaseNotice {

    /**
     * BaseNotice constructor.
     */
    public function __construct() {
        add_action('admin_notices', [$this, 'noticeContent'], 15);
    }

    /**
     * @since 1.0.0
     */
    public function beforeTitle() {
    }

    /**
     * @since 1.0.0
     */
    public function beforeMessage() {
    }

    /**
     * @since 1.0.0
     */
    public function afterMessage() {
    }

    /**
     * @since 1.0.0
     */
    public function afterButtons() {
    }

    /**
     * @return void
     * @since  1.0
     * @author Triboon
     */
    public function noticeContent() {
        $show = $this->shouldShowNotice();
        if (!$show) {
            return;
        }
        ?>
        <div id="<?php echo esc_attr($this->getId()); ?>" class="notice pubjet-notice show">
            <?php
            // Icon
            pubjet_render_condition($this->showIcon(), function () {
                ?>
                <div class="pubjet-notice__icon">
                    <img src="<?php echo esc_url($this->getIcon()) ?>"/>
                </div>
                <?php
            });
            ?>
            <div class="pubjet-notice__content">
                <?php
                $this->beforeTitle();
                // Title
                pubjet_render_condition($this->showTitle(), function () {
                    ?>
                    <div class="pubjet-notice__title">
                        <?php echo $this->getTitle(); ?>
                    </div>
                    <?php
                });
                $this->beforeMessage();
                // Message
                pubjet_render_condition($this->showText(), function () {
                    ?>
                    <div class="pubjet-notice__message">
                        <?php echo $this->getText(); ?>
                    </div>
                    <?php
                });
                $this->afterMessage();
                // Buttons
                pubjet_render_condition($this->showButtons(), function () {
                $buttons = $this->getButtons();
                ?>
                <div class="pubjet-notice__buttons">
                    <?php
                    // Render buttons
                    if ($buttons && is_array($buttons)) {
                        foreach ($buttons as $button) {
                            $this->renderButton($button);
                        }
                    }
                    // Remind me
                    pubjet_render_condition($this->showRemindMe(), function () {
                        ?>
                        <a href="#"
                           class="button pubjet-notice__remindme pubjet-call-to-rate-btn-close pubjet-notice-remind-me"
                           data-security="<?php echo wp_create_nonce('pubjet-admin-notice'); ?>">
                            <?php echo $this->getRemindMeText(); ?>
                        </a>
                        <?php
                    });
                    // Permanent
                    pubjet_render_condition($this->showPermanentHide(), function () {
                        ?>
                        <a href="#"
                           class="button pubjet-notice__permanenthide pubjet-call-to-rate-btn-close pubjet-notice-permanent-hide"
                           data-security="<?php echo wp_create_nonce('pubjet-admin-notice'); ?>">
                            <?php echo $this->getPermanentHideText(); ?>
                        </a>
                        <?php
                    });
                    ?>
                </div>
            </div>
        <?php
        });
        $this->afterButtons();
        ?>
        </div>
        <?php
    }

    /**
     * @since 1.0.0
     */
    protected function renderButton($args) {
        $args         = pubjet_parse_args($args, [
            'icon'  => false,
            'text'  => '',
            'url'   => '#',
            'class' => [],
            'atts'  => [],
        ]);
        $button_class = pubjet_class_names([
                                               'button',
                                               pubjet_flat_string($args['class']),
                                           ]);
        ?>
        <a
                href="<?php echo esc_attr($args['url']); ?>"
                class="<?php echo pubjet_flat_string($button_class); ?>"
            <?php pubjet_html_tag_atts($args['atts']); ?>
        >
            <?php
            echo $args['icon'];
            echo $args['text'];
            ?>
        </a>
        <?php
    }

    /**
     * @return boolean
     * @since 1.0.0
     */
    protected function shouldShowNotice() {
        if (!pubjet_is_admin()) {
            return false;
        }
        // Remind me
        if (get_transient('pubjet_remind_admin_notice_' . $this->getId())) {
            return false;
        }
        // Closed permanently
        if (get_option('pubjet_permanent_hide_admin_notice_' . $this->getId())) {
            return false;
        }
        if (!$this->shouldShowOnPage()) {
            return false;
        }

        return true;
    }

    /**
     * @return boolean
     * @since 1.0.0
     */
    protected function shouldShowOnPage() {
        global $pagenow;
        $pages = $this->showOnPages();
        if (!$pages) {
            return true;
        }
        $pages = is_array($pages) ? $pages : [$pages];
        if (empty($pages) || !is_array($pages)) {
            return true;
        }
        if (in_array($pagenow, $pages)) {
            return true;
        }
        if (empty($_GET['page'])) {
            return false;
        }
        foreach ($pages as $page) {
            if (strpos($page, '*') !== false) {
                $ppage = str_replace('*', '', $page);
                if (strpos($_GET['page'], $ppage) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @since 1.0.0
     */
    protected function showOnPages() {
        return ['index.php', 'pubjet*'];
    }

    /**
     * @return bool
     */
    protected function showIcon() {
        return true;
    }

    /**
     * @since 1.0
     */
    protected function showTitle() {
        return true;
    }

    /**
     * @since 1.0
     */
    protected function showText() {
        return true;
    }

    /**
     * @since 1.0.0
     */
    protected function showButtons() {
        return true;
    }

    /**
     * @since 1.0.0
     */
    protected function showRemindMe() {
        return true;
    }

    /**
     * @return boolean
     */
    protected function showPermanentHide() {
        return true;
    }

    /**
     * @since 1.0
     */
    protected function getRemindMeText() {
        return pubjet__('remindme-later');
    }

    /**
     * @since 1.0
     */
    protected function getPermanentHideText() {
        return pubjet__('permanent-hide');
    }

    /**
     * @since 1.0.0
     */
    protected function getButtons() {
        return false;
    }

    /**
     * @return mixed
     */
    public function getIcon() {
        return PUBJET_IMAGES_URL . 'pubjet-icon.svg';
    }

    /**
     * @return mixed
     */
    abstract protected function getId();

    /**
     * @return mixed
     */
    abstract public function getTitle();

    /**
     * @return mixed
     */
    abstract public function getText();

}