function Card(f,s) {
	this.fig=f;
	this.suit=c;
	this.toString = function() {return this.fig+this.suit;}
}

var cardFig = [ 'A','K','Q','J','10','9','8','7','6','5','4','3','2' ];
var cardSuit = [ 's','h','d','c' ];

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
	this.cards=0;

	function cmp(a,b) {
		return cardFig.indexOf(a)-cardFig.indexOf(b);
	}
	function pointsfor(figs,s) {
		var l = figs.length;
		var p = 0;
		for (var f of figs) {
			if (f == 'A') p+=4;
			else if (f == 'K' && l>1) p+=3;
			else if (f == 'Q' && l>2) p+=2;
			else if (f == 'J' && l>3) p+=1;
		}
		//bonus points
		if (l < 3) p+=3-l; //void +3, singleton +2, doubleton +1
		else if (l >= 6) {
			if (l > 7) l=7;
			if (s=="s" || s=="h") p+=l-5;
			else if (p >= 5) p+=l-5;
		}
		return p;
	}
	function points() {
		var p=0;
		p+=pointsfor(spades,'s');
		p+=pointsfor(hearts,'h');
		p+=pointsfor(diamonds,'d');
		p+=pointsfor(clubs,'c');
		return p;
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
		that.cards=spades.length+hearts.length+diamonds.length+clubs.length;
	}

	function addlink(f,c) {
		if (that.phase=='game') {
			if (that.current && (that.user
				 || (that.partner.user && that.partner.contractor) 
				 || (that.partner.user && that.contractor))
				)
				return '<input type="button" value="'+f+'" onclick="putCard(\''+that.name+'\',\''+c+'\')">';
			return '<input class="disabled" type="button" value="'+f+'">';
		}
		return ' '+f;
	}
	this.view = function() {
		var cur = that.current ? "active" : "";
		var s='<span class="name '+cur+'">'+that.name+'</span>';
		if (that.tricks>0) s += ' '+that.tricks;
		if (that.cards==0) return s;

		if (that.phase=='auction') {
			if (that.user || that.name.substring(0,2)=='AI') s+=' p'+points()+'<br>';
			else return s;
		}
		else if (that.phase=='game') {
			if (that.user || (that.partner.contractor && that.r.cards<13) || (that.partner.user && that.contractor)) {
			}
			else return s+'<br>Cards: '+that.cards;
		}
		else return s;

		s += '<table class="player"><tr><td>';
		s += Bridge.card('','s')+'</td><td>';
		for (var f of spades) s+=addlink(f,f+'s');
		s += '</td></tr><tr><td>';
		s += Bridge.card('','h')+'</td><td>';
		for (var f of hearts) s+=addlink(f,f+'h');
		s += '</td></tr><tr><td>';
		s += Bridge.card('','d')+'</td><td>';
		for (var f of diamonds) s+=addlink(f,f+'d');
		s += '</td></tr><tr><td>';
		s += Bridge.card('','c')+'</td><td>';
		for (var f of clubs) s+=addlink(f,f+'c');
		s += '</td></tr></table>';
		return s;
	}
};

var Bridge={};
Bridge.PHASE = ['Join','Wait','Deal','Auction','Game'];
Bridge.card = function (fig,suit) {
	//if (!isEmpty(fig)) fig+=' ';
	if (suit == 'N') return fig+'NT';
	return fig+'<img width="14px" src="'+rooturl+'res/'+suit+'.gif">'
}
Bridge.cardx = function (s) {
	var fig = s.substring(0,s.length-1);
	var suit = s.substring(s.length-1,s.length);
	return Bridge.card(fig,suit);
}
Bridge.bids = function() {
	var rsuit = ['c','d','h','s','N'];
	var s='<table><tr>';
	s += '<td colspan="7"><ul class="horiz">';
	s += '<li bid="P" onclick="setBid(this)">Pass</li>&nbsp;';
	s += '<li bid="D" onclick="setBid(this)">Double</li>&nbsp;';
	s += '<li bid="R" onclick="setBid(this)">Redouble';
	s += '</ul></td></tr><tr>';
	for (var i=1; i <= 7; ++i) {
		s += '<td><ul>';
		for (var su of rsuit) {
			s += '<li bid="'+i+su+'" onclick="setBid(this)">';
			s += Bridge.card(i,su);
		}
		s += '</ul></td>';
	}
	s += '</ul></td></tr></table>';
	return s;
}
Bridge.isWinner = function(contract, tricks) {
	var fig = parseInt(contract.substring(0,contract.length-1));
	return tricks - (fig+6);
}
