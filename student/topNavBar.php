<?php
$Student_user = $_SESSION['stud_fname'];
$stud_id = $_SESSION['stud_id'];
require '../api/db-connect.php';
?>


<nav class="navbar bg-primary fixed-top" style="box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.4);">


    <div class="container-fluid">


        <li class="sidebar-item">
            <a href="dashboard.php" class="sidebar-link">
                <!-- <i class="fa-solid fa-house"></i> -->
                <i class="lni lni-home"></i>

            </a>
        </li>
        <li title="Profile" class="sidebar-item">
            <a href="profile.php" class="sidebar-link">
                <i class="lni lni-user"></i>
            </a>
        </li>

        <li title="Report" class="sidebar-item">
            <a href="report_questions.php" class="sidebar-link">
                <i class="lni lni-popup"></i>
            </a>
        </li>
        <!-- <li class="sidebar-item">
            <a href="leaderboards-tests.php" class="sidebar-link">
                <i class="lni lni-bar-chart"></i>
            </a>
        </li> -->

        <li class="navbar-toggler custom-toggler btn btn-outline-light" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars"></i>
        </li>

       
        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel" style="width: 70%; border-top-left-radius: 12px; border-bottom-left-radius: 12px;">

            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="offcanvasNavbarLabel">Student</h5>
                <button type="button" class="btn-close btn-close-red" data-bs-dismiss="offcanvas" aria-label="Close Navigation Menu">

                </button>
            </div>

            <div class="offcanvas-body">
                <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
                    <li class="nav-item">
                        <a class="active" href="#"><i class="fa fa-home"></i></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Link</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Dropdown
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Action</a></li>
                            <li><a class="dropdown-item" href="#">Another action</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="#">Something else here</a></li>



                        </ul>
                    </li>

                </ul>



            </div>


        </div>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>