<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $outputFile = 'modified_file.csv';
    $maxGroupCount = isset($_POST['maxGroupCount']) ? $_POST['maxGroupCount'] : 5; //5;

    // Read CSV into array
    $data = array_map('str_getcsv', file($file));
    // error_log(json_encode($data),3,"error.log");
    $header = array_shift($data); // Get headers

    // Column indexes based on header
    $columns = array_flip($header);
    $staff_name_col = $columns['staff_name'];
    $department_col = $columns['department'];
    $is_manager_col = $columns['is_departmement_manager'];
    $is_devotion_committee_col = $columns['is_devotion_committee'];
    $old_group_col = $columns['old_group'];
    $new_group_col = $columns['new_group'];
    $staff_no_col = $columns['staff_no'];

    // Group members by old_group and devotion committee status
    $groups = [
        'total_devotion_members' => 0,
        'avg_devotion_members_in_group' => 0,
        'members_count_per_department' => [],
        'total_staff' => 0
    ];

    $newGroups = [];
    $cntStaff = 0;
    foreach ($data as &$row) {
        $old_group = $row[$old_group_col];
        $is_devotion = $row[$is_devotion_committee_col] == 'yes';
        $is_manager = $row[$is_manager_col] == 'yes';

        // Initialize group count trackers
        if (!isset($groups[$old_group])) {
            $groups[$old_group] = [
                'total' => 0, 
                'devotion_committee' => 0, 
                'managers' => 0, 
                'department_membership' => [], 
                'new_groups' => []];
        }

        // Count totals and devotion committee members
        $groups[$old_group]['total']++;
        if ($is_devotion) {
            $groups[$old_group]['devotion_committee']++;
            
            $groups['total_devotion_members']++;

            if($groups['total_devotion_members'] > 0){
                $groups['avg_devotion_members_in_group'] = ceil($groups['total_devotion_members']/$maxGroupCount);
            }
        }

        if($is_manager){
            $groups[$old_group]['managers']++; 
        }

        // By departement
        $groups[$old_group]['department_membership'][$row[$department_col]][] = $row[$staff_no_col];
        $groups['members_count_per_department'][$row[$department_col]][] = $row[$staff_no_col];
        $groups['total_staff'] = $cntStaff++;
    }

    // error_log(json_encode($groups),3,"error.log");
    function has_grouped_reached_department_limit($row, $groups, $newGroups, $maxGroupCount, $department_col, $newGroup){
        $has_grouped_reached_department_limit = false;
        $departmentStaff = isset($groups['members_count_per_department'][$row[$department_col]]) ? $groups['members_count_per_department'][$row[$department_col]] : [];
        $avgDepartmentMemberPerGroup = ceil(count($departmentStaff)/$maxGroupCount);
        // error_log(json_encode([$avgDepartmentMemberPerGroup, $row[$department_col]]),3,"error.log");
        if($newGroups != null){
            // $groups[$newGroup][]
            $departmentMembersInNewGroup = isset($newGroups[$newGroup]['department_membership'][$row[$department_col]]) ? $newGroups[$newGroup]['department_membership'][$row[$department_col]] : [];
            if(!empty($departmentMembersInNewGroup) && count($departmentMembersInNewGroup) > $avgDepartmentMemberPerGroup){
                // error_log(json_encode([$departmentMembersInNewGroup, $row[$department_col]]),3,"error.log");
                $has_grouped_reached_department_limit = true;
            }
        }

        return $has_grouped_reached_department_limit;
    }

    // Calculate new group size limits
    $max_general_in_group = $groups['total_staff'] > 0 ? $groups['total_staff']/$maxGroupCount : 0;
    $max_devotion_in_group = $groups['avg_devotion_members_in_group']; 


    // Assign new groups
    foreach ($data as &$row) {
        $old_group = $row[$old_group_col];
        $is_devotion = $row[$is_devotion_committee_col] == 'yes';
        $is_manager = $row[$is_manager_col] == 'yes';

        // Assign a new group that meets the criteria
        $can_assign = false;
        do {
            $new_group = 'Group_' . rand(1, $maxGroupCount); // Randomly pick a group (assuming groups 1 to 5)

            if(!isset($newGroups[$new_group])){
                $newGroups[$new_group] = ['general' => 0, 'devotion' => 0];
            }

            $general_count = $newGroups[$new_group]['general'];
            $devotion_count = $newGroups[$new_group]['devotion'];

            $has_grouped_reached_department_limit = has_grouped_reached_department_limit($row, $groups,$newGroups, $maxGroupCount, $department_col, $new_group);

            $can_assign = (!$is_devotion && $general_count < $max_general_in_group && !$has_grouped_reached_department_limit) ||
                          ($is_devotion && $devotion_count < $max_devotion_in_group);
            
        } while (!$can_assign);

        // Increment count in the assigned new group
        if($can_assign){
            $newGroups[$new_group][$is_devotion ? 'devotion' : 'general']++;
            $newGroups[$new_group]['department_membership'][$row[$department_col]][] = $row[$staff_no_col];
            $row[$new_group_col] = $new_group; // Assign new group in the row
        }
        
    }

    // error_log(json_encode($newGroups),3,"error.log");

    // Write the modified data to a new CSV file
    $output = fopen($outputFile, 'w');
    fputcsv($output, $header); // Write header
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);

    // Provide the file for download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $outputFile . '"');
    readfile($outputFile);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload CSV</title>

    <!--Bootstrap and JQuery CDN-->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <!--Custom CSS and JS-->
    <!-- <link rel="stylesheet" href="styles.css"> -->
    <!--Datatable CDN-->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/dt-1.10.24/datatables.min.css" />
    <script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.10.24/datatables.min.js"></script>
    <!--End of CDN-->

</head>
<body>
    <div class="container">
        <div class="row">
            <form role="form" method="post" class="form-horizontal form-groups-bordered" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label for="maxGroupCount">Max Group Count:</label>
                    <div class="col-xs-8">
                        <input type="number" name="maxGroupCount" id="maxGroupCount" value="5" min="1" required>
                    </div>
                </div>
                <!-- <div class="form-group">
                    <label for="percentageDevotionCommittee">Percentage of Devotion Committee Members:</label>
                    <div class="col-xs-8">
                        <input type="number" name="percentageDevotionCommittee" id="percentageDevotionCommittee" value="10" min="1" max="100" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="percentageOldGroupMembership">Percentage of Old Group Members:</label>
                    <div class="col-xs-8">
                        <input type="number" name="percentageOldGroupMembership" id="percentageOldGroupMembership" value="30" min="1" max="100" required>
                    </div>
                </div> -->

                <div class="form-group">
                    <label for="csv_file">Upload Current Grouping CSV File:</label>
                    <div class="col-xs-8">
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="col-xs-offset-4 col-xs-8">
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </div>
 
            </form>
            </div>
        </div>
    </div>
</body>
</html>
