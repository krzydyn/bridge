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
$r->addRoute("GET","/favicon.ico",function() {//args[0] constain whole match
	$req=Request::getInstance();
	$f="";
	if (file_exists($f)) {
		header("Content-Type: ".make_content_type($f));
		readfile($f);
	}
	else {
		header($req->getval("srv.SERVER_PROTOCOL")." 404 Not Found", true, 404);
	}
});
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
	$req=Request::getInstance();
	$f="..".$req->getval("uri");
	if (file_exists($f)) {
		header("Content-Type: ".make_content_type($f));
		readfile($f);
	}
	else {
		header($req->getval("srv.SERVER_PROTOCOL")." 404 Not Found", true, 404);
	}
});
$r->addRoute("GET","(/res/.*)",function() {
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
	$req=Request::getInstance();
	$f="..".$req->getval("uri");
	if (file_exists($f)) {
		header("Content-Type: ".make_content_type($f));
		readfile($f);
	}
	else {
		header($req->getval("srv.SERVER_PROTOCOL")." 404 Not Found", true, 404);
	}
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

	$c = ob_get_contents();
	ob_end_clean();
	if ($c) $req->addval("error",$c);
	$r = $req->getval("state");
	$c = $req->getval("error");
	if ($c) $r->error=$c;
	logstr("echo:".json_encode($r));
	echo json_encode($r);
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
	$db = DB::connectDefault();

	$reqtabs=array(
		"tables"=>"name varchar(255),expireOn date,state text",
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
	$db->close();
}
?>
