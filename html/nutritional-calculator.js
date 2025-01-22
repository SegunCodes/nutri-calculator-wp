class NutritionalCalculator {
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
                            ${this.ingredients
                                .map(
                                    (ingredient) =>
                                        `<option value="${ingredient.name}" data-calories="${ingredient.calories}" data-protein="${ingredient.protein}" data-fat="${ingredient.fat}" data-carbs="${ingredient.carbs}">
                                        ${ingredient.name}
                                    </option>`
                                )
                                .join("")}
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
            const grams = parseFloat(gramsInput.value);
    
            if (!ingredient || grams <= 0 || isNaN(grams)) {
                alert("Please select an ingredient and enter a valid amount of grams.");
                return;
            }
    
            let existingRow = Array.from(recipeTable.rows).find(
                (row) => row.cells[0].textContent === ingredient
            );
    
            if (existingRow) {
                const existingGrams = parseFloat(existingRow.cells[1].textContent);
                existingRow.cells[1].textContent = existingGrams + grams;
            } else {
                const newRow = recipeTable.insertRow();
                newRow.innerHTML = `
                    <td>${ingredient}</td>
                    <td contenteditable="true">${grams}</td>
                   <td><button class="deleteButton">Delete</button></td>`;
            }
    
            toggleVisibility(recipeSection, true);
            ingredientSelect.value = "";
            gramsInput.value = "";
        });
    
        recipeTable.addEventListener("click", (event) => {
            if (event.target.classList.contains("deleteButton")) {
                event.target.closest("tr").remove();
            }
        });
    
        calculateNutritionButton.addEventListener("click", () => {
            const recipeName = this.container.querySelector("#recipeName").value;
    
            if (!recipeName) {
                alert("Please enter a recipe name.");
                return;
            }
    
            let totalCalories = 0;
            let totalProtein = 0;
            let totalFat = 0;
            let totalCarbs = 0;
            let totalGrams = 0;
    
            Array.from(recipeTable.rows).forEach((row) => {
                const ingredient = row.cells[0].textContent;
                const grams = parseFloat(row.cells[1].textContent);
                const ingredientData = this.ingredients.find((item) => item.name === ingredient);
    
                if (ingredientData) {
                    totalCalories += (ingredientData.calories * grams) / 100;
                    totalProtein += (ingredientData.protein * grams) / 100;
                    totalFat += (ingredientData.fat * grams) / 100;
                    totalCarbs += (ingredientData.carbs * grams) / 100;
                    totalGrams = totalCalories + totalProtein + totalFat + totalCarbs;
                }
            });
    
            nutritionTable.innerHTML = `
                <tr><td>Calories</td><td>${totalCalories.toFixed(1)} g</td></tr>
                <tr><td>Protein</td><td>${totalProtein.toFixed(1)} g</td></tr>
                <tr><td>Fat</td><td>${totalFat.toFixed(1)} g</td></tr>
                <tr><td>Carbs</td><td>${totalCarbs.toFixed(1)} g</td></tr>`;
    
            nutritionTitle.textContent = `Nutrition Information for "${recipeName}"`;
            toggleVisibility(nutritionTitle, true);
            totalServings.textContent = `Total Servings: ${totalGrams.toFixed(1)} grams`;
            toggleVisibility(totalServings, true);
        });
    }
    
}
