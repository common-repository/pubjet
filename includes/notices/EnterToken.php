<?php

namespace triboon\pubjet\includes\notices;

// Exit if accessed directly
defined('ABSPATH') || exit;

class EnterToken extends BaseNotice {

    /**
     * @return mixed|void
     */
    protected function getId() {
        return 'pubjet-enter-token';
    }

    /**
     * @return mixed|void
     */
    public function getTitle() {
        return pubjet__('pubjet-token');
    }

    /**
     * @return mixed|void
     */
    public function getText() {
        return pubjet__('enter-token-desc');
    }

    /**
     * @since 1.0.0
     */
    protected function showOnPages() {
        return ['index.php'];
    }

    /**
     * @return array
     */
    protected function getButtons() {
        return [
            [
                'text'  => 'تنظیمات پابجت',
                'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-settings"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>',
                'url'   => admin_url('admin.php?page=pubjet_settings'),
                'class' => ['button-primary'],
            ],
        ];
    }

    /**
     * @return boolean
     * @since 1.0.0
     */
    protected function shouldShowNotice() {
        $show = parent::shouldShowNotice();
        if (!$show) {
            return false;
        }

        $token = pubjet_token();
        if (empty($token)) {
            return true;
        }

        return false;
    }


}