<?php
get_header();
?>

<div class="container">
    <div class="recipe-page">
        <div class="recipe-image-container">
            <?php
            $recipe_id = isset($_GET['recipe_id']) ? intval($_GET['recipe_id']) : 0;

            if ($recipe_id > 0) {
                global $wpdb;
                $table_recipes = $wpdb->prefix . 'recipes';
                $table_recipe_info = $wpdb->prefix . 'recipe_info';

                $recipe = $wpdb->get_row($wpdb->prepare(
                    "SELECT r.*, ri.ingredients, ri.instructions, ri.calories, ri.protein, ri.carbs, ri.fat 
                    FROM $table_recipes r
                    JOIN $table_recipe_info ri ON r.id = ri.recipe_id
                    WHERE r.id = %d",
                    $recipe_id
                ));

                if ($recipe) {
                    if ($recipe->image) {
                        echo '<img src="' . esc_url($recipe->image) . '" alt="' . esc_attr($recipe->title) . '" class="recipe-image">';
                    }
                } else {
                    echo '<p>Рецепт не найден</p>';
                }
            } else {
                echo '<p>Не указан ID рецепта</p>';
            }
            ?>
        </div>
        <div class="recipe-details">
            <?php
            if ($recipe) {
                echo '<h1 class="recipe-title">' . esc_html($recipe->title) . '</h1>';
                echo '<p class="recipe-description">' . esc_html($recipe->short_description) . '</p>';
                echo '<div class="recipe-info">';
                echo '<p class="recipe-category">Категория: ' . esc_html($recipe->category_day) . '</p>';
                echo '<p class="recipe-time">Время приготовления: ' . esc_html($recipe->cooking_time) . ' мин.</p>';
                echo '</div>';
                echo '<div class="recipe-instructions">';
                echo '<h2 class="recipe-section-title">Инструкция:</h2>';
                echo '<p class="recipe-instructions-text">' . nl2br(esc_html($recipe->instructions)) . '</p>';
                echo '</div>';
                echo '<div class="recipe-ingredients">';
                echo '<h2 class="recipe-section-title">Ингредиенты:</h2>';
                echo '<p class="recipe-ingredients-text">' . nl2br(esc_html($recipe->ingredients)) . '</p>';
                echo '</div>';
                echo '<div class="recipe-nutrition">';
                echo '<h2 class="recipe-section-title">Пищевая ценность:</h2>';
                echo '<p>Калории: ' . esc_html($recipe->calories) . '</p>';
                echo '<p>Белки: ' . esc_html($recipe->protein) . '</p>';
                echo '<p>Углеводы: ' . esc_html($recipe->carbs) . '</p>';
                echo '<p>Жиры: ' . esc_html($recipe->fat) . '</p>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</div>

<?php
get_footer();
?>