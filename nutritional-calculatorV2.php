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

require_once plugin_dir_path( __FILE__ ) . 'includes/admin-menu.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/database.php';

function fmf_enqueue_scripts() {
    wp_enqueue_script('tailwind-cdn', 'https://cdn.tailwindcss.com', [], null, true);
}

add_action('wp_enqueue_scripts', 'fmf_enqueue_scripts');

function fmf_display_nutrition_calculator() {
    global $wpdb;
    
    // Fetch ingredients from the database
    $table_name = $wpdb->prefix . 'ingredients';
    $ingredients = $wpdb->get_results("SELECT * FROM $table_name");
    
    // Prepare the ingredients data for JavaScript
    $ingredients_data = [];
    foreach ($ingredients as $ingredient) {
        $ingredient_name = $ingredient->ingredient_name;
        $nutrition_info = json_decode($ingredient->nutrition_info, true) ?? []; 

        $ingredient_data = [
            'name' => $ingredient_name,
            'nutrition' => $nutrition_info,
        ];

        $ingredients_data[] = $ingredient_data;
    }

    // Pass the ingredients data to JavaScript using wp_localize_script
    wp_register_script('fmf-nutrition-calculator', '', [], false, true);
    wp_localize_script('fmf-nutrition-calculator', 'FMF_Ingredients', [
        'ingredients' => $ingredients_data
    ]);

    ?>
    <style>
        .fmf-body {
            font-family: 'Playfair Display', serif;
            background-color: #c7b29b;
            color: #523f30;
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

        /* Responsive Styles */
        @media (max-width: 768px) {
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
        }

        @media (max-width: 480px) {
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
                    <div class="flex justify-center mb-6">
                        <div class="fmf-rounded-box w-1/2 mr-4">
                            <div class="flex items-center mb-2">
                                <div class="fmf-icon-wrapper">
                                    <img src="https://placehold.co/50x50" alt="logo placeholder" class="h-full w-full">
                                </div>
                                <h2 class="text-lg font-bold ml-4">Ingredients</h2>
                            </div>
                            <div class="fmf-form-group">
                                <label for="ingredient">Select Ingredient:</label>
                                <select id="ingredient">
                                    <option value="" disabled selected>Choose an ingredient</option>
                                    ${this.ingredients.map(ingredient => `
                                        <option value="${ingredient.name}" data-nutrition='${JSON.stringify(ingredient.nutrition)}'>
                                            ${ingredient.name}
                                        </option>
                                    `).join('')}
                                </select>
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
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                
                                <div class="fmf-hidden" id="recipeSection">
                                    <tbody></tbody>
                                </div>
                            </table>
                            <br>
                            <button class="fmf-button" id="calculateNutritionButton">Get Nutrition Info</button>
                        </div>
                    </div>

                    <div class="fmf-rounded-box">
                        <div class="flex items-center mb-4">
                            <div class="fmf-icon-wrapper">
                                <img src="https://placehold.co/50x50" alt="logo placeholder" class="h-full w-full">
                            </div>
                            <h2 class="text-lg font-bold ml-4 fmf-hidden" id="nutritionTitle"></h2>
                            <h5 id="totalServings" class="text-lg ml-4 font-bold fmf-hidden"></h5>
                        </div>
                        <table class="w-full fmf-table">
                            <thead>
                                <tr>
                                    <th>Recipe Total</th>
                                    <th>Nutritional Info</th>
                                </tr>
                            </thead>
                            <tbody id="nutritionTable"></tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex justify-center">
                        <button class="bg-gray-500 text-white p-2 rounded">Print</button>
                    </div>
                `;

                this.container.innerHTML = html;
            }

            bindEvents() {
                const ingredientSelect = this.container.querySelector("#ingredient");
                const gramsInput = this.container.querySelector("#grams");
                const addIngredientButton = this.container.querySelector("#addIngredientButton");
                const recipeTable = this.container.querySelector("#recipeTable tbody");
                const calculateNutritionButton = this.container.querySelector("#calculateNutritionButton");
                const nutritionTable = this.container.querySelector("#nutritionTable");
                const nutritionTitle = this.container.querySelector("#nutritionTitle");
                const totalServings = this.container.querySelector("#totalServings");
                const recipeSection = this.container.querySelector("#recipeSection");
            
                const toggleVisibility = (element, show) => {
                    element.classList.toggle("fmf-hidden", !show);
                };
            
                addIngredientButton.addEventListener("click", () => {
                    const selectedOption = ingredientSelect.options[ingredientSelect.selectedIndex];
                    const ingredient = selectedOption.value;
                    const calories = selectedOption.dataset.calories;
                    const protein = selectedOption.dataset.protein;
                    const fat = selectedOption.dataset.fat;
                    const carbs = selectedOption.dataset.carbs;
                    const grams = gramsInput.value;
            
                    if (!ingredient || !grams) return;
            
                    const row = document.createElement("tr");
                    row.innerHTML = `
                        <td>${ingredient}</td>
                        <td>${grams}g</td>
                        <td>
                            <button class="deleteBtn">X</button>
                        </td>
                    `;
                    recipeTable.appendChild(row);
                    gramsInput.value = "";
            
                    toggleVisibility(recipeSection, true);
                });
            
                calculateNutritionButton.addEventListener("click", () => {
                    const rows = recipeTable.querySelectorAll("tr");
                    const totalNutrition = {};

                    rows.forEach((row) => {
                        const ingredientName = row.querySelector("td:first-child").textContent;
                        const ingredientData = this.ingredients.find(ing => ing.name === ingredientName);
                        const grams = parseFloat(row.querySelector("td:nth-child(2)").textContent.replace("g", ""));

                        for (const [nutrient, value] of Object.entries(ingredientData.nutrition)) {
                            totalNutrition[nutrient] = (totalNutrition[nutrient] || 0) + (value * grams / 100);
                        }
                    });

                    nutritionTable.innerHTML = Object.entries(totalNutrition).map(([nutrient, amount]) => `
                        <tr>
                            <td>${nutrient}</td>
                            <td>${amount.toFixed(2)}</td>
                        </tr>
                    `).join('');

                    nutritionTitle.textContent = "Total Nutritional Information";
                    toggleVisibility(nutritionSection, true);
                });
            }
        }

        const nutritionCalculator = new FMF_NutritionalCalculator({
            selector: "#fmfNutritionCalculator",
            ingredients: FMF_Ingredients.ingredients,
        });
    </script>
    <?php
}

add_action( 'wp_footer', 'fmf_display_nutrition_calculator' );
