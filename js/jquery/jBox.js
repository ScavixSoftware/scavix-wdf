/*
 * jBox 1.0 - a webpage UI dialog widget written in javascript on top of the jQuery library
 * By Daniel Lin(http://www.aspstat.com)
 * Copyright (c) 2007 Daniel Lin Licensed under the MIT License: http://www.opensource.org/licenses/mit-license.php
 * Last modify:2007-11-20
*/
//Usage:jBox.open( boxID , contentType , content , title , windowStyles , ajaxOptions)

var jBox={
    //imgs:['/Lib/jbox_min.gif', '/Lib/jbox_close.gif', '/Lib/jbox_restore.gif','/Lib/jbox_resize.gif'],
    minClass:'jbtnMin',
    closeClass:'jbtnClose',
    restoreClass:'jbtnRestore',
    resizeClass:'jbtnResize',
	boxes: [],minimizeorder: 0,
	findBoxIndex:function(id){
		for( var i = 0 ; i< jBox.boxes.length;i++){
			if( jBox.boxes[i].jBoxID == id ){
				return i;
			}
		}
		return null;
	},
	findBox:function(id){
		for( var i = 0 ; i< jBox.boxes.length;i++){
			if( jBox.boxes[i].jBoxID == id ){
				return jBox.boxes[i];
			}
		}
		return null;
	},
	_maxZIndex:999,
	ie:document.all && !window.opera,
	domBody:(document.compatMode=="CSS1Compat")? document.documentElement : document.body,
	//scroll offset
	pageXYOffset : function(){
	    return [ (jBox.ie)?jBox.domBody.scrollLeft:window.pageXOffset,
	        (jBox.ie)?jBox.domBody.scrollTop:window.pageYOffset ];
    },
    //visiable zone
    pageInnerSize: function(){
        var domclientWidth=document.documentElement && parseInt(document.documentElement.clientWidth) || 100000;
        return [ (jBox.ie)?jBox.domBody.clientWidth:(/Safari/i.test(navigator.userAgent))? window.innerWidth: Math.min(domclientWidth, window.innerWidth-16),
           (jBox.ie)? jBox.domBody.clientHeight: window.innerHeight];
    },
    //max scroll width or max scrol offset+visiable zone
    pageSize:function(){
        var scrollSize;if( jBox.ie ){
           scrollSize  = [(document.body.scrollWidth > document.body.offsetWidth)?document.body.scrollWidth:document.body.offsetWidth,
			   (document.body.scrollHeight > document.body.offsetHeight)?document.body.scrollHeight:document.body.offsetHeight];
        }else{
           scrollSize = [document.body.scrollWidth,window.innerHeight + window.scrollMaxY];
        }
        return scrollSize;
    },
	init:function(id){
		var holder =$('#jBoxHolder');
	    if (holder.length == 0){holder = $('<div id="jBoxHolder"></div>');$(document.body).append( holder );}
		var box = $('<div class="jBox" id="'+id+'"></div' ).appendTo(holder);
		box.append('<div class="jBoxHandler">jBox<div class="jBoxControls"><div class="'+jBox.minClass+'" title="Minimize"></div><div class="'+jBox.closeClass+'" title="Close"></div></div></div>')
           .append('<div class="jBoxContent"></div>')
		   .append('<div class="jBoxButtons"></div>')
		   .append('<div class="jBoxStatus"><div class="'+jBox.resizeClass+'">&nbsp;</div></div');
		box.css( 'zIndex' , $('div.jBox').length+99);
		//bind custom property with DOM
		box.handler = box.find('div.jBoxHandler');box.controls = box.find( 'div.jBoxControls');
		box.content = box.find('div.jBoxContent');box.status = box.find( 'div.jBoxStatus' );
		box.buttons = box.find( 'div.jBoxButtons' );box.resize = box.find( 'div.'+jBox.resizeClass );
	    //bind custom method & event with DOM
	    box.open=function(type, source, title){jBox.open(this, type, source, title);};
		box.load = function(type,source,title){ jBox.load( this, type , source , title);};
		box.close=function(){if( undefined !=this.onClosed ){this.onClosed();} return true;};
		box.show=function(){ jBox.show(this);};
	    box.hide=function(){ jBox.hide(this);};
		box.isResize=function(bol){jBox._isResize(this, bol);};
	    box.hasScroll=function(bol){jBox._hasScroll(this, bol);};
		box.hasButtons = function(n){jBox._hasButtons(this,n);}
	    box.setSize=function(w, h){jBox.setSize(this, w, h);};
	    box.moveTo=function(x, y){jBox.moveTo(this, x, y);};
		box.jBoxID = id;
		jBox._maxZIndex++;
        box.css( 'z-index' , jBox._maxZIndex);
		box.bringUp = function(){
			if( box.css('z-index') < jBox._maxZIndex ){
			jBox._maxZIndex++;
			box.css( 'z-index' , jBox._maxZIndex);}
		};
		box.click( function(){ jBox.findBox(this.id).bringUp(); } );
	    this.boxes[this.boxes.length]=box;
	    return box;
	},
	open:function( id, type, source, title,attr){
		function getValue(Name){
			var config=new RegExp(Name+"=([^,]+)", "i");
			return (config.test(attr))? eval( '('+RegExp.$1+')') : 0;
		}
		var box = jBox.findBox( id );
		//Find a jbox element has been created else create new element
		if ( null== box ){
			box=this.init(id);
			box.css("visibility" ,"visible");box.css("display" , "block");box.content.css("display","block");

			box.hasScroll(getValue("scrolling"));
			box.hasButtons(getValue("buttons"));
			box.isModel = getValue("model");

			if ( box.isModel ) jBox.showModel();


			box.minimizable=getValue("minimizable");
			if( !box.minimizable  ){ box.controls.find('div[@title=Minimize]').remove()};


			if ( getValue("draggable") ){box.handler.mousedown(function(e){jBox.etarget=this;jBox.setupDrag(e);});}
			box.isResize(getValue("resize"));
			if ( box.resizeBool ){box.resize.mousedown(function(e){jBox.etarget=this;jBox.setupDrag(e);});}

			box.controls.click( function(e){jBox.setupControls(e);} );

			box.load(type, source, title);
			box.setSize(getValue(("width")), (getValue("height")));
			var xpos=getValue("center")? "middle" : getValue("left");
			var ypos=getValue("center")? "middle" : getValue("top");
			box.moveTo(xpos, ypos);
		}else{
            if( box.state == 'minimized' ) jBox.restore(box.controls.find('div[@title=Restore]').get(0),box);
		    box.bringUp();
		}
		return box;
    },
	load:function(box,type,source,title){
	    var type=type.toLowerCase();
  	    if (typeof title!='undefined') box.handler.get(0).firstChild.nodeValue=title;
        if (title==''){
			box.handler.css( 'backgroundColor' ,'#fff' );box.handler.css('padding' ,'0px');box.content.css('padding','0px 10px');
        }
	    if (type=="inline"){
		    box.content.html(source);
	    }
	    else if (type=="div"){
			var copy=$('#'+source).clone();copy.attr('id' , box.jBoxID + copy.attr('id') );
			copy.appendTo( box.content );
			copy.css('display','block');
	    }else if (type=="iframe"){
		    box.content.css('overflow','hidden');
		    if ( box.content.children().length<1 || box.content.children().get(0).tagName!="IFRAME")
			    box.content.html('<iframe src="" style="margin:0; padding:0; width:100%; height: 100%" name="jb_iframe-'+box.jBoxID+'"></iframe>');
		    window.frames["jb_iframe-"+box.jBoxID].location.replace(source);
		}else if ( type == "ajax" ){
		    box.content.html( 'loading...' );
		    $.ajax( { type: "GET", url:source ,
		          success:function(data){
		             box.content.html( data );
		          },
		          error:function(r){
		             box.content.html( r.responseText );
		          }
		    });
		}
	    box.content.datatype=type;
	},
    close:function(box){
	   try{var closewinbol=box.close();}
	   catch(err){var closewinbol=true;}
	   finally{
		 if (typeof closewinbol=="undefined"){
			alert("An error has occured somwhere inside your \"onclose\" event handler")
			var closewinbol=true
		 }
	   }
	   if (closewinbol){
		 var index = jBox.findBoxIndex( box.jBoxID );
		 delete jBox.boxes[index];
		 for( var i = index ; i<jBox.boxes.length; i++){
			 if( (i+1)<jBox.boxes.length ){
				 jBox.boxes[i] = jBox.boxes[i+1];
			 }
		 }
		 jBox.boxes.length--;
		 box.remove();
	     if ( box.isModel ) jBox.hideModel();
		 delete box.handler;delete box.content;delete box.buttons;
		 delete box.resize;delete box.controls;delete box.status;

		 if (box.state == 'minimized'){
		      jBox.minimizeorder--;
		      for ( var i= 0; i<jBox.boxes.length; i++)
		      {
				  if( jBox.boxes[i].state == 'minimized' ){
					  if( jBox.boxes[i].minimizeorder>box.minimizeorder){
					  jBox.boxes[i].minimizeorder--;
					  jBox._minimizeRedraw( jBox.boxes[i] );
					  }
				  }
		      }
	     }
	   }
	   return closewinbol;
    },
    show:function(box){
		box.css('display','block');
		if ( box.isModel ) jBox.showModel();
	},
	hide:function(box){
	    box.css('display','none');
	    if ( box.isModel ) jBox.hideModel();
	},
    _hasScroll:function(box, bol){box.content.css("overflow",(bol)? "auto" : "hidden");},
	_isResize:function(box, bol){box.status.css("display",(bol)? "block" : "none");box.resizeBool=(bol)? 1 : 0},
    _hasButtons:function(box,n){
      box.buttons.css("display", (n>0)? "block" : "none");
      if( n>0 ){
		 if( (n&1)>0 ){
			 var btnOK = $('<input value=" 确认 " type="button"/>');
			 btnOK.appendTo( box.buttons);
			 btnOK.click( function(){
			   if( undefined!= box.onOkClick )
				   box.onOkClick(box);
			   else
				   jBox.close(box);

			 });
		 }

	     if( (n&2)>0 ){
			 var btnCancel = $('&nbsp;&nbsp;<input value=" 取消 " type="button"/>');
			 btnCancel.appendTo( box.buttons);
			 btnCancel.click( function(){
			   if( undefined!= box.onCancelClick )
				   box.onCancelClick(box);
			   else
				   jBox.close(box);
			 });
		 }
	  }
	  box.buttonsBool = n>0?1:0;
	},
	setSize:function(box, w, h){
        w =parseInt(w); h =parseInt(h);
		if( w<= 0 )w = 320;
        if( h<=0 ) h = 100;
		box.css("width",w+'px');
		box.content.css("height",h+'px');},
	moveTo:function(box, x, y){
	    var pageInnerSize = jBox.pageInnerSize();
	    var pageOffset = jBox.pageXYOffset();

	    var boxpos = [(x=="middle")? pageOffset[0]+(pageInnerSize[0]-box.get(0).offsetWidth)/2 : pageOffset[0]+parseInt(x),
           (y=="middle")? pageOffset[1]+(pageInnerSize[1]-box.get(0).offsetHeight)/2: pageOffset[1]+parseInt(y)];

        box.css("left",boxpos[0]+"px");box.css("top", boxpos[1]+"px");
	},
	minimize:function(btn,box){
       jBox.saveViewState(box);box.state = 'minimized';
       $(btn).removeClass().addClass( jBox.restoreClass ).attr('title','Restore');
	   box.content.css('display','none');box.buttons.css('display','none');box.status.css('display','none');
	   if( typeof box.minimizeorder == 'undefined' ){
		   jBox.minimizeorder++;box.minimizeorder = jBox.minimizeorder;
	   }
	   jBox._minimizeRedraw( box );
	},
	_minimizeRedraw:function(box){
	   box.css('left','10px');box.css('width','200px');
       var margin = box.minimizeorder*10;
       var a = jBox.pageXYOffset();var b = jBox.pageInnerSize();
	   box.css('top',a[1]+b[1]-(box.handler.get(0).offsetHeight*box.minimizeorder)-margin+'px');
	},
	restore:function(btn,box){
		if( box.state == 'minimized'){
			jBox.minimizeorder--;
			for ( var i= 0; i<jBox.boxes.length; i++)
		    {
			    if( jBox.boxes[i].state == 'minimized' ){
					  if( jBox.boxes[i].minimizeorder>box.minimizeorder){
					  jBox.boxes[i].minimizeorder--;
					  jBox._minimizeRedraw( jBox.boxes[i] );
					}
				}
		    }
			box.minimizeorder = undefined;
		};
		var pageOffset = jBox.pageXYOffset();
		box.state='restore';
		$(btn).removeClass().addClass( jBox.minClass ).attr('title','Minimize');
		box.content.css('display','block');
		if( box.buttonsBool ) box.buttons.css('display','block');
		if( box.resizeBool ) box.status.css('display','block');
		box.css( 'left',parseInt(box.lastPos[0])+pageOffset[0]+'px');
		box.css( 'top',parseInt(box.lastPos[1])+pageOffset[1]+'px');
		box.css( 'width',parseInt(box.lastSize[0])+'px');
	},
	showModel:function(){
      var model =$('#jBox_hideIframe');
	  if ( model.length==0 )
	  {
	     model = $('<iframe id="jBox_hideIframe" scrolling="no" frameborder="0" style="position:absolute; top:0px; left:0px;-moz-opacity:0.7; opacity:0.7;filter:alpha(opacity=70);" ></iframe>');
		 model.css( "background","#000");
		 model.appendTo( document.body );
		 $(window).bind( 'resize', function(){jBox.showModel();})
	  }
	  var ps= jBox.pageSize();
	  model.css('width' , ps[0]+'px');
	  model.css('height', ps[1]+'px');
	},
    hideModel:function(){
	    $(window).unbind( 'resize')
	    var model =$('#jBox_hideIframe');
	    if ( model.length>0 )
    	   model.remove();
    },
    setupControls:function(e){
	    var sourceobj=window.event? window.event.srcElement : e.target;
		var box = jBox._retBox(sourceobj);
	    if (/Minimize/i.test(sourceobj.getAttribute("title")))
		   jBox.minimize(sourceobj, box);
	    else if (/Restore/i.test(sourceobj.getAttribute("title")))
		   jBox.restore(sourceobj, box);
	    else if (/Close/i.test(sourceobj.getAttribute("title")))
		   jBox.close(box);

	    return false;
	},
	reDraw:function(box, e){
	      box.css('width', Math.max(jBox.width+jBox.distancex, 150)+"px");
	      box.content.css('height',Math.max(jBox.contentheight+jBox.distancey, 100)+"px");
    },
    setupDrag:function(e){
	    var boxE=jBox.etarget;
		var box = jBox._retBox(boxE);
	    var e=window.event || e;
	    jBox.initmousex=e.clientX;
	    jBox.initmousey=e.clientY;
	    jBox.initx=parseInt(box.get(0).offsetLeft);
	    jBox.inity=parseInt(box.get(0).offsetTop);
	    jBox.width=parseInt(box.get(0).offsetWidth);
	    jBox.contentheight=parseInt(box.content.get(0).offsetHeight);
	    if (box.content.datatype=="iframe"){
		   box.css('backgroundColor','#F8F8F8');
		   box.content.css('visibility','hidden');
	    }
	    document.onmousemove=jBox.getDistance;
	    document.onmouseup=function(){
		  if (box.content.datatype=="iframe"){
			 box.content.css('visibility','visible');
		  }
		  jBox.stopDrag();
	    }
	    return false;
	},
	getDistance:function(e){
	    var etarget=jBox.etarget;
	    var e=window.event || e;
	    jBox.distancex=e.clientX-jBox.initmousex;
	    jBox.distancey=e.clientY-jBox.initmousey;
		var box = jBox._retBox(etarget);
	    if (etarget.className=='jBoxHandler')
		{
			box.css('left',jBox.distancex+jBox.initx+'px')
   	        box.css('top',jBox.distancey+jBox.inity+'px')
		}
	    else if (etarget.className==jBox.resizeClass)
		   jBox.reDraw(box, e);
	    return false;
	},
	stopDrag:function(){
		jBox.etarget=null;
	    document.onmousemove=null;
	    document.onmouseup=null;
	},
	saveViewState:function(box){
		var pageOffset = jBox.pageXYOffset();
		box.lastPos=[ parseInt((box.css('left')||box.get(0).offsetLeft))-pageOffset[0],
			parseInt((box.css('top')||box.get(0).offsetTop))-pageOffset[1] ];
		box.lastSize = [parseInt (box.css('width')),
			parseInt( box.css('height'))];
	},
    _retBox:function(dom){
		return jBox.findBox(  $(dom).parents('div.jBox').get(0).id );
	}
}
jBox.alert = function( msg , w ,h ){
	if( undefined==w)w=0;if( undefined==h)h=0;
	return jBox.open( 'jBoxAlert' , 'inline' , msg , 'Information' , 'center=1,buttons=1,draggable=1,model=1,minimizable=0,width='+w+',height='+h, null);
}
jBox._tipTimeout = function(){
    var box = jBox.findBox( 'jBoxTip' );
    if( null!=box ){
    if(  undefined==box.isMouseOver || null==box.isMouseOver || !box.isMouseOver ) jBox.close( box );
    else setTimeout( jBox._tipTimeout , 3000 );}
}
jBox.tip = function( e ,msg , w , h ){
    var box = jBox.findBox( 'jBoxTip' );
    if( null == box ){
    var e = e||window.event;
    if( undefined==w)w=150;if(  undefined==h)h=20;
    var pageInner = jBox.pageInnerSize();
    var pageOffset = jBox.pageXYOffset();
    var mousePos=[e.clientX,e.clientY];
    box = jBox.open( 'jBoxTip' , 'inline' , msg , '' , 'top='+mousePos[1]+',left='+mousePos[0]+',minimizable=0,width='+w+',height='+h, null);
    box.content.mouseover( function(){var box = jBox.findBox( 'jBoxTip' );
      box.isMouseOver = true;} );
    box.content.mouseout(function(){ var box = jBox.findBox( 'jBoxTip' );
      box.isMouseOver = false;} );
    setTimeout( jBox._tipTimeout , 2000 );
    }
    return box;
}

