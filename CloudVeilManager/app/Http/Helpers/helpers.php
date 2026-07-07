<?php

use \Illuminate\Support\HtmlString;

function __redirect_value() {
	if(isset($_GET['redirect'])) {
		return $_GET['redirect'];
	} else if(isset($_POST['redirect'])) {
		return $_POST['redirect'];
	} else {
		return null;
	}
}

function redirect_field() {
	$redirect = __redirect_value();

	if($redirect != null) {
		return new HtmlString('<input type="hidden" name="redirect" value="'.$redirect.'">');
	} else {
		return new HtmlString("");
	}
}