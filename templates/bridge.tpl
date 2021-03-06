<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">
<head>
  <meta http-equiv="Content-type" content="text/html;charset=utf-8" />
  <title><%val("cfg.sitetitle")%></title>
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
var stopRefresh=false;

function calcState(st) {
	var n=0;
	st.userpos=0;
	if (!isEmpty(st.west.name)) {
		++n;
		if (st.west.name == st.user) st.userpos=1;
	}
	if (!isEmpty(st.north.name)) {
		++n;
		if (st.west.name == st.user) st.userpos=2;
	}
	if (!isEmpty(st.east.name)) {
		++n;
		if (st.west.name == st.user) st.userpos=3;
	}
	if (!isEmpty(st.south.name)) {
		++n;
		if (st.west.name == st.user) st.userpos=4;
	}
	st.players=n;
	n=0;
	if (st.west.hand) n+=st.west.hand.length;
	if (st.north.hand) n+=st.north.hand.length;
	if (st.east.hand) n+=st.east.hand.length;
	if (st.south.hand) n+=st.south.hand.length;
	st.cards=n;
}
function saveState(st) {
	calcState(st);
	var e=st.error;
	st.error='';
	saveLocal('bridge.state',st);
	st.error=e;
}
function emptyState() {
	return {user:'',table:'',phase:'',north:'',south:'',west:'',east:''};
}
function readState() {
	var st = readLocal('bridge.state');
	if (!st) st = emptyState();
	if (!st.north || !st.south || !st.west || !st.east)
		st = emptyState();
	st.error='';
	calcState(st);
	return st;
}
function showState() {
	//if (stopRefresh) return ;
	var st = readState();
	log('saved state = '+st.phase);
	if (isEmpty(st.phase)) {
		stopRefresh=false;
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
		st = JSON.parse(tx);
		st.phase='';
	}catch(e) {
		st.error=e.toString()+'<br>'+tx;
	}
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
		if (o) {
			st=o;
			calcState(st);
		}
	}
	catch(e) {
		log(e.stack);
		log(tx);
		st.error='info:'+e.toString()+'<br>'+tx;
	}
	saveState(st);
	if (!st.phase) showJoin(st);
	else showTable(st);
}
function makePlayer(st,pd) {
	var p = new Player(pd.hand);
	p.name=pd.name;
	p.table=st.table;
	p.phase=st.phase;
	p.face=pd.face;
	p.tricks=pd.tricks;
	p.user = (pd.name == st.user) ? 1 : 0;
	p.current = (pd.name == st.player) ? 1 : 0;
	p.contractor = (pd.name == st.contractor) ? 1 : 0;
	return p;
}
function faceView(st,f) {
	if (!f) return '';
	var s='<div class="face ';
	if (st.phase=='auction') s+='bubble';
	s+='">';
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
	s += '<tr><td colspan="3" class="north">N '+faceView(st,st.north.face)+'</td></tr>';
	s += '<tr><td class="west">W'+faceView(st,st.west.face)+'</td><td></td>';
	s += '<td class="east">E'+faceView(st,st.east.face)+'</td></tr>';
	s += '<tr><td colspan="3" class="south">S '+faceView(st,st.south.face)+'</td></tr>';
	return s+'</table>';
}
function setBid(obj) {
	var bid=obj.getAttribute('bid');
	var st = readState();
	var user = st.player ? st.player : st.user;
	var u='u='+user+'&t='+st.table+'&bid='+bid;
	ajax.async('get','<%val("cfg.rooturl")%>api/setbid?'+u,onGetInfoReady);
	stopRefresh=false;
}
function ddlist(obj) {
	obj.addEventListener("focusout",()=>setTimeout(function() {
		for (var d=obj.nextSibling; d; d=d.nextSibling) {
			if (d.nodeName.toLowerCase() != 'div') continue;
			d.style.display='none';
		}
		stopRefresh=false;
	},550));

	stopRefresh=true;
	var hidden=false;
	for (var d=obj.nextSibling; d; d=d.nextSibling) {
		if (d.nodeName.toLowerCase() != 'div') continue;
		if (d.style.display == 'inline') {
			d.style.display = 'none';
			hidden=true;
		}
		else {
			d.style.display = 'inline';
		}
	}
	if (hidden) stopRefresh=false;
}
function showTable(st) {
	if (stopRefresh) return ;
	$('status').innerHTML = 'Hi '+st.user+', you are at table `'+st.table+'`'+' ['+st.phase+']';

	var s='<div>';

	if (st.userpos > 0) {
	if (st.phase=='deal') {
        s += ' <input type="button" value="deal" onclick="dealCards()">';
    }
	if (st.phase=='wait' || st.phase=='deal') {
		if (st.players < 4) s += '<input type="button" value="AI+" onclick="joinAI()">'
		if (st.players > 0) s += '<input type="button" value="AI-" onclick="removeAI()">'
	}

	if (st.phase!='wait' && st.phase!='deal')
		s += '<input type="button" value="reset" onclick="resetTable()">'
	}
	else s+= 'All places are occupied, you can observe<br>';
	s += '<input type="button" value="exit" onclick="exitTable()">'

	if (st.phase=='wait') {
		s += ' <span>waiting for players</span>';
	}
	if (st.phase=='auction' || st.phase=='game') {
		s += '<input type="button" value="auto" onclick="autoplay()">'
	}
	s += '</div>';

	var plr = [ makePlayer(st,st.west), makePlayer(st,st.north), makePlayer(st,st.east), makePlayer(st,st.south)];
	for (var i=0; i < plr.length; ++i) {
		plr[i].partner = plr[(i+2)%plr.length];
		plr[i].r = plr[(i+3)%plr.length];
	}

	s += '<table class="table">';
	s += '<tr><td class="top left">';
	if (st.phase=='auction') {
		if (st.user == st.player) {
			s += '<div class="ddlist">'
			s += '<input type="button" value="bid" onclick="ddlist(this)"><br>';
			s += '<div class="bids">';
			s += Bridge.bids();
			s += '</div></div>';
		}
		else {
			s += '<input class="disabled" type="button" value="bid">'
		}
	}
	else if (st.phase=='game') {
		s += '<b>Contract:</b> '+Bridge.cardx(st.contract)+'<br>';
		s += '<b>For:</b> '+st.contractor+'<br>';
		s += '<b>Playing:</b> '+st.player+'<br>';
	}
	else if (st.phase=='gameovr') {
		s += '<b>Contract:</b> '+Bridge.cardx(st.contract)+'<br>';
		s += '<b>For:</b> '+st.contractor+'<br>';
		var tricks=0;
		for (var i=0; i < plr.length; ++i) {
			if (plr[i].contractor) tricks=plr[i].tricks+plr[i].partner.tricks;
		}
		tricks=Bridge.isWinner(st.contract, tricks);
		if (tricks<0) s += '<div class="looser">';
		else s += '<div class="winner">';
		if (tricks == 0) s += ' WIN!';
		else if (tricks > 0) s += ' WIN! (+'+tricks+')';
		else s += ' LOST! ('+tricks+')';
		s += '</div>';
	}
	s += '</td><td>'+plr[1].view()+'</td><td></td></tr>';
	s += '<tr><td>'+plr[0].view()+'</td>';
	s += '<td class="board">'+boardView(st)+'</td>';
	s += '<td class>'+plr[2].view()+'</td></tr>';
	s += '<tr><td></td><td>'+plr[3].view()+'</td><td></td></tr>';
	s += '</table>';
	$('content').innerHTML=s;

	if (st.error) $('error').innerHTML=st.error;
	else $('error').innerHTML='';
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
	var st = readState();
	var u='u='+st.user+'&t='+st.table;
	ajax.async('get','<%val("cfg.rooturl")%>api/deal?'+u,onGetInfoReady);
}

function showJoin(st) {
	$('status').innerHTML = 'Please join a table to play';
	var s='<table><tr><td>Your name: </td>';
	s += '<td><input id="user" type="text" value="'+st.user+'" size="10" placeholder="your name"></td></tr>';
	s += '<tr><td>Table name: </td>';
	s += '<td><input id="table" type="text" value="'+st.table+'" size="10" placeholder="table name"> </td>';
	s += '<td><input type="button" value="join" onclick="joinTable()"></tr></table>';
	$('content').innerHTML=s;
	if (st.error) $('error').innerHTML=st.error;
	else $('error').innerHTML='';
}
function autoplay() {
	var st = readState();
	var u='u='+st.user+'&t='+st.table;
	ajax.async('get','<%val("cfg.rooturl")%>api/autoplay?'+u,onGetInfoReady);
}
</script>
<div class="center"><div class="pack">
<!--
<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="5Z3P525EK5RNN">
<table>
<tr><td><input type="hidden" name="on0" value="Kwota">Kwota</td></tr><tr><td><select name="os0">
	<option value="Troszkę">Troszkę 5,00 PLN</option>
	<option value="Więcej">Więcej 10,00 PLN</option>
	<option value="Bardzo">Bardzo 50,00 PLN</option>
</select> </td></tr>
</table>
<input type="hidden" name="currency_code" value="PLN">
<input type="image" src="https://www.paypalobjects.com/pl_PL/PL/i/btn/btn_buynowCC_LG.gif" border="0" name="submit" alt="PayPal – Płać wygodnie i bezpiecznie">
<img alt="" border="0" src="https://www.paypalobjects.com/pl_PL/i/scr/pixel.gif" width="1" height="1">

<input type="submit" name="submit" value="Wesprzyj">
</form>
-->

<!-- This works ok -->
<!--
<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="NCS3TX8E872HJ">
<input type="image" src="https://www.paypalobjects.com/pl_PL/PL/i/btn/btn_buynowCC_LG.gif" border="0" name="submit" alt="PayPal – Płać wygodnie i bezpiecznie">
<img alt="" border="0" src="https://www.paypalobjects.com/pl_PL/i/scr/pixel.gif" width="1" height="1">
</form>

<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="NCS3TX8E872HJ">
<input type="image" src="https://www.paypalobjects.com/pl_PL/PL/i/btn/btn_paynow_SM.gif" border="0" name="submit" alt="PayPal – Płać wygodnie i bezpiecznie">
<img alt="" border="0" src="https://www.paypalobjects.com/pl_PL/i/scr/pixel.gif" width="1" height="1">
</form>
-->

</div></div>
</body></html>
