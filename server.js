const express = require('express');
const app = express();
const PORT = 3000;

app.use(express.json());

// Question 5 માટે Express API Endpoint
app.get('/api/express-data', (req, res) => {
    res.json({
        success: true,
        message: "Hello from Express REST API!",
        timestamp: new Date()
    });
});

// Question 6 માટે Express Server માંથી PHP API Call કરવાનું Route
app.get('/call-php-api', async (req, res) => {
    try {
        const response = await fetch('http://localhost/49_Radadiya%20Jenisha_A3/q6_php_api.php');
        const data = await response.json();
        res.json({
            message: "Response received in Express from PHP REST API:",
            data_from_php: data
        });
    } catch (error) {
        res.status(500).json({ error: "Failed to call PHP API", details: error.message });
    }
});

app.listen(PORT, () => {
    console.log(`Express Server running on http://localhost:${PORT}`);
});