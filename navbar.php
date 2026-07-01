<?php
error_reporting(E_ALL);

require_once 'db/config.php'; // Your MySQLi connection ($db)

$adminId = $_SESSION['login_user_id'] ?? null;


// Prevent redirect loop if this is used on index.php
$currentPage = basename($_SERVER['PHP_SELF']);

if (!$adminId) {
    if ($currentPage !== 'index.php') {
        $_SESSION['error'] = "You must be logged in.";
        header('Location: index.php');
        exit();
    }
}
$roleId = '';
$role_name = '';
$isAdmin = false;
$permissions = [];

if ($adminId) {
    // 1. Fetch admin role
    $adminQuery = "SELECT admin_role FROM admin WHERE _id = '$adminId'";
    $adminResult = mysqli_query($db, $adminQuery);

    if ($adminRow = mysqli_fetch_assoc($adminResult)) {
        $roleId = $adminRow['admin_role'];
    } else {
        $_SESSION['error'] = "Admin not found.";
        header('Location: index.php');
        exit();
    }

    // 2. Fetch role details
    $roleQuery = "SELECT * FROM roles WHERE role_id = '$roleId'";
    $roleResult = mysqli_query($db, $roleQuery);

    if ($roleRow = mysqli_fetch_assoc($roleResult)) {
        $role_name = strtolower($roleRow['role_name']); // consistent naming
        $isAdmin = ($role_name === 'admin');
    } else {
        $_SESSION['error'] = "Role not found.";
        header('Location: index.php');
        exit();
    }

    // 3. Fetch permissions using role name
    $permQuery = "
        SELECT p.title 
        FROM navigation_menus p
        INNER JOIN role_permissions rp ON p.id = rp.permission_id
        WHERE rp.role_name = '$role_name'
    ";
    $permResult = mysqli_query($db, $permQuery);

    while ($permRow = mysqli_fetch_assoc($permResult)) {
        $permissions[] = $permRow['title'];
    }
}

?>

<nav class="pcoded-navbar menupos-fixed menu-light ">
    <div class="navbar-wrapper  ">
        <div class="navbar-content scroll-div ">
            <ul class="nav pcoded-inner-navbar ">
                <li class="nav-item pcoded-menu-caption">
                    <label>Navigation</label>
                </li>
                <!-- Dashboard -->
                <?php if ($isAdmin || in_array('Dashboard', $permissions)): ?>
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link" style="background:#192f59; color:#fff;">
                            <span class="pcoded-micon"><i class="feather icon-home"></i></span>
                            <span>Dashboard</span>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Leads Menu -->
                <?php if ($isAdmin || in_array('Manage Leads', $permissions)): ?>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-edit"></i></span>
                            <span class="pcoded-mtext">Leads</span>
                        </a>
                        <ul class="pcoded-submenu">
                            <?php if ($isAdmin || in_array('All Leads', $permissions)): ?>
                                <li><a href='admission-leads.php'>All Leads</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('Verified Leads', $permissions) || in_array('All', $permissions)): ?>
                                <li>
                                    <a href="daily-calling-report.php">

                                        <span>Calling Report</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- <?php if ($isAdmin || in_array('Hot Leads', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href='hot-leads.php'>Hot Leads</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('Warm Leads', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href='warm-leads.php'>Warm Leads</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('Own Leads', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href='own-leads.php'>Own Leads</a></li>
                            <?php endif; ?> -->
                        </ul>
                    </li>
                <?php endif; ?>
                <!-- Leads Menu -->
                <!-- <?php if ($isAdmin || in_array('Manage Website Leads', $permissions)): ?>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-edit"></i></span>
                            <span class="pcoded-mtext">Website Leads</span>
                        </a>
                        <ul class="pcoded-submenu">
                            <?php if ($isAdmin || in_array('vis contact Leads', $permissions)): ?>
                                <li><a href='vis-contact-us.php'>Contact Us</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('vis career leads', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href='vis-career.php'>Career</a></li>
                            <?php endif; ?>
                            <?php if ($isAdmin || in_array('vis internship leads', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href='vis-internship.php'>Internship</a></li>
                            <?php endif; ?>

                          
                        </ul>
                    </li>
                <?php endif; ?> -->

                <!-- <?php if ($isAdmin || in_array('Facebook Leads', $permissions)): ?>
                    <li class="nav-item">
                        <a href="facebook-leadsync.php" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-edit"></i></span>
                            <span class="pcoded-mtext">Facebook Leads</span>
                        </a>
                      
                    </li>
                <?php endif; ?> -->

                <!-- Recycle Bin -->
                <?php if ($isAdmin || in_array('Recycle Bin', $permissions) || in_array('All', $permissions)): ?>
                    <li class="nav-item">
                        <a href="recycle-bin.php" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-lock"></i></span>
                            <span>Recycle Bin</span>
                        </a>
                    </li>
                <?php endif; ?>


                <!-- <?php if ($isAdmin || in_array('Add New Service', $permissions) || in_array('All Service', $permissions) || in_array('All', $permissions)): ?>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-briefcase"></i></span>
                            <span class="pcoded-mtext">Services</span>
                        </a>
                        <ul class="pcoded-submenu">
                            <?php if ($isAdmin || in_array('Add New Service', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="add-service.php">Add New Service</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('All Service', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="manage-service.php">All Services</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?> -->
                <?php if ($isAdmin || in_array('Add New Service', $permissions) || in_array('All Service', $permissions) || in_array('All', $permissions)): ?>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-briefcase"></i></span>
                            <span class="pcoded-mtext">Categories</span>
                        </a>
                        <ul class="pcoded-submenu">
                            <?php if ($isAdmin || in_array('Add New Service', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="add-parent-category.php">Add New Parent Category</a></li>
                            <?php endif; ?>
                            <?php if ($isAdmin || in_array('Add New Service', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="manage-parent-categories.php">All Categories</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('All Service', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="add-new-subcategory.php">Add New Subcategory</a></li>
                            <?php endif; ?>
                            <?php if ($isAdmin || in_array('All Service', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="manage-subcategories.php">All Subcategories</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?>
                <!-- <?php if (
                            $isAdmin ||
                            in_array('Add New Client', $permissions) ||
                            in_array('All Client', $permissions) ||
                            in_array('All Recycle', $permissions) ||
                            in_array('All', $permissions)
                        ): ?>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-user"></i></span>
                            <span class="pcoded-mtext">Clients</span>
                        </a>
                        <ul class="pcoded-submenu">
                            <?php if ($isAdmin || in_array('Add New Client', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="add-client.php">Add New Client</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('All Client', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="manage-client.php">All Clients</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('All Recycle', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="recycle-client.php">Recycle Bin</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?> -->

                <!-- <?php if ($isAdmin || in_array('Add New State', $permissions) || in_array('All State', $permissions) || in_array('All', $permissions)): ?>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-globe"></i></span>
                            <span class="pcoded-mtext">State</span>
                        </a>
                        <ul class="pcoded-submenu">
                            <?php if ($isAdmin || in_array('Add New State', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="add-state.php">Add New State</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('All State', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="manage-states.php">All States</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?> -->

                <!-- <?php if ($isAdmin || in_array('Add New City', $permissions) || in_array('All City', $permissions) || in_array('All', $permissions)): ?>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-map-pin"></i></span>
                            <span class="pcoded-mtext">City</span>
                        </a>
                        <ul class="pcoded-submenu">
                            <?php if ($isAdmin || in_array('Add New City', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="add-city.php">Add New City</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('All City', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="manage-city.php">All City</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?> -->



                <!-- <?php if ($isAdmin || in_array('Tickets', $permissions) || in_array('All', $permissions)): ?>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-bookmark"></i></span>
                            <span class="">Tickets</span>
                        </a>
                    </li>
                <?php endif; ?> -->

                <!-- <?php if ($isAdmin || in_array('Quote', $permissions) || in_array('All', $permissions)): ?>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-file-text"></i></span>
                            <span class="">Quotes</span>
                        </a>
                    </li>
                <?php endif; ?> -->


                <!-- <?php if (
                            $isAdmin ||
                            in_array('Add New Employee', $permissions) ||
                            in_array('All Employees', $permissions) ||
                            in_array('All Trainees', $permissions) ||
                            in_array('All', $permissions)
                        ): ?>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-users"></i></span>
                            <span class="pcoded-mtext">Manage Employees</span>
                        </a>
                        <ul class="pcoded-submenu">
                            <?php if ($isAdmin || in_array('Add New Employee', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="add-employee.php">Add New Employee</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('All Employees', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="manage-employees.php">All Employees</a></li>
                            <?php endif; ?>
                            <?php if ($isAdmin || in_array('All Trainees', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="manage-trainee.php">All Trainees</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?> -->
                <!-- <?php if (
                            $isAdmin ||
                            in_array('Add New Position', $permissions) ||
                            in_array('All Positions', $permissions) ||
                            in_array('Add New Responsibility', $permissions) ||
                            in_array('All Responsibilities', $permissions) ||
                            in_array('All', $permissions)
                        ): ?>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-users"></i></span>
                            <span class="pcoded-mtext">Manage Positions</span>
                        </a>
                        <ul class="pcoded-submenu">
                            <?php if ($isAdmin || in_array('Add New Position', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="add-position.php">Add New Position</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('All Positions', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="manage-positions.php">All Positions</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('Add New Responsibility', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="add-duty.php">Add New Duty</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('All Responsibilities', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="manage-duties.php">All Duties</a></li>
                            <?php endif; ?>

                        </ul>
                    </li>
                <?php endif; ?> -->
                <!-- 
                <?php if (
                    $isAdmin ||
                    in_array('Add New Offer Letter', $permissions) ||
                    in_array('All Offer Letters', $permissions) ||
                    in_array('All', $permissions)
                ): ?>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-file-text"></i></span>
                            <span class="pcoded-mtext">Manage Offer Letter</span>
                        </a>
                        <ul class="pcoded-submenu">
                            <?php if ($isAdmin || in_array('Add New Offer Letter', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="add-offer-letter.php">Add New Letter</a></li>
                            <?php endif; ?>
                            <?php if ($isAdmin || in_array('Add Content', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="add-content.php">Add Content</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('All Offer Letters', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="manage-letters.php">All Letters</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?> -->

                <!-- <?php if (
                            $isAdmin ||
                            in_array('Add Salary Slip', $permissions) ||
                            in_array('All Salary Slips', $permissions) ||
                            in_array('Requested Salary Slips', $permissions) ||
                            in_array('All', $permissions)
                        ): ?>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-file-text"></i></span>
                            <span class="pcoded-mtext">Manage Salary Slip</span>
                        </a>
                        <ul class="pcoded-submenu">
                            <?php if ($isAdmin || in_array('Add Salary Slip', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="add-salary-slip.php">Add Salary Slip</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('All Salary Slips', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="manage-salary-slip.php">All Salary Slips</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('Requested Salary Slips', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="requested-salary-slips.php">Requested Salary Slips</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?> -->

                <!-- <?php if (
                            $isAdmin ||
                            in_array('Add New Greetings', $permissions) ||
                            in_array('All Greetings', $permissions) ||
                            in_array('Add New Employee Month', $permissions) ||
                            in_array('All Employee Month', $permissions) ||
                            in_array('Add Birthday Greetings', $permissions) ||
                            in_array('All Birthday Greetings', $permissions) ||
                            in_array('Add Work Anniversary', $permissions) ||
                            in_array('All Work Anniversary', $permissions) ||
                            in_array('All', $permissions)
                        ): ?>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-file-text"></i></span>
                            <span class="pcoded-mtext">Manage Greetings</span>
                        </a>
                        <ul class="pcoded-submenu">
                            <?php if ($isAdmin || in_array('Add New Greetings', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="add-new-greeting.php">Add New Joinee Greetings</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('All Greetings', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="manage-greetings.php">All Joinee Greetings</a></li>
                            <?php endif; ?>
                            <?php if ($isAdmin || in_array('Add New Employee Month', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="add-new-employee-month.php">Add Employee Month</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('All Employee Month', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="manage-employee-month.php">All Employee Month</a></li>
                            <?php endif; ?>
                            <?php if ($isAdmin || in_array('Add Birthday Greetings', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="add-birthday-greetings.php">Add Birthday Greetings</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('All Birthday Greetings', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="manage-birthday-greetings.php">All Birthday Greetings</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('Add Work Anniversary', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="add-work-anniversary.php">Add Work Anniversary</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('All Work Anniversary', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="manage-work-anniversary.php">All Work Anniversary</a></li>
                            <?php endif; ?>
                        </ul>

                    </li>
                <?php endif; ?> -->
                <!-- <?php if (
                            $isAdmin ||
                            in_array('Add New Employee Month', $permissions) ||
                            in_array('All Employee Month', $permissions) ||
                            in_array('All', $permissions)
                        ): ?>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-file-text"></i></span>
                            <span class="pcoded-mtext">Employee of the Month</span>
                        </a>
                        <ul class="pcoded-submenu">
                            <?php if ($isAdmin || in_array('Add New Employee Month', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="add-new-employee-month.php">Add Employee Month</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('All Employee Month', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="manage-employee-month.php">All Employee Month</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?> -->
                <!-- <?php if (
                            $isAdmin ||
                            in_array('Manage Certificates', $permissions) ||
                            in_array('Add New Internship Letter', $permissions) ||
                            in_array('All Internship Letters', $permissions) ||
                            in_array('Add New Experience Letter', $permissions) ||
                            in_array('All Experience Letters', $permissions) ||
                            in_array('All', $permissions)
                        ): ?>
                    <li class="nav-item pcoded-hasmenu">
                            <?php if ($isAdmin || in_array('Manage Certificates', $permissions) || in_array('All', $permissions)): ?>
                        <a href="#!" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-file-text"></i></span>
                            <span class="pcoded-mtext">Manage Certificates</span>
                        </a>
                        <?php endif; ?>

                        <ul class="pcoded-submenu">
                            <?php if ($isAdmin || in_array('Add New Internship Letter', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="add-internship-letter.php">Add Intership Letter</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('All Internship Letters', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="manage-internship-letters.php">All Internship Letters</a></li>
                            <?php endif; ?>
                            <?php if ($isAdmin || in_array('Add New Experience Letter', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="add-experience-letter.php">Add New Experience Letter</a></li>
                            <?php endif; ?>
                            <?php if ($isAdmin || in_array('All Experience Letters', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="manage-experience-letters.php">All Experience Letters</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?> -->
                <!-- <?php if (
                            $isAdmin ||
                            in_array('Add Id Card', $permissions) ||
                            in_array('All Id Cards', $permissions)
                        ): ?>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-file-text"></i></span>
                            <span class="pcoded-mtext">Manage Id Card</span>
                        </a>
                        <ul class="pcoded-submenu">
                            <?php if ($isAdmin || in_array('Add Id Card', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="add-id-card.php">Add Id Card</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('All Id Cards', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="manage-id-cards.php">All Id Card</a></li>
                            <?php endif; ?>
                            
                        </ul>

                    </li>
                <?php endif; ?> -->
                <!-- <?php if (
                            $isAdmin ||
                            in_array('Add Event', $permissions) ||
                            in_array('All Events', $permissions)
                        ): ?>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-file-text"></i></span>
                            <span class="pcoded-mtext">Manage Event </span>
                        </a>
                        <ul class="pcoded-submenu">
                            <?php if ($isAdmin || in_array('Add Event', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="add-event.php">Add Event</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('All Events', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="manage-events.php">All Events</a></li>
                            <?php endif; ?>
                            
                        </ul>

                    </li>
                <?php endif; ?>
 -->



                <?php if (
                    $isAdmin ||
                    in_array('Add New DPR', $permissions) ||
                    in_array('All DPR', $permissions)
                ): ?>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-users"></i></span>
                            <span class="pcoded-mtext">DPR</span>
                        </a>
                        <ul class="pcoded-submenu">
                            <?php if ($isAdmin || in_array('Add New DPR', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="dpr-form.php">Add DPR</a></li>
                            <?php endif; ?>
                            <?php if ($isAdmin || in_array('All DPR', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="view-dpr.php">View DPR</a></li>
                            <?php endif; ?>

                        </ul>
                    </li>
                <?php endif; ?>
                <?php if (
                    $isAdmin ||
                    in_array('Add New Admin User', $permissions) ||
                    in_array('All Admin Users', $permissions) ||
                    in_array('All', $permissions)
                ): ?>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-users"></i></span>
                            <span class="pcoded-mtext">Admin Users</span>
                        </a>
                        <ul class="pcoded-submenu">
                            <?php if ($isAdmin || in_array('Add New Admin User', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="add-user.php">Add New Admin User</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('All Admin Users', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="manage-user.php">All Admin Users</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?>


                <?php if (
                    $isAdmin ||
                    in_array('Add New Role', $permissions) ||
                    in_array('All Roles', $permissions) ||
                    in_array('All', $permissions)
                ): ?>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-users"></i></span>
                            <span class="pcoded-mtext">Admin Roles</span>
                        </a>
                        <ul class="pcoded-submenu">
                            <?php if ($isAdmin || in_array('Add New Role', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="add-role.php">Add New Role</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('All Roles', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="manage-role.php">All Roles</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if (
                    $isAdmin ||
                    in_array('Add New Permission', $permissions) ||
                    in_array('All Permissions', $permissions) ||
                    in_array('All', $permissions)
                ): ?>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-users"></i></span>
                            <span class="pcoded-mtext">Admin Permissions</span>
                        </a>
                        <ul class="pcoded-submenu">
                            <?php if ($isAdmin || in_array('Add New Permission', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="add-permission.php">Add New Permission</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('All Permissions', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="manage-permission.php">All Permissions</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?>



                <?php if ($isAdmin || in_array('Change Password', $permissions) || in_array('All', $permissions)): ?>
                    <li class="nav-item">
                        <a href="changepass.php" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-lock"></i></span>
                            <span class="">Change Password</span>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- ✅ Employee-specific Options -->
                <?php if ($role_name === 'employee'): ?>
                    <?php if ($isAdmin || in_array('View Profile', $permissions)): ?>
                        <li class="nav-item">
                            <a href="view-profile.php" class="nav-link">
                                <span class="pcoded-micon"><i class="feather icon-user"></i></span>
                                <span>View Profile</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($isAdmin || in_array('Request Salary', $permissions)): ?>
                        <li class="nav-item">
                            <a href="request-slip.php" class="nav-link">
                                <span class="pcoded-micon"><i class="feather icon-file-text"></i></span>
                                <span>Request Salary Slip</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($isAdmin || in_array('Change Emp Password', $permissions)): ?>
                        <li class="nav-item">
                            <a href="changepass.php" class="nav-link">
                                <span class="pcoded-micon"><i class="feather icon-lock"></i></span>
                                <span>Change Password</span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>



                <?php if (
                    $isAdmin ||
                    in_array('General Settings', $permissions) ||
                    in_array('Website Settings', $permissions) ||
                    in_array('System Settings', $permissions) ||
                    in_array('Financial Settings', $permissions) ||
                    in_array('All', $permissions)
                ): ?>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-settings"></i></span>
                            <span class="pcoded-mtext">Settings</span>
                        </a>
                        <ul class="pcoded-submenu">
                            <?php if ($isAdmin || in_array('General Settings', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="profile.php">General Settings</a></li>
                            <?php endif; ?>

                            <?php if ($isAdmin || in_array('System Settings', $permissions) || in_array('All', $permissions)): ?>
                                <li><a href="system-settings.php">System Settings</a></li>
                            <?php endif; ?>

                            <!-- Uncomment when needed -->
                            <!--
            <?php if ($isAdmin || in_array('Website Settings', $permissions) || in_array('All', $permissions)): ?>
                <li><a href="company-settings.php">Website Settings</a></li>
            <?php endif; ?>

            <?php if ($isAdmin || in_array('Financial Settings', $permissions) || in_array('All', $permissions)): ?>
                <li><a href="financial-settings.php">Financial Settings</a></li>
            <?php endif; ?>
            -->
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if ($isAdmin || in_array('User Logs', $permissions)): ?>
                    <li class="nav-item">
                        <a href="user-logs.php" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-lock"></i></span>
                            <span>User Logs</span>
                        </a>
                    </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a href="logout.php" class="nav-link" style="background:#192f59; color:#fff;">
                        <span class="pcoded-micon"><i class="feather icon-power"></i></span>
                        <span class="">Log out</span>
                    </a>
                </li>

            </ul>


        </div>
    </div>
</nav>