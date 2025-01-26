<script src="https://cdn.tailwindcss.com"></script> 
<script>
    const predefinedOptions = [
        "Calories",
        "Protein (g)",
        "Fat (g)",
        "Carbs (g)",
        "Fiber (g)",
        "Thiamine (mg)",
        "Riboflavin (mg)",
        "Niacin (mg)",
        "Pantothenic Acid (mg)",
        "Vitamin B-6 (mg)",
        "Choline (mg)",
        "Betaine (mg)",
        "Folate (μg)",
        "Vitamin E (mg)",
        "Beta Carotene (μg)",
        "Vitamin A (IU)",
        "Lutein + Zeaxanthin (μg)",
        "Vitamin K (μg)",
        "Calcium (mg)",
        "Iron (mg)",
        "Sodium (mg)",
        "Zinc (mg)",
        "Copper (mg)",
        "Magnesium (mg)",
        "Phosphorus (mg)",
        "Potassium (mg)"
    ];

    function populateForm(data) {
        document.querySelector('input[name="ingredient_name"]').value = data.ingredient_name;
        const tableBody = document.querySelector('#nutritionTable tbody');
        tableBody.innerHTML = '';

        const nutritionInfo = JSON.parse(data.nutrition_info);

        // Loop through each item in the nutrition info and create a row
        Object.entries(nutritionInfo).forEach(([name, value]) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="border border-gray-300 p-3">
                    <select name="attributes[]" required
                        class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="" disabled>Select Nutrient</option>
                        ${predefinedOptions
                            .map(option => `<option value="${option}" ${option === name ? "selected" : ""}>${option}</option>`)
                            .join('')}
                    </select>
                </td>
                <td class="border border-gray-300 p-3">
                    <input type="number" step="0.01" name="values[]" value="${value}" required
                        class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </td>
                <td class="border border-gray-300 p-3 text-center">
                    <button type="button" class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500" onclick="removeRow(this)">Delete</button>
                </td>
            `;
            tableBody.appendChild(row);
        });

        // Add hidden input to track ingredient ID
        let hiddenInput = document.querySelector('input[name="ingredient_id"]');
        if (!hiddenInput) {
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'ingredient_id';
            document.querySelector('#nutritionForm').appendChild(hiddenInput);
        }
        hiddenInput.value = data.id;
        console.log(hiddenInput.value)
    }

    function addNutritionRow() {
        const table = document.getElementById('nutritionTable').getElementsByTagName('tbody')[0];
        const currentSelections = getSelectedOptions();

        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="border border-gray-300 p-3">
                <select name="attributes[]" required
                    class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="" disabled selected>Select Nutrient</option>
                    ${predefinedOptions
                        .filter(option => !currentSelections.includes(option))
                        .map(option => `<option value="${option}">${option}</option>`)
                        .join('')}
                </select>
            </td>
            <td class="border border-gray-300 p-3">
                <input type="number" step="0.01" name="values[]" placeholder="e.g., 50" required
                    class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </td>
            <td class="border border-gray-300 p-3 text-center">
                <button type="button" class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500" onclick="removeRow(this)">Delete</button>
            </td>
        `;
        table.appendChild(row);
        updateDropdowns();
    }

    function removeRow(button) {
        const row = button.parentElement.parentElement;
        row.remove();
        updateDropdowns();
    }

    function getSelectedOptions() {
        const selects = document.querySelectorAll('select[name="attributes[]"]');
        return Array.from(selects).map(select => select.value).filter(value => value);
    }

    function updateDropdowns() {
        const currentSelections = getSelectedOptions();
        const selects = document.querySelectorAll('select[name="attributes[]"]');
        selects.forEach(select => {
            const currentValue = select.value;
            select.innerHTML = `
                <option value="" disabled ${!currentValue ? "selected" : ""}>Select Nutrient</option>
                ${predefinedOptions
                    .filter(option => !currentSelections.includes(option) || option === currentValue)
                    .map(option => `<option value="${option}" ${option === currentValue ? "selected" : ""}>${option}</option>`)
                    .join('')}`;
        });
    }

    window.onload = function() {
        const editData = <?php echo json_encode($ingredient_to_edit); ?>;
        if (editData) {
            populateForm(editData);
        }
    };
</script>

<div class="p-6 bg-white shadow-md rounded">
    <h1 class="text-3xl font-bold mb-4">Manage Ingredients</h1>
    <form method="POST" id="nutritionForm" class="space-y-6">
        <h2 class="text-2xl font-semibold">Add/Edit Ingredient (100g)</h2>
        <input type="hidden" name="ingredient_id" value="<?php echo $ingredient_to_edit->id ?? ''; ?>">
        <input type="text" name="ingredient_name" placeholder="Ingredient Name" required
            class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">

        <h3 class="text-xl font-medium mt-4">Nutrition Information</h3>
        <table id="nutritionTable" class="table-auto w-full border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-300 p-3 text-left">Name</th>
                    <th class="border border-gray-300 p-3 text-left">Value</th>
                    <th class="border border-gray-300 p-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Nutrition rows will be dynamically generated here -->
            </tbody>
        </table>

        <button type="button" onclick="addNutritionRow()"
            class="mt-4 px-6 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">Add More Nutrition Info</button>
        
        <br><br>

        <button type="submit" name="fmf_add_ingredient"
            class="px-6 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500">Save Ingredient</button>
    </form>

    <h2 class="text-2xl font-semibold mt-8">Existing Ingredients</h2>
    <div class="overflow-x-auto">
        <table class="w-full table-auto mt-4 border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-300 p-3">Name</th>
                    <th class="border border-gray-300 p-3">Nutrition Info</th>
                    <th class="border border-gray-300 p-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ingredients as $ingredient) : ?>
                    <tr>
                        <td class="border border-gray-300 p-3"><?php echo $ingredient->ingredient_name; ?></td>
                        <td class="border border-gray-300 p-3">
                            <?php
                            $nutrition = json_decode($ingredient->nutrition_info, true);
                            foreach ($nutrition as $attribute => $value) {
                                echo "<strong>$attribute:</strong> $value<br>";
                            }
                            ?>
                        </td>
                        <td class="border border-gray-300 p-3">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="ingredient_id" value="<?php echo $ingredient->id; ?>">
                                <button type="submit" name="fmf_edit_ingredient"
                                    class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">Edit</button>
                                <button type="submit" name="fmf_delete_ingredient"
                                    class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
