<?php


// ============================================================
// Load Balancer URL
// ============================================================

$url =
    "http://localhost:8080/Large-Scale-Data-Management/load_test/load_balancer.php";


// ============================================================
// TEST ONE WORKLOAD AT A TIME
//
// Change only this value:
//
// 1
// 10
// 25
// 50
// 100
//
// ============================================================

$tests = 100;


// ============================================================
// Database connection
// ============================================================

$db = new mysqli(
    "127.0.0.1",
    "root",
    "",
    "university_db",
    3306
);


if ($db->connect_error) {

    die(
        "Database connection failed: "
        . $db->connect_error
    );
}


// ============================================================
// Clear previous experiment
// ============================================================

$db->query(
    "TRUNCATE TABLE tbl_registrations"
);

$db->query(
    "TRUNCATE TABLE tbl_baseline_results"
);


// ============================================================
// Set concurrency
// ============================================================

$concurrent = $tests;


// ============================================================
// Create CURL multi handler
// ============================================================

$multi =
    curl_multi_init();

$handles = [];


// ============================================================
// Count requests going to each app node
// ============================================================

$node1Requests = 0;
$node2Requests = 0;


// ============================================================
// Start timer
// ============================================================

$start = microtime(true);


// ============================================================
// Create concurrent requests
// ============================================================

for (
    $i = 1;
    $i <= $concurrent;
    $i++
) {


    // --------------------------------------------------------
    // Student ID
    //
    // S001 ... S100
    // --------------------------------------------------------

    $student_id =
        "S" . str_pad(
            $i,
            3,
            "0",
            STR_PAD_LEFT
        );


    // --------------------------------------------------------
    // Test ONE course
    // --------------------------------------------------------

    $course_id = "C001";


    // --------------------------------------------------------
    // Count load balancing
    // --------------------------------------------------------

    if ($i % 2 == 1) {

        $node1Requests++;

    } else {

        $node2Requests++;
    }


    // --------------------------------------------------------
    // Create CURL request
    // --------------------------------------------------------

    $ch =
        curl_init($url);


    curl_setopt(
        $ch,
        CURLOPT_POST,
        true
    );


    curl_setopt(
        $ch,
        CURLOPT_POSTFIELDS,
        [
            "request" =>
                $i,

            "student_id" =>
                $student_id,

            "course_id" =>
                $course_id,

            // Tell register.php
            // this is a load test
            "test_mode" =>
                1
        ]
    );


    curl_setopt(
        $ch,
        CURLOPT_RETURNTRANSFER,
        true
    );


    curl_setopt(
        $ch,
        CURLOPT_TIMEOUT,
        30
    );


    curl_multi_add_handle(
        $multi,
        $ch
    );


    $handles[] = $ch;
}


// ============================================================
// Execute concurrent requests
// ============================================================

do {

    curl_multi_exec(
        $multi,
        $running
    );


    if ($running > 0) {

        curl_multi_select(
            $multi
        );
    }

} while ($running > 0);


// ============================================================
// Stop timer
// ============================================================

$end = microtime(true);


// ============================================================
// Count successful / failed
// ============================================================

$successful = 0;
$failed = 0;


foreach ($handles as $ch) {


    $response =
        trim(
            curl_multi_getcontent($ch)
        );


    // --------------------------------------------------------
    // IMPORTANT:
    //
    // register.php returns exactly:
    //
    // SUCCESS
    // or
    // FAILED
    // --------------------------------------------------------

    if ($response === "SUCCESS") {

        $successful++;

    } else {

        $failed++;
    }


    curl_multi_remove_handle(
        $multi,
        $ch
    );


    curl_close($ch);
}


curl_multi_close(
    $multi
);


// ============================================================
// Calculate performance
// ============================================================

$totalTime =
    $end - $start;


$totalTimeMs =
    $totalTime * 1000;


$averageResponse =
    $totalTimeMs / $concurrent;


$throughput =
    $successful / $totalTime;


// ============================================================
// Get final registration count
// ============================================================

$result =
    $db->query(
        "
        SELECT COUNT(*) AS total
        FROM tbl_registrations
        WHERE course_id = 'C001'
        "
    );


$row =
    $result->fetch_assoc();


$finalRegistrationCount =
    (int) $row["total"];


// ============================================================
// Save experiment result
// ============================================================

$stmt =
    $db->prepare(
        "
        INSERT INTO tbl_baseline_results
        (
            concurrent_requests,
            avg_response_time_ms,
            successful,
            failed,
            throughput,
            final_registration_count
        )
        VALUES (?, ?, ?, ?, ?, ?)
        "
    );


$stmt->bind_param(
    "idiiid",
    $concurrent,
    $averageResponse,
    $successful,
    $failed,
    $throughput,
    $finalRegistrationCount
);


$stmt->execute();

$stmt->close();


// ============================================================
// Display result
// ============================================================

echo PHP_EOL;

echo "========================================"
    . PHP_EOL;

echo "FOR UPDATE CONCURRENCY TEST"
    . PHP_EOL;

echo "========================================"
    . PHP_EOL;

echo "Concurrent Requests: "
    . $concurrent
    . PHP_EOL;

echo "Course: C001"
    . PHP_EOL;

echo "Node 1 Requests: "
    . $node1Requests
    . PHP_EOL;

echo "Node 2 Requests: "
    . $node2Requests
    . PHP_EOL;

echo "Total Time: "
    . number_format(
        $totalTimeMs,
        2
    )
    . " ms"
    . PHP_EOL;

echo "Average Response: "
    . number_format(
        $averageResponse,
        2
    )
    . " ms"
    . PHP_EOL;

echo "Successful: "
    . $successful
    . PHP_EOL;

echo "Failed: "
    . $failed
    . PHP_EOL;

echo "Throughput: "
    . number_format(
        $throughput,
        2
    )
    . " req/sec"
    . PHP_EOL;

echo "Final C001 Registrations: "
    . $finalRegistrationCount
    . PHP_EOL;

echo "========================================"
    . PHP_EOL;


// ============================================================
// Close database
// ============================================================

$db->close();

?>