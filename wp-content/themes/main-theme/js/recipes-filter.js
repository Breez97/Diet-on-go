jQuery(document).ready(function($) {
    function filterRecipes() {
		const searchTerm = $('#recipe-search').val().toLowerCase();
		const categoryDayFilter = $('#recipe-category-day-filter').val();
		const categoryFilter = $('#recipe-category-filter').val();
		const cookingTimeFilter = $('#recipe-cooking-time-filter').val();	
		$.ajax({
			url: recipeFilterParams.ajaxurl,
			type: 'POST',
			data: {
				action: 'filter_recipes',
				search_term: searchTerm,
				category_day_filter: categoryDayFilter,
				category_filter: categoryFilter,
				cooking_time_filter: cookingTimeFilter
			},
			success: function(response) {
				console.log('AJAX Success Response:', response);
				$('#recipes-grid').html(response);
			}
		});
	}
    $('#recipe-search, #recipe-category-day-filter, #recipe-category-filter, #recipe-cooking-time-filter').on('input change', filterRecipes);
});