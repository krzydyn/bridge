<?php
function updateTable($db,$row) {
	$row["expireOn"]=time()+3600;
	$r=$db->query("update tables set west=?,north=?,east=?,south=?,expireOn=? where name=?",
		array(1=>$row["west"],2=>$row["north"],3=>$row["east"],4=>$row["south"],5=>$row["expireOn"],
				6=>$row["name"]));
	return $r;
}
function api_join($a) {
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
	$q="select * from tables where name=?";
	$r=$db->query($q,array("1"=>$t));
	if ($r===false) {
		echo json_encode(array("error"=>$db->errmsg()));
		return;
	}
	$row=$r->fetch();
	if (!$row) {
		$r=$db->query("insert into tables (name,west,north,east,south,expireOn)"
						." values (?,?,'','','',?)",
						array(1=>$t,2=>$u,3=>time()+3600));
		if ($r===false) {
			echo json_encode(array("error"=>$db->errmsg()));
			return;
		}
	}
	else {
		$seat=array("west","north","east","south");
		$k="";
		foreach ($seat as $k) {
			if ($row[$k]==$u) break;
		}
		logstr("select table ".print_r($row,true));
		if ($row[$k] != $u) {
			foreach ($seat as $k) {
				if (!$row[$k]) { $row[$k]=$u; break; }
			}
		}
		if (updateTable($db,$row)===false) {
			echo json_encode(array("error"=>$db->errmsg()));
			return;
		}
	}
	logstr("table $t found: ".print_r($row,true));
	$db->close();
	api_getinfo($a);
}
function api_exit($a) {
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
	$q="select * from tables where name=?";
	$r=$db->query($q,array("1"=>$t));
	if ($r===false) {
		echo json_encode(array("error"=>$db->errmsg()));
		return;
	}
	$row=$r->fetch();
	if (!$row) { }
	else {
		$seat=array("west","north","east","south");
		$k="";
		foreach ($seat as $k) {
			if ($row[$k]==$u) {$row[$k]="";break;}
		}
		if (updateTable($db,$row)===false) {
			echo json_encode(array("error"=>$db->errmsg()));
			return;
		}
	}
	echo json_encode(array("info"=>""));
}

function api_getinfo($a) {
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
	if ($t) {
		$q="select * from tables where name=?";
		$r=$db->query($q,array("1"=>$t));
	}
	else $r=$db->query("select * from tables");
	if ($r===false) {
		echo json_encode(array("error"=>$db->errmsg()));
		return;
	}
	$seat=array("west","north","east","south");
	$tables=array();
	while ($row=$r->fetch()) {
		foreach ($seat as $k) {
			if (!$row[$k]) $row[$k]="";
		}
		$tables[]=$row;
	}
	logstr("info(".$u.") ".print_r($tables[0],true));
	if (sizeof($tables) == 0) echo json_encode(array("error"=>"Table '$t' not found"));
	else if ($t) echo json_encode(array("info"=>$tables[0]));
	else echo json_encode(array("tables"=>$tables));
}

class Card {
	function __constructor($f,$c) {$this->fig=$f; $this->col=$c;}
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
	$q="select * from tables where name=?";
	$r=$db->query($q,array("1"=>$t));
	$c = shuffleCards();
	$db->close();
	api_getinfo($a);
}
?>
