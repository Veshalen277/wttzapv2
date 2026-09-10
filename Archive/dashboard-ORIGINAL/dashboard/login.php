<?php session_start(); ?>

<?php include "header.php" ?>

<?php

if (isset($_POST['signin'])) {
    $email_username = $_POST['email_username'];
    $password = $_POST['password'];

    // Escape inputs to prevent SQL injection
    $email_username = mysqli_real_escape_string($conn, $email_username);
    $password = mysqli_real_escape_string($conn, $password);

    // Hash the password for secure comparison if your database stores hashed passwords
    // $password = md5($password); // Uncomment if passwords are stored as MD5 hash

    $query = "SELECT * FROM users WHERE (email = '$email_username' OR username = '$email_username') AND password = '$password'";
    $user = mysqli_query($conn, $query);

    if (!$user) {
        die('Query Failed: ' . mysqli_error($conn));
    }

    $row = mysqli_fetch_assoc($user);
    
    // if ($row) {
    //     // Storing the value in session
    //     $_SESSION['id'] = $row['ID'];
    //     $_SESSION['name'] = $row['username'];
    //     $_SESSION['email'] = $row['email'];

    //     // Redirect to dashboard
    //     header('Location: reports/reports_index.php?user_id=' . $row['ID']);
    // } else {
    //     // Redirect back to login on failure
    //     header('Location: login.php');
    // }
    if ($row) {
    // Storing the value in session
    $_SESSION['id'] = $row['ID'];
    $_SESSION['name'] = $row['username'];
    $_SESSION['email'] = $row['email'];
    
    if ($row['is_admin'] == 1) {
        // Redirect admins to dashboard
        header('Location: dashboard.php');
    } else {
        // Redirect regular users to their reports page
        header('Location: reports/reports_index.php?user_id=' . $row['ID']);
    }
} else {
    // Redirect back to login on failure
    header('Location: login.php');
}
}

// if (isset($_POST['signin'])) {

//   $email = $_POST['email'];

//   $password = $_POST['password'];



//   $query = "SELECT * from users WHERE email = '$email' AND password = '$password'";

//   $user = mysqli_query($conn, $query);



//   if (!$user) {

//     die('query Failed' . mysqli_error($conn));

//   }



//   while ($row = mysqli_fetch_array($user)) {



//     $user_id = $row['ID'];

//     $user_name = $row['username'];

//     $user_email = $row['email'];

//     $user_password = $row['password'];

//   }

//   if ($user_email == $email  &&  $user_password == $password) {



//     $_SESSION['id'] = $user_id;       // Storing the value in session

//     $_SESSION['name'] = $user_name;   // Storing the value in session

//     $_SESSION['email'] = $user_email; // Storing the value in session

//     //! Session data can be hijacked. Never store personal data such as password, security pin, credit card numbers other important data in $_SESSION

// //    header('location: dashboard.php?user_id=' . $user_id);

//     header('location: dashboard.php?user_id=' . $user_id);

//   } else {

//     header('location: login.php');

//   }

// }

?>


<!-- 
<div class="container col-4 border rounded bg-light mt-5" style='--bs-bg-opacity: .5;'>

  <h1 class="text-center">Sign In</h1>

  <hr>

  <form action="" method="post">

    <div class="mb-3">

      <label for="email" class="form-label">Email ID</label>

      <input type="email" class="form-control" name="email" placeholder="Enter your email" autocomplete="off" required>

      <small class="text-muted">Your email is safe with us.</small>

    </div>

    <div class="mb-3">

      <label for="password" class="form-label">Password</label>

      <input type="password" class="form-control" name="password" placeholder="Enter your password" required>

      <small class="text-muted">Do not share your password.</small>

    </div>

    <div class="mb-3">

      <input type="submit" name="signin" value="Sign In" class="btn btn-primary">

    </div>

  </form>

</div> -->
<div class="container col-4 border rounded bg-light mt-5" style='--bs-bg-opacity: .5;'>
  <h1 class="text-center">Sign In</h1>
  <hr>
  <form action="" method="post">
    <div class="mb-3">
      <label for="email_username" class="form-label">Email or Username</label>
      <input type="text" class="form-control" name="email_username" placeholder="Enter your email or username" autocomplete="off" required>
      <small class="text-muted">Your email or username is safe with us.</small>
    </div>
    <div class="mb-3">
      <label for="password" class="form-label">Password</label>
      <input type="password" class="form-control" name="password" placeholder="Enter your password" required>
      <small class="text-muted">Do not share your password.</small>
    </div>
    <div class="mb-3">
      <input type="submit" name="signin" value="Sign In" class="btn btn-primary">
    </div>
  </form>
</div>



<?php include "footer.php" ?>