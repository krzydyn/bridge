<?php
include_once("api/helpers.php");
class BridgePlayer {
	var $hand;
	var $points;
	function __construct($p) {
		global $seat,$cardFig,$cardSuit;
		$this->hand = array();
		foreach ($cardSuit as $s) $this->hand[$s]=array();
		foreach ($p->hand as $h) {
			$s = substr($h,-1);
			$f = substr($h,0,-1);
			$this->hand[$s][] = $f;
		}
		logstr("player: ".json_encode($p));
		$p=0;
		foreach ($cardSuit as $s)
			$p += $this->pointsfor($this->hand[$s]);
		$this->points = $p;
	}
	function pointsfor($figs) {
		$l = sizeof($figs);
		$p = 0;
		foreach ($figs as $f) {
			if ($f == 'A') $p+=4;
			else if ($f == 'K' && $l>1) $p+=3;
			else if ($f == 'Q' && $l>2) $p+=2;
			else if ($f == 'J' && $l>3) $p+=1;
		}
		if ($l <= 1) $p+=1; //single or void
		else if ($l > 6) $p+=1; //long suit
		return $p;
	}
	function longestSuit() {
		global $cardSuit;
		$len=0; $suit="";
		foreach ($cardSuit as $s) {
			$l = sizeof($this->hand[$s]);
			if ($len <= $l) { $len=$l; $suit=$s;}
		}
		return $suit;
	}
	function strongestSuit() {
		global $cardSuit;
		$points=0; $suit="";
		foreach ($cardSuit as $s) {
			$p = $this->pointsfor($this->hand[$s]);
			if ($points < $p) { $points=$p; $suit=$s;}
		}
		return $suit;
	}

	function last_value_bid($bids) {
		for ($i=sizeof($bids); $i>0; ) {
			--$i;
			$b = $bids[$i];
			if ($b=="P" || $b=="D" || $b=="R") continue;
			return $b;
		}
		return "";
	}
	function last_partner_bid($bids) {
		for ($i=sizeof($bids)-2; $i>0; $i-=2) {
			$b = $bids[$i];
			if ($b=="P" || $b=="D" || $b=="R") continue;
			return $b;
		}
		return "";
	}

	function fixBid($bids,$b) {
		if ($b=="P") return $b;
		if (checkBidAllowed($bids,$b)) return $b;
		logstr("fixing bid ".$b);
		if ($b=="D" || $b=="R") return "P";
		$f = substr($b,0,-1);
		while ($f < 7) {
			$f = $f+1;
			$b=$f.substr($b,-1);
			if (checkBidAllowed($bids,$b)) return $b;
		}
		return "P";
	}

	//most probable suis distribusion in one hand: 4-3-3-3
	// points in one suit is 10 (AKQJ=4+3+2+1=10)
	// max in one hand is 37 (4*AKQ+J=4*9+1=37)
	function calc_auction($bids) {
		logstr("points ".$this->points);
		logstr("bids ".implode(",",$bids));
		//minimum bid
		$minbid = $this->last_value_bid($bids);
		if (!$minbid) return $this->opening_bid("");
		//partner bid
		$parbid = $this->last_partner_bid($bids);
		if (!$parbid) $b=$this->opening_bid($minbid);
		else $b=$this->next_bid($minbid,$parbid,$bids[sizeof($bids)-1]);
		return $this->fixBid($bids,$b);
	}
	function opening_bid($minbid) {
		if ($this->points < 13) return "P"; // pass

		$s = $this->longestSuit();
		$l = sizeof($this->hand[$s]);

		logstr("longestSuit = ".$s.", len=".$l);
		// 13-21 level 1
		if ($this->points < 15) {
			if ($l < 5) return "1c";
			return "1".$s;
		}
		if ($this->points < 22) {
			if ($l < 5) return "1N";
			return "1".$s;
		}

		// 22.. level 2
		if ($l < 5) return "2N";
		return "2".$s;
	}
	function next_bid($minbid,$parbid,$bid) {
		if ($bid=="D") return "R";

		if ($this->points < 6) return "P";
		$s = substr($parbid,-1);
		$f = substr($parbid,0,-1);

		//8 major suit fit
		if (sizeof($this->hand[$s]) + 5 >= 8 && ($s=="s" || $s=="h")) {
			if ($this->points < 11) return ($f+1).$s; //poor
			if ($this->points < 13) return ($f+2).$s; //medium
			return ($f+3).$s; //game
		}
		// minor support 5
		else if (sizeof($this->hand[$s]) >= 5 && ($s=="d" || $s=="c")) {
			if ($this->points < 11) return ($f+1).$s; //poor
			if ($this->points < 13) return ($f+2).$s; //medium
			$s = $this->longestSuit();
			$l = sizeof($this->hand[$s]);
			if ($l < 5) return ($f+1)."N";
		}

		//find new suit
		$s = $this->longestSuit();
		$l = sizeof($this->hand[$s]);
		if ($l >= 5) {
			return ($f+1).$s;
		}
		return "P";
	}
}
?>
