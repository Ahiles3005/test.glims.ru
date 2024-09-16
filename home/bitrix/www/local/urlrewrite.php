<?php $notBitrix = strpos($_SERVER['REQUEST_URI'], '/bitrix/');
$haveGet = strripos($_SERVER['REQUEST_URI'], "?") !== false;
if ($haveGet) {
	$url = explode("?", $_SERVER['REQUEST_URI']);
	if (($_SERVER['DOCUMENT_URI'] != strtolower($url[0])) && $notBitrix === false) {
		header('Location: https://' . $_SERVER['HTTP_HOST'] . strtolower($url[0]) . "?" . $url[1], true, 301);
		exit();
	}
} else {
	if ($_SERVER['DOCUMENT_URI'] != strtolower($_SERVER['REQUEST_URI']) && $notBitrix === false) {
		header('Location: https://' . $_SERVER['HTTP_HOST'] . strtolower($_SERVER['REQUEST_URI']), true, 301);
		exit();
	}
}

require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/urlrewrite.php';
