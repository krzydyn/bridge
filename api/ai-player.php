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
		logstr("player :".print_r($p,true));
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
		return $p;
	}
	function longestSuit() {
		$len=0; $suit="";
		foreach ($cardSuit as $s) {
			$l = sizeof($this->hand[$s]);
			if ($len < $l) { $len=$l; $suit=$s;}
		}
		return $suit;
	}

	// points in one suit is 10
	function opening_bid() {
		$p=$this->points;
		if ($p < 13) return "P"; // pass
		$s = $this->longestSuit();
		$l = sizeof($this->hand[$s]);

		// 13-21 level 1
		if ($p < 15) {
			if ($l < 5) return "1c";
			return "1".$s;
		}
		if ($p < 22) {
			if ($l < 5) return "1N";
			return "1".$s;
		}

		// 22.. level 2
		if ($l < 5) return "2N";
		return "2".$s;
	}
	function opening_resp($bid) {
		if ($bid=="P" || $bid=="D" || $bid=="R") return "P";
		if ($this->points < 6) return "P";
		$s = substr($bid,-1);
		$f = substr($bid,0,-1);

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
		return "P";
	}
}
?>
