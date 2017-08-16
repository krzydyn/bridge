<?php
include_once("api/helpers.php");
/*
 * http://www.bridgeworld.com/indexphp.php?page=/pages/learn/beginners/lesson5.html
 *
 * high-cards points:
 *   A=4, K=3, Q=2, J=1
 * distribution points:
 *   Void = 3, Singleton = 2, Doubleton = 1.
 * length points:
 *   major len=6 1, len=7 2
 *   minor(good) len=6 1, len=7 2  (good=contains 2 of 3 top honors)
 * NOTE: don't count distribution points when bidding NT
 * to open bid hcp>=10 and points>=13
 *
 * most probable suis distribusion in one hand: 4-3-3-3, 5-3-3-2, 4-4-3-2
 * points in one suit is 10 (AKQJ=4+3+2+1=10), total points = 40
 * max in one hand is 37 (4*AKQ+J=4*9+1=37)
 * 635013559600 possible hands newton(52,13)
 * game = 9-11 tricks
 * slam = 12-13 tricks
 *
 */
class BridgePlayer {
	var $hand;
	var $hcp;    //HCP = high-card points
	var $points; //hcp+distr+length
	function __construct($p) {
		global $seat,$cardSuit;
		$this->hand = array();
		foreach ($cardSuit as $s) $this->hand[$s]=array();
		foreach ($p->hand as $h) {
			$s = substr($h,-1);
			$f = substr($h,0,-1);
			$this->hand[$s][] = $f;
		}
		$p=0;
		foreach ($cardSuit as $s)
			$p += $this->pointsHCP($this->hand[$s]);
		$this->hcp = $p;

		//bonus points
		$p=0;
		foreach ($cardSuit as $s) {
			$l = sizeof($this->hand[$s]);
			if ($l < 3) $p+=3-$l; //void +3, singleton +2, doubleton +1
			else if ($l >= 6) {
				if ($l > 7) $l=7;
				if ($s=="s" || $s=="h") $p+=$l-5;
				else if ($this->hcp >= 5) $p+=$l-5;
			}
		}
		$this->points=$this->hcp + $p;
	}
	function pointsHCP($figs) {
		$l = sizeof($figs);
		$p = 0;
		foreach ($figs as $f) {
			if ($f == 'A') $p+=4;
			else if ($f == 'K' && $l>1) $p+=3;
			else if ($f == 'Q' && $l>2) $p+=2;
			else if ($f == 'J' && $l>3) $p+=1;
		}
		return $p;
	}
	function shortestSuit() {
		global $cardSuit;
		$len=13; $suit="";
		foreach ($cardSuit as $s) {
			$l = sizeof($this->hand[$s]);
			if ($l == 0) continue;
			if ($len > $l) {
				 $len=$l; $suit=$s;
			}
		}
		return $suit;
	}
	function longestSuit() {
		global $cardSuit;
		$points=0; $len=0; $suit="";
		foreach ($cardSuit as $s) {
			$l = sizeof($this->hand[$s]);
			if ($len < $l) {
				 $len=$l; $suit=$s; $points = $this->pointsHCP($this->hand[$s]);
			}
			else if ($len == $l) {
				$p = $this->pointsHCP($this->hand[$s]);
				if ($points < $p) {$suit=$s;$points=$p;}
			}
		}
		return $suit;
	}
	function strongestSuit() {
		global $cardSuit;
		$points=0; $len=0; $suit="";
		foreach ($cardSuit as $s) {
			if (sizeof($this->hand[$s]) < 3) continue;
			$p = $this->pointsHCP($this->hand[$s]);
			if ($points < $p) {
				$points=$p; $suit=$s; $len=sizeof($this->hand[$s]);
			}
			else if ($points == $p && $len < sizeof($this->hand[$s])) {
				$suit=$s; $len=sizeof($this->hand[$s]);
			}
		}
		return $suit;
	}
	function isWinningCard($c,$cardoff) {
		global $cardSuit,$cardFig;
		$s=substr($c,-1);
		$tf=substr($c,0,-1);
		foreach ($cardFig as $f) {
			if ($f == $tf) return true;
			if (array_search($f.$s,$cardoff)===false) break;
		}
		return false;
	}
	function winningCard($trump,$cardoff) {
		global $cardSuit,$cardFig;
		/*$tp=0;
		foreach ($cardoff as $c) {
			if (substr($c,-1)==$trump) ++$tp;
		}*/
		foreach ($cardSuit as $s) {
			if ($s == $trump || sizeof($this->hand[$s])==0) continue;
			$tf = $this->hand[$s][0];
			foreach ($cardFig as $f) {
				if ($f == $tf) return $tf.$s;
				if (array_search($f.$s,$cardoff)===false) break;
			}
		}
		if ($trump == "N") return false;
		$s=$trump;
		if (sizeof($this->hand[$s])==0) return false;
		$tf = $this->hand[$s][0];
		foreach ($cardFig as $f) {
			if ($f == $tf) return $tf.$s;
			if (array_search($f.$s,$cardoff)===false) break;
		}
		return false;
	}
	function highestSuit($trump) {
		global $cardSuit,$cardFig;
		$fm=$cardFig[sizeof($cardFig)-1];
		$sm="";
		foreach ($cardSuit as $s) {
			if ($s == $trump || sizeof($this->hand[$s])==0) continue;
			$f = $this->hand[$s][0];
			if (array_search($f,$cardFig) < array_search($fm,$cardFig)) {
				$fm=$f; $sm=$s;
			}
		}
		if ($trump=="N") return $sm;
		$s = $trump;
		if (sizeof($this->hand[$s])==0) return $sm;
		if (empty($sm)) {
			$f = $this->hand[$s][0];
			if (array_search($f,$cardFig) < array_search($fm,$cardFig)) {
				$fm=$f; $sm=$s;
			}
		}
		return $sm;
	}
	function smallestSuit($trump) {
		global $cardSuit,$cardFig;
		$fm=$cardFig[0];
		$sm="";
		foreach ($cardSuit as $s) {
			if (sizeof($this->hand[$s])==0 || $s==$trump) continue;
			$f=$this->hand[$s][sizeof($this->hand[$s])-1];
			if (array_search($f,$cardFig) >= array_search($fm,$cardFig)) {
				$fm=$f; $sm=$s;
			}
		}
		if ($trump=="N") return $sm;
		if ($sm) return $sm;
		$s=$trump;
		if (sizeof($this->hand[$s])>0) {
			$f=$this->hand[$s][sizeof($this->hand[$s])-1];
			if (array_search($f,$cardFig) > array_search($fm,$cardFig)) {
				$fm=$f; $sm=$s;
			}
		}
		return $sm;
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
		logstr("hcp=".$this->hcp."  points=".$this->points);
		logstr("bids=".implode(",",$bids));
		$minbid = $this->last_value_bid($bids);
		if (!$minbid) return $this->opening_bid($bids);
		//partner bid
		$parbid = $this->last_partner_bid($bids);
		if (!$parbid) $b=$this->opening_bid($bids);
		else $b=$this->next_bid($parbid,$bids);
		return $this->fixBid($bids,$b);
	}

	function opening_bid($bids) {
		logstr("opening bid");
		if ($this->points < 13 || $this->hcp < 10) return "P"; // pass

		$s = $this->longestSuit();
		$l = sizeof($this->hand[$s]);

		logstr("longestSuit = ".$s.", len=".$l);
		// 13-21 level 1
		if ($this->points < 15) {
			if ($l < 5) {
				if (sizeof($bids) > 0) return "P";
				return "1c";
			}
			return "1".$s;
		}
		if ($this->points < 22) {
			if ($l < 5 && $this->hcp > 14) return "1N";
			return "1".$s;
		}

		// 22.. level 2
		if ($l < 5 && $this->hcp > 20) return "2N";
		if ($this->points < 24) { //preempt oponents if have strong color
			if ($l >= 8) {
				if ($this->hcp < 7) return "2".$s;
				if ($this->hcp < 8) return "3".$s;
				return "4".$s;
			}
			return "2".$s;
		}

		return "2c"; //strong 2c
	}
	function next_bid($parbid,$bids) {
		logstr("next bid (partner $parbid)");
		if ($this->points < 5) return "P";
		$s = substr($parbid,-1);
		$f = substr($parbid,0,-1);

		if ($s=="N") { //no trump
			if ($f >= 3) return "P"; //(TODO higher bids)
			//fall to longest suit selection
		}
		else { 
			//8 major suit fit
			if (sizeof($this->hand[$s]) + 4 + $f >= 8 && ($s=="s" || $s=="h")) {
				if ($f >= 4) return "P"; //(TODO higher bids)
				if ($this->points < 11) return $f.$s; //poor
				if (sizeof($bids) < 4) ++$f;
				if ($this->points < 13) return $f.$s; //medium
				++$f;
				if ($f > 4) return "P"; //(TODO higher bids)
				return $f.$s; //game (9-11 tricks)
			}
			// minor support 5
			else if (sizeof($this->hand[$s]) + 4 + $f >= 9 && ($s=="d" || $s=="c")) {
				if ($f>=5) return "P"; //(TODO higher bids)
				if ($this->points < 11) return $f.$s; //poor
				if (sizeof($bids) < 4) ++$f;
				if ($this->points < 13) return $f.$s; //medium
				$ms = $this->longestSuit();
				$l = sizeof($this->hand[$ms]);
				if ($l < 5 && $this->hcp > 10+$f) return $f."N";
				if ($ms=='s' || $ms=='h') $s=$ms;
				++$f;
				if ($f > 5) return "P"; //(TODO higher bids)
				return $f.$s;
			}
		}

		//find new suit
		if (sizeof($bids) < 4)
			$s = $this->longestSuit();
		else
			$s = $this->strongestSuit();
		logstr("new suit: $s");
		$l = sizeof($this->hand[$s]);
		if ($l >= 5) {
			if (($s=="s" || $s=="h") && $f<4) return $f.$s;
			if (($s=="d" || $s=="c") && $f<5) return $f.$s;
			return "P";
		}
		if ($this->hcp > 10+$f && $f < 3) {
			return $f."N";
		}
		if ($f < 3)
			return $f.$s;
		return "P";
	}
	function calc_cardplay($trick, $dumb, $contract, $cardoff) {
		$cwin=0;
		$trump=substr($contract,-1);
		logstr("playing hand :".json_encode($this->hand));
		if (sizeof($trick)==0) return $this->open_trick($trick, $dumb, $trump, $cardoff);
		return $this->next_trick($trick, $dumb, $trump, $cardoff);
	}

	function open_trick($trick, $dumb, $trump, $cardoff) {
		global $cardSuit;
		logstr("open trick");
		//winning card
		$c2 = $this->winningCard($trump,$cardoff);
		if ($c2) {
			logstr("winning card: ".$c2);
			return $c2;
		}

		//singleton
		$s=$this->shortestSuit($trump);
		if ($s!=$trump && sizeof($this->hand[$s]) == 1) {
			$c2=$this->hand[$s][0].$s;
			logstr("singleton: ".$c2);
			return $c2;
		}

		//find highest card
		$s=$this->highestSuit($trump);
		if (sizeof($this->hand[$s]) > 1) return $this->hand[$s][1].$s;
		$s=$this->smallestSuit($trump);
		return $this->hand[$s][sizeof($this->hand[$s])-1].$s;
	}
	function next_trick($trick, $dumb, $trump, $cardoff) {
		global $cardSuit;
		logstr("next trick:".json_encode($trick));
		//find current winning card
		$winid=0;
		$cwin=$trick[0];
		for ($i=1; $i < sizeof($trick); ++$i) {
			$c2=$trick[$i];
			if (beatCard($cwin,$c2,$trump)) {
				$winid=$i;
				$cwin=$c2;
			}
		}
		logstr("current win: ".$cwin);
		$parid=sizeof($trick)-2;
		$c0 = $trick[0];
		$s = substr($c0,-1);

		if (sizeof($this->hand[$s]) > 0) {
			if ($winid==$parid) return $this->hand[$s][sizeof($this->hand[$s])-1].$s;
			$cb="";
			foreach ($this->hand[$s] as $f) {
				$c2=$f.$s;
				if ($this->isWinningCard($c2,$cardoff)===false) break;
				if (beatCard($cwin,$c2,$trump)===false) break;
				logstr("winnig card: ".$c2);
				$cb=$c2;
			}
			if ($cb) return $cb;
			foreach ($this->hand[$s] as $f) {
				$c2=$f.$s;
				if (beatCard($cwin,$c2,$trump)===false) break;
				logstr("beating card: ".$c2);
				$cb=$c2;
			}
			if ($cb) return $cb;
			return $this->hand[$s][sizeof($this->hand[$s])-1].$s;
		}

		// no card in color
		if ($winid==$parid) {
			$s = $this->smallestSuit($trump);
			return $this->hand[$s][sizeof($this->hand[$s])-1].$s;
		}

		if ($trump!="N" && sizeof($this->hand[$trump]) > 0) {
			$s=$trump;
			$cb="";
			foreach ($this->hand[$trump] as $f) {
				$c2=$f.$s;
				if (beatCard($cwin,$c2,$trump)===false) break;
				$cb=$c2;
			}
			if ($cb) return $cb;
		}
		$s=$this->smallestSuit($trump);
		if (!$s) return false;
		return $this->hand[$s][sizeof($this->hand[$s])-1].$s;
	}
}
?>
