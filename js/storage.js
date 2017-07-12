function storageAvailable(type) {
    try {
        var storage = window[type], x = '__storage_test__';
        storage.setItem(x, x);
        storage.removeItem(x);
        return true;
    }
    catch(e) {
        return e instanceof DOMException && (
            // everything except Firefox
            e.code === 22 ||
            // Firefox
            e.code === 1014 ||
            // test name field too, because code might not be present
            // everything except Firefox
            e.name === 'QuotaExceededError' ||
            // Firefox
            e.name === 'NS_ERROR_DOM_QUOTA_REACHED') &&
            // acknowledge QuotaExceededError only if there's something already stored
            storage.length !== 0;
    }
}

var storegeExpireDuration=24*60*60; //in seconds
function saveLocal(key,val) {
	var expireon = Math.floor(Date.now()/1000)+storegeExpireDuration;
	var obj = val;
	if (obj instanceof String) obj={'expireOn':expireon, 'v':val};
	else {
		obj['expireOn']=expireon;
	}
	//console.log('savekey '+key);
	//console.log(obj);
	window.localStorage.setItem(key,JSON.stringify(obj));
}

function removeLocal(key) {
	window.localStorage.removeItem(key);
}

function readLocal(key) {
	var tx = window.localStorage.getItem(key);
	if (!tx) {
		//console.log('no key '+key);
		return null;
	}
	var expireon = Math.floor(Date.now/1000);
	var obj = JSON.parse(tx);
	//console.log('readkey '+key);
	//console.log(obj);
	if (!obj['expireOn'] || obj['expireOn'] < expireon) {
		window.localStorage.removeItem(key);
		obj=null;
	}
	return obj;
}
