
function getCookie(name, defaultVal) {
  var start = ("; " + document.cookie).indexOf("; " + name + "=");
  var len = start+name.length+1;
  if(!defaultVal)
  	defaultVal = null;
  if ((!start) && (name != document.cookie.substring(0,name.length))) return defaultVal;
  if (start == -1) return defaultVal;
  var end = document.cookie.indexOf(";",len);
  if (end == -1) end = document.cookie.length;
  return unescape(document.cookie.substring(len,end));
}

function setCookie(name, value, expires, path, domain, secure) {
	if(!expires)
		expires = expires_date;
	if(!path)
		path = "/";
  c = name + "=" +escape(value) +
      ( (expires) ? ";expires=" + expires.toGMTString() : "") +
      ( (path) ? ";path=" + path : "") +
      ( (domain) ? ";domain=" + domain : "") +
      ( (secure) ? ";secure" : "");
  document.cookie = c;
}

function deleteCookie(name,path,domain) {
  if (getCookie(name)) document.cookie = name + "=" +
     ( (path) ? ";path=" + path : "") +
     ( (domain) ? ";domain=" + domain : "") +
     ";expires=Thu, 01-Jan-70 00:00:01 GMT";
}

var today = new Date();
var expires_date = new Date(today.getTime() + (8 * 7 * 86400000));

function storeMasterCookie() {
	if (!setCookie('MasterCookie'))
  	setCookie('MasterCookie','MasterCookie');
}

function storeIntelligentCookie(name,value) {
  if (getCookie('MasterCookie')) {
      var IntelligentCookie = getCookie(name);
      if ((!IntelligentCookie) || (IntelligentCookie != value)) {
          setCookie(name,value,expires_date);
          var IntelligentCookie = getCookie(name);
          if ((!IntelligentCookie) || (IntelligentCookie != value))
              Delete_Cookie('MasterCookie');
      }
  }
}