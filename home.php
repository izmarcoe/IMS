<?php
session_start();
include ('./conn/conn.php');

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if the user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Fetch the user's name from the database
    $stmt = $conn->prepare("SELECT `Fname`, `Lname` FROM `login_db` WHERE `user_id` = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch as associative array
        $fname = $row['Fname'];
        $lname = $row['Lname'];
        $user_name = $fname . " " . $lname; // Concatenate first and last name
    }

    ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login System with QR Code Scanner</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="CSS/home.css">
    <script>
        // Replace the current history state
        window.history.replaceState({ page: 'home' }, '', '');
        
        // Add a new history entry
        window.history.pushState({ page: 'home' }, '', '');
        
        // Handle back button
        window.addEventListener('popstate', function(event) {
            // If trying to go back, push forward again
            window.history.pushState({ page: 'home' }, '', '');
            
            // Refresh the page to ensure latest state
            window.location.reload();
        });

        // Ensure page is fresh on load
        if (performance.navigation.type === 2) {
            location.reload(true);
        }

        // Periodic session check
        async function checkSession() {
            try {
                const response = await fetch('endpoint/check_session.php');
                const data = await response.json();
                if (!data.logged_in) {
                    window.location.href = 'http://localhost/IMS/';
                }
            } catch (error) {
                console.error('Session check failed:', error);
            }
        }

        // Check session every 30 seconds
        setInterval(checkSession, 30000);

        // Check session on page visibility change
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                checkSession();
            }
        });
    </script>
</head>
<body>
    
    <div class="main">

        <div class="container text-center">
            <h1 class="text-center">Welcome <br> <?php echo htmlspecialchars($user_name); ?>!</h1>
            <h2 class="text-center">Wait for the special admin to give you a role.</h2>
            <a class="btn btn-dark" href="endpoint/logout.php">Logout</a>
        </div>

    </div>

</body>
</html>

    <?php
    
} else {
    header("http://localhost/IMS/");
    exit();
}
?>