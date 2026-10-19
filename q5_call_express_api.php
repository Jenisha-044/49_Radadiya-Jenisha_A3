<?php
// Express REST API નો URL (જે 3000 પોર્ટ પર ચાલશે)
$apiUrl = "http://localhost:3000/api/express-data";

// cURL અથવા file_get_contents વડે કૉલ કરવો
$response = @file_get_contents($apiUrl);

if ($response === false) {
    echo "<h2>Error: Could not connect to Express REST API.</h2>";
    echo "<p>Please start the Express server by running <b>'node server.js'</b> in your terminal.</p>";
} else {
    $data = json_decode($response, true);
    
    echo "<h2>Question 5: Call Express REST API in PHP</h2>";
    echo "<p><b>Message from Express API:</b> " . htmlspecialchars($data['message']) . "</p>";
    echo "<p><b>Timestamp:</b> " . htmlspecialchars($data['timestamp']) . "</p>";
}
?>