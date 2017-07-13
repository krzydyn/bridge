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
$r->addRoute("GET","/.*(css|js)",function() {
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
	require_once("api/bridge.php");
	$args = $req->getval("req");
	$func = "api_".$func;
	$func($args);
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
