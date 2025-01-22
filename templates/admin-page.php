<div class="p-6 bg-white shadow-md rounded">
    <h1 class="text-3xl font-bold mb-4">Manage Ingredients</h1>
    <form method="POST" id="nutritionForm" class="space-y-6">
        <h2 class="text-2xl font-semibold">Add New Ingredient</h2>
        
        <input type="text" name="ingredient_name" placeholder="Ingredient Name" required
            class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">

        <h3 class="text-xl font-medium mt-4">Nutrition Information</h3>
        <table id="nutritionTable" class="table-auto w-full border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-300 p-3 text-left">Name</th>
                    <th class="border border-gray-300 p-3 text-left">Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="border border-gray-300 p-3">
                        <input type="text" name="attributes[]" placeholder="e.g., Calories" required
                            class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </td>
                    <td class="border border-gray-300 p-3">
                        <input type="number" step="0.01" name="values[]" placeholder="e.g., 100" required
                            class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </td>
                </tr>
            </tbody>
        </table>

        <button type="button" onclick="addNutritionRow()"
            class="mt-4 px-6 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">Add More Nutrition Info</button>
        
        <br><br>

        <button type="submit" name="fmf_add_ingredient"
            class="px-6 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500">Add Ingredient</button>
    </form>

    <h2 class="text-2xl font-semibold mt-8">Existing Ingredients</h2>
    <div class="overflow-x-auto">
        <table class="w-full table-auto mt-4 border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-300 p-3">ID</th>
                    <th class="border border-gray-300 p-3">Name</th>
                    <th class="border border-gray-300 p-3">Nutrition Info</th>
                    <th class="border border-gray-300 p-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ingredients as $ingredient) : ?>
                    <tr>
                        <td class="border border-gray-300 p-3"><?php echo $ingredient->id; ?></td>
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

<script>
    function addNutritionRow() {
        const table = document.getElementById('nutritionTable');
        const row = table.insertRow(-1);
        row.innerHTML = `
            <td class="border border-gray-300 p-3">
                <input type="text" name="attributes[]" placeholder="e.g., Vitamin C" required
                    class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </td>
            <td class="border border-gray-300 p-3">
                <input type="number" step="0.01" name="values[]" placeholder="e.g., 50mg" required
                    class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </td>
        `;
    }
</script>
