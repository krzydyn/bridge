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
<div>
	<span>Current phase:</span><span id="phase"></span>
	<input type="button" value="next" onclick="next()">
</div>
<div class="cards">
<%for($i=0; $i<52; ++$i){%>
	<span class="card"></span>
<%}%>
</div>
<div id="table"></div>

<script>
window.addEventListener("load", loadPhase);
var pack = new Pack();
showpack();
function loadPhase() {
	var p = readLocal('brg-phase');
	if (isEmpty(p)) p='Rozdanie';
	setPhase(p);
}
function setPhase(p) {
	log('setPahse '+p);
	$('phase').innerHTML=p;
}
function next() {
	log('next ');
	pack.shuffle();
	showpack();
}
function showpack() {
	var cui = $('.card');
	var c = pack.getCards();
	for (var i=0; i < c.length; ++i) {
		cui[i].innerHTML=c[i].fig+'<img src="res/'+c[i].col+'.gif>';
	}
}
</script>
</body></html>
