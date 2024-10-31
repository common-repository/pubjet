<?php

namespace triboon\pubjet\includes\enums;

// Exit if accessed directly
defined('ABSPATH') || exit;

abstract class EnumBacklinkStatus {
    const Pending = 'pending';
    const Publish = 'publish';
    const Future  = 'future';
}