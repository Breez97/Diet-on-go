<?php
/*
Plugin Name: Recipe Generator
Description: Плагин для генерации рецептов с фильтрацией по категориям
Version: 1.0
Author: Shamrov Ilya
*/

if (!defined('ABSPATH')) {
    exit;
}

class RecipeGenerator {

    public function __construct() {
        add_shortcode('generate_recipe', [$this, 'render_generated_recipe']);
		add_shortcode('recipe_collection', [$this, 'recipe_collection_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
		add_action('admin_menu', [$this, 'recipe_generator_add_admin_page']);
		register_activation_hook(__FILE__, [$this, 'recipe_generator_create_table']);
    }

	public function recipe_generator_create_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'recipe_collections';
		if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			$charset_collate = $wpdb->get_charset_collate();
			$sql = "CREATE TABLE $table_name (
				id BIGINT(20) NOT NULL AUTO_INCREMENT,
				collection_name VARCHAR(255) NOT NULL,
				breakfast_recipe_id INT(11) NOT NULL,
				lunch_recipe_id INT(11) NOT NULL,
				dinner_recipe_id INT(11) NOT NULL,
				PRIMARY KEY (id)
			) $charset_collate;";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}
	}

	public function recipe_generator_add_admin_page() {
		add_menu_page(
			'Recipe Generator Collections',
			'Подборки рецептов',
			'manage_options',
			'recipe-generator-collections',
			array($this, 'recipe_generator_admin_page'),
			'dashicons-list-view',
			20
		);
	}

	public function recipe_generator_admin_page() {
		global $wpdb;
		if (isset($_POST['save_collection'])) {
			$collection_name = sanitize_text_field($_POST['collection_name']);
			$breakfast_id = isset($_POST['breakfast_recipe']) ? absint($_POST['breakfast_recipe']) : null;
			$lunch_id = isset($_POST['lunch_recipe']) ? absint($_POST['lunch_recipe']) : null;
			$dinner_id = isset($_POST['dinner_recipe']) ? absint($_POST['dinner_recipe']) : null;
			$wpdb->insert(
				$wpdb->prefix . 'recipe_collections', [
					'collection_name' => $collection_name,
					'breakfast_recipe_id' => $breakfast_id,
					'lunch_recipe_id' => $lunch_id,
					'dinner_recipe_id' => $dinner_id
				]
			);
			echo '<div class="updated"><p>Подборка успешно сохранена</p></div>';
		}
		$breakfast_recipes = get_posts([
			'post_type' => 'recipe', 
			'posts_per_page' => -1, 
			'meta_query' => [
				[
					'key' => 'category_day',
					'value' => 'Завтрак',
					'compare' => '='
				]
			]
		]);
		$lunch_recipes = get_posts([
			'post_type' => 'recipe', 
			'posts_per_page' => -1, 
			'meta_query' => [
				[
					'key' => 'category_day',
					'value' => 'Обед',
					'compare' => '='
				]
			]
		]);
		$dinner_recipes = get_posts([
			'post_type' => 'recipe', 
			'posts_per_page' => -1, 
			'meta_query' => [
				[
					'key' => 'category_day',
					'value' => 'Ужин',
					'compare' => '='
				]
			]
		]);
		?>
		<div class="wrap">
			<h1>Создать подборку рецептов</h1>
			<form method="POST">
				<label for="collection_name">Название подборки:</label>
				<input type="text" name="collection_name" id="collection_name" required>
				<h2>Завтрак</h2>
				<select name="breakfast_recipe" id="breakfast_recipe">
					<option value="">Выберите рецепт</option>
					<?php foreach ($breakfast_recipes as $recipe): ?>
						<option value="<?php echo esc_attr($recipe->ID); ?>"><?php echo esc_html($recipe->post_title); ?></option>
					<?php endforeach; ?>
				</select>
				<h2>Обед</h2>
				<select name="lunch_recipe" id="lunch_recipe">
					<option value="">Выберите рецепт</option>
					<?php foreach ($lunch_recipes as $recipe): ?>
						<option value="<?php echo esc_attr($recipe->ID); ?>"><?php echo esc_html($recipe->post_title); ?></option>
					<?php endforeach; ?>
				</select>
				<h2>Ужин</h2>
				<select name="dinner_recipe" id="dinner_recipe">
					<option value="">Выберите рецепт</option>
					<?php foreach ($dinner_recipes as $recipe): ?>
						<option value="<?php echo esc_attr($recipe->ID); ?>"><?php echo esc_html($recipe->post_title); ?></option>
					<?php endforeach; ?>
				</select>
				<p><input type="submit" name="save_collection" value="Сохранить подборку" class="button button-primary"></p>
			</form>
		</div>
		<?php
	}

	public function recipe_collection_shortcode($atts) {
		global $wpdb;
		$atts = shortcode_atts(array(
			'collection_id' => 0,
		), $atts);
		$collection_id = absint($atts['collection_id']);
		if ($collection_id === 0) {
			return '<p>Неверный ID коллекции.</p>';
		}
		$collection = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}recipe_collections WHERE id = %d",
			$collection_id
		));
		if (!$collection) {
			return '<p>Коллекция не найдена.</p>';
		}
		$output = '<div class="recipe-collection">';
		$output .= '<h3 class="collection-title">' . esc_html($collection->collection_name) . '</h3>';
		$recipes_ids = array(
			'breakfast' => $collection->breakfast_recipe_id,
			'lunch' => $collection->lunch_recipe_id,
			'dinner' => $collection->dinner_recipe_id,
		);
		$output .= '<div class="recipe-cards-container">';
		foreach ($recipes_ids as $meal => $recipe_id) {
			if ($recipe_id) {
				$recipe = get_post($recipe_id);
				if ($recipe) {
					$title = get_the_title($recipe);
					$short_description = get_field('short_description', $recipe->ID);
					$image = get_field('image', $recipe->ID);
					$cooking_time = get_field('cooking_time', $recipe->ID);
					$category_day = get_field('category_day', $recipe->ID);
					$output .= '<div class="recipe-card">';
					$output .= '<a href="' . get_permalink($recipe) . '">';
					$output .= '<img class="recipe-image" src="' . esc_url($image) . '" alt="' . esc_attr($title) . '">';
					$output .= '<p class="recipe-title">' . esc_html($title) . '</p>';
					$output .= '<p class="recipe-description">' . esc_html($short_description) . '</p>';
					$output .= '<div class="recipe-meta">';
					$output .= '<p class="recipe-category">' . esc_html($category_day) . '</p>';
					$output .= '<p class="recipe-time">' . esc_html($cooking_time) . ' мин.</p>';
					$output .= '</div>';
					$output .= '</a>';
					$output .= '</div>';
				}
			}
		}
		$output .= '</div>';
		$output .= '</div>';
		return $output;
	}	

    public function render_generated_recipe() {
		$categories = [
			'Обычное' => 'Обычное',
			'Вегетарианское' => 'Вегетарианское', 
			'Веганское' => 'Веганское',
			'Безглютеновое' => 'Безглютеновое',
			'Кето' => 'Кето'
		];
		$meal_types = [
			'breakfast' => 'Завтрак',
			'lunch' => 'Обед',
			'dinner' => 'Ужин'
		];
		$cooking_times = [
			'all' => 'Любое время',
			'quick' => 'До 15 минут',
			'medium' => '15-30 минут',
			'long' => 'Более 30 минут'
		];
		$calories = [
			'all' => 'Любые калории',
			'low' => 'Менее 50 ккал',
			'medium' => '50-100 ккал',
			'high' => 'Более 100 ккал'
		];
		ob_start();
		?>
		<div class="recipe-filter-container">
			<div class="recipe-filter-row">
				<?php foreach ($meal_types as $type_key => $type_name): ?>
				<div class="recipe-filter-block">
					<h3><?php echo esc_html($type_name); ?></h3>
					<div class="recipe-filter">
						<label for="<?php echo esc_attr($type_key); ?>-category">Категория:</label>
						<select id="<?php echo esc_attr($type_key); ?>-category">
							<option value="all">Все</option>
							<?php foreach ($categories as $category_key => $category_name): ?>
								<option value="<?php echo esc_attr( $category_key ); ?>"><?php echo esc_html($category_name); ?></option>
							<?php endforeach; ?>
						</select>
						<label for="<?php echo esc_attr($type_key); ?>-cooking-time">Время готовки:</label>
						<select id="<?php echo esc_attr($type_key); ?>-cooking-time">
							<?php foreach ($cooking_times as $time_key => $time_name): ?>
								<option value="<?php echo esc_attr( $time_key ); ?>"><?php echo esc_html($time_name); ?></option>
							<?php endforeach; ?>
						</select>
						<label for="<?php echo esc_attr($type_key); ?>-calories">Калорийность:</label>
						<select id="<?php echo esc_attr($type_key); ?>-calories">
							<?php foreach ($calories as $calorie_key => $calorie_name): ?>
								<option value="<?php echo esc_attr($calorie_key); ?>"><?php echo esc_html($calorie_name); ?></option>
							<?php endforeach; ?>
						</select>
						<button class="generate-recipe" data-type="<?php echo esc_attr($type_key); ?>"><?php echo esc_html($type_name); ?></button>
					</div>
					<div id="<?php echo esc_attr($type_key); ?>-result"></div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
	
	public function generate_recipe() {
		$category = isset($_POST['category']) ? sanitize_text_field( $_POST['category'] ) : 'all';
		$type = isset($_POST['type']) ? sanitize_text_field( $_POST['type'] ) : '';
		$cooking_time_filter = isset($_POST['cooking_time']) ? sanitize_text_field( $_POST['cooking_time'] ) : 'all';
		$calories_filter = isset($_POST['calories']) ? sanitize_text_field( $_POST['calories'] ) : 'all';
		$meal_types = [
			'breakfast' => 'Завтрак',
			'lunch' => 'Обед',
			'dinner' => 'Ужин'
		];
		$type_name = $meal_types[$type] ?? '';
		$args = array(
			'post_type' => 'recipe',
			'posts_per_page' => 1,
			'orderby' => 'rand',
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key' => 'category_day',
					'value' => $type_name,
					'compare' => '='
				)
			)
		);
		if ($category !== 'all') {
			$args['meta_query'][] = array(
				'key' => 'recipe_category',
				'value' => $category,
				'compare' => '='
			);
		}
		if ($cooking_time_filter !== 'all') {
			$cooking_time_query = array(
				'relation' => 'OR'
			);
			switch ($cooking_time_filter) {
				case 'quick':
					$cooking_time_query[] = array(
						'key' => 'cooking_time',
						'value' => 15,
						'compare' => '<=',
						'type' => 'NUMERIC'
					);
					break;
				case 'medium':
					$cooking_time_query[] = array(
						'key' => 'cooking_time',
						'value' => array(15, 30),
						'compare' => 'BETWEEN',
						'type' => 'NUMERIC'
					);
					break;
				case 'long':
					$cooking_time_query[] = array(
						'key' => 'cooking_time',
						'value' => 30,
						'compare' => '>',
						'type' => 'NUMERIC'
					);
					break;
			}
			$args['meta_query'][] = $cooking_time_query;
		}
		if ($calories_filter !== 'all') {
			$calories_query = array(
				'relation' => 'OR'
			);
			switch ($calories_filter) {
				case 'low':
					$calories_query[] = array(
						'key' => 'calories_per_100g',
						'value' => 50,
						'compare' => '<=',
						'type' => 'NUMERIC'
					);
					break;
				case 'medium':
					$calories_query[] = array(
						'key' => 'calories_per_100g',
						'value' => array(50, 100),
						'compare' => 'BETWEEN',
						'type' => 'NUMERIC'
					);
					break;
				case 'high':
					$calories_query[] = array(
						'key' => 'calories_per_100g',
						'value' => 100,
						'compare' => '>',
						'type' => 'NUMERIC'
					);
					break;
			}
			$args['meta_query'][] = $calories_query;
		}
		$query = new WP_Query($args);
		if ($query->have_posts()) {
			$query->the_post();
			$image = get_field('image');
			$short_description = get_post_meta(get_the_ID(), 'short_description', true);
			$cooking_time = get_post_meta(get_the_ID(), 'cooking_time', true);
			$category_day = get_post_meta(get_the_ID(), 'category_day', true);
			$calories = get_post_meta(get_the_ID(), 'calories_per_100g', true);
			$output = '<div class="recipe-card">';
			$output .= '<a href="' . esc_url(get_permalink()) . '">';
			$output .= '<img class="recipe-image" src="' . esc_url($image) . '" alt="' . esc_attr(get_the_title()) . '">';
			$output .= '<p class="recipe-title">' . esc_html(get_the_title()) . '</p>';
			$output .= '<p class="recipe-description">' . esc_html($short_description) . '</p>';
			$output .= '<div class="recipe-meta">';
			$output .= '<p class="recipe-category">' . esc_html($category_day) . '</p>';
			$output .= '<p class="recipe-time">' . esc_html($cooking_time) . ' мин.</p>';
			$output .= '</div></a></div>';
			echo $output;
			wp_reset_postdata();
		} else {
			$random_args = array(
				'post_type' => 'recipe',
				'posts_per_page' => 1,
				'orderby' => 'rand',
				'meta_query' => array(
					array(
						'key' => 'category_day',
						'value' => $type_name,
						'compare' => '='
					)
				)
			);
			$random_query = new WP_Query($random_args);
			$random_query->the_post();
			$image = get_field('image');
			$short_description = get_post_meta(get_the_ID(), 'short_description', true);
			$cooking_time = get_post_meta(get_the_ID(), 'cooking_time', true);
			$category_day = get_post_meta(get_the_ID(), 'category_day', true);
			$calories = get_post_meta(get_the_ID(), 'calories_per_100g', true);
			$output = '<p class="not-found-message">Рецепт по указанным категориям не найден. Cлучайный рецепт:</p>';
			$output .= '<div class="recipe-card">';
			$output .= '<a href="' . esc_url(get_permalink()) . '">';
			$output .= '<img class="recipe-image" src="' . esc_url($image) . '" alt="' . esc_attr(get_the_title()) . '">';
			$output .= '<p class="recipe-title">' . esc_html(get_the_title()) . '</p>';
			$output .= '<p class="recipe-description">' . esc_html($short_description) . '</p>';
			$output .= '<div class="recipe-meta">';
			$output .= '<p class="recipe-category">' . esc_html($category_day) . '</p>';
			$output .= '<p class="recipe-time">' . esc_html($cooking_time) . ' мин.</p>';
			$output .= '</div></a></div>';
			echo $output;
			wp_reset_postdata();
		}
		wp_die();
	}
    
    public function enqueue_scripts() {
		wp_enqueue_script('jquery');
		wp_enqueue_script('recipe-generator-script', plugin_dir_url(__FILE__) . 'js/recipe-generator.js', array('jquery'), null, true);
		wp_localize_script('recipe-generator-script', 'recipeGenerator', array(
			'ajaxUrl' => admin_url('admin-ajax.php'),
		));
		wp_enqueue_style('recipe-generator-style', plugin_dir_url(__FILE__) . 'style.css');
	}
}

if (class_exists('RecipeGenerator')) {
    $obj = new RecipeGenerator();
}

add_action('wp_ajax_generate_recipe', array($obj, 'generate_recipe'));
add_action('wp_ajax_nopriv_generate_recipe', array($obj, 'generate_recipe'));
