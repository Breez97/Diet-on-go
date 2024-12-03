<?php

function all_recipes_from_db() {
    global $wpdb;
    $table_recipes = $wpdb->prefix . 'recipes';
    $table_recipe_info = $wpdb->prefix . 'recipe_info';
    $categories_day_query = "SELECT DISTINCT category_day FROM $table_recipes ORDER BY category_day";
    $categories_day = $wpdb->get_col($categories_day_query);
    $categories_query = "SELECT DISTINCT category FROM $table_recipes ORDER BY category";
    $categories = $wpdb->get_col($categories_query);
    $query = "
        SELECT r.id, r.title, r.short_description, r.image, r.category, r.category_day, r.cooking_time
        FROM $table_recipes r
        ORDER BY r.title
    ";
    $recipes = $wpdb->get_results($query);
    $output = '<div class="recipes-container">';
	$output .= '<div class="recipes-filter-section">';
	$output .= '<input type="text" id="recipe-search" placeholder="Поиск рецептов...">';
	$output .= '<div class="filter-row">';
	$output .= '<select id="recipe-category-day-filter">';
	$output .= '<option value="">Все дни</option>';
	foreach ($categories_day as $category_day) {
		$output .= '<option value="' . esc_attr($category_day) . '">' . esc_html($category_day) . '</option>';
	}
	$output .= '</select>';
	$output .= '<select id="recipe-category-filter">';
	$output .= '<option value="">Все категории</option>';
	foreach ($categories as $category) {
		$output .= '<option value="' . esc_attr($category) . '">' . esc_html($category) . '</option>';
	}
	$output .= '</select>';
	$output .= '<select id="recipe-cooking-time-filter">';
	$output .= '<option value="">Любое время готовки</option>';
	$output .= '<option value="0-15">До 15 минут</option>';
	$output .= '<option value="15-30">15-30 минут</option>';
	$output .= '<option value="30+">Более 30 минут</option>';
	$output .= '</select></div></div></div>';
    $output .= '<div id="recipes-grid" class="recipe-cards-container">';
    if ($recipes) {
        foreach ($recipes as $recipe) {
            $output .= '<div class="recipe-card" data-category="' . esc_attr($recipe->category_day) . '" data-title="' . esc_attr(strtolower($recipe->title)) . '">';
            $output .= '<a href="' . esc_url(home_url('/single-recipe/?recipe_id=' . $recipe->id)) . '">';
            if ($recipe->image) {
                $output .= '<img class="recipe-image" src="' . esc_url($recipe->image) . '" alt="' . esc_attr($recipe->title) . '">';
            }
            $output .= '<p class="recipe-title">' . esc_html($recipe->title) . '</p>';
            $output .= '<p class="recipe-description">' . esc_html($recipe->short_description) . '</p>';
            $output .= '<div class="recipe-meta">';
            $output .= '<p class="recipe-category">' . esc_html($recipe->category_day) . '</p>';
            $output .= '<p class="recipe-time">' . esc_html($recipe->cooking_time) . ' мин.</p>';
            $output .= '</div></a></div>';
        }
    } else {
        $output .= '<p>Нет рецептов для отображения</p>';
    }
    $output .= '</div></div>';
    wp_localize_script('recipes-filter', 'recipeFilterParams', array(
		'ajaxurl' => admin_url('admin-ajax.php')
	));
    return $output;
}
add_shortcode('all_recipes_from_db', 'all_recipes_from_db');

function filter_recipes() {
    $search_term = sanitize_text_field($_POST['search_term']);
    $category_day_filter = sanitize_text_field($_POST['category_day_filter']);
    $category_filter = sanitize_text_field($_POST['category_filter']);
    $cooking_time_filter = sanitize_text_field($_POST['cooking_time_filter']);
    global $wpdb;
    $table_recipes = $wpdb->prefix . 'recipes';
    $query_conditions = array();
    $query_params = array();
    if (!empty($search_term)) {
        $query_conditions[] = "(title LIKE %s OR short_description LIKE %s)";
        $search_param = '%' . $wpdb->esc_like($search_term) . '%';
        $query_params[] = $search_param;
        $query_params[] = $search_param;
    }
    if (!empty($category_day_filter)) {
        $query_conditions[] = "category_day = %s";
        $query_params[] = $category_day_filter;
    }
    if (!empty($category_filter)) {
        $query_conditions[] = "category = %s";
        $query_params[] = $category_filter;
    }
    if (!empty($cooking_time_filter)) {
        switch ($cooking_time_filter) {
            case '0-15':
                $query_conditions[] = "CAST(cooking_time AS UNSIGNED) <= 15";
                break;
            case '15-30':
                $query_conditions[] = "CAST(cooking_time AS UNSIGNED) BETWEEN 15 AND 30";
                break;
            case '30+':
                $query_conditions[] = "CAST(cooking_time AS UNSIGNED) > 30";
                break;
        }
    }
    $where_clause = !empty($query_conditions) ? 'WHERE ' . implode(' AND ', $query_conditions) : '';
    $query = $wpdb->prepare("
        SELECT id, title, short_description, image, category, category_day, cooking_time
        FROM $table_recipes
        $where_clause
        ORDER BY title
    ", $query_params);
    $recipes = $wpdb->get_results($query);
    $response = '';
    if ($recipes) {
        foreach ($recipes as $recipe) {
            $response .= '<div class="recipe-card" data-category="' . esc_attr($recipe->category_day) . '" data-recipe-type="' . esc_attr($recipe->category) . '" data-title="' . esc_attr(strtolower($recipe->title)) . '">';
            $response .= '<a href="' . esc_url(home_url('/single-recipe/?recipe_id=' . $recipe->id)) . '">';
            if ($recipe->image) {
                $response .= '<img class="recipe-image" src="' . esc_url($recipe->image) . '" alt="' . esc_attr($recipe->title) . '">';
            }
            $response .= '<p class="recipe-title">' . esc_html($recipe->title) . '</p>';
            $response .= '<p class="recipe-description">' . esc_html($recipe->short_description) . '</p>';
            $response .= '<div class="recipe-meta">';
            $response .= '<p class="recipe-category">' . esc_html($recipe->category_day) . '</p>';
            $response .= '<p class="recipe-type">Тип: ' . esc_html($recipe->category) . '</p>';
            $response .= '<p class="recipe-time">' . esc_html($recipe->cooking_time) . ' мин.</p>';
            $response .= '</div></a></div>';
        }
    } else {
        $response = '<p>Нет рецептов, соответствующих фильтру.</p>';
    }
    echo $response;
    wp_die();
}
add_action('wp_ajax_filter_recipes', 'filter_recipes');
add_action('wp_ajax_nopriv_filter_recipes', 'filter_recipes');

?>