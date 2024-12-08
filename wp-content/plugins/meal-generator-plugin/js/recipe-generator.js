jQuery(document).ready(function ($) {
    $('.generate-recipe').on('click', function () {
        var button = $(this);
        var type = button.data('type');
        var category = $('#' + type + '-category').val();
        var cookingTime = $('#' + type + '-cooking-time').val();
        var calories = $('#' + type + '-calories').val();
        var data = {
            action: 'generate_recipe',
            category: category,
            type: type,
            cooking_time: cookingTime,
            calories: calories
        };
        $.post(recipeGenerator.ajaxUrl, data, function (response) {
            $('#' + type + '-result').html(response);
        });
    });
});
