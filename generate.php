<?php
require_once 'vendor/autoload.php';

use Faker\Factory;

// Define the path to the CSV file
$outputFile = 'generated_data.csv'; // Update this to your desired location

// Create a Faker instance
$faker = Factory::create();

// Define the number of rows to generate
$numRows = 100; // You can adjust this number as needed

// Define the CSV headers
$header = ['staff_name', 'department', 'is_departmement_manager', 'is_devotion_committee', 'old_group'];

// Open the CSV file for writing
$output = fopen($outputFile, 'w');
fputcsv($output, $header); // Write the header

// Track which departments already have a manager
$departmentManagers = [];

// Get the array of departments from the csv department column 
// $departments = array_unique(array_column($data, 'department'));

// Generate data and write to CSV
for ($i = 0; $i < $numRows; $i++) {
    $staff_name = $faker->name;
    $department = $faker->randomElement(['HR', 'Finance', 'Logistics', 'IT', 'Marketing']);
    
    
    // Check if the department already has a manager
    if (isset($departmentManagers[$department])) {
        $is_department_manager = 'no'; // This department already has a manager
    } else {
        $is_department_manager = $faker->boolean(20) ? 'yes' : 'no'; // 20% chance of being a manager
        if ($is_department_manager === 'yes') {
            $departmentManagers[$department] = true; // Mark this department as having a manager
        }
    }

    $is_devotion_committee = $faker->boolean(15) ? 'yes' : 'no'; // 15% chance of being in the devotion committee
    $old_group = 'Group_' . $faker->numberBetween(1, 5); // Random old group between Group_1 and Group_5

    // Write a row to the CSV file
    fputcsv($output, [$staff_name, $department, $is_department_manager, $is_devotion_committee, $old_group]);
}

// Close the CSV file
fclose($output);

echo "Test data generated and saved to $outputFile";
