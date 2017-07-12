<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">
<head>
  <meta http-equiv="Content-type" content="text/html;charset=utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="<%val("cfg.rooturl")%>js/misc.js"></script>
  <script src="<%val("cfg.rooturl")%>js/storage.js"></script>
  <script src="<%val("cfg.rooturl")%>js/bridge.js"></script>
  <title>Brydż</title>
</head>
<body>
<div id="cards"></div>
<div>
	<span id="phase"></span>
	<input id="action" type="button" value="go" onclick="next()" style="visibility:hidden">
</div>
<div id="table"></div>

<script>
window.addEventListener("load", loadPhase);
var deck = new Deck();
var state=0;
showdeck();
function loadPhase() {
	var p = readLocal('brg-phase');
	if (isEmpty(p)) p=0;
	setPhase(p);
}
function setPhase(p) {
	state=p;
	var s=Bridge.STATE[p];
	log('setPahse '+state);
	//$('phase').innerHTML=s;
	$('action').value=s;
	$('action').style.visibility='visible';
}
function next() {
	log('next ');
	setPhase(state+1);
}
function showdeck() {
	var c = deck.getCards();
	var s='';
	for (var i=0; i < c.length; ++i) {
		s+='<span>';
		s+=c[i].fig+'<img src="res/'+c[i].col+'.gif">';
		s+='</span> ';
	}
	$('cards').innerHTML=s;
}
</script>
</body></html>
