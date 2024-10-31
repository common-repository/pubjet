<?php

namespace triboon\pubjet\includes\enums;

// Exit if accessed directly
defined('ABSPATH') || exit;

class EnumAjaxPrivType {
	const LoggedIn  = 'loggedin';
	const Anonymous = 'anonymous';
	const Both      = 'both';
}