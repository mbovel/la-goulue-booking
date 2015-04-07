;(function($) {

	var yesSelector = {
		// Last item were "mousedown" has occured
		'baseItemID' : false,
		
		// Last item were "mouseenter" has occured
		'lastItemID' : false,
		
		// Group of current "baseItem"
		'currentGroupID' : false,
		
		// Used to give each group of selectable items a unique id,
		// Iterated every time a new group is initialized.
		'maxGroupID' : 1,
		
		// Defaults settings
		'defaults' : {
			'itemsSelector' : 'td',
			'independent' : false
		},
		
		'mousedown' : function(e) {
			$this = $(this);
			
			id = $this.attr('data-yes-id').split('-', 2);
			yesSelector.currentGroupID = parseFloat(id[0]);
			yesSelector.baseItemID =  yesSelector.lastItemID = parseFloat(id[1]);
			
			$this.addClass('selected');
			$('[data-yes-id="' + yesSelector.currentGroupID + '"] .selected').not($this).removeClass('selected');
			
			e.preventDefault();
		},
		
		'mouseenter' : function() {
			if(yesSelector.baseItemID !== false) {
				$this = $(this);
				
				id = $this.attr('data-yes-id').split('-', 2);
				
				if( parseFloat(id[0]) !== yesSelector.currentGroupID )
					return false;
				
				currentItemID = parseFloat(id[1]);
				
				diff = currentItemID - yesSelector.lastItemID;
				
				var i = 0;
				if(diff < 0 && currentItemID < yesSelector.baseItemID  && yesSelector.baseItemID < yesSelector.lastItemID) {
					for(i = yesSelector.lastItemID; i >= currentItemID; i--)
						$('[data-yes-id="' + yesSelector.currentGroupID + '-' + i + '"]').toggleClass('selected');
					$('[data-yes-id="' + yesSelector.currentGroupID + '-' + yesSelector.baseItemID + '"]').toggleClass('selected');
				}
				else if(diff < 0 && currentItemID < yesSelector.baseItemID) {
					for(i = yesSelector.lastItemID - 1; i >= currentItemID; i--)
						$('[data-yes-id="' + yesSelector.currentGroupID + '-' + i + '"]').toggleClass('selected');
				}
				else if (diff < 0) {
					for(i = yesSelector.lastItemID; i > currentItemID; i--)
						$('[data-yes-id="' + yesSelector.currentGroupID + '-' + i + '"]').toggleClass('selected');
				}
				else if (currentItemID <= yesSelector.baseItemID) {
					for(i = yesSelector.lastItemID; i < currentItemID; i++)
						$('[data-yes-id="' + yesSelector.currentGroupID + '-' + i + '"]').toggleClass('selected');
				}
				else if (yesSelector.baseItemID >= yesSelector.lastItemID) {
					for(i = yesSelector.lastItemID; i <= currentItemID; i++)
						$('[data-yes-id="' + yesSelector.currentGroupID + '-' + i + '"]').toggleClass('selected');
					$('[data-yes-id="' + yesSelector.currentGroupID + '-' + yesSelector.baseItemID + '"]').toggleClass('selected');
				}
				else {
					for(i = yesSelector.lastItemID + 1; i <= currentItemID; i++)
						$('[data-yes-id="' + yesSelector.currentGroupID + '-' + i + '"]').toggleClass('selected');
				}
				
				yesSelector.lastItemID = currentItemID;
			}
		},
		
		'init' : function( groupElement, itemsSelector ) {
			items = $(itemsSelector, groupElement);
			
			groupElement.attr('data-yes-id', yesSelector.maxGroupID);
			items.attr('data-yes-id', function(index) { return yesSelector.maxGroupID + '-' + index });
			
			items.mousedown(yesSelector.mousedown).mouseenter(yesSelector.mouseenter);
			
			yesSelector.maxGroupID++;
		}
	};

	$.fn.yesSelector = function(options) {
		var settings = $.extend(yesSelector.defaults, options);
		
		if( settings.independent ) {
			this.filter(function() {
				yesSelector.init( $(this), settings.itemsSelector );
			});
		}
		else {
			yesSelector.init( this, settings.itemsSelector );
		}
		
		return this;
	};
	
	jQuery(document).mouseup(function() {
		yesSelector.currentGroupID = yesSelector.baseItemID = yesSelector.lastItemID = false;
	});


})(jQuery);