/**
 * Alexandr Dubenko
 * @param minHeight
 * @param maxHeight
 * @return {*}
 * @constructor
 */
$.fn.TextAreaExpander = function(minHeight, maxHeight) {

		var hCheck = !($.browser.msie);
		var padding = parseInt($(this).css("padding-top")) + parseInt($(this).css("padding-bottom"));

		// resize a textarea
		function ResizeTextarea(e) {

			// event or initialize element?
			e = e.target || e;

			// find content length and box width
			var vlen = e.value.length, ewidth = e.offsetWidth;
			if (vlen != e.valLength || ewidth != e.boxWidth) {

				if (hCheck && (vlen < e.valLength || ewidth != e.boxWidth)) e.style.height = "0px";
				var h = Math.max(e.expandMin, Math.min(e.scrollHeight, e.expandMax));

				e.style.overflow = (e.scrollHeight > h ? "auto" : "hidden");
				e.style.height = ($.browser.safari || $.browser.opera) ? h - padding + "px" : h + "px";

				e.valLength = vlen;
				e.boxWidth = ewidth;
			}

			return true;
		}

		// initialize
		this.each(function() {

			// is a textarea?
			if (this.nodeName.toLowerCase() != "textarea") return;

			// set height restrictions
			var p = this.className.match(/expand(\d+)\-*(\d+)*/i);
			this.expandMin = minHeight || (p ? parseInt('0'+p[1]) : 0);
			this.expandMax = maxHeight || (p ? parseInt('0'+p[2]) : 99999);

			// initial resize
			//ResizeTextarea(this);

			// zero vertical padding and add events
			if (!this.Initialized) {
				this.Initialized = true;
				$(this).bind("keyup", ResizeTextarea).bind("focus", ResizeTextarea);
			}
		});

		return this;
	};
