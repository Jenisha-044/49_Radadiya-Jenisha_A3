<?php
require_once "db.php";

$result = $conn->query("SELECT * FROM students");

if (!$result) {
    die("Error fetching data from MySQL: " . $conn->error);
}

$xml = new SimpleXMLElement('<students/>');

while ($row = $result->fetch_assoc()) {
    $student = $xml->addChild('student');
    $student->addChild('id', $row['id']);
    $student->addChild('name', htmlspecialchars($row['name']));
    $student->addChild('course', htmlspecialchars($row['course']));
    $student->addChild('email', htmlspecialchars($row['email']));
}

// XML તરીકે સેવ કરો
if ($xml->asXML("exported_students.xml")) {
    echo "Successfully exported MySQL students table data into 'exported_students.xml'!";
} else {
    echo "Error writing XML file.";
}
?>