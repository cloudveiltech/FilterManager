<?php

use \Illuminate\Support\HtmlString;

/**
 * Return a safe same-origin relative redirect path, or null if unsafe.
 * Rejects absolute URLs, protocol-relative URLs, and backslash tricks.
 */
function safe_redirect_path($redirect)
{
	if ($redirect === null || $redirect === '') {
		return null;
	}

	if (!is_string($redirect)) {
		return null;
	}

	// Only allow relative paths that start with a single slash.
	if (!str_starts_with($redirect, '/') || str_starts_with($redirect, '//')) {
		return null;
	}

	// Block control characters and backslashes (e.g. /\evil.com).
	if (preg_match('/[\x00-\x1f\\\\\s]/', $redirect)) {
		return null;
	}

	return $redirect;
}

function __redirect_value() {
	if(isset($_GET['redirect'])) {
		return safe_redirect_path($_GET['redirect']);
	} else if(isset($_POST['redirect'])) {
		return safe_redirect_path($_POST['redirect']);
	} else {
		return null;
	}
}

function redirect_field() {
	$redirect = __redirect_value();

	if($redirect != null) {
		return new HtmlString('<input type="hidden" name="redirect" value="'.e($redirect).'">');
	} else {
		return new HtmlString("");
	}
}
