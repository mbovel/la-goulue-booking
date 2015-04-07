function DragSelect(/*Element|String*/ el, /*String*/ selector, /*Object*/ options) {
	/********/
	/* Vars */
	/********/
	
	// Element: the container element
	var el = typeof el === 'string' ? document.getElementById(el) : el;
	
	// HTMLCollection: the selectable items
	var items = selector.charAt(0) === '.' ? el.getElementsByClassName(selector.substr(1)) : el.getElementsByTagName(selector);
	
	// Array: array of selected items
	var selectedItems = [];
	
	// int: Index of item where "mousedown" occured
	var baseItemIndex = -1;
	
	// int: Index of item where last "mouseover" occured
	var lastItemIndex = -1;
	
	// int: Index of item before the first selectable item in current range
	var lowerBound = -1;
	
	// int: Index of item after the last selectable item in current range
	var upperBound = Infinity;
	
	// Object: Options
	var options = options || {};
	
	
	/**********/
	/* Events */
	/**********/
	
	el.addEventListener('mousedown', mousedownHandler);
	el.addEventListener('touchstart', mousedownHandler);
	
	el.addEventListener('mousemove', mouseoverHandler);
	el.addEventListener('touchmove', function(/*Event*/ e) {
		mouseoverHandler({
			target: document.elementFromPoint(e.touches[0].screenX, e.touches[0].screenY)
		});
	});
	
	document.addEventListener('mouseup', mouseupHandler);
	document.addEventListener('touchend', mouseupHandler);
	
	
	/********************/
	/* Helper functions */
	/********************/
	
	function mousedownHandler(/*Event*/ e) {
		baseItemIndex = lastItemIndex = indexOf(e.target);
		
		if(baseItemIndex !== -1) {
			if(options.singleSelection === true) {
				clearSelection();
			}
			
			if(options.singleRange === true) {
				for(var i = baseItemIndex; i >= 0; --i) {
					if(items[i].classList.contains('disabled')) {
						lowerBound = i;
						break;
					}
				}
				
				for(var i = baseItemIndex; i < items.length; ++i) {
					if(items[i].classList.contains('disabled')) {
						upperBound = i;
						break;
					}
				}
			}
			
			toggleItemSelection(baseItemIndex);
			
			if(e.preventDefault !== undefined) {
				e.preventDefault();
			}
		}
	}
	
	function mouseoverHandler(/*Event*/ e) {
		if(baseItemIndex === -1) {
			return;
		}
		
		if(e.preventDefault !== undefined) {
			e.preventDefault();
		}
		
		var currentItemIndex = indexOf(e.target);
		
		if(currentItemIndex === -1 || currentItemIndex === lastItemIndex) {
			return;
		}
		
		var diff = currentItemIndex - lastItemIndex;
		
		if(diff < 0 && currentItemIndex < baseItemIndex && baseItemIndex < lastItemIndex) {
			for(i = lastItemIndex; i >= currentItemIndex; --i) {
				toggleItemSelection(i);
			}
			toggleItemSelection(baseItemIndex);
		}
		else if(diff < 0 && currentItemIndex < baseItemIndex) {
			for(i = lastItemIndex - 1; i >= currentItemIndex; --i) {
				toggleItemSelection(i)
			}
		}
		else if(diff < 0) {
			for(i = lastItemIndex; i > currentItemIndex; --i) {
				toggleItemSelection(i);
			}
		}
		else if(currentItemIndex <= baseItemIndex) {
			for(i = lastItemIndex; i < currentItemIndex; ++i) {
				toggleItemSelection(i);
			}
		}
		else if(baseItemIndex >= lastItemIndex) {
			for(i = lastItemIndex; i <= currentItemIndex; ++i) {
				toggleItemSelection(i);
			}
			toggleItemSelection(baseItemIndex);
		}
		else {
			for(i = lastItemIndex + 1; i <= currentItemIndex; ++i) {
				toggleItemSelection(i);
			}
		}
		
		lastItemIndex = currentItemIndex;
	}
	
	function mouseupHandler(/*Event*/ e) {
		baseItemIndex = lastItemIndex = lowerBound = -1;
		upperBound = Infinity;
	}
	
	function /*int*/ indexOf(/*Element*/ item) {
		for(var i = 0; i !== items.length; ++i) {
			if(items[i] === item) {
				return i;
			}
		}
		
		return -1;
	}
	function /*void*/ toggleItemSelection(/*int*/ index) {
		if(items[index].classList.contains('disabled')) {
			return;
		}
		
		if(index <= lowerBound || index >= upperBound) {
			return;
		}
		
		var selectedIndex = selectedItems.indexOf(items[index]);
		
		if(selectedIndex === -1) {
			selectedItems.push(items[index]);
			items[index].classList.add('selected');
		}
		else {
			selectedItems.splice(selectedIndex, 1);
			items[index].classList.remove('selected');
		}
	}
	
	function /*void*/ clearSelection() {
		selectedItems.forEach(function(i) {
			i.classList.remove('selected');
		});
		
		selectedItems = [];
	}
	
	function /*void*/ getSelection() {
		return selectedItems.slice();
	}
	
	return {
		clearSelection: clearSelection,
		getSelection: getSelection
	};
}