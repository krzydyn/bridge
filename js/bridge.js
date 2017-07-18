function Card(f,s) {
	this.fig=f;
	this.suit=c;
	this.toString = function() {return this.fig+this.suit;}
}

var cardFig = [ 'A','K','Q','J','10','9','8','7','6','5','4','3','2' ];
var cardSuit = [ 's','h','d','c' ];
var cardSuit_pl = [ 'Pik','Kier','Karo','Trefl' ];
var cardSuit_en = [ 'Spade','Heart','Diamond','Club' ];

function Deck() {
	var cards = []; // private
	for (var fig of cardFig) {
		for (var suit of cardSuit)
			cards.push(new Card(fig,suit));
	}
	this.getCards = function() { return cards; }
	this.shuffle = function() {
		var j, x, i;
    	for (i = cards.length; i; --i) {
        	j = Math.floor(Math.random() * i);
        	x = cards[i - 1];
        	cards[i - 1] = cards[j];
        	cards[j] = x;
		}
		x=null;
	}
}

var Player = function() {
	var that=this;
	var spades = [];
	var hearts = [];
	var diamonds = [];
	var clubs = [];

	this.phase = '';
	this.name = '';
	this.face = '';
	this.current = 0;

	function addlink(f,c) {
		if (that.phase=='game') {
			if (that.current)
				return '<input type="button" value="'+f+'" onclick="putCard(\''+that.name+'\',\''+c+'\')">';
			return '<input class="disabled" type="button" value="'+f+'">';
		}
		return ' '+f;
	}
	function cmp(a,b) {
		return cardFig.indexOf(a)-cardFig.indexOf(b);
	}

	this.addSpade = function(f) { spades.push(f); }
	this.addHeart = function(f) { hearts.push(f); }
	this.addDiamond = function(f) { diamonds.push(f); }
	this.addClub = function(f) { clubs.push(f); }
	this.sort = function() {
		spades.sort(cmp);
		hearts.sort(cmp);
		diamonds.sort(cmp);
		clubs.sort(cmp);
	}

	this.view = function() {
		var cur = that.current ? "active" : "";
		var s='<span class="name '+cur+'">'+that.name+'</span><br>';
		if (spades.length+hearts.length+diamonds.length+clubs.length==0) return s;
		s += '<div class="left nowrap pack">';
		s += '<img width="15" src="'+rooturl+'res/s.gif">';
		for (var f of spades) s+=' '+addlink(f,f+'s');
		s += '<br>';
		s += '<img width="15" src="'+rooturl+'res/h.gif">';
		for (var f of hearts) s+=' '+addlink(f,f+'h');
		s += '<br>';
		s += '<img width="15" src="'+rooturl+'res/d.gif">';
		for (var f of diamonds) s+=' '+addlink(f,f+'d');
		s += '<br>';
		s += '<img width="15" src="'+rooturl+'res/c.gif">';
		for (var f of clubs) s+=' '+addlink(f,f+'c');
		s += '</div>';
		return s;
	}
};

var Bridge={};
Bridge.PHASE = ['Join','Wait','Deal','Auction','Game'];
Bridge.bids = function() {
	var rsuit = ['c','d','h','s'];
	var s='<table><tr>';
	s += '<td colspan="7"><ul class="horiz">';
	s += '<li bid="P" onclick="setBid(this)">pass';
	s += '<li bid="D" onclick="setBid(this)">double';
	s += '<li bid="R" onclick="setBid(this)">redouble';
	s += '</ul></td></tr><tr>';
	for (var i=1; i <= 7; ++i) {
		s += '<td><ul>';
		for (var su of rsuit) {
			s += '<li bid="'+i+su+'" onclick="setBid(this)">'+i;
			s += ' <img width="15px" src="'+rooturl+'res/'+su+'.gif">'
		}
		s += '<li bid="'+i+'N" onclick="setBid(this)">'+i+' NT';
		s += '</ul></td>';
	}
	s += '</ul></td></tr></table>';
	return s;
}
