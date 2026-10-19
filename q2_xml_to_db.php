<?php
require_once "db.php";

if (!file_exists("students.xml")) {
    die("Error: students.xml file not found!");
}

$xml = simplexml_load_file("students.xml");

if ($xml === false) {
    die("Error: Cannot parse XML file!");
}

$stmt = $conn->prepare("INSERT INTO students (name, course, email) VALUES (?, ?, ?)");
$inserted = 0;

foreach ($xml->student as $student) {
    $name = (string) $student->name;
    $course = (string) $student->course;
    $email = (string) $student->email;

    $stmt->bind_param("sss", $name, $course, $email);
    if ($stmt->execute()) {
        $inserted++;
    }
}

echo "Successfully imported $inserted student records from XML to MySQL database!";
$stmt->close();
?>