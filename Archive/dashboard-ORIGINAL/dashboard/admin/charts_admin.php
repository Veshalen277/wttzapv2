<?php
include "../header.php";
include "../functions.php";
$user = isset($_SESSION['u_data']) ? $_SESSION['u_data'] : '';



?>

<div class="container-fluid">
    <div class="row">
<div class="col-md-3 col-sm-12">
<?php include '../inc/sidebar.php';?>
</div>
<div class="col-md-9 col-sm-12">
<div class="container-fluid">
<div class="row">
<div class="col-md-9 col-sm-12">
<div id="page-wrapper">
<div class="container-fluid">
<!-- Page Heading -->
<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header">Welcome to Admin</h1>
    </div>
</div>
<!-- /.row -->

<!-- Cards for different sections -->
<div class="row">
    <div class="col-lg-3 col-md-6">
        <div class="card text-white bg-primary mb-3">
            <div class="card-header">
                <div class="row">
                    <div class="col-3">
                        <i class="fa fa-file-text fa-5x"></i>
                    </div>
                    <div class="col-9 text-end">
                        <div class="h1"><?php echo $leave_count; ?></div>
                        <div>Leave Applications</div>
                    </div>
                </div>
            </div>
            <a href="../users/leave-neg.php" class="text-white">
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <span class="small">View Details</span>
                    <span><i class="fa fa-arrow-circle-right"></i></span>
                </div>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card text-white bg-success mb-3">
            <div class="card-header">
                <div class="row">
                    <div class="col-3">
                        <i class="fa fa-comments fa-5x"></i>
                    </div>
                    <div class="col-9 text-end">
                        <div class="h1"><?php echo $report_count; ?></div>
                        <div>Reports</div>
                    </div>
                </div>
            </div>
            <a href="view_reports.php" class="text-white">
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <span class="small">View Details</span>
                    <span><i class="fa fa-arrow-circle-right"></i></span>
                </div>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card text-white bg-warning mb-3">
            <div class="card-header">
                <div class="row">
                    <div class="col-3">
                        <i class="fa fa-shopping-cart fa-5x"></i>
                    </div>
                    <div class="col-9 text-end">
                        <div class="h1"><?php echo $task_count; ?></div>
                        <div>Orders</div>
                    </div>
                </div>
            </div>
            <a href="orders.php" class="text-white">
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <span class="small">View Details</span>
                    <span><i class="fa fa-arrow-circle-right"></i></span>
                </div>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card text-white bg-danger mb-3">
            <div class="card-header">
                <div class="row">
                    <div class="col-3">
                        <i class="fa fa-user fa-5x"></i>
                    </div>
                    <div class="col-9 text-end">
                        <div class="h1"><?php echo $user_count; ?></div>
                        <div>Users</div>
                    </div>
                </div>
            </div>
            <a href="users_list.php" class="text-white">
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <span class="small">View Details</span>
                    <span><i class="fa fa-arrow-circle-right"></i></span>
                </div>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card text-white bg-dark mb-3">
            <div class="card-header">
                <div class="row">
                    <div class="col-3">
                        <i class="fa fa-list fa-5x"></i>
                    </div>
                    <div class="col-9 text-end">
                        <div class="h1"><?php echo $work_count; ?></div>
                        <div>Work Records</div>
                    </div>
                </div>
            </div>
            <a href="assigned_work.php" class="text-white">
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <span class="small">View Details</span>
                    <span><i class="fa fa-arrow-circle-right"></i></span>
                </div>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card text-white bg-danger mb-3">
            <div class="card-header">
                <div class="row">
                    <div class="col-3">
                        <i class="fa fa-comments fa-5x"></i>
                    </div>
                    <div class="col-9 text-end">
                        <div class="h1"><?php echo $suggestion; ?></div>
                        <div>Suggestions</div>
                    </div>
                </div>
            </div>
            <a href="suggestion_board.php" class="text-white">
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <span class="small">View Details</span>
                    <span><i class="fa fa-arrow-circle-right"></i></span>
                </div>
            </a>
        </div>
    </div>
</div>
<!-- /.row -->
</div>
<!-- /.container-fluid -->
</div>
<!-- /#page-wrapper -->
</div>
</div>
<div id="wrapper border border-1">
<!-- Tab Navigation -->
<ul class="nav nav-tabs" role="tablist">
<li class="nav-item">
<a class="nav-link active" data-bs-toggle="tab" href="#dashboard1">Income Reports</a>
</li>
<li class="nav-item">
<a class="nav-link" data-bs-toggle="tab" href="#dashboard2">Work Reports</a>
</li>
</ul>

<div class="tab-content">
<div id="dashboard1" class="tab-pane fade show active">
<div class="row">
<div class="col-lg-12">
    <h1 class="page-header">Income</h1>
    <h3 class="text-center">Income Reports</h3>
    <div id="columnchart_material" style="width: 100%; height: 500px;"></div>
    <?php 
        function getReportStatistics() {
            global $con;
            $query = "SELECT 
                        COUNT(*) AS total_reports,
                        SUM(total_income) AS total_income,
                        SUM(total_expenses) AS total_expenses,
                        SUM(net_total) AS total_net
                    FROM reports WHERE status = 'active'";
            $result = mysqli_query($con, $query);
            if (!$result) {
                die("Database query failed: " . mysqli_error($con));
            }
            return mysqli_fetch_assoc($result);
        }

        $report_stats = getReportStatistics();
        $total_reports = $report_stats['total_reports'];
        $total_income = $report_stats['total_income'];
        $total_expenses = $report_stats['total_expenses'];
        $total_net = $report_stats['total_net'];
    ?>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawChart);
        function drawChart() {
            var data = google.visualization.arrayToDataTable([
                ['Metric', 'Value'],
                ['Total Reports', <?php echo $total_reports; ?>],
                ['Total Income', <?php echo $total_income; ?>],
                ['Total Expenses', <?php echo $total_expenses; ?>],
                ['Net Total', <?php echo $total_net; ?>]
            ]);
            var options = {
                title: 'Reports Statistics',
                is3D: true
            };
            var chart = new google.visualization.PieChart(document.getElementById('columnchart_material'));
            chart.draw(data, options);
        }
    </script>
</div>
</div>
</div>
<div id="dashboard2" class="tab-pane fade">
<div class="row">
<div class="col-lg-12">
    <h1 class="page-header">Work</h1>
    <h3 class="text-center">Work Table Statistics</h3>
    <div id="columnchart_material2" style="width: 100%; height: 500px;"></div>
    <script type="text/javascript">
            google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawChart2);
        function drawChart2() {
            var data = google.visualization.arrayToDataTable([
                ['Data', 'Count'],
                ['Total Tasks', <?php echo $total_tasks; ?>],
                ['Total Employees', <?php echo $total_employees; ?>]
            ]);
            var options = {
                title: 'Work Table Statistics',
                is3D: true
            };
            var chart = new google.visualization.PieChart(document.getElementById('columnchart_material2'));
            chart.draw(data, options);
        }
    </script>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>



<!-- Include footer -->
<?php include "../footer.php"; ?>
