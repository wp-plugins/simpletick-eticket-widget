(function($) {
	$(function() {
				var url = DomainAutocomplete.url
						+ "?action=simpletix_domain_search";
				$("#domain").autocomplete({
							source : url,
							delay : 100,
							minLength : 5
						});
			});

})(jQuery);