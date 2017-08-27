<?php
include_once("api/helpers.php");
function api_join($a) {
	global $seat;
	$req=Request::getInstance();
	$db=null;
	try {
		$db = DB::connectDefault();
	}
	catch(Exception $e) {
		$req->addval("error",get_class($e).": ".$e->getMessage());
		return ;
	}
	$u=$a["u"];
	$t=$a["t"];
	$r=$db->query("select name,state from tables where name=?",array("1"=>$t));
	if ($r===false) {
		$req->addval("error","DB:".$db->errmsg());
		return;
	}
	$row=$r->fetch();
	if (!$row) {
		$state = new State();
		$state->phase="wait";
		$state->west->name=$u;
		$r=$db->query("insert into tables (name,expireOn,state) values (?,?,?)",
						array(1=>$t,2=>(time()+3600),3=>json_encode($state)));
		if ($r===false) {
			$req->addval("error","DB:".$db->errmsg());
			return;
		}
	}
	else {
		$k="";
		$state = json_decode($row["state"]);
		if ($u=="ai") {
			$n=1;
			foreach ($seat as $k) {
				if (strtoupper(substr($state->$k->name,0,2))=="AI") ++$n;
			}
			$u="AI_".$n;
		}
		//check if already exist
		foreach ($seat as $k) {
			if ($state->$k->name == $u) break;
		}
		if ($state->$k->name == $u) { }
		else {
			//find empty seat and go there
			foreach ($seat as $k) {
				if (!$state->$k->name) { $state->$k->name=$u; break; }
			}
		}
		$n=0;
		$c=0;
		foreach ($seat as $k) {
			if ($state->$k->name) ++$n;
			if (is_array($state->$k->hand))
				$c += sizeof($state->$k->hand);
		}
		if ($n < 4) $state->phase="wait";
		else if (!$state->dealer) {
			$state->phase="deal";
			foreach ($seat as $k)
				$state->$k->hand=array();
		}
		else if (!$state->contract) $state->phase="auction";
		else if ($c > 0) $state->phase="game";
		else $state->phase="gameovr";

		$row["state"]=json_encode($state);
		if (saveRow($db,$row)===false) {
			$req->addval("error","DB:".$db->errmsg());
			return;
		}
	}
	$db->close();
	api_getinfo($a);
}
function api_joinai($a) {
	$a["u"]="ai";
	api_join($a);
}
function api_removeai($a) {
	$a["u"]="ai";
	api_exit($a);
}
function api_exit($a) {
	global $seat;
	$req=Request::getInstance();
	$db=null;
	try {
		$db = DB::connectDefault();
	}
	catch(Exception $e) {
		$req->addval("error",get_class($e).": ".$e->getMessage());
		return ;
	}
	$u=$a["u"];
	$t=$a["t"];
	$r=$db->query("select name,state from tables where name=?",array("1"=>$t));
	if ($r===false) {
		$req->addval("error","DB:".$db->errmsg());
		return;
	}
	$row=$r->fetch();
	if (!$row) {
		$req->addval("error","no such table");
		return;
	}
	else {
		$state = json_decode($row["state"]);
		$k="";
		if ($u=="ai") {
			foreach ($seat as $k) {
				if (strtoupper(substr($state->$k->name,0,2))=="AI") $u=$state->$k->name;
			}
		}
		foreach ($seat as $k) {
			if ($state->$k->name==$u) {
				$state->phase="wait";
				$state->$k->name="";
				break;
			}
		}
		//fix array
		foreach ($seat as $k) {
			if (!is_array($state->$k->hand)) $state->$k->hand=array();
		}
		
		$row["state"]=json_encode($state);
		if (saveRow($db,$row)===false) {
			$req->addval("error","DB:".$db->errmsg());
			return;
		}
	}
	$db->close();
	api_getinfo($a);
}

function api_getinfo($a) {
	global $seat;
	$req=Request::getInstance();
	$db=null;
	try {
		$db = DB::connectDefault();
	}
	catch(Exception $e) {
		$req->addval("error",get_class($e).": ".$e->getMessage());
		return ;
	}
	$u=$a["u"];
	$t=$a["t"];
	$r=$db->query("select name,state from tables where name=?",array("1"=>$t));
	if ($r===false) {
		$req->addval("error","DB:".$db->errmsg());
		return;
	}
	$row=$r->fetch();
	if (!$row) {
		$state=new State();
	}
	else {
		$state = json_decode($row["state"]);
		foreach ($seat as $k) {
			if (!is_array($state->$k->hand))
				$state->$k->hand=array();
		}
		if (property_exists($state,"wisted")) unset($state->wisted);
	}
	$state->table=$t;
	$state->user=$u;
	$req->setval("state",$state);
}

function api_reset($a) {
	global $seat;
	$req=Request::getInstance();
	$db=null;
	try {
		$db = DB::connectDefault();
	}
	catch(Exception $e) {
		$req->addval("error",get_class($e).": ".$e->getMessage());
		return ;
	}
	$u=$a["u"];
	$t=$a["t"];
	$r=$db->query("select name,state from tables where name=?",array("1"=>$t));
	if ($r===false) {
		$req->addval("error","DB:".$db->errmsg());
		return;
	}
	$row=$r->fetch();
	if (!$row) {
		$req->addval("error","no such table");
		return;
	}
	else {
		$state = json_decode($row["state"]);
		resetState($state);
		$state->phase="deal";
		$row["state"]=json_encode($state);
		if (saveRow($db,$row)===false) {
			$req->addval("error","DB:".$db->errmsg());
		}
	}
	$db->close();
	api_getinfo($a);
}

function api_deal($a) {
	global $seat;
	$req=Request::getInstance();
	$db=null;
	try {
		$db = DB::connectDefault();
	}
	catch(Exception $e) {
		$req->addval("error",get_class($e).": ".$e->getMessage());
		return ;
	}
	$u=$a["u"];
	$t=$a["t"];
	$r=$db->query("select name,state from tables where name=?",array("1"=>$t));
	if ($r===false) {
		$req->addval("error","DB:".$db->errmsg());
		return;
	}
	if ($row=$r->fetch()) {
		$state=json_decode($row["state"]);
		if ($state->phase != "deal") {
			$req->addval("error","no deal in phase".$state->phase);
			return;
		}
		if ($state->player != "" && $state->player != $u) {
			logstr("expecting user ".$state->player);
			$req->addval("error","wrong player, not your turn");
			return;
		}
		
		$cards = shuffleCards();
		foreach ($seat as $k) {
			$state->$k->hand=array();
		}
		for ($i=0; $i<sizeof($cards); ) {
			foreach ($seat as $k) {
				$state->$k->hand[]=$cards[$i]->fig.$cards[$i]->suit;
				++$i;
			}
		}
		logstr("dealer is: ".seatPlayer($state,$u));
		foreach ($seat as $k) {
			sortcards($state->$k->hand);
			logstr("$k:".json_encode($state->$k->hand));
		}
		$state->phase="auction";
		$state->dealer=$u;
		$state->player=$u;
		$state->bids = array();
		$row["state"]=json_encode($state);
		saveRow($db,$row);
	}
	$db->close();
	api_getinfo($a);
}
function api_setbid($a){
	global $seat;
	$req=Request::getInstance();
	$db=null;
	try {
		$db = DB::connectDefault();
	}
	catch(Exception $e) {
		$req->addval("error",get_class($e).": ".$e->getMessage());
		return ;
	}
	$u=$a["u"];
	$t=$a["t"];
	$bid=$a["bid"];
	$r=$db->query("select name,state from tables where name=?",array("1"=>$t));
	if ($r===false) {
		$req->addval("error","DB:".$db->errmsg());
		return;
	}
	if ($row=$r->fetch()) {
		$state=json_decode($row["state"]);
		if ($state->phase != "auction") {
			$req->addval("error","no bid in phase".$state->phase);
			return;
		}
		if ($state->player != $u) {
			logstr("expecting user ".$state->player);
			$req->addval("error","not your turn, waiting for ".$state->player);
			return;
		}

		if (!checkBidAllowed($state->bids,$bid)) {
			$req->addval("error","bid not allowed");
			return;
		}
		
		$k = seatPlayer($state,$u);
		$state->$k->face = $bid;
		$state->bids[] = $bid;
		checkAuctionEnd($state);

		$row["state"]=json_encode($state);
		saveRow($db,$row);
	}
	$db->close();
	api_getinfo($a);
}
function api_put($a) {
	global $seat;
	$req=Request::getInstance();
	$db=null;
	try {
		$db = DB::connectDefault();
	}
	catch(Exception $e) {
		$req->addval("error",get_class($e).": ".$e->getMessage());
		return ;
	}
	$u=$a["u"];
	$t=$a["t"];
	$c=$a["c"];

	$r=$db->query("select name,state from tables where name=?",array("1"=>$t));
	if ($r===false) {
		$req->addval("error","DB:".$db->errmsg());
		return;
	}
	if ($row=$r->fetch()) {
		$state=json_decode($row["state"]);
		if ($state->phase != "game") {
			$req->addval("error","no put in phase".$state->phase);
			return;
		}
		if ($state->player != $u) {
			logstr("expecting user ".$state->player);
			$req->addval("error","not your turn, waiting for ".$state->player);
			return;
		}

		$k = seatPlayer($state,$state->player);
		if ($state->$k->face) {
			//clear all faces
			foreach ($seat as $k) $state->$k->face = "";
		}
		foreach ($seat as $k) {
			if ($state->$k->name==$u) {
				if (!in_array($c,$state->$k->hand)) {
					$req->addval("error","you don't have this card".$state->player);
					return;
				}
				$state->$k->hand = array_values(array_diff($state->$k->hand,array($c)));
				$state->$k->face = $c;
				$state->cardoff[] = $c;
				break;
			}
		}
		checkTrickEnd($state);
		$row["state"]=json_encode($state);
		saveRow($db,$row);
	}

	$db->close();
	api_getinfo($a);
}
function api_autoplay($a) {
	global $seat;
	$req=Request::getInstance();
	$db=null;
	try {
		$db = DB::connectDefault();
	}
	catch(Exception $e) {
		$req->addval("error",get_class($e).": ".$e->getMessage());
		return ;
	}
	$u=$a["u"];
	$t=$a["t"];

	$r=$db->query("select name,state from tables where name=?",array("1"=>$t));
	if ($r===false) {
		$req->addval("error","DB:".$db->errmsg());
		return;
	}
	if ($row=$r->fetch()) {
		$state=json_decode($row["state"]);
		if (auto_play($state)) {
			$row["state"]=json_encode($state);
			saveRow($db,$row);
		}
	}
	$db->close();
	api_getinfo($a);
}
function api_undo($a) {
	global $seat;
	$req=Request::getInstance();
	$db=null;
	try {
		$db = DB::connectDefault();
	}
	catch(Exception $e) {
		$req->addval("error",get_class($e).": ".$e->getMessage());
		return ;
	}
	$u=$a["u"];
	$t=$a["t"];

	$r=$db->query("select name,state from tables where name=?",array("1"=>$t));
	if ($r===false) {
		$req->addval("error","DB:".$db->errmsg());
		return;
	}
	if ($row=$r->fetch()) {
		$state=json_decode($row["state"]);
		if (undoTurn($state)) {
			$row["state"]=json_encode($state);
			saveRow($db,$row);
		}
	}
	$db->close();
	api_getinfo($a);
}
?>
