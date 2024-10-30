(function($){
	// Sortable rows
	$(".wp-menubar-widgets").sortable({
		items: "> tbody > tr",
		placeholder: "ui-sortable-placeholder",
		cursor: "move",
		opacity: 0.8,
		revert: 150,
		update: function(e, ui){
			var checkbox = $(ui.item[0]).find(".check-column > input");
			!checkbox.attr("checked") && checkbox.attr("checked", "checked" );
		}
	});
	
	
})(jQuery);
	