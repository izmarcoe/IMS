<?php
session_start();
include('./conn/conn.php');

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if the user is logged in and has the role 'new_user'
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'new_user') {
    $user_id = $_SESSION['user_id'];

    // Fetch the user's name and role from the database
    $stmt = $conn->prepare("SELECT `Fname`, `Lname`, `role` FROM `login_db` WHERE `user_id` = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch as associative array
        $fname = $row['Fname'];
        $lname = $row['Lname'];
        $role = $row['role']; // Fetch the user's role
        $user_name = htmlspecialchars($fname . " " . $lname); // Concatenate first and last name

        // Check if the user is a new user
        if ($role === 'new_user') {
?>
            <!DOCTYPE html>
            <html lang="en">

            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Welcome</title>
                <link href="./src/output.css" rel="stylesheet">
                <script>
                    // Replace the current history state
                    window.history.replaceState({
                        page: 'home'
                    }, '', '');

                    // Add a new history entry
                    window.history.pushState({
                        page: 'home'
                    }, '', '');

                    // Handle back button
                    window.addEventListener('popstate', function(event) {
                        // If trying to go back, push forward again
                        window.history.pushState({
                            page: 'home'
                        }, '', '');

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
                    <div class="container ">
                        <h1>Welcome, <?php echo $user_name; ?>!</h1>
                        <h2>You don't have access to the dashboard yet. Please contact your Admin and try again later.</h2>
                        <a class='btn btn-dark logout' href='endpoint/logout.php'>Logout</a>
                    </div>
                    <div class="image-container">
                        <img src="icons/noRole.svg" alt="Unauthorized Access">
                    </div>
                </div>
            </body>

            </html>
<?php

        } else {
            // Render the main content for other users
        }
    } else {
        // Redirect if user not found
        header("Location: http://localhost/IMS/");
        exit();
    }
} else {
    header("Location: http://localhost/IMS/");
    exit();
}
?>