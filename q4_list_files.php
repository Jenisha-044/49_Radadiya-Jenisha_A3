<?php
// જે ડિરેક્ટરીની ફાઇલો જોવી હોય તેનો પાથ (વર્તમાન ફોલ્ડર માટે '.')
$directory = ".";

if (is_dir($directory)) {
    $files = scandir($directory);

    echo "<h2>Files in Directory: " . htmlspecialchars(realpath($directory)) . "</h2>";
    echo "<ul>";

    foreach ($files as $file) {
        // '.' અને '..' ને ફિલ્ટર કરો
        if ($file !== "." && $file !== "..") {
            $isDir = is_dir($directory . "/" . $file) ? " [Folder]" : " [File]";
            echo "<li>" . htmlspecialchars($file) . "<b>" . $isDir . "</b></li>";
        }
    }

    echo "</ul>";
} else {
    echo "Invalid directory.";
}
?>