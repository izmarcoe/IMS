<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
session_start();
include('./conn/conn.php');

// Check if the user is logged in and has the role 'new_user'
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    $user_id = $_SESSION['user_id'];

    // Fetch the user's current role from database
    $stmt = $conn->prepare("SELECT `Fname`, `Lname`, `role` FROM `login_db` WHERE `user_id` = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $currentRole = $row['role'];
        
        // Check if role changed from new_user to employee
        if ($_SESSION['user_role'] == 'new_user' && $currentRole == 'employee') {
            $_SESSION['user_role'] = $currentRole; // Update session role
            header("Location: ./dashboards/employee_dashboard.php");
            exit();
        }
        
        // Continue with existing new_user check
        if ($_SESSION['user_role'] == 'new_user') {
            $fname = $row['Fname'];
            $lname = $row['Lname'];
            $user_name = htmlspecialchars($fname . " " . $lname);
?>
            <!DOCTYPE html>
            <html lang="en">

            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Welcome</title>
                <link href="./src/output.css" rel="stylesheet">
                <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                <script src="./JS/roleMonitor.js"></script>
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

            <body class="bg-gradient-to-br from-green-800 via-green-600 to-green-900 min-h-screen">
                <div class="min-h-screen flex items-center justify-center px-4 backdrop-blur-sm">
                    <div class="max-w-4xl w-full flex flex-col md:flex-row items-center justify-between gap-8 p-8 bg-white/90 backdrop-blur-md rounded-2xl shadow-2xl border border-green-200/20 hover:shadow-green-500/10 transition-all duration-300">
                        <div class="text-center md:text-left space-y-6">
                            <div class="space-y-2">
                                <h1 class="text-4xl font-bold text-green-800 tracking-tight">
                                    Welcome, <span class="text-green-900"><?php echo $user_name; ?></span>!
                                </h1>
                                <h2 class="text-xl text-green-700/80 max-w-lg font-medium">
                                    You don't have access to the dashboard yet. Please contact your Admin and try again later.
                                </h2>
                            </div>
                            <a href='endpoint/logout.php' 
                               class="inline-block px-8 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transform hover:-translate-y-0.5 transition-all duration-200 shadow-lg hover:shadow-green-500/50 font-semibold">
                                Logout
                            </a>
                        </div>
                        <div class="w-full md:w-1/2 transform hover:scale-105 transition-transform duration-300">
                            <img src="icons/noRole.svg" 
                                 alt="Unauthorized Access" 
                                 class="w-full max-w-md mx-auto drop-shadow-2xl">
                        </div>
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
        header("Location: http://localhost/IMS/user_login.php");
        exit();
    }
} else {
    header("Location: http://localhost/IMS/user_login.php");
    exit();
}
?>