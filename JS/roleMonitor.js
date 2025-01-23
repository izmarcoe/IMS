$(document).ready(function() {
    function checkRole() {
        $.ajax({
            url: '/IMS/endpoint/check_role.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Check if account is deactivated
                    if (response.status === 'deactivated') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Account Deactivated',
                            text: 'Your account has been deactivated. Please contact the administrator.',
                            showConfirmButton: false,
                            timer: 2000
                        }).then(() => {
                            window.location.href = '/IMS/endpoint/logout.php';
                        });
                        return;
                    }
                    
                    // Check for role changes
                    if (response.needs_update) {
                        console.log('Role changed:', response.current_role);
                        
                        switch(response.current_role) {
                            case 'employee':
                                window.location.href = '/IMS/dashboards/employee_dashboard.php';
                                break;
                            case 'admin':
                                window.location.href = '/IMS/dashboards/admin_dashboard.php';
                                break;
                            case 'new_user':
                                window.location.href = '/IMS/home.php';
                                break;
                            default:
                                window.location.href = '/IMS/user_login.php';
                        }
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Role check failed:', error);
            }
        });
    }

    // Check role every 3 seconds
    setInterval(checkRole, 3000);
});