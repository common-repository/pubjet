<?php

namespace triboon\pubjet\includes\enums;

// Exit if accessed directly
defined('ABSPATH') || exit;

abstract class EnumBacklinkPosition {
    const All          = 'all';
    const FooterInner  = 'footer_inner';
    const FooterMain   = 'footer_main';
    const FooterAll    = 'footer_all';
    const SidebarInner = 'sidebar_inner';
    const SidebarMain  = 'sidebar_main';
    const SidebarAll   = 'sidebar_all';
    const HeaderInner  = 'header_inner';
    const HeaderMain   = 'header_main';
    const HeaderAll    = 'header_all';
}