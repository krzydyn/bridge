<?php
require_once("api/ai-player.php");

global $seat,$cardFig,$cardSuit;
$seat=array("west","north","east","south");
$cardFig = array( 'A','K','Q','J','10','9','8','7','6','5','4','3','2' );
$cardSuit = array( 's','h','d','c' );

class Card {
	function __construct($f,$s) {$this->fig=$f; $this->suit=$s;}
	var $fig;
	var $suit;
}
class Player {
	var $name="";
	var $hand=array();
	var $face="";
	var $tricks=0;
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
	var $contractor;   //player for contract
	var $cardoff=array(); // cards already played
	var $west;
	var $north;
	var $east;
	var $south;
}
function saveRow($db,$row) {
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
		$state->$k->tricks=0;
	}
	$state->dealer="";
	$state->contract="";
	$state->contractor="";
	$state->player="";
	$state->bids=array();
	$state->cardoff=array();
}
function shuffleCards() {
	global $cardFig,$cardSuit;
	$cards = array();
	foreach ($cardFig as $fig) {
        foreach ($cardSuit as $col)
            $cards[] = new Card($fig,$col);
    }
	for ($i = sizeof($cards); $i>0; ) {
		--$i;
        $j = rand(0,$i);
        $x = $cards[$i];
        $cards[$i] = $cards[$j];
        $cards[$j] = $x;
    }
	return $cards;
}

function seatNext($k,$n) {
	global $seat;
	$l = sizeof($seat);
	for ($i=0; $i < $l; ++$i) {
		if ($seat[$i] == $k) return $seat[($i+$n)%$l];
	}
	return false;
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
function cmpcard($c1, $c2) {
	global $cardFig,$cardSuit;
	$f1=substr($c1,0,-1);
	$f2=substr($c2,0,-1);
	$s1=substr($c1,-1);
	$s2=substr($c2,-1);
	if ($s1==$s2) $r=array_search($f1,$cardFig)-array_search($f2,$cardFig);
	else $r=array_search($s1, $cardSuit)-array_search($s2, $cardSuit);
	return $r;
}
function sortcards(&$cards) {
	usort($cards,"cmpcard");
}

function cmpbid($b1,$b2) {
	$suit=array('c','d','h','s','N');
	$f1=substr($b1,0,-1);
	$f2=substr($b2,0,-1);
	if ($f1 > 7 || $f2 > 7) return false;
	$i = $f1 - $f2;
	if ($i != 0) return $i;
	$f1=substr($b1,-1);
	$f2=substr($b2,-1);
	return array_search($f1,$suit) - array_search($f2,$suit);
}
function checkBidAllowed($bids,$b) {
	if ($b=='P') return true;
	$l=sizeof($bids);
	if ($l==0) {
		if ($b=='D' || $b=='R') return false;
		return true;
	}
	$bid=$bids[$l-1];
	if ($b=='D') {
		if ($bid=='P' || $bid=='D' || $bid=='R') return false;
		return true;
	}
	if ($b=='R') {
		if ($bid=='D') return true;
		return false;
	}
	//valuable bid
	for ($i=sizeof($bids); $i>0; ) {
		--$i;
		$bid = $bids[$i];
		if ($bid=='P' || $bid=='D' || $bid=='R') ;
		else break;
	}
	return cmpbid($bid,$b) < 0;
}
function checkAuctionEnd($st) {
	global $seat;
	if ($st->phase != "auction") return ;
	$l = sizeof($st->bids);
	$n=0;
	if ($l >= 4) {
		for ($i=sizeof($st->bids); $i>0; ) {
			--$i;
			$bid = $st->bids[$i];
			if ($bid=='P') ++$n;
			else break;
		}
	}
	if ($n==4) {
		// 4 x pass = restart game
		resetState($st);
		$st->phase="deal";
	}
	else if ($n==3) {
		//find last valued bid
		while ($bid=='D' || $bid=='R') {
			--$i; ++$n;
			$bid = $st->bids[$i];
		}
		$st->contract=$st->bids[sizeof($st->bids)-$n-1];
		//player is the first biding contract suit in winning pair
		$suit = substr($st->contract,-1);
		for ($i=$i%2; $i+$n<sizeof($st->bids); $i += 2) {
			if (substr($st->bids[$i],-1) == $suit) {
				$st->contractor=nextPlayer($st,$st->dealer,$i);
				break;
			}
		}
		$st->player=nextPlayer($st,$st->contractor);
		$st->west->face="";
		$st->north->face="";
		$st->east->face="";
		$st->south->face="";
		$st->phase="game";
	}
	else $st->player=nextPlayer($st,$st->player);
}
// $c2 will beat $c1
function beatCard($c1,$c2,$trump) {
	global $cardFig;
	$s1 = substr($c1,-1);
	$s2 = substr($c2,-1);
	if ($s1 == $trump && $s2 != $trump) return false;
	if ($s2 == $trump && $s1 != $trump) return true;
	if ($s1 != $s2) return true;
	$f1 = substr($c1,0,-1);
	$f2 = substr($c2,0,-1);
	return array_search($f1,$cardFig) > array_search($f2,$cardFig);
}
function checkTrickEnd($state) {
	global $seat;
	$np=nextPlayer($state,$state->player);
	$k = seatPlayer($state,$np);
	if ($state->$k->face) {
		// trick end, find the winner
		$trump = substr($state->contract,-1);
		$c = $state->$k->face;
		$win = $k;
		foreach ($seat as $k) {
			if ($k == $win) continue;
			if (beatCard($c,$state->$k->face,$trump)) {
				$c = $state->$k->face;
				$win = $k;
			}
		}
		$np = $state->$win->name;
		$state->$win->tricks += 1;
		if (sizeof($state->$win->hand) == 0) {
			$state->phase="gameovr";
			$np="";
		}
	}
	$state->player = $np;
}
function auto_play($st) {
	global $seat;
	logstr("autoplay[ph=".$st->phase.",p=".$st->player."]");
	$k = seatPlayer($st,$st->player);
	if (!$k) return false;

	$p = new BridgePlayer($st->$k);
	if ($st->phase == "auction") {
		$bid = $p->calc_auction($st->bids);
		logstr("bid=".$bid);
		$st->$k->face = $bid;
		$st->bids[] = $bid;
		checkAuctionEnd($st);
		return true;
	}
	else if ($st->phase == "game") {
		$dump = array(); // cards of dump player
		$trick = array(); // current trick
		$pl = nextPlayer($st,$st->contractor,2);
		$pl = seatPlayer($st,$pl);
		$dump = $st->$pl->hand;
		if ($st->$k->face) {
			foreach ($seat as $s) $st->$s->face = "";
		}
		else {
			for($i=0; $i < 4; ++$i) {
				$pl = nextPlayer($st,$st->player,$i+1);
				$pl = seatPlayer($st,$pl);
				if ($st->$pl->face)
					$trick[] = $st->$pl->face;
			}
		}
		$c = $p->calc_cardplay($trick,$dump,$st->contract,$st->cardoff);
		if (!$c) return false;
		$st->$k->hand = array_values(array_diff($st->$k->hand,array($c)));
		$st->$k->face = $c;
		$st->cardoff[] = $c;
		logstr("card=".$c);
		checkTrickEnd($st);
		return true;
	}
	return false;
}
function undoTurn($state) {
}
function addGamePoints($state) {
	global $seat;
	if ($state->phase != "gameovr") return ;
	$trics=0;
	foreach ($seat as $k) {
		if ($state->$k->name==$state->contractor) $tricks+=$state->$k->tricks;
	}
	$n = $trics - substr($state->contract,0,1);
	$suit=substr($state->contract,1);
	$points=0;
	if ($n >= 0) {
		if ($suit=='c' || $suit=='d') // minor
			$points += 20*$n;
		else if ($suit=='h' || $suit=='s') //major
			$points += 30*$n;
		else // NT
			$points += 40+30*($n-1);
	}
}

?>
