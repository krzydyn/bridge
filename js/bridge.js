function Card(f,s) {
	this.f=f;
	this.s=c;
	this.toString = function() {return this.f+this.s;}
}

var cardFig = [ 'A','K','Q','J','10','9','8','7','6','5','4','3','2' ];
var cardSuit = [ 's','h','d','c' ];

function Deck() {
	var cards = []; // private
	for (var f of cardFig) {
		for (var s of cardSuit)
			cards.push(new Card(f,s));
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

var Player = function(cards) {
	var that=this;
	var hand=[];
	var hcp;
	var points;

	this.phase = '';
	this.name = '';
	this.table = '';
	this.face = '';
	this.current = 0;
	this.cards=cards.length;

	//contructor start
	for (var s of cardSuit) { hand[s]=[]; }
	for (var c of cards) {
		var f = c.substring(0,c.length-1);
		var s = c.substring(c.length-1,c.length);
		hand[s].push(f);
	}
	sorthand();
	hcp=0;
	points=0;
	for (var s of cardSuit) {
		hcp += pointsHCP(hand[s]);
		points += pointsBonus(hand[s],s);
	}
	points+=hcp;
	//constructor end

	function pointsHCP(figs) {
		var l = figs.length;
		var p = 0;
		for (var f of figs) {
			if (f == 'A') p+=4;
			else if (f == 'K' && l>1) p+=3;
			else if (f == 'Q' && l>2) p+=2;
			else if (f == 'J' && l>3) p+=1;
		}
		return p;
	}
	function pointsBonus(figs,s) {
		var l = figs.length;
		var p = 0;
		if (l < 3) p+=3-l; //void +3, singleton +2, doubleton +1
		else if (l >= 6) {
			if (l > 7) l=7;
			if (s=='s' || s=='h') p+=l-5;
			else if (p >= 5) p+=l-5;
		}
		return p;
	}

	function cmpcard(a,b) {
		return cardFig.indexOf(a)-cardFig.indexOf(b);
	}
	function sorthand() {
		for (var s of cardSuit) {
			hand[s].sort(cmpcard);
		}
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
			if (that.user || that.table=='test') s+=' p'+points+'/'+hcp+'<br>';
			else return s;
		}
		else if (that.phase=='game') {
			if (that.user || (that.partner.contractor && that.cards<13) || (that.partner.user && that.contractor) || 
					that.table=='test') {}
			else return s+'<br>Cards: '+that.cards;
		}
		else return s;

		s += '<table class="player"><tr><td>';
		s += Bridge.card('','s')+'</td><td>';
		for (var f of hand['s']) s+=addlink(f,f+'s');
		s += '</td></tr><tr><td>';
		s += Bridge.card('','h')+'</td><td>';
		for (var f of hand['h']) s+=addlink(f,f+'h');
		s += '</td></tr><tr><td>';
		s += Bridge.card('','d')+'</td><td>';
		for (var f of hand['d']) s+=addlink(f,f+'d');
		s += '</td></tr><tr><td>';
		s += Bridge.card('','c')+'</td><td>';
		for (var f of hand['c']) s+=addlink(f,f+'c');
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
