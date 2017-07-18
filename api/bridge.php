<?php
global $seat,$cardFig,$cardSuit;
$seat=array("west","north","east","south");
$cardFig = array( 'A','K','Q','J','10','9','8','7','6','5','4','3','2' );
$cardSuit = array( 's','h','d','c' );

class Player {
	var $name="";
	var $hand=array();
	var $face="";
}
class State {
	function __construct() {
		$this->west=new Player();
		$this->north=new Player();
		$this->east=new Player();
		$this->south=new Player();
	}
	var $phase="";
	var $dealer="";
	var $player="";    //current player
	var $bids=array(); //list of bids
	var $contract;     //final contract
	var $west;
	var $north;
	var $east;
	var $south;
}

function updateTable($db,$row) {
	$row["expireOn"]=time()+3600;
	$r=$db->query("update tables set expireOn=?,state=? where name=?",
		array(1=>$row["expireOn"],2=>$row["state"],3=>$row["name"]));
	return $r;
}
function resetState($state) {
	global $seat;
	foreach ($seat as $k) {
		$state->$k->hand=array();
		$state->$k->face="";
	}
	$state->phase="deal";
	$state->dealer="";
	$state->player="";
	$state->bids=array();
}
function api_join($a) {
	global $seat;
	$db=null;
	try {
		$db = DB::connectDefault();
	}
	catch(Exception $e) {
		echo json_encode(array("error"=>get_class($e).": ".$e->getMessage()));
		return ;
	}
	$u=$a["u"];
	$t=$a["t"];
	$r=$db->query("select name,state from tables where name=?",array("1"=>$t));
	if ($r===false) {
		echo json_encode(array("error"=>$db->errmsg()));
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
			echo json_encode(array("error"=>$db->errmsg()));
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
			//find empty seat and sit down
			foreach ($seat as $k) {
				if (!$state->$k->name) { $state->$k->name=$u; break; }
			}
		}
		$n=0;
		foreach ($seat as $k) {
			if ($state->$k->name) ++$n;
		}
		if ($n < 4) $state->phase="wait";
		else if (!$state->dealer) {
			$state->phase="deal";
			foreach ($seat as $k)
				$state->$k->hand=array();
		}
		else if (!$state->contract) $state->phase="auction";
		else $state->phase="game";

		$row["state"]=json_encode($state);
		if (updateTable($db,$row)===false) {
			echo json_encode(array("error"=>$db->errmsg()));
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
	$db=null;
	try {
		$db = DB::connectDefault();
	}
	catch(Exception $e) {
		echo json_encode(array("error"=>get_class($e).": ".$e->getMessage()));
		return ;
	}
	$u=$a["u"];
	$t=$a["t"];
	$r=$db->query("select name,state from tables where name=?",array("1"=>$t));
	if ($r===false) {
		echo json_encode(array("error"=>$db->errmsg()));
		return;
	}
	$row=$r->fetch();
	if (!$row) {
		echo json_encode(array("error"=>"no such table"));
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
		if (updateTable($db,$row)===false) {
			echo json_encode(array("error"=>$db->errmsg()));
			return;
		}
	}
	$db->close();
	api_getinfo($a);
}

function api_getinfo($a) {
	global $seat;
	$db=null;
	try {
		$db = DB::connectDefault();
	}
	catch(Exception $e) {
		echo json_encode(array("error"=>get_class($e).": ".$e->getMessage()));
		return ;
	}
	$u=$a["u"];
	$t=$a["t"];
	$r=$db->query("select name,state from tables where name=?",array("1"=>$t));
	if ($r===false) {
		echo json_encode(array("error"=>$db->errmsg()));
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
	}
	echo json_encode(array("state"=>$state));
}

function api_reset($a) {
	global $seat;
	$db=null;
	try {
		$db = DB::connectDefault();
	}
	catch(Exception $e) {
		echo json_encode(array("error"=>get_class($e).": ".$e->getMessage()));
		return ;
	}
	$u=$a["u"];
	$t=$a["t"];
	$r=$db->query("select name,state from tables where name=?",array("1"=>$t));
	if ($r===false) {
		echo json_encode(array("error"=>$db->errmsg()));
		return;
	}
	$row=$r->fetch();
	if (!$row) {
		echo json_encode(array("error"=>"no such table"));
		return;
	}
	else {
		$state = json_decode($row["state"]);
		resetState($state);
		$row["state"]=json_encode($state);
		if (updateTable($db,$row)===false) {
			echo json_encode(array("error"=>$db->errmsg()));
			return;
		}
	}
	$db->close();
	api_getinfo($a);
}

class Card {
	function __construct($f,$c) {$this->fig=$f; $this->col=$c;}
	var $fig;
	var $col;
}
function shuffleCards() {
	global $cardFig,$cardSuit;
	$cards = array();
	foreach ($cardFig as $fig) {
        foreach ($cardSuit as $col)
            $cards[] = new Card($fig,$col);
    }
	for ($i = sizeof($cards); $i>0; --$i) {
        $j = rand(0,$i);
        $x = $cards[$i - 1];
        $cards[$i - 1] = $cards[$j];
        $cards[$j] = $x;
    }
	return $cards;
}

function seatPlayer($st, $n) {
	global $seat;
	foreach ($seat as $k) {
		if ($st->$k->name == $n) return $k;
	}
	return false;
}
function nextPlayer($st, $name, $n=1) {
	global $seat;
	$l = sizeof($seat);
	for ($i=0; $i < $l; ++$i) {
		$k = $seat[$i];
		if ($st->$k->name == $name) {
			$k = $seat[($i+$n)%$l];
			return $st->$k->name;
		}
	}
	return false;
}

function api_deal($a) {
	global $seat;
	$db=null;
	try {
		$db = DB::connectDefault();
	}
	catch(Exception $e) {
		echo json_encode(array("error"=>get_class($e).": ".$e->getMessage()));
		return ;
	}
	$u=$a["u"];
	$t=$a["t"];
	$r=$db->query("select name,state from tables where name=?",array("1"=>$t));
	if ($r===false) {
		echo json_encode(array("error"=>$db->errmsg()));
		return;
	}
	if ($row=$r->fetch()) {
		$state=json_decode($row["state"]);
		if ($state->phase != "deal") {
			echo json_encode(array("error"=>"no deal in phase".$state->phase));
			return;
		}
		if ($state->player != "" && $state->player != $u) {
			logstr("expecting user ".$state->player);
			echo json_encode(array("error"=>"wrong player, not your turn"));
			return;
		}
		
		$cards = shuffleCards();
		foreach ($seat as $k) {
			$state->$k->hand=array();
		}
		for ($i=0; $i<sizeof($cards); ) {
			foreach ($seat as $k) {
				$state->$k->hand[]=$cards[$i]->fig.$cards[$i]->col;
				++$i;
			}
		}
		$state->phase="auction";
		$state->dealer=$u;
		$state->player=$u;
		$state->bids = array();
		$row["state"]=json_encode($state);
		updateTable($db,$row);
	}
	$db->close();
	api_getinfo($a);
}
function cmpbid($b1,$b2) {
	$suit=array('c','d','h','s','N');
	$f1=substr($b1,0,1);
	$f2=substr($b2,0,1);
	$i = $f1 - $f2;
	if ($i != 0) return $i;
	$f1=substr($b1,1);
	$f2=substr($b2,1);
	return array_search($f1,$suit) - array_search($f2,$suit);
}
function checkBidAllowed($st,$b) {
	if ($b=='P') return true;
	$l=sizeof($st->bids);
	if ($l==0) {
		if ($b=='D' || $b=='R') return false;
		return true;
	}
	$bid=$st->bids[$l-1];
	if ($b=='D') {
		if ($bid=='P' || $bid=='D' || $bid=='R') return false;
		return true;
	}
	if ($b=='R') {
		if ($bid=='D') return true;
		return false;
	}
	//valuable bid
	for ($i=sizeof($st->bids); $i>0; ) {
		--$i;
		$bid = $st->bids[$i];
		if ($bid=='P' || $bid=='D' || $bid=='R') ;
		else break;
	}
	return cmpbid($bid,$b) < 0;
}
function checkEndAuction($st) {
	global $seat;
	if ($st->phase != "auction") return ;
	$n=0;
	$l = sizeof($st->bids);
	if ($l >= 4) {
		for ($i=sizeof($st->bids); $i>0; ) {
			--$i;
			$bid = $st->bids[$i];
			if ($bid=='P' || $bid=='D' || $bid=='R') ++$n;
			else break;
		}
	}
	if ($n==$l) {
		resetState($st);
	}
	else if ($n>=3) {
		$st->contract=$st->bids[sizeof($st->bids)-$n-1];
		$suit = substr($st->contract,-1);
		for ($i=0; $i+$n<sizeof($st->bids); ++$i) {
			if (substr($st->bids[$i],-1) == $suit) {
				$st->player=nextPlayer($st,$st->dealer,$i+1);
			}
		}
		$st->west->face="";
		$st->north->face="";
		$st->east->face="";
		$st->south->face="";
		$st->phase="game";
	}
	else $st->player=nextPlayer($st,$st->player);
}

function api_setbid($a){
	global $seat;
	$db=null;
	try {
		$db = DB::connectDefault();
	}
	catch(Exception $e) {
		echo json_encode(array("error"=>get_class($e).": ".$e->getMessage()));
		return ;
	}
	$u=$a["u"];
	$t=$a["t"];
	$bid=$a["bid"];
	$r=$db->query("select name,state from tables where name=?",array("1"=>$t));
	if ($r===false) {
		echo json_encode(array("error"=>$db->errmsg()));
		return;
	}
	if ($row=$r->fetch()) {
		$state=json_decode($row["state"]);
		if ($state->phase != "auction") {
			echo json_encode(array("error"=>"no bid in phase".$state->phase));
			return;
		}
		if ($state->player != $u) {
			logstr("expecting user ".$state->player);
			echo json_encode(array("error"=>"not your turn, waiting for ".$state->player));
			return;
		}

		if (!checkBidAllowed($state,$bid)) {
			echo json_encode(array("error"=>"bid not allowed"));
			return;
		}
		
		$k = seatPlayer($state,$u);
		$state->$k->face = $bid;
		$state->bids[] = $bid;
		checkEndAuction($state);

		$row["state"]=json_encode($state);
		updateTable($db,$row);
	}
	$db->close();
	api_getinfo($a);
}

function api_put($a) {
	global $seat;
	$db=null;
	try {
		$db = DB::connectDefault();
	}
	catch(Exception $e) {
		echo json_encode(array("error"=>get_class($e).": ".$e->getMessage()));
		return ;
	}
	$u=$a["u"];
	$t=$a["t"];
	$c=$a["c"];

	$r=$db->query("select name,state from tables where name=?",array("1"=>$t));
	if ($r===false) {
		echo json_encode(array("error"=>$db->errmsg()));
		return;
	}
	if ($row=$r->fetch()) {
		$state=json_decode($row["state"]);
		if ($state->phase != "game") {
			echo json_encode(array("error"=>"no put in phase".$state->phase));
			return;
		}
		if ($state->player != $u) {
			logstr("expecting user ".$state->player);
			echo json_encode(array("error"=>"not your turn, waiting for ".$state->player));
			return;
		}

		foreach ($seat as $k) {
			if ($state->$k->name==$u) {
				$state->$k->hand = array_values(array_diff($state->$k->hand,array($c)));
				$state->$k->face = $c;
			}
		}
		$state->player=nextPlayer($state,$state->player);
		$row["state"]=json_encode($state);
		updateTable($db,$row);
	}

	$db->close();
	api_getinfo($a);
}
?>
