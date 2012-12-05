/**
 * jQuery.serialScroll
 * Copyright (c) 2007-2008 Ariel Flesler - aflesler(at)gmail(dot)com | http://flesler.blogspot.com
 * Dual licensed under MIT and GPL.
 * Date: 8/3/2008
 *
 * @projectDescription Animated scrolling of series.
 * @author Ariel Flesler
 * @version 1.1.2
 *
 * @id jQuery.serialScroll
 * @id jQuery.fn.serialScroll
 * @param {Object} settings Hash of settings, it is passed in to jQuery.ScrollTo, none is required.
 * @return {jQuery} Returns the same jQuery object, for chaining.
 *
 * http://flesler.blogspot.com/2008/02/jqueryserialscroll.html
 *
 * Notes:
 *	- The plugin requires jQuery.ScrollTo.
 *	- The hash of settings, is passed to jQuery.ScrollTo, so its settings can be used as well.
 */
;(function( $ ){

	var $serialScroll = $.serialScroll = function( settings ){
		$.scrollTo.window().serialScroll( settings );
	};

	//Many of these defaults, belong to jQuery.ScrollTo, check it's demo for an example of each option.
	//@see http://www.flesler.webs/jQuery.ScrollTo/
	$serialScroll.defaults = {//the defaults are public and can be overriden.
		duration:1000, //how long to animate.
		axis:'x', //which of top and left should be scrolled
		event:'click', //on which event to react.
		start:0, //first element (zero-based index)
		step:1, //how many elements to scroll on each action
		lock:true,//ignore events if already animating
		cycle:true //cycle endlessly ( constant velocity )
		/*
		interval:0, //it's the number of milliseconds to automatically go to the next
		lazy:false,//go find the elements each time (allows AJAX or JS content, or reordering)
		stop:false, //stop any previous animations to avoid queueing
		force:false,//force the scroll to the first element on start ?
		jump: false,//if true, when the event is triggered on an element, the pane scrolls to it
		items:null, //selector to the items (relative to the matched elements)
		prev:null, //selector to the 'prev' button
		next:null, //selector to the 'next' button
		onBefore: //function called before scrolling, if it returns false, the event is ignored
		*/		
	};

	$.fn.serialScroll = function( settings ){
		settings = $.extend( {}, $serialScroll.defaults, settings );
		var event = settings.event, //this one is just to get shorter code when compressed
			step = settings.step, // idem
			duration = settings.duration / step; //save it, we'll need it

		return this.each(function(){
			var 
				$pane = $(this),
				items = settings.lazy ? settings.items : $( settings.items, $pane ),
				actual = settings.start,
				timer; //for the interval

			if( settings.force )
				jump.call( this, {}, actual );//generate an initial call

			// Button binding, optional
			$(settings.prev||[]).bind( event, -step, move );
			$(settings.next||[]).bind( event, step, move );
			
			// Custom events bound to the container
			$pane.bind('prev.serialScroll', -step, move ) //you can trigger with just 'prev'
				 .bind('next.serialScroll', step, move ) //for example: $(container).trigger('next');
				 .bind('goto.serialScroll', jump ) //for example: $(container).trigger('goto', [4] );
				 .bind('start.serialScroll', function(e, i){
				 	if( !settings.interval ){
				 		settings.interval = i || 1000;
				 		clear();
				 		next();
				 	}
				 })
				.bind('stop.serialScroll', function(){
				 	clear();
				 	settings.interval = false;				 	
			 	});

			if( !settings.lazy && settings.jump )//can't use jump if using lazy items
				items.bind( event, function( e ){
					e.data = items.index(this);
					jump( e, this );
				});

			function move( e ){
				e.data += actual;
				jump( e, this );
			};			
			function jump( e, button ){
				if( typeof button == 'number' ){//initial or special call from the outside $(container).trigger('goto',[index]);
					e.data = button;
					button = this;
				}

				var 
					pos = e.data, elem,
					real = e.type, //is a real event triggering ?
					$items = $(items,$pane),
					limit = $items.length;

				if( real )//real event object
					e.preventDefault();

				pos %= limit; //keep it under the limit
				if( pos < 0 )
					pos += limit;

				elem = $items[pos];

				if( settings.interval ){
					clear();//clear any possible automatic scrolling.
					timer = setTimeout( next, settings.interval ); //I'll use the namespace to avoid conflicts
				}

				if( isNaN(pos) || real && actual == pos || //could happen, save some CPU cycles in vain
					settings.lock && $pane.is(':animated') || //no animations while busy
					!settings.cycle && !$items[e.data] || //no cycling
					real && settings.onBefore && //callback returns false ?
				 	settings.onBefore.call(button, e, elem, $pane, $items, pos) === false ) return;

				if( settings.stop )
					$pane.queue('fx',[]).stop();//remove all its animations

				settings.duration = Math.abs(duration * (actual - pos ));//keep constant velocity

				$pane.scrollTo( elem, settings );
				actual = pos;
			};
			function next(){
				$pane.trigger('next.serialScroll');
			};
			function clear(){
				clearTimeout(timer);	
			};
		});
	};

})( jQuery );