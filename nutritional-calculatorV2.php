<?php
/**
 * Plugin Name: Fresh Milled Flour Nutrition Calculator
 * Description: A custom plugin for managing ingredients and dynamically displaying the nutrition calculator.
 * Version:     2
 * Author:      Joe-Alabi Olusegun
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin root directory path
define( 'FMF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Include necessary files
require_once FMF_PLUGIN_DIR . 'includes/database.php';
require_once FMF_PLUGIN_DIR . 'includes/admin-menu.php';

/**
 * Enqueue Tailwind CSS via CDN
 */
function fmf_enqueue_scripts() {
    // Enqueue Tailwind CSS
    wp_enqueue_script( 'tailwind-cdn', 'https://cdn.tailwindcss.com', [], null, true );

    // Optionally, enqueue your custom scripts here if moved to separate files
}
add_action( 'wp_enqueue_scripts', 'fmf_enqueue_scripts' );

/**
 * Display Nutrition Calculator in Footer (Only on Specific Page)
 */
function fmf_display_nutrition_calculator() {
    // Check if we're on the target page by slug
    if ( ! is_page( 'fresh-milled-flour-nutrition-calculator' ) ) {
        return; // Exit the function if not on the target page
    }

    global $wpdb;

    // Define table name with dynamic prefix
    $table_name = $wpdb->prefix . 'ingredients';

    // Check if the table exists before querying
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
        // Log the error
        error_log( "FMF Plugin Error: Table '$table_name' does not exist." );

        // Optionally, display an admin notice
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p>Fresh Milled Flour Nutrition Calculator: Required database table does not exist. Please deactivate and reactivate the plugin.</p></div>';
        });

        return; // Exit the function to prevent further errors
    }

    // Fetch ingredients from the database
    $ingredients = $wpdb->get_results( "SELECT * FROM $table_name" );

    // Prepare the ingredients data for JavaScript
    $ingredients_data = [];
    foreach ( $ingredients as $ingredient ) {
        $ingredient_name   = $ingredient->ingredient_name;
        $nutrition_info    = json_decode( $ingredient->nutrition_info, true ) ?? [];

        $ingredient_data = [
            'name'      => $ingredient_name,
            'nutrition' => $nutrition_info,
        ];

        $ingredients_data[] = $ingredient_data;
    }

    // Pass the ingredients data to JavaScript
    // Instead of wp_localize_script, we'll embed the data directly in the script
    ?>
    <script type="text/javascript">
        // Pass PHP data to JavaScript
        const FMF_Ingredients = <?php echo wp_json_encode( [ 'ingredients' => $ingredients_data ] ); ?>;
    </script>

    <style>
        .fmf-body {
            font-family: 'Playfair Display', serif;
            background-color: #c7b29b;
            color: #523f30;
            padding: 100px;
        }
        .fmf-rounded-box {
            border-radius: 12px;
            border: 2px solid #523f30;
            background-color: #e4d7c5;
            padding: 20px;
        }
        .fmf-icon-wrapper {
            width: 50px;
            height: 50px;
            overflow: hidden;
        }

        .fmf-table th, .fmf-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            word-wrap: break-word;
        }

        .fmf-table th {
            background-color: #f4f4f4;
        }

        .fmf-form-group {
            margin-bottom: 20px;
        }

        .fmf-form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .fmf-form-group input, 
        .fmf-form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
        }
        .fmf-button {
            padding: 10px 20px;
            background-color: #bc6e15;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }

        .fmf-button:hover {
            background-color: #a76012;
        }

        .fmf-form-group input {
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }

        .fmf-hidden {
            display: none;
        }

        .fmf-suggestion-item {
            padding: 8px;
            cursor: pointer;
        }

        .fmf-suggestion-item:hover {
            background-color: #f0f0f0;
        }

        .fmf-no-results {
            padding: 8px;
            color: #999;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .fmf-body {
                padding: 50px;
            }
            .fmf-table, .fmf-table th, .fmf-table td {
                font-size: 0.9rem;
            }

            .fmf-form-group input, 
            .fmf-form-group select {
                font-size: 0.9rem;
            }

            .fmf-button {
                padding: 8px 15px;
                font-size: 0.9rem;
            }
            
            .fmf-input-section {
                flex-direction: column;
            }

            .fmf-input-section-top {
                margin-bottom: 30px;
            }

            .fmf-rounded-box{
                width: 100%;
            }
            
        }

        @media (max-width: 480px) {
            .fmf-body {
                padding: 20px;
            }
            .fmf-table {
                font-size: 0.8rem;
            }

            .fmf-table th, .fmf-table td {
                padding: 8px;
            }

            .fmf-button {
                padding: 7px 10px;
                font-size: 0.8rem;
            }

            .fmf-form-group input {
                font-size: 0.9rem;
            }
        }
    </style>

    <div id="fmfNutritionCalculator" class="fmf-body"></div>

    <script>
        class FMF_NutritionalCalculator {
            constructor(config) {
                this.container = document.querySelector(config.selector);
                this.ingredients = config.ingredients;
                this.init();
            }

            init() {
                this.renderUI();
                this.bindEvents();
            }

            renderUI() {
                const html = `
                    <div class="flex justify-center mb-20 fmf-input-section">
                        <div class="fmf-rounded-box w-1/2 mr-4 fmf-input-section-top">
                            <div class="flex items-center mb-2">
                                <div class="fmf-icon-wrapper">
                                    <img width="512" height="504" src="https://grainsinsmallplaces.net/wp-content/uploads/2022/11/cropped-cropped-Grains-In-Small-Places-Logo-1.png" class="custom-logo" alt="Grains In Small Places Logo" decoding="async" fetchpriority="high" srcset="https://grainsinsmallplaces.net/wp-content/uploads/2022/11/cropped-cropped-Grains-In-Small-Places-Logo-1.png 512w, https://grainsinsmallplaces.net/wp-content/uploads/2022/11/cropped-cropped-Grains-In-Small-Places-Logo-1-300x295.png 300w" sizes="(max-width: 512px) 100vw, 512px">
                                </div>
                                <h2 class="text-lg font-bold ml-4">Ingredients</h2>
                            </div>
                            <div class="fmf-form-group">
                                <label for="ingredient">Search Ingredient:</label>
                                <input type="text" id="ingredient" placeholder="Start typing an ingredient..." autocomplete="off">
                                <div id="ingredientSuggestions" class="absolute w-auto max-w-sm bg-white shadow-lg rounded-md mt-1 z-10 overflow-auto max-h-60"></div>
                            </div>
                            <div class="fmf-form-group">
                                <label for="grams">Enter Amount (in grams):</label>
                                <input type="number" id="grams" placeholder="Enter grams">
                            </div>
                            <button class="fmf-button" id="addIngredientButton">Add to Recipe</button>
                        </div>

                        <div class="fmf-rounded-box w-1/2">
                            <div class="fmf-form-group">
                                <label for="recipeName">Recipe Name:</label>
                                <input type="text" id="recipeName" placeholder="Enter recipe name">
                            </div>
                            <table class="w-full fmf-table" id="recipeTable">
                                <thead>
                                    <tr>
                                        <th>Ingredient</th>
                                        <th>Amount (g)</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                            <br>
                            <button class="fmf-button" id="calculateNutritionButton">Get Nutrition Info</button>
                        </div>
                    </div>

                    <div class="fmf-rounded-box" id="contentToPrint">
                        <div class="flex items-center mb-4">
                            <div class="fmf-icon-wrapper">
                                <img width="512" height="504" src="https://grainsinsmallplaces.net/wp-content/uploads/2022/11/cropped-cropped-Grains-In-Small-Places-Logo-1.png" class="custom-logo" alt="Grains In Small Places Logo" decoding="async" fetchpriority="high" srcset="https://grainsinsmallplaces.net/wp-content/uploads/2022/11/cropped-cropped-Grains-In-Small-Places-Logo-1.png 512w, https://grainsinsmallplaces.net/wp-content/uploads/2022/11/cropped-cropped-Grains-In-Small-Places-Logo-1-300x295.png 300w" sizes="(max-width: 512px) 100vw, 512px">
                            </div>
                            <h2 class="text-lg font-bold ml-4 fmf-hidden" id="nutritionTitle"></h2>
                        </div>
                        <div class="grid grid-cols-2 gap-4 p-2">
                            <table class="w-full fmf-table">
                                <thead>
                                    <tr>
                                        <th>Recipe Total</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="nutritionTable"></tbody>
                            </table>  
                             <table class="w-full fmf-table">
                                <thead>
                                    <tr>
                                        <th>Total Servings</th>
                                        <th>
                                            <input class="w-full" type="number" id="servings" value="1">
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="servingsTable"></tbody>
                            </table>                        
                        </div>
                    </div>

                    <div class="mt-4 flex justify-center">
                        <button class="bg-gray-500 text-white p-2 rounded" id="printButton">Print</button>
                    </div>
                `;

                this.container.innerHTML = html;
                this.bindAutoComplete();

                const printButton = document.getElementById("printButton");
                printButton.addEventListener("click", this.printDiv.bind(this));
            } 

            printDiv() {
                const printContent = document.getElementById("contentToPrint").innerHTML;
                const originalContent = document.body.innerHTML;

                document.body.innerHTML = printContent;
                window.print();
                document.body.innerHTML = originalContent;
            }

            bindAutoComplete() {
                const ingredientInput = document.getElementById('ingredient');
                const suggestionsContainer = document.getElementById('ingredientSuggestions');

                ingredientInput.addEventListener('input', () => {
                    const query = ingredientInput.value.toLowerCase();
                    const filteredIngredients = this.ingredients.filter(ingredient => 
                        ingredient.name.toLowerCase().includes(query)
                    );

                    suggestionsContainer.innerHTML = '';
                    if (filteredIngredients.length > 0) {
                        filteredIngredients.forEach(ingredient => {
                            const suggestion = document.createElement('div');
                            suggestion.classList.add(
                                'p-2', 
                                'hover:bg-gray-200', 
                                'cursor-pointer', 
                                'transition', 
                                'rounded-md'
                            );
                            suggestion.textContent = ingredient.name;
                            suggestion.addEventListener('click', () => {
                                ingredientInput.value = ingredient.name;
                                suggestionsContainer.innerHTML = ''; // Hide suggestions
                            });
                            suggestionsContainer.appendChild(suggestion);
                        });
                    } else {
                        const noResults = document.createElement('div');
                        noResults.textContent = 'No ingredients found';
                        noResults.classList.add(
                            'p-2', 
                            'text-gray-500', 
                            'italic', 
                            'text-center'
                        );
                        suggestionsContainer.appendChild(noResults);
                    }
                });
            }


            bindEvents() {
                const ingredientSelect = this.container.querySelector("#ingredient");
                const gramsInput = this.container.querySelector("#grams");
                const addIngredientButton = this.container.querySelector("#addIngredientButton");
                const recipeTable = this.container.querySelector("#recipeTable tbody");
                const calculateNutritionButton = this.container.querySelector("#calculateNutritionButton");
                const nutritionTable = this.container.querySelector("#nutritionTable");
                const nutritionTitle = this.container.querySelector("#nutritionTitle");
                const recipeName = this.container.querySelector("#recipeName");
                const servingsInput = this.container.querySelector("#servings");
                const servingsTable = this.container.querySelector("#servingsTable");

                // Ensure the servings input value is always at least 1
                servingsInput.addEventListener("input", () => {
                    if (parseInt(servingsInput.value) < 1) {
                        servingsInput.value = 1;
                    }
                    updateNutrition(); // Update nutrition whenever servings are changed
                });

                addIngredientButton.addEventListener("click", () => {
                    const ingredientInput = this.container.querySelector("#ingredient");
                    const ingredient = ingredientInput.value.trim();
                    // const nutrition = JSON.parse(selectedOption.dataset.nutrition || '{}');
                    const grams = gramsInput.value.trim();

                    if (!ingredient || !grams) {
                        alert('Please select an ingredient and enter the amount in grams.');
                        return;
                    }

                    // Check if ingredient is already added
                    const existingRow = Array.from(recipeTable.rows).find(row => row.cells[0].textContent === ingredient);
                    if (existingRow) {
                        alert('This ingredient has already been added.');
                        return;
                    }

                    const row = document.createElement("tr");
                    row.innerHTML = `
                        <td>${ingredient}</td>
                        <td>${grams} g</td>
                        <td><button class="fmf-button remove-row">Remove</button></td>
                    `;
                    recipeTable.appendChild(row);
                    gramsInput.value = "";
                    ingredientInput.value = "";

                    // Show nutrition section if hidden
                    nutritionTitle.classList.add('fmf-hidden');
                    nutritionTable.innerHTML = '';
                    servingsTable.innerHTML = '';
                });

                // Delegate event for removing rows
                recipeTable.addEventListener("click", (e) => {
                    if (e.target && e.target.classList.contains('remove-row')) {
                        const row = e.target.closest('tr');
                        row.remove();
                    }
                });

                calculateNutritionButton.addEventListener("click", () => {
                    const rows = recipeTable.querySelectorAll("tr");
                    if (rows.length === 0) {
                        alert('Please add at least one ingredient.');
                        return;
                    }

                    const totalNutrition = {};

                    rows.forEach((row) => {
                        const ingredientName = row.cells[0].textContent;
                        const grams = parseFloat(row.cells[1].textContent.replace("g", ""));

                        const ingredientData = this.ingredients.find(ing => ing.name === ingredientName);
                        if (!ingredientData) return;

                        for (const [nutrient, value] of Object.entries(ingredientData.nutrition)) {
                            totalNutrition[nutrient] = (totalNutrition[nutrient] || 0) + (value * grams / 100);
                        }
                    });

                    this.totalNutrition = totalNutrition;

                    // Populate the nutrition table
                    nutritionTable.innerHTML = Object.entries(totalNutrition).map(([nutrient, amount]) => `
                        <tr>
                            <td>${nutrient}</td>
                            <td>${amount.toFixed(2)}</td>
                        </tr>
                    `).join('');

                    // Update the servings table based on the initial servings input value
                    updateServingsTable();

                    // Show the nutrition section
                    nutritionTitle.textContent = recipeName.value;
                    nutritionTitle.classList.remove('fmf-hidden');
                });

                // Function to update the servings table dynamically
                const updateServingsTable = () => {
                    const servings = parseInt(servingsInput.value);
                    servingsTable.innerHTML = Object.entries(this.totalNutrition).map(([nutrient, amount]) => {
                        const servingValue = (amount / servings);
                        const displayValue = isNaN(servingValue) ? 0 : servingValue.toFixed(2);  // Check for NaN and set default to 0
                        return `
                            <tr>
                                <td>${nutrient}</td>
                                <td><span class="serving-value">${displayValue}</span></td>
                            </tr>
                        `;
                    }).join('');
                }

                const updateNutrition = () => {
                    if (!this.totalNutrition) return; 
                    updateServingsTable(); 
                }
            }
        }

        // Initialize the calculator
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof FMF_Ingredients !== 'undefined' && FMF_Ingredients.ingredients.length > 0) {
                const nutritionCalculator = new FMF_NutritionalCalculator({
                    selector: "#fmfNutritionCalculator",
                    ingredients: FMF_Ingredients.ingredients,
                });
            } else {
                console.error('FMF_Ingredients data is not available.');
            }
        });
    </script>
    <?php
}
add_action( 'wp_footer', 'fmf_display_nutrition_calculator' );

/**
 * Activation Hook: Create Ingredients Table
 */
function fmf_plugin_activate() {
    fmf_create_ingredients_table();
}
register_activation_hook( __FILE__, 'fmf_plugin_activate' );
