<?php

namespace triboon\pubjet\includes\enums;

// Exit if accessed directly
defined('ABSPATH') || exit;

abstract class EnumPostStatus {
    const Draft   = 'draft';
    const Pending = 'pending';
    const Publish = 'publish';
    const Sent    = 'sent';
    const Failed  = 'failed';
    const Trash   = 'trash';
    const Future  = 'future';
}