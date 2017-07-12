<?php
require_once("config.php");
require_once($config["cmslib"]."router.php");
require_once($config["cmslib"]."request.php");
/*echo "<pre>";
print_r(Request::getInstance()->getval(""));
echo "</pre>";
exit;*/

$r = new Router();
//static content
$r->addRoute("GET","/.*(css|js|html)",function() {
	$req=Request::getInstance();
	$f=".".$req->getval("uri");
	if (file_exists($f)) {
		header("Content-Type: ".make_content_type($f));
		readfile($f);
	}
	else {
		header($req->getval("srv.SERVER_PROTOCOL")." 404 Not Found", true, 404);
	}
});

$r->addRoute("GET","(/res/.*)",function() {
	$args = func_get_args();
	$f=".".$req->getval("uri");
	if (file_exists($f)) {
		header("Content-Type: ".make_content_type($f));
		readfile($f);
	}
	else {
		header($req->getval("srv.SERVER_PROTOCOL")." 404 Not Found", true, 404);
	}
});
$r->addRoute("GET","/ajax\\.js",function() {
	$f="../ajax.js";
	header("Content-Type: ".make_content_type($f));
	readfile($f);
});

//valid php scripts
$r->addRoute("","/api/(\\w+).*",function() {
	global $config;
	$req=Request::getInstance();
	$args = func_get_args();
	$func = strtolower($args[1]);
	$f = "./api/".$func.".php";
	if (!file_exists($f)) {
		header($req->getval("srv.SERVER_PROTOCOL")." 404 Not Found", true, 404);
		return ;
	}
	require_once($f);
	$func = "api_".$func;
	if ($func=="api_translate") {
		$lang=$req->getval("req.lang");
		$dst="pol";
		if ($lang=="pl") {$lang="pol";$dst="spa";}
		else if ($lang=="en") {$lang="eng";$dst="spa";}
		else {$lang="spa";}
		$func($lang,$dst,$req->getval("req.phrase"));
	}
	else if ($func=="api_autocomplete") {
		$lang=$req->getval("req.lang");
		if ($lang=="pl") $lang="pol";
		else if ($lang=="en") $lang="eng";
		else $lang="spa";
		$func($lang,$req->getval("req.phrase"));
	}
});

$r->addRoute("GET","/.*",function() {
global $config;
$t = new TemplateEngine();
$t->load("bridge.tpl");
});

$r->addRoute("","",function() {
	$req=Request::getInstance();
	header($req->getval("srv.SERVER_PROTOCOL")." 404 Not Found", true, 404);
});

$r->route(Request::getInstance()->getval("method"), Request::getInstance()->getval("uri"));
?>
