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
  <link rel="stylesheet" href="<%val("cfg.rooturl")%>css/bridge.css" type="text/css"/>
</head>
<body>
<div id="status"></div>
<div class="pack left" id="content"></div>
<div id="error"></div>

<script>
window.addEventListener("load", showState);
var ajax = new Ajax();
var deck = new Deck();

function saveState(st) {
	if (st.info) {
		var n=0;
		if (st.info.west) ++n;
		if (st.info.north) ++n;
		if (st.info.east) ++n;
		if (st.info.south) ++n;
		st.info.players=n;
	}
	saveLocal('bridge.state',st);
}
function readState() {
	var st = readLocal('bridge.state');
	if (!st) st = {user:'', table:'',phase:''};
	if (!st.user) st.user='';
	if (!st.table) st.table='';
	if (!st.phase) st.phase='';
	if (!st.info) st.info={};
	return st;
}
function showState() {
	var st = readState();
	st.info='';
	log('showState: '+st.phase);
	if (isEmpty(st.phase)) {
		showJoin(st);
	}
	else if (st.phase == 'joined') {
		getInfo(st);
		showTable(st);
	}
	else {
		showJoin(st);
	}
}
function onJoinReady(rc,tx) {
	$('status').innerHTML = '';
	if (rc!=200) {
		$('status').innerHTML = 'error '+rc;
		return ;
	}
	var st = readState();
	var o = JSON.parse(tx);
	st.error=o['error'];
	st.info=o['info'];
	if (st.info) st.phase='joined';
	else st.phase='join';
	saveState(st);
	if (!st.info) showJoin(st);
	else showTable(st);
}
function onExitReady(rc,tx) {
	$('status').innerHTML = '';
	if (rc!=200) {
		$('status').innerHTML = 'error '+rc;
		return ;
	}
	var st = readState();
	var o = JSON.parse(tx);
	st.error=o['error'];
	st.info='';
	st.phase='';
	saveState(st);
	showJoin(st);
}
function onGetInfoReady(rc,tx) {
	$('status').innerHTML = '';
	if (rc!=200) {
		$('status').innerHTML = 'error '+rc;
		return ;
	}
	var st = readState();
	var o = JSON.parse(tx);
	st.error=o['error'];
	st.info=o['info'];
	saveState(st);
	if (!st.info) showJoin(st);
	else showTable(st);
}
function showTable(st) {
	$('status').innerHTML = st.user+', you are at table ['+st.table+']';
	var s='<div>';
	s += '<input type="button" value="exit" onclick="exitTable()">'
	if (st.info.players < 4)
		s += ' <span>waiting for players';
	else if (!st.info.cards)
		s += ' <input type="button" value="deal" onclick="dealCards()">';
	s += '</div>';
	s += '<table class="brt">';
	s += '<tr><td></td><td>'+st.info.north+'(N)</td><td></td></tr>';
	s += '<tr><td>'+st.info.west+'(W)</td><td class="brt"></td><td>(E)'+st.info.east+'</td></tr>';
	s += '<tr><td></td><td>'+st.info.south+'(S)</td><td></td></tr>';
	s += '</table>';
	$('content').innerHTML=s;
	if (st.error) {
		$('error').innerHTML=st.error;
	}
}
function getInfo(st) {
	var u='u='+st.user+'&t='+st.table;
	ajax.async('get','<%val("cfg.rooturl")%>api/getinfo?'+u,onGetInfoReady);
}
function joinTable() {
	var st = readState();
	if (!isEmpty($('user').value)) st.user = $('user').value;
	if (!isEmpty($('table').value)) st.table = $('table').value;
	saveState(st);

	$('status').innerHTML = 'connecting <img src="<%val("cfg.rooturl")%>icony/loading_small.gif"><br>';
	var u='u='+st.user+'&t='+st.table;
	ajax.async('get','<%val("cfg.rooturl")%>api/join?'+u,onJoinReady);
}
function exitTable() {
	var st = readState();
	var u='u='+st.user+'&t='+st.table;
    ajax.async('get','<%val("cfg.rooturl")%>api/exit?'+u,onExitReady);
}
function dealCards() {
	log('dealCards');
	var st = readState();
	var u='u='+st.user+'&t='+st.table;
    ajax.async('get','<%val("cfg.rooturl")%>api/deal?'+u,onGetInfoReady);
}

function showJoin(st) {
	log('showJoin');
	var s='Join table to play<br>';
	s += 'Your name: ';
	s += '<input id="user" type="text" value="'+st.user+'" size="10" placeholder="your name"><br>';
	s += 'Table name: ';
	s += '<input id="table" type="text" value="'+st.table+'" size="10" placeholder="table name"> ';
	s += '<input type="button" value="join" onclick="joinTable()"><br>';
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
