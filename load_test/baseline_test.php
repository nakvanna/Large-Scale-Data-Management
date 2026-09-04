<?php

// ============================================================
// Load Balancer URL
// ============================================================

$url = "http://localhost:8080/Large-Scale-Data-Management/load_test/load_balancer.php";

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
// Database connections
// ============================================================

// DB Node 1 :3306
$db = new mysqli(
    "127.0.0.1",
    "root",
    "",
    "university_db",
    3306
);

// DB Node 2 :3307
$db2 = new mysqli(
    "127.0.0.1",
    "root",
    "",
    "university_db",
    3307
);

if ($db->connect_error) {
    die(
        "Database 1 connection failed: "
        . $db->connect_error
    );
}

if ($db2->connect_error) {
    die(
        "Database 2 connection failed: "
        . $db2->connect_error
    );
}

// ============================================================
// Clear previous experiment
// ============================================================

// Clear registrations from DB1
$db->query(
    "TRUNCATE TABLE tbl_registrations"
);

// Clear registrations from DB2
$db2->query(
    "TRUNCATE TABLE tbl_registrations"
);

// Clear experiment results
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

$multi = curl_multi_init();

$handles = [];

// ============================================================
// Count requests going to each App Server node
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
    // Generate students across BOTH database shards
    //
    // Odd request  → DB1
    // Even request → DB2
    //
    // Example for 100:
    //
    // S001 → DB1
    // S251 → DB2
    // S002 → DB1
    // S252 → DB2
    // ...
    // S050 → DB1
    // S300 → DB2
    // --------------------------------------------------------

    if ($i % 2 == 1) {

        // DB1: S001 - S250
        $student_number = ($i + 1) / 2;

    } else {

        // DB2: S251 - S500
        $student_number = 250 + ($i / 2);
    }

    $student_id =
        "S" . str_pad(
            $student_number,
            3,
            "0",
            STR_PAD_LEFT
        );

    // --------------------------------------------------------
    // Test ONE course
    // --------------------------------------------------------

    $course_id = "C001";

    // --------------------------------------------------------
    // Count requests going to each App Server
    //
    // Load balancer uses the same round-robin rule:
    //
    // Odd  → App Server 1 :8080
    // Even → App Server 2 :8081
    // --------------------------------------------------------

    if ($i % 2 == 1) {

        $node1Requests++;

    } else {

        $node2Requests++;
    }

    // --------------------------------------------------------
    // Create CURL request
    // --------------------------------------------------------

    $ch = curl_init($url);

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
    // register.php returns:
    //
    // SUCCESS
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
// Get FINAL registration count
//
// IMPORTANT:
// Count registrations from BOTH database shards.
//
// DB1 + DB2 = GLOBAL registration count
// ============================================================

// ------------------------------------------------------------
// DB1 :3306
// ------------------------------------------------------------

$result1 =
    $db->query(
        "
        SELECT COUNT(*) AS total
        FROM tbl_registrations
        WHERE course_id = 'C001'
        "
    );

$row1 =
    $result1->fetch_assoc();

$db1Registrations =
    (int) $row1["total"];

// ------------------------------------------------------------
// DB2 :3307
// ------------------------------------------------------------

$result2 =
    $db2->query(
        "
        SELECT COUNT(*) AS total
        FROM tbl_registrations
        WHERE course_id = 'C001'
        "
    );

$row2 =
    $result2->fetch_assoc();

$db2Registrations =
    (int) $row2["total"];

// ------------------------------------------------------------
// Global registration count
// ------------------------------------------------------------

$finalRegistrationCount =
    $db1Registrations
    +
    $db2Registrations;

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

// ------------------------------------------------------------
// Show registration count on EACH database shard
// ------------------------------------------------------------

echo "DB1 C001 Registrations: "
    . $db1Registrations
    . PHP_EOL;

echo "DB2 C001 Registrations: "
    . $db2Registrations
    . PHP_EOL;

echo "Global C001 Registrations: "
    . $finalRegistrationCount
    . PHP_EOL;

echo "========================================"
    . PHP_EOL;

// ============================================================
// Close database connections
// ============================================================

$db->close();
$db2->close();

?>