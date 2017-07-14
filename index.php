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
$r->addRoute("GET","(/icony/.*)",function() {
	$args = func_get_args();
	$f="..".$args[1];
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
	logstr("api: ".$func."(".print_r($args,true).")");
	$func($args);
});

$r->addRoute("GET","/.*",function() {
	global $config;
	initdb();
	$t = new TemplateEngine();
	$t->load("bridge.tpl");
});

$r->addRoute("","",function() {
	$req=Request::getInstance();
	header($req->getval("srv.SERVER_PROTOCOL")." 404 Not Found", true, 404);
});

$r->route(Request::getInstance()->getval("method"), Request::getInstance()->getval("uri"));

function initdb() {
	logstr("initdb");
	$db = DB::connectDefault();

	$reqtabs=array(
		"tables"=>"name varchar(255),expireOn date".
					",west varchar(255),north varchar(255),east varchar(255),south varchar(255)",
	);
	$tabs=$db->tables();
	if ($tabs===false) {
		logstr($db->errmsg());
		return false;
	}

	while (list($t,$v)=each($reqtabs)) {
		if (in_array($t,$tabs)) continue;
		logstr("create table $t $v");
		$r=$db->tabcreate($t,$v);
	}
}
?>
