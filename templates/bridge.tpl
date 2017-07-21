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
<table class="pack"><tr><td>
<div id="status"></div>
<div id="error"></div>
</td><td>
<div id="loading" class="loading"><img src="<%val("cfg.rooturl")%>icony/loading_small.gif"></div>
</td></tr></table><br>
<div class="pack" id="content"></div>

<script>
window.addEventListener("load", showState);
var ajax = new Ajax();
window.rooturl='<%val("cfg.rooturl")%>';

for (var dd of $('.ddlist')) {dd.addEventListener("focusout",()=>setTimeout(function() {
	for (obj of dd.childNodes) { //children=elements, childNodes=any node
		if (d.nodeName.toLowerCase() != 'div') continue;
		obj.style.display='none';
	}
},500));}

function calcState(st) {
	if (st.info) {
		var n=0;
		if (!isEmpty(st.info.west.name)) ++n;
		if (!isEmpty(st.info.north.name)) ++n;
		if (!isEmpty(st.info.east.name)) ++n;
		if (!isEmpty(st.info.south.name)) ++n;
		st.players=n;
		n=0;
		if (st.info.west.hand) n+=st.info.west.hand.length;
		if (st.info.north.hand) n+=st.info.north.hand.length;
		if (st.info.east.hand) n+=st.info.east.hand.length;
		if (st.info.south.hand) n+=st.info.south.hand.length;
		st.cards=n;
	}
	else st.phase='';
}
function saveState(st) {
	calcState(st);
	var e=st.error;
	st.error='';
	saveLocal('bridge.state',st);
	st.error=e;
}
function readState() {
	var st = readLocal('bridge.state');
	if (!st) st = {user:'', table:'',phase:''};
	if (!st.user) st.user='';
	if (!st.table) st.table='';
	if (!st.phase) st.phase='';
	st.error='';
	calcState(st);
	return st;
}
function showState() {
	var st = readState();
	log('saved state = '+st.phase);
	if (isEmpty(st.phase)) {
		showJoin(st);
	}
	else {
		getInfo(st);
	}
}
function onExitReady(rc,tx) {
	if (rc!=200) {
		$('error').innerHTML = 'error '+rc;
		return ;
	}
	var st = readState();
	try {
		var o = JSON.parse(tx);
		st.error=o['error'];
		st.info=null;
		st.phase='';
	}catch(e) {
		st.error=e.toString()+'<br>'+tx;
	}
	if (st.error) $('error').innerHTML=st.error;
	else $('error').innerHTML='';
	saveState(st);
	showJoin(st);
}
function onGetInfoReady(rc,tx,tag) {
	if (rc!=200) {
		$('error').innerHTML = 'error '+rc;
		return ;
	}
	var st = readState();
	try {
		var o=JSON.parse(tx);
		if (o['error']) {
			st.error=o['error'];
			log("st.error="+st.error);
		}
		else st.error='';
		if (o['state']) {
			st.info=o['state'];
			st.phase=st.info.phase;
		}
		calcState(st);
	}
	catch(e) {
		log(e);
		st.error=e.toString()+'<br>'+tx;
	}
	saveState(st);
	if (st.error) $('error').innerHTML=st.error;
	else $('error').innerHTML='';
	if (!st.phase) showJoin(st);
	else showTable(st);

	if (tag=='gi') setTimeout(showState,3000);
}
function makePlayer(st,pd) {
	var p = new Player();
	p.name=pd.name;
	p.phase=st.phase;
	p.face=pd.face;
	p.tricks=pd.tricks;
	p.user = (pd.name == st.user) ? 1 : 0;
	p.current = (pd.name == st.info.player) ? 1 : 0;
	p.contractor = (pd.name == st.info.contractor) ? 1 : 0;
	for (var fc of pd.hand) {
		var fig = fc.substring(0,fc.length-1);
		var suit = fc.substring(fc.length-1,fc.length);
		if (suit=='s') p.addSpade(fig);
		else if (suit=='h') p.addHeart(fig);
		else if (suit=='d') p.addDiamond(fig);
		else p.addClub(fig);
	}
	p.sort();
	return p;
}
function faceView(f) {
	if (!f) return '';
	var s='<div class="face">';
	if (f=='P') s+='pass';
	else if (f=='D') s+='double';
	else if (f=='R') s+='redouble';
	else {
		var fig = f.substring(0,f.length-1);
		var suit = f.substring(f.length-1,f.length);
		s+=Bridge.card(fig,suit);
	}
	s += '</div>';
	return s;
}
function boardView(st) {
	var s='<table class="board">';
	s += '<tr><td colspan="3" class="north">N '+faceView(st.north.face)+'</td></tr>';
	s += '<tr><td class="west">W'+faceView(st.west.face)+'</td><td></td><td class="east">E'+faceView(st.east.face)+'</td></tr>';
	s += '<tr><td colspan="3" class="south">S '+faceView(st.south.face)+'</td></tr>';
	return s+'</table>';
}
function ddlist(obj) {
	for (var d=obj.nextSibling; d; d=d.nextSibling) {
		if (d.nodeName.toLowerCase() != 'div') continue;
		if (d.style.display == 'inline') d.style.display = 'none';
		else {
			d.style.display = 'inline';
		}
	}
}
function showTable(st) {
	$('status').innerHTML = 'Hi '+st.user+', you are at table `'+st.table+'`'+' ['+st.phase+']';

	var s='<div>';

	if (st.phase=='auction') {
		s += '<div class="ddlist">'
		s += '<input type="button" value="bid" onclick="ddlist(this)"><br>';
		s += '<div class="bids">';
		s += Bridge.bids();
		s += '</div></div>';
	}
	else if (st.phase=='deal') {
        s += ' <input type="button" value="deal" onclick="dealCards()">';
    }
	if (st.players > 0)
		s += '<input type="button" value="AI-" onclick="removeAI()">'
	if (st.players < 4)
		s += '<input type="button" value="AI+" onclick="joinAI()">'

	if (st.phase!='deal')
		s += '<input type="button" value="reset" onclick="resetTable()">'
	s += '<input type="button" value="exit" onclick="exitTable()">'

	if (st.phase=='wait') {
		s += ' <span>waiting for players</span>';
	}
	s += '</div>';

	var plr = [ makePlayer(st,st.info.west), makePlayer(st,st.info.north), makePlayer(st,st.info.east), makePlayer(st,st.info.south)];
	for (var i=0; i < plr.length; ++i) {
		plr[i].partner = plr[(i+2)%plr.length];
		plr[i].r = plr[(i+3)%plr.length];
	}

	s += '<table class="table">';
	s += '<tr><td class="top left">';
	if (st.phase=='game') {
		s += '<b>Contract:</b> '+Bridge.cardx(st.info.contract)+'<br>';
		s += '<b>For:</b> '+st.info.contractor+'<br>';
		s += '<b>Playing:</b> '+st.info.player+'<br>';
	}
	else if (st.phase=='gameovr') {
		s += '<b>Contract:</b> '+Bridge.cardx(st.info.contract)+'<br>';
		s += '<b>For:</b> '+st.info.contractor+'<br>';
		var tricks=0;
		for (var i=0; i < plr.length; ++i) {
			if (plr[i].contractor) tricks=plr[i].tricks+plr[i].partner.tricks;
		}
		tricks=Bridge.isWinner(st.info.contract, tricks);
		if (tricks == 0) s += '<b>You WIN!</b><br>';
		else if (tricks > 0) s += '<b>You WIN! (+'+tricks+')</b><br>';
		else s += '<b>You LOST! (-'+tricks+')</b><br>';
	}
	s += '</td><td>'+plr[1].view()+'</td><td></td></tr>';
	s += '<tr><td>'+plr[0].view()+'</td>';
	s += '<td class="board">'+boardView(st.info)+'</td>';
	s += '<td class>'+plr[2].view()+'</td></tr>';
	s += '<tr><td></td><td>'+plr[3].view()+'</td><td></td></tr>';
	s += '</table>';
	$('content').innerHTML=s;
}
function putCard(u,c) {
	var st = readState();
	var u='u='+u+'&t='+st.table+'&c='+c;
	ajax.async('get','<%val("cfg.rooturl")%>api/put?'+u,onGetInfoReady);
}
function getInfo(st) {
	var u='u='+st.user+'&t='+st.table;
	ajax.async('get','<%val("cfg.rooturl")%>api/getinfo?'+u,onGetInfoReady,'gi');
}
function setBid(obj) {
	var bid=obj.getAttribute('bid');
	var st = readState();
	//log(st);
	var user = st.info.player ? st.info.player : s.user;
	var u='u='+user+'&t='+st.table+'&bid='+bid;
	ajax.async('get','<%val("cfg.rooturl")%>api/setbid?'+u,onGetInfoReady);
}
function joinTable() {
	var st = readState();
	if (!isEmpty($('user').value)) st.user = $('user').value;
	if (!isEmpty($('table').value)) st.table = $('table').value;
	saveState(st);

	var u='u='+st.user+'&t='+st.table;
	ajax.async('get','<%val("cfg.rooturl")%>api/join?'+u,onGetInfoReady);
}
function exitTable() {
	var st = readState();
	var u='u='+st.user+'&t='+st.table;
    ajax.async('get','<%val("cfg.rooturl")%>api/exit?'+u,onExitReady);
}
function resetTable() {
	var st = readState();
	var u='u='+st.user+'&t='+st.table;
    ajax.async('get','<%val("cfg.rooturl")%>api/reset?'+u,onGetInfoReady);
}
function joinAI() {
	var st = readState();
	var u='u='+st.user+'&t='+st.table;
    ajax.async('get','<%val("cfg.rooturl")%>api/joinai?'+u,onGetInfoReady);
}
function removeAI() {
	var st = readState();
	var u='u='+st.user+'&t='+st.table;
    ajax.async('get','<%val("cfg.rooturl")%>api/removeai?'+u,onGetInfoReady);
}
function dealCards() {
	log('dealCards');
	var st = readState();
	var u='u='+st.user+'&t='+st.table;
    ajax.async('get','<%val("cfg.rooturl")%>api/deal?'+u,onGetInfoReady);
}

function showJoin(st) {
	log('showJoin');
	var s='<table><tr><th colspan="2">Join table to play</td></tr>';
	s += '<tr><td>Your name: </td>';
	s += '<td><input id="user" type="text" value="'+st.user+'" size="10" placeholder="your name"></td></tr>';
	s += '<tr><td>Table name: </td>';
	s += '<td><input id="table" type="text" value="'+st.table+'" size="10" placeholder="table name"> </td>';
	s += '<td><input type="button" value="join" onclick="joinTable()"></tr></table>';
	$('content').innerHTML=s;

	if (st.error) $('error').innerHTML=st.error;
	else $('error').innerHTML='';
}
</script>
</body></html>
