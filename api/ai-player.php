<?php
include_once("api/helpers.php");
//
// most probable suis distribusion in one hand: 4-3-3-3, 5-3-3-2, 4-4-3-2
// points in one suit is 10 (AKQJ=4+3+2+1=10)
// max in one hand is 37 (4*AKQ+J=4*9+1=37)
// 635013559600 possible hands newton(52,13)
// game = 9-11 tricks
// slam = 12-13 tricks
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
		for ($i=sizeof($bids); $i>0; --$i) {
			$b = $bids[$i-1];
			if ($b=="P" || $b=="D" || $b=="R") continue;
			return $b;
		}
		return "";
	}
	function last_partner_bid($bids) {
		for ($i=sizeof($bids)-1; $i>0; $i-=2) {
			$b = $bids[$i-1];
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

	function calc_auction($bids) {
		logstr("points ".$this->points);
		logstr("bids ".implode(",",$bids));
		$minbid = $this->last_value_bid($bids);
		if (!$minbid) return $this->opening_bid();
		//partner bid
		$parbid = $this->last_partner_bid($bids);
		if (!$parbid) $b=$this->opening_bid();
		else $b=$this->next_bid($parbid,$bids[sizeof($bids)-1]);
		return $this->fixBid($bids,$b);
	}
	function opening_bid() {
		logstr("opening bid");
		if ($this->points < 12) return "P"; // pass

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
	function next_bid($parbid) {
		logstr("next bid (partner $parbid)");
		if ($this->points < 6) return "P";
		$s = substr($parbid,-1);
		$f = substr($parbid,0,-1);

		if ($s=="N") { //no trump
			//fall to longest suit selection
		}
		else { 
			//8 major suit fit
			logstr("8fit = ".(sizeof($this->hand[$s]) + 4 + $f));
			if (sizeof($this->hand[$s]) + 4 + $f >= 8 && ($s=="s" || $s=="h")) {
				if ($f >= 4) return "P"; //(TODO higher bids)
				if ($this->points < 11) return $f.$s; //poor
				if ($this->points < 13) return ($f+1).$s; //medium
				$f += 2;
				if ($f > 4) $f=4;
				return $f.$s; //game (9-11 tricks)
			}
			// minor support 5
			else if (sizeof($this->hand[$s]) + 4 + $f >= 9 && ($s=="d" || $s=="c")) {
				if ($this->points < 11) return $f.$s; //poor
				if ($this->points < 13) return ($f+1).$s; //medium
				$s = $this->longestSuit();
				$l = sizeof($this->hand[$s]);
				if ($l < 5) return $f."N";
				return ($f+1).$s;
			}
		}

		//find new suit
		$s = $this->longestSuit();
		$l = sizeof($this->hand[$s]);
		logstr("new suit $s len=$l");
		if ($l >= 5) {
			return $f.$s;
		}
		if ($this->points > 12 && $f < 3) {
			return $f."N";
		}
		return "P";
	}
}
?>
