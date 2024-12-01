<?php

/*
Plugin Name: Recipes Plugin
Description: Плагин для взаимодействия с базой данных рецептов
Version: 1.0
Author: Shamrov Ilya
*/

if (!defined('ABSPATH')) {
    exit; 
}

class RecipesPlugin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_items'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_init', array($this, 'create_tables'));
        add_action('admin_post_save_recipe', array($this, 'save_recipe'));
        add_action('admin_post_update_recipe', array($this, 'update_recipe'));
        add_action('admin_post_delete_recipe', array($this, 'delete_recipe'));
    }

    public function add_menu_items() {
        add_menu_page( 
            'Recipes', 
            'Рецепты', 
            'manage_options', 
            'recipes', 
            array($this, 'recipes_page'),
            'dashicons-carrot', 
            6 
        );

        add_submenu_page(
            'recipes', 
            'Добавить рецепт', 
            'Добавить рецепт', 
            'manage_options', 
            'add_recipe', 
            array($this, 'add_recipe_page')
        );
        
        add_submenu_page(
            null,
            'Редактировать рецепт',
            'Редактировать рецепт',
            'manage_options',
            'edit_recipe',
            array($this, 'edit_recipe_page')
        );
    }

    public function enqueue_scripts() {
		wp_enqueue_media();
        wp_enqueue_script('jquery', plugin_dir_url( __FILE__ ) . 'js/jquery.js', array(), null, true);
        wp_enqueue_script('recipe-upload-script', plugin_dir_url( __FILE__ ) . 'js/recipe-upload.js', array('jquery'), null, true );
        wp_enqueue_style('recipe-style', plugin_dir_url( __FILE__ ) . 'css/style.css');
    }

	public function create_tables() {
		global $wpdb;

		$table_recipes = $wpdb->prefix . "recipes";
		$charset_collate = $wpdb->get_charset_collate();
		if ($wpdb->get_var("SHOW TABLES LIKE '$table_recipes'") != $table_recipes) {
			$sql_recipes = "CREATE TABLE IF NOT EXISTS $table_recipes (
				id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				title VARCHAR(255),
				short_description TEXT,
				image VARCHAR(255),
				category VARCHAR(255),
				category_day VARCHAR(255),
				cooking_time INT
			) $charset_collate";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql_recipes);
		}

		$table_recipe_info = $wpdb->prefix . "recipe_info";
		if ($wpdb->get_var("SHOW TABLES LIKE '$table_recipe_info'") != $table_recipe_info) {
			$sql_recipe_info = "CREATE TABLE IF NOT EXISTS $table_recipe_info (
				id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				recipe_id INT NOT NULL,
                description TEXT,
				ingredients TEXT,
				instructions TEXT,
				calories INT,
				protein DECIMAL(5,2),
				carbs DECIMAL(5,2),
				fat DECIMAL(5,2),
				FOREIGN KEY (recipe_id) REFERENCES $table_recipes(id) ON DELETE CASCADE
			) $charset_collate";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql_recipe_info);
		}
	}

    public function recipes_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'recipes';
        $recipes = $wpdb->get_results("SELECT * FROM $table_name");
    
        echo '<div class="wrap">';
        echo '<h1>Все рецепты</h1>';
        echo '<table class="widefat fixed" cellspacing="0">';
        echo '<head><tr><th>Название</th><th>Описание</th><th>Категория</th><th>Категория дня</th><th>Изображение</th><th>Действия</th></tr></head>';
        foreach ($recipes as $recipe) {
            echo '<tr>';
            echo '<td>' . esc_html($recipe->title) . '</td>';
            echo '<td>' . esc_html($recipe->short_description) . '</td>';
            echo '<td>' . esc_html($recipe->category) . '</td>';
            echo '<td>' . esc_html($recipe->category_day) . '</td>';
            echo '<td>';
            if ($recipe->image) echo '<img src="' . esc_url($recipe->image) . '" width="100" />';
            echo '</td><td>';
            echo '<a href="' . admin_url('admin.php?page=edit_recipe&id=' . $recipe->id) . '" class="button">Редактировать</a>';
            echo ' <form method="post" action="' . admin_url('admin-post.php') . '" style="display:inline;">';
            echo '<input type="hidden" name="action" value="delete_recipe">';
            echo '<input type="hidden" name="recipe_id" value="' . $recipe->id . '">';
            echo '<input type="submit" value="Удалить" class="button button-delete" onclick="return confirm(\'Удалить этот рецепт?\');">';
            echo '</form></td></tr>';
        }
        echo '</table></div>';
    }

    public function add_recipe_page() {
        echo '<div class="wrap">';
        echo '<h1>Добавить новый рецепт</h1>';
        ?>
        <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_recipe" />
            <table class="form-table">
                <tr>
                    <th><label for="title">Название</label></th>
                    <td><input type="text" name="title" id="title" required /></td>
                </tr>
                <tr>
                    <th><label for="short_description">Краткое описание</label></th>
                    <td><textarea name="short_description" id="short_description" required></textarea></td>
                </tr>
                <tr>
                    <th><label for="category">Категория</label></th>
                    <td>
                        <select name="category" id="category" required>
                            <option value="Обычное">Обычное</option>
                            <option value="Вегетарианское">Вегетарианское</option>
                            <option value="Веганское">Веганское</option>
                            <option value="Безглютеновое">Безглютеновое</option>
                            <option value="Кето">Кето</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="category_day">Категория дня</label></th>
                    <td>
                        <select name="category_day" id="category_day" required>
                            <option value="Завтрак">Завтрак</option>
                            <option value="Обед">Обед</option>
                            <option value="Ужин">Ужин</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="cooking_time">Время готовки</label></th>
                    <td><input type="number" name="cooking_time" id="cooking_time" required /></td>
                </tr>
                <tr>
                    <th><label for="image">Изображение</label></th>
                    <td>
                        <input type="hidden" name="image" id="recipe_image" />
                        <input type="button" class="button" value="Выбрать изображение" id="upload_image_button" />
                        <div id="image_preview"></div>
                    </td>
                </tr>
                <tr>
                    <th><label for="description">Описание</label></th>
                    <td><textarea name="description" id="description" required></textarea></td>
                </tr>
				<tr>
                    <th><label for="ingredients">Ингредиенты</label></th>
                    <td><textarea name="ingredients" id="ingredients" required></textarea></td>
                </tr>
				<tr>
                    <th><label for="instructions">Инструкции</label></th>
                    <td><textarea name="instructions" id="instructions" required></textarea></td>
                </tr>
				<tr>
                    <th><label for="calories">Калории</label></th>
                    <td><input type="number" name="calories" id="calories" required /></td>
                </tr>
				<tr>
                    <th><label for="protein">Белки (г)</label></th>
                    <td><input type="number" step="0.01" name="protein" id="protein" required /></td>
                </tr>
				<tr>
                    <th><label for="carbs">Углеводы (г)</label></th>
                    <td><input type="number" step="0.01" name="carbs" id="carbs" required /></td>
                </tr>
				<tr>
                    <th><label for="fat">Жиры (г)</label></th>
                    <td><input type="number" step="0.01" name="fat" id="fat" required /></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Добавить рецепт" />
            </p>
        </form>
        </div>
        <?php
    }

    public function save_recipe() {
		global $wpdb;
		if (isset($_POST['title'], $_POST['short_description'], $_POST['category'], $_POST['category_day'], $_POST['cooking_time'], $_POST['description'], $_POST['ingredients'], $_POST['instructions'], $_POST['calories'], $_POST['protein'], $_POST['carbs'], $_POST['fat'])) {
			$table_name = $wpdb->prefix . 'recipes';
			$recipe_insert = $wpdb->insert($table_name, [
					'title' => sanitize_text_field($_POST['title']),
					'short_description' => sanitize_textarea_field($_POST['short_description']),
					'category' => sanitize_text_field($_POST['category']),
					'category_day' => sanitize_text_field($_POST['category_day']),
					'cooking_time' => sanitize_text_field($_POST['cooking_time']),
					'image' => esc_url_raw($_POST['image'])
				]);
			$recipe_id = $wpdb->insert_id;
			$recipe_info_insert = $wpdb->insert($wpdb->prefix . 'recipe_info', [
					'recipe_id' => $recipe_id,
                    'description' => sanitize_textarea_field($_POST['description']),
					'ingredients' => sanitize_textarea_field($_POST['ingredients']),
					'instructions' => sanitize_textarea_field($_POST['instructions']),
					'calories' => intval($_POST['calories']),
					'protein' => floatval($_POST['protein']),
					'carbs' => floatval($_POST['carbs']),
					'fat' => floatval($_POST['fat']),
				]);
			wp_redirect(admin_url('admin.php?page=recipes'));
		} else {
			wp_die('Не все поля заполнены');
		}
	}

    public function edit_recipe_page() {
        global $wpdb;
        $recipe_id = intval($_GET['id']);
        $recipe = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}recipes WHERE id = %d", $recipe_id));
        $recipe_info = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}recipe_info WHERE recipe_id = %d", $recipe_id));
        echo '<div class="wrap">';
        echo '<h1>Редактировать рецепт</h1>';
        ?>
        <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_recipe" />
            <input type="hidden" name="id" value="<?php echo esc_attr($recipe->id); ?>" />
            <table class="form-table">
                <tr>
                    <th><label for="title">Название</label></th>
                    <td><input type="text" name="title" id="title" value="<?php echo esc_attr($recipe->title); ?>" required></td>
                </tr>
                <tr>
                    <th><label for="short_description">Краткое описание</label></th>
                    <td><textarea name="short_description" id="short_description" required><?php echo esc_textarea($recipe->short_description); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="category">Категория</label></th>
                    <td>
                        <select name="category" id="category" required>
                            <option value="Обычное" <?php selected($recipe->category, 'Обычное'); ?>>Обычное</option>
                            <option value="Вегетарианское" <?php selected($recipe->category, 'Вегетарианское'); ?>>Вегетарианское</option>
                            <option value="Веганское" <?php selected($recipe->category, 'Веганское'); ?>>Веганское</option>
                            <option value="Безглютеновое" <?php selected($recipe->category, 'Безглютеновое'); ?>>Безглютеновое</option>
                            <option value="Кето" <?php selected($recipe->category, 'Кето'); ?>>Кето</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="category_day">Категория дня</label></th>
                    <td>
                        <select name="category_day" id="category_day" required>
                            <option value="Завтрак" <?php selected($recipe->category_day, 'завтрак'); ?>>Завтрак</option>
                            <option value="Обед" <?php selected($recipe->category_day, 'обед'); ?>>Обед</option>
                            <option value="Ужин" <?php selected($recipe->category_day, 'ужин'); ?>>Ужин</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="cooking_time">Время готовки</label></th>
                    <td><input type="number" name="cooking_time" id="cooking_time" value="<?php echo esc_attr($recipe->cooking_time); ?>" required /></td>
                </tr>
                <tr>
                    <th><label for="image">Изображение</label></th>
                    <td>
                        <input type="hidden" name="image" id="recipe_image" value="<?php echo esc_attr($recipe->image); ?>" />
                        <input type="button" class="button" value="Загрузить изображение" id="upload_image_button" />
                        <div id="image_preview">
                            <?php if ($recipe->image) : ?>
                                <img src="<?php echo esc_url($recipe->image); ?>" width="100" />
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th><label for="description">Описание</label></th>
                    <td><textarea name="description" id="description" required><?php echo esc_textarea($recipe_info->description); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="ingredients">Ингредиенты</label></th>
                    <td><textarea name="ingredients" id="ingredients" required><?php echo esc_textarea($recipe_info->ingredients); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="instructions">Инструкции</label></th>
                    <td><textarea name="instructions" id="instructions" required><?php echo esc_textarea($recipe_info->instructions); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="calories">Калории</label></th>
                    <td><input type="number" name="calories" id="calories" value="<?php echo esc_attr($recipe_info->calories); ?>" required /></td>
                </tr>
                <tr>
                    <th><label for="protein">Белки (г)</label></th>
                    <td><input type="number" step="0.01" name="protein" id="protein" value="<?php echo esc_attr($recipe_info->protein); ?>" required /></td>
                </tr>
                <tr>
                    <th><label for="carbs">Углеводы (г)</label></th>
                    <td><input type="number" step="0.01" name="carbs" id="carbs" value="<?php echo esc_attr($recipe_info->carbs); ?>" required /></td>
                </tr>
                <tr>
                    <th><label for="fat">Жиры (г)</label></th>
                    <td><input type="number" step="0.01" name="fat" id="fat" value="<?php echo esc_attr($recipe_info->fat); ?>" required /></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Сохранить изменения" />
            </p>
        </form>
        </div>
        <?php
    }    
    
    public function update_recipe() {
        global $wpdb;
        if (!isset($_POST['id'], $_POST['title'], $_POST['short_description'], $_POST['category'], $_POST['category_day'], $_POST['cooking_time'], $_POST['description'], $_POST['ingredients'], $_POST['instructions'], $_POST['calories'], $_POST['protein'], $_POST['carbs'], $_POST['fat'])) {
            wp_die('Не все обязательные поля были заполнены.');
        }
        $id = intval($_POST['id']);
        $title = sanitize_text_field($_POST['title']);
        $short_description = sanitize_textarea_field($_POST['short_description']);
        $category = sanitize_text_field($_POST['category']);
        $category_day = sanitize_text_field($_POST['category_day']);
        $cooking_time = intval($_POST['cooking_time']);
        $description = sanitize_textarea_field($_POST['description']);
        $ingredients = sanitize_textarea_field($_POST['ingredients']);
        $instructions = sanitize_textarea_field($_POST['instructions']);
        $calories = intval($_POST['calories']);
        $protein = floatval($_POST['protein']);
        $carbs = floatval($_POST['carbs']);
        $fat = floatval($_POST['fat']);
        $image = isset($_POST['image']) ? esc_url_raw($_POST['image']) : '';
        $wpdb->update($wpdb->prefix . 'recipes', compact('title', 'short_description', 'category', 'category_day', 'cooking_time', 'image'), ['id' => $id]);
        $wpdb->update($wpdb->prefix . 'recipe_info', compact('description', 'ingredients', 'instructions', 'calories', 'protein', 'carbs', 'fat'), ['recipe_id' => $id]);
        wp_redirect(admin_url('admin.php?page=recipes'));
    }

    public function delete_recipe() {
        global $wpdb;
        $recipe_id = intval($_POST['recipe_id']);
        $result = $wpdb->delete(
            $wpdb->prefix . 'recipes', 
            ['id' => $recipe_id], 
            ['%d']
        );
        wp_redirect(admin_url('admin.php?page=recipes'));
    }
}

if (class_exists('RecipesPlugin')) {
    $obj = new RecipesPlugin();
}