<?php

$url = "http://localhost/Large-Scale-Data-Management/actions/register.php";

// $tests = [1, 10, 25, 50, 100];
$tests = [100];

// Database connection
$db = new mysqli(
    "127.0.0.1",
    "root",
    "",
    "university_db",
    3306
);

if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error);
}


// Clear old experiment results
$db->query("TRUNCATE TABLE tbl_registrations");
$db->query("TRUNCATE TABLE tbl_baseline_results");


$testNumber = 1;


foreach ($tests as $concurrent) {

    $multi = curl_multi_init();
    $handles = [];

    $start = microtime(true);


    // Create concurrent requests
    for ($i = 1; $i <= $concurrent; $i++) {

        // S001 ... S500
        $student_id =
            "S" . str_pad($i, 3, "0", STR_PAD_LEFT);

        // C001 ... C020
        $course_number =
            (($i - 1) % 2) + 1;

        $course_id = "C001";


        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            "student_id" => $student_id,
            "course_id" => $course_id
        ]);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_multi_add_handle($multi, $ch);

        $handles[] = $ch;
    }


    // Run requests concurrently
    do {

        curl_multi_exec($multi, $running);

        if ($running > 0) {
            curl_multi_select($multi);
        }

    } while ($running > 0);


    $end = microtime(true);


    // Count results
    $successful = 0;
    $failed = 0;


    foreach ($handles as $ch) {

        $response =
            trim(curl_multi_getcontent($ch));


        if ($response === "SUCCESS") {
            $successful++;
        } else {
            $failed++;
        }


        curl_multi_remove_handle($multi, $ch);

        curl_close($ch);
    }


    curl_multi_close($multi);


    // Calculate performance
    $time =
        $end - $start;

    $time_ms =
        $time * 1000;

    $avg_response =
        $time_ms / $concurrent;

    $throughput =
        $successful / $time;


    // Get final registration count
    $result = $db->query(
        "SELECT COUNT(*) AS total
         FROM tbl_registrations"
    );

    $row = $result->fetch_assoc();

    $finalRegistrationCount =
        (int) $row["total"];


    // Insert experiment result
    $stmt = $db->prepare(
        "INSERT INTO tbl_baseline_results
        (
            test_number,
            concurrent_requests,
            avg_response_time_ms,
            successful,
            failed,
            throughput,
            final_registration_count
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)"
    );


    $stmt->bind_param(
        "iidiiid",
        $testNumber,
        $concurrent,
        $avg_response,
        $successful,
        $failed,
        $throughput,
        $finalRegistrationCount
    );


    $stmt->execute();

    $stmt->close();


    // Display result
    echo "==============================" . PHP_EOL;

    echo "Test: $testNumber" . PHP_EOL;

    echo "Concurrent Requests: "
        . $concurrent
        . PHP_EOL;

    echo "Total Time: "
        . number_format($time_ms, 2)
        . " ms"
        . PHP_EOL;

    echo "Average Response: "
        . number_format($avg_response, 2)
        . " ms"
        . PHP_EOL;

    echo "Successful: "
        . $successful
        . PHP_EOL;

    echo "Failed: "
        . $failed
        . PHP_EOL;

    echo "Throughput: "
        . number_format($throughput, 2)
        . " req/sec"
        . PHP_EOL;

    echo "Final Registrations: "
        . $finalRegistrationCount
        . PHP_EOL;


    $testNumber++;
}


$db->close();

echo "==============================" . PHP_EOL;
echo "Baseline test completed." . PHP_EOL;

?>