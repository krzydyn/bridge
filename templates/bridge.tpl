<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">
<head>
  <title><%val("cfg.sitetitle")%></title>
  <meta http-equiv="Content-type" content="text/html;charset=utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="<%val("cfg.rooturl")%>ajax.js"></script>
  <script src="<%val("cfg.rooturl")%>js/misc.js"></script>
  <script src="<%val("cfg.rooturl")%>js/storage.js"></script>
  <script src="<%val("cfg.rooturl")%>js/bridge.js"></script>
  <link rel="stylesheet" href="<%val("cfg.rooturl")%>css/style.css" type="text/css"/>
</head>
<body>
<div id="actions"> </div>
<div class="pack left" id="content"></div>

<script>
window.addEventListener("load", attachTable);
var ajax = new Ajax();
var deck = new Deck();
var state=0;
function attachTable() {
	var state = readLocal('bridge.state');
	if (!state && $('user') && !isEmpty($('user').value)) {
		state = {};
		state.user = $('user').value;
		state.table = $('table').value;
		saveLocal('bridge.state',state);
	}
	if (!state) buildAttach();
	else {
		var u='t='+state.table+'&u='+state.user;
		ajax.async('get','<%val("cfg.rooturl")%>api/attach?'+u);
	}
}

function buildAttach() {
	log('buildAttach');
	var s='Joint to table play<br>';
	s += '<input id="user" type="text" value="" size="10" placeholder="your name"><br>';
	s += '<input id="table" type="text" value="" size="10" placeholder="table name"> ';
	s += '<input type="button" value="join" onclick="attachTable()"><br>';
	$('content').innerHTML=s;
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
