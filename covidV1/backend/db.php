<?php
// Database Connection
$conn = mysqli_connect("localhost", "root", "", "covid_system");

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>