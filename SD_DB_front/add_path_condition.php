<?php

$success = True; //keep track of errors so it redirects the page only if there are no errors
$db_conn = OCILogon("ora_b6h1b", "a42828146", "dbhost.ugrad.cs.ubc.ca:1522/ug");

if ($db_conn) {
  if (array_key_exists('addpath', $_POST)) {
      if (is_numeric($_POST['pathcondID'])) {
        $findpathcondID = executePlainSQL("SELECT PathCondition.pathcondID
                                           FROM PathCondition
                                           WHERE PathCondition.pathcondID = {$_POST['pathcondID']}");
        if (countRows($findpathcondID) != 0) {
          $pathID = getNewID("path");
          executePlainSQL("INSERT INTO Path VALUES (
                                {$pathID},
                                '{$_POST['city']}',
                                '{$_POST['location']}',
                                '{$_POST['startpoint']}',
                                '{$_POST['endpoint']}',
                                {$_POST['pathcondID']}
                                )");
          OCICommit($db_conn);
          popWindow("add path successfully");
        }
      }
  } else if (array_key_exists('addpathcondition', $_POST)) {
      $pathcondID = getNewID("pathcondition");
      executePlainSQL("INSERT INTO PathCondition VALUES (
                            {$pathcondID},
                            '{$_POST['roadtype']}',
                            '{$_POST['weather']}',
                            '{$_POST['climate']}',
                            '{$_POST['dayornight']}'
                            )");
      OCICommit($db_conn);
      popWindow("add path condition successfully");

  }

  if ($_POST && $success) {
		//POST-REDIRECT-GET -- See http://en.wikipedia.org/wiki/Post/Redirect/Get
		header("location: add_path_condition.php");
	}

} else {
	echo "cannot connect";
	$e = OCI_Error(); // For OCILogon errors pass no handle
	echo htmlentities($e['message']);
}

// helper function

function popWindow($message)
{
    echo '<script language="javascript">alert("' . "{$message}" . '");</script>';
    header("Location:add_path_condition.php");
}

function countRows($result)
{
    $rownum = 0;
    while (OCI_Fetch_Array($result, OCI_BOTH)) {
        $rownum++;
    }
    return $rownum;
}

function printResult($result) { //prints results from a select statement
	echo "<br>Got data from table tab1:<br>";
	echo "<table>";
	echo "<tr><th>ID</th><th>Name</th></tr>";

	while ($row = OCI_Fetch_Array($result, OCI_BOTH)) {
		echo "<tr><td>" . $row["NID"] . "</td><td>" . $row["NAME"] . "</td></tr>"; //or just use "echo $row[0]"
	}
	echo "</table>";

}

function executePlainSQL($cmdstr) { //takes a plain (no bound variables) SQL command and executes it
	//echo "<br>running ".$cmdstr."<br>";
	global $db_conn, $success;
	$statement = OCIParse($db_conn, $cmdstr); //There is a set of comments at the end of the file that describe some of the OCI specific functions and how they work

	if (!$statement) {
		echo "<br>Cannot parse the following command: " . $cmdstr . "<br>";
		$e = OCI_Error($db_conn); // For OCIParse errors pass the
		// connection handle
		echo htmlentities($e['message']);
		$success = False;
	}

	$r = OCIExecute($statement, OCI_DEFAULT);
	if (!$r) {
		echo "<br>Cannot execute the following command: " . $cmdstr . "<br>";
		$e = oci_error($statement); // For OCIExecute errors pass the statementhandle
		echo htmlentities($e['message']);
		$success = False;
	} else {

	}
	return $statement;

}

function printWhole($tableName)
{
    $result = executePlainSQL("SELECT * FROM {$tableName}");
    OCICommit($db_conn);

    // echo "<br>Table [{$tableName}]:<br>";

    // table head
    $ncols = oci_num_fields($result);
    echo '<table class="table table-striped"><thead><tr>';
    for ($i = 1; $i <= $ncols; $i++) {
        $column_name = oci_field_name($result, $i);
        echo "<th>{$column_name}</th>";
    }
    echo "</tr></thead>";

    echo "<tbody>";
    // table data
    while ($row = OCI_Fetch_Array($result, OCI_BOTH)) {
        echo "<tr>";
        // checkbox
        for ($i = 0; $i < $ncols; $i++) {
            echo "<td>{$row[$i]}</td>";
        }
        echo "</tr>";
    }
    echo "</tbody>";

    echo "</table>";
}

function printWhole2()
{
    $result = executePlainSQL("SELECT Path.*, PathCondition.roadtype, PathCondition.weather, PathCondition.climate, PathCondition.dayornight FROM Path, PathCondition WHERE Path.pathcondID = PathCondition.pathcondID");
    OCICommit($db_conn);

    // echo "<br>Table [{$tableName}]:<br>";

    // table head
    $ncols = oci_num_fields($result);
    echo '<table class="table table-striped"><thead><tr>';
    for ($i = 1; $i <= $ncols; $i++) {
        $column_name = oci_field_name($result, $i);
        echo "<th>{$column_name}</th>";
    }
    echo "</tr></thead>";

    echo "<tbody>";
    // table data
    while ($row = OCI_Fetch_Array($result, OCI_BOTH)) {
        echo "<tr>";
        // checkbox
        for ($i = 0; $i < $ncols; $i++) {
            echo "<td>{$row[$i]}</td>";
        }
        echo "</tr>";
    }
    echo "</tbody>";

    echo "</table>";
}

function getNewID($name)
{
    if ($name == "path") {
        $id = "pathID";
        $table = "Path";
    } else if ($name == "pathcondition") {
        $id = "pathcondID";
        $table = "PathCondition";
    }
    $listID = 1;
    OCICommit($db_conn);

    while (1) {
        $flag = 0;
        $result = executePlainSQL("SELECT {$id} FROM {$table}");
        while ($row = OCI_Fetch_Array($result)) {
            if ($listID == $row[0]) {
                $flag = 1;
            }
        }
        if ($flag == 0) {
            break;
        }
        $listID++;
    }
    return $listID;
}
?>

<!DOCTYPE html>
<html lang="en">

  <head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Self Driving Simulator - Path & Condition</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom fonts for this template -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href='https://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800' rel='stylesheet' type='text/css'>
    <link href='https://fonts.googleapis.com/css?family=Merriweather:400,300,300italic,400italic,700,700italic,900,900italic' rel='stylesheet' type='text/css'>

    <!-- Plugin CSS -->
    <link href="vendor/magnific-popup/magnific-popup.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="css/creative.min.css" rel="stylesheet">

  </head>

  <style type="text/css">
  a.tab{
    margin-left:25px;margin-right:27px;
  }

  input.form-control{
    width:20%;
  }

  </style>

  <body id="page-top">

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNav">
      <div class="container">
        <a class="navbar-brand js-scroll-trigger" href="#page-top">Start Bootstrap</a>
        <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
          <ul class="navbar-nav ml-auto">
            <li class="nav-item">
              <a class="nav-link" href="login_page.html">Login/Sign Up</a>
            </li>
            <li class="nav-item">
              <a class="nav-link js-scroll-trigger" href="#service-window">Insert/Delete</a>
            </li>
            <li class="nav-item">
              <a class="nav-link js-scroll-trigger" href="#services">Other Services</a>
            </li>
            <li class="nav-item">
              <a class="nav-link js-scroll-trigger" href="#contact">Contact</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>

<!--     <header class="masthead text-center text-white d-flex">
      <div class="container my-auto">
        <div class="row">
          <div class="col-lg-10 mx-auto">
            <h1 class="text-uppercase">
              <strong>Your Favorite Source of Free Bootstrap Themes</strong>
            </h1>
            <hr>
          </div>
          <div class="col-lg-8 mx-auto">
            <p class="text-faded mb-5">Start Bootstrap can help you build better websites using the Bootstrap CSS framework! Just download your template and start going, no strings attached!</p>
            <a class="btn btn-primary btn-xl js-scroll-trigger" href="#about">Find Out More</a>
          </div>
        </div>
      </div>
    </header> -->

    <!-- <section class="bg-primary" id="about">
      <div class="container">
        <div class="row">
          <div class="col-lg-8 mx-auto text-center">
            <h2 class="section-heading text-white">We've got what you need!</h2>
            <hr class="light my-4">
            <p class="text-faded mb-4">Start Bootstrap has everything you need to get your new website up and running in no time! All of the templates and themes on Start Bootstrap are open source, free to download, and easy to use. No strings attached!</p>
            <a class="btn btn-light btn-xl js-scroll-trigger" href="#services">Get Started!</a>
          </div>
        </div>
      </div>
    </section> -->
    <section id="service-window">
      <div class="container">
        <ul class="nav nav-tabs">
          <li class="nav-item"><a class="nav-link active" id="path-tab" data-toggle="tab" href="#Path" role="tab" aria-controls="path" aria-selected="true">Path<br/></a></li>
          <li class="nav-item"><a class="nav-link" id="road-condition-tab" data-toggle="tab" href="#RoadCondition" role="tab" aria-controls="road-condition" aria-selected="false">road & condition<br></a></li>
        </ul>
        <br>

        <div class="tab-content">
          <div id="Path" class="tab-pane fade show active" role="tabpanel" aria-labelledby="path-tab">
            <h3>Add New Path</h3>
            <form method="POST" action="add_path_condition.php">
              <div style="width:100%; height:300px; overflow: auto">
                <?php
                  $db_conn = OCILogon("ora_b6h1b", "a42828146", "dbhost.ugrad.cs.ubc.ca:1522/ug");
                  if($db_conn){
                    $tableName = "Path";
                    echo "<h1>TESTING</h1>";
                    printWhole2();
                  }
                  ?>
              </div>
              <!-- end of table -->
              <br>
            </form>
            <br>
            <br>
            <form method="POST" action="add_path_condition.php">
              <div class="form-group">
                <label for="city-input">City:</label>
                <input type="text" class="form-control" name="city">
              </div>
              <div class="form-group">
                <label for="location-input">Location:</label>
                <input type="text" class="form-control" name="location">
              </div>
              <div class="form-group">
                <label for="start-point-input">Start Point:</label>
                <input type="text" class="form-control" name="startpoint">
              </div>
              <div class="form-group">
                <label for="end-point-input">End Point:</label>
                <input type="text" class="form-control" name="endpoint">
              </div>
              <div class="form-group">
                <label for="path-condition-input">Path Condtion ID:</label>
                <input type="text" class="form-control" name="pathcondID">
              </div>
              <input type="submit" class="btn btn-dark" value="ADD" name="addpath">
            </form>
          </div>
          <!-- end of car tab -->
          <div id="RoadCondition" class="tab-pane fade" role="tabpanel" aria-labelledby="road-condition-tab">
            <h3>Add New Path Condtion</h3>
              <div style="width:100%; height:300px; overflow: auto">
                <?php
                  $db_conn = OCILogon("ora_b6h1b", "a42828146", "dbhost.ugrad.cs.ubc.ca:1522/ug");
                  if($db_conn){
                    $tableName = "PathCondition";
                    echo "<h1>TESTING</h1>";
                    printWhole($tableName);
                  }
                  ?>
              </div>
            <br>
            <br>
            <form method="POST" action="add_path_condition.php">
              <div class="form-group">
                <label for="road-type-input">Road Type:</label>
                <input type="text" class="form-control" name="roadtype">
              </div>
              <div class="form-group">
                <label for="weather-input">Weather:</label>
                <input type="text" class="form-control" name="weather">
              </div>
              <div class="form-group">
                <label for="climate-input">Climate:</label>
                <input type="text" class="form-control" name="climate">
              </div>
              <div class="form-group">
                <label for="dayornight-input">Day Or Night:</label>
                <input type="text" class="form-control" name="dayornight">
              </div>
              <input type="submit" class="btn btn-dark" value="ADD" name="addpathcondition">
            </form>
          </div>
        </div>
      </div>
    </section>

    <section id="services">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center">
            <h2 class="section-heading">At Your Service</h2>
            <hr class="my-4">
          </div>
        </div>
      </div>
      <div class="container">
        <div class="row">
          <div class="col-lg-3 col-md-6 text-center">
            <div class="service-box mt-5 mx-auto">
              <i class="fas fa-4x fa-code text-primary mb-3 sr-icon-3"></i>
              <!-- <h3 class="mb-3">Add Software</h3> -->
              <p></p>
              <a href="index.php" class="btn btn-outline-dark" role="button" aria-disabled="true">Home</a>
              <p class="text-muted mb-0">Goto home page</p>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 text-center">
            <div class="service-box mt-5 mx-auto">
              <i class="fas fa-4x fa-gem text-primary mb-3 sr-icon-1"></i>
              <!-- <h3 class="mb-3">Add Car</h3> -->
              <p></p>
              <!-- <button type="button" class="btn btn-outline-dark" herf="add_car_device_software.html">Component</button> -->
              <a href="add_car_device_software.php" class="btn btn-outline-dark" role="button" aria-disabled="true">Component</a>
              <p class="text-muted mb-0">Add/Delete components</p>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 text-center">
            <div class="service-box mt-5 mx-auto">
              <i class="fas fa-4x fa-paper-plane text-primary mb-3 sr-icon-2"></i>
              <!-- <h3 class="mb-3">Add Device</h3> -->
              <p></p>
              <a href="add_path_condition.php" class="btn btn-outline-dark" role="button" aria-disabled="true">Path & Condition</a>
              <p class="text-muted mb-0">Add/Delete path and condition</p>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 text-center">
            <div class="service-box mt-5 mx-auto">
              <i class="fas fa-4x fa-heart text-primary mb-3 sr-icon-4"></i>
              <!-- <h3 class="mb-3">Start a Test</h3> -->
              <p></p>
              <a href="test_management.php" class="btn btn-outline-dark" role="button" aria-disabled="true">Test</a>
              <p class="text-muted mb-0">Manage Test</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="p-0" id="portfolio">
      <div class="container-fluid p-0">
        <div class="row no-gutters popup-gallery">
          <div class="col-lg-4 col-sm-6">
            <a class="portfolio-box" href="img/portfolio/fullsize/1.jpg">
              <img class="img-fluid" src="img/portfolio/thumbnails/1.jpg" alt="">
              <div class="portfolio-box-caption">
                <div class="portfolio-box-caption-content">
                  <div class="project-category text-faded">
                    Category
                  </div>
                  <div class="project-name">
                    Project Name
                  </div>
                </div>
              </div>
            </a>
          </div>
          <div class="col-lg-4 col-sm-6">
            <a class="portfolio-box" href="img/portfolio/fullsize/2.jpg">
              <img class="img-fluid" src="img/portfolio/thumbnails/2.jpg" alt="">
              <div class="portfolio-box-caption">
                <div class="portfolio-box-caption-content">
                  <div class="project-category text-faded">
                    Category
                  </div>
                  <div class="project-name">
                    Project Name
                  </div>
                </div>
              </div>
            </a>
          </div>
          <div class="col-lg-4 col-sm-6">
            <a class="portfolio-box" href="img/portfolio/fullsize/3.jpg">
              <img class="img-fluid" src="img/portfolio/thumbnails/3.jpg" alt="">
              <div class="portfolio-box-caption">
                <div class="portfolio-box-caption-content">
                  <div class="project-category text-faded">
                    Category
                  </div>
                  <div class="project-name">
                    Project Name
                  </div>
                </div>
              </div>
            </a>
          </div>
          <div class="col-lg-4 col-sm-6">
            <a class="portfolio-box" href="img/portfolio/fullsize/4.jpg">
              <img class="img-fluid" src="img/portfolio/thumbnails/4.jpg" alt="">
              <div class="portfolio-box-caption">
                <div class="portfolio-box-caption-content">
                  <div class="project-category text-faded">
                    Category
                  </div>
                  <div class="project-name">
                    Project Name
                  </div>
                </div>
              </div>
            </a>
          </div>
          <div class="col-lg-4 col-sm-6">
            <a class="portfolio-box" href="img/portfolio/fullsize/5.jpg">
              <img class="img-fluid" src="img/portfolio/thumbnails/5.jpg" alt="">
              <div class="portfolio-box-caption">
                <div class="portfolio-box-caption-content">
                  <div class="project-category text-faded">
                    Category
                  </div>
                  <div class="project-name">
                    Project Name
                  </div>
                </div>
              </div>
            </a>
          </div>
          <div class="col-lg-4 col-sm-6">
            <a class="portfolio-box" href="img/portfolio/fullsize/6.jpg">
              <img class="img-fluid" src="img/portfolio/thumbnails/6.jpg" alt="">
              <div class="portfolio-box-caption">
                <div class="portfolio-box-caption-content">
                  <div class="project-category text-faded">
                    Category
                  </div>
                  <div class="project-name">
                    Project Name
                  </div>
                </div>
              </div>
            </a>
          </div>
        </div>
      </div>
    </section>

    <section class="bg-dark text-white">
      <div class="container text-center">
        <h2 class="mb-4">Free Download at Start Bootstrap!</h2>
        <a class="btn btn-light btn-xl sr-button" href="http://startbootstrap.com/template-overviews/creative/">Download Now!</a>
      </div>
    </section>

    <section id="contact">
      <div class="container">
        <div class="row">
          <div class="col-lg-8 mx-auto text-center">
            <h2 class="section-heading">Let's Get In Touch!</h2>
            <hr class="my-4">
            <p class="mb-5">Ready to start your next project with us? That's great! Give us a call or send us an email and we will get back to you as soon as possible!</p>
          </div>
        </div>
        <div class="row">
          <div class="col-lg-4 ml-auto text-center">
            <i class="fas fa-phone fa-3x mb-3 sr-contact-1"></i>
            <p>123-456-6789</p>
          </div>
          <div class="col-lg-4 mr-auto text-center">
            <i class="fas fa-envelope fa-3x mb-3 sr-contact-2"></i>
            <p>
              <a href="mailto:your-email@your-domain.com">feedback@startbootstrap.com</a>
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Bootstrap core JavaScript -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Plugin JavaScript -->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="vendor/scrollreveal/scrollreveal.min.js"></script>
    <script src="vendor/magnific-popup/jquery.magnific-popup.min.js"></script>

    <!-- Custom scripts for this template -->
    <script src="js/creative.min.js"></script>

  </body>
</html>
