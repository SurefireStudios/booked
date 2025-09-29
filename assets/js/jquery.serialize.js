// Serialize Function
(function($) {
	
	var originalSerializeArray = $.fn.serializeArray;
	$.fn.extend({
	    serializeArray: function () {
	        var brokenSerialization = originalSerializeArray.apply(this);
	        var checkboxValues = $(this).find('input[type=checkbox]').map(function () {
	            return { 'name': this.name, 'value': this.checked };
	        }).get();
	        var checkboxKeys = $.map(checkboxValues, function (element) { return element.name; });
	        var withoutCheckboxes = $.grep(brokenSerialization, function (element) {
	            return $.inArray(element.name, checkboxKeys) == -1;
	        });
	
	        return $.merge(withoutCheckboxes, checkboxValues);
	    }
	});
	
	$.fn.serializeObject = function()
	{
	    var o = {};
	    var a = this.serializeArray();
	    $.each(a, function() {
	        if (o[this.name] !== undefined) {
	            if (!o[this.name].push) {
	                o[this.name] = [o[this.name]];
	            }
	            o[this.name].push(this.value || '');
	        } else {
	            o[this.name] = this.value || '';
	        }
	    });
	    return o;
	};
	 
})(jQuery);