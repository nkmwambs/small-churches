<?php
// Define the file path
$file = 'generated_data.csv'; // Update this path to your CSV file location

if (!file_exists($file)) {
    die("File not found.");
}

$outputFile = $file; // Save changes back to the same file

// Read CSV into array
$data = array_map('str_getcsv', file($file));
$header = array_shift($data); // Get headers

// Column indexes based on header
$columns = array_flip($header);
$staff_name_col = $columns['staff_name'];
$department_col = $columns['department'];
$is_manager_col = $columns['is_departmement_manager'];
$is_devotion_committee_col = $columns['is_devotion_committee'];
$old_group_col = $columns['old_group'];
$new_group_col = $columns['new_group'];

// Group members by old_group and devotion committee status
$groups = [];
foreach ($data as &$row) {
    $old_group = $row[$old_group_col];
    $is_devotion = $row[$is_devotion_committee_col] == 'yes';

    // Initialize group count trackers
    if (!isset($groups[$old_group])) {
        $groups[$old_group] = ['members' => [], 'total' => 0, 'devotion_committee' => 0, 'new_groups' => []];
    }

    // Add member to group list
    $groups[$old_group]['members'][] = $row;
    $groups[$old_group]['total']++;
    if ($is_devotion) {
        $groups[$old_group]['devotion_committee']++;
    }
}

// Shuffle each old group to randomize the data
foreach ($groups as $old_group => &$group_data) {
    shuffle($group_data['members']);
}

// Define new groups (e.g., Group_1, Group_2, etc.)
$new_groups = ['Group_1', 'Group_2', 'Group_3', 'Group_4', 'Group_5'];
$group_index = 0; // To cycle through groups for balanced assignment

// Assign new groups in a balanced manner
foreach ($groups as $old_group => &$group_data) {
    foreach ($group_data['members'] as &$row) {
        $is_devotion = $row[$is_devotion_committee_col] == 'yes';

        // Calculate the maximum allowable members for the constraints
        $max_general_in_group = ceil(0.3 * $group_data['total']);
        $max_devotion_in_group = ceil(0.1 * $group_data['devotion_committee']);

        // Find a new group that meets both balance and constraint requirements
        do {
            $new_group = $new_groups[$group_index];
            $group_index = ($group_index + 1) % count($new_groups); // Cycle through groups

            // Initialize new group counts if not set
            if (!isset($group_data['new_groups'][$new_group])) {
                $group_data['new_groups'][$new_group] = ['general' => 0, 'devotion' => 0];
            }
            $general_count = $group_data['new_groups'][$new_group]['general'];
            $devotion_count = $group_data['new_groups'][$new_group]['devotion'];

            // Check if the current new group meets the constraints
            $can_assign = (!$is_devotion && $general_count < $max_general_in_group) ||
                          ($is_devotion && $devotion_count < $max_devotion_in_group);
        } while (!$can_assign);

        // Increment count for the assigned new group
        $group_data['new_groups'][$new_group][$is_devotion ? 'devotion' : 'general']++;
        $row[$new_group_col] = $new_group; // Assign new group in the row
    }
}

// Write the modified data back to the original CSV file
$output = fopen($outputFile, 'w');
fputcsv($output, $header); // Write header
foreach ($groups as $group_data) {
    foreach ($group_data['members'] as $row) {
        fputcsv($output, $row);
    }
}
fclose($output);

echo "New groups assigned and CSV file updated successfully.";
