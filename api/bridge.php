<?php
global $seat;
$seat=array("west","north","east","south");

class Player {
	var $name="";
	var $hand=array();
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
	var $player="";  //current player
	var $bids="";    //list of bids
	var $declararer; //first suit bid same as in contract
	var $contract;   //final contract
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
		if ($state->$k->name == $u) {
		}
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
		if ($n==4) $state->phase="deal";
		$row["state"]=json_encode($state);
		if (updateTable($db,$row)===false) {
			echo json_encode(array("error"=>$db->errmsg()));
			return;
		}
		logstr("table $t found: ".print_r($state,true));
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
		foreach ($seat as $k) {
			$state->$k->hand=array();
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
			if ($state->$k->name==$u) {$state->$k->name="";break;}
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

class Card {
	function __construct($f,$c) {$this->fig=$f; $this->col=$c;}
	var $fig;
	var $col;
}
function shuffleCards() {
	$cardFig = array( 'A','K','Q','J','10','9','8','7','6','5','4','3','2' );
	$cardCol = array( 's','h','d','c' );
	$cards = array();
	foreach ($cardFig as $fig) {
        foreach ($cardCol as $col)
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
		foreach ($seat as $k) {
			if ($state->$k->name==$u) {
				$state->$k->hand = array_values(array_diff($state->$k->hand,array($c)));
			}
		}
		$row["state"]=json_encode($state);
		updateTable($db,$row);
	}

	$db->close();
	api_getinfo($a);
}
function api_setbid($a){
	api_getinfo($a);
}
?>
