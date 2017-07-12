function Card(f,c) {
	this.fig=f;
	this.col=c;
	this.toString = function() {return this.fig+this.col;}
}

var cardFig = [ 'A','K','Q','J','10','9','8','7','6','5','4','3','2' ];
var cardCol = [ 's','h','d','c' ];
var cardCol_pl = [ 'Pik','Kier','Karo','Trefl' ];
var cardCol_en = [ 'Spade','Heart','Diamond','Club' ];

function Pack() {
	var cards = []; // private
	for (var fig of cardFig) {
		for (var col of cardCol)
			cards.push(new Card(fig,col));
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
	var name;
	var cards = [];
};

var Bridge = function() {
	var playes = [];
}
