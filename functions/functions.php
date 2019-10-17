<?php 

require "./vendor/autoload.php";

                                                    // HELPER FUNCTIONS

function clean($string) {

	return htmlentities($string);
}


function redirect($location){

	return header("Location: {$location}");
}


function set_message($message){

	if(!empty($message)){
		$_SESSION['message'] = $message;
	} else {
		$message = "";
	}
}


function display_message(){
	if(isset($_SESSION['message'])) {
		echo $_SESSION['message'];
		unset ($_SESSION['message']);
	}
}


function token_generator(){  //works together with recover.php. Check at the end which has the input type hidden and echo the value out

	$token = $_SESSION['token'] = md5(uniqid(mt_rand(), true)); //creates unique id with a random number as a prefix. more secure than a static prefix.
	return $token;
}

 //This is heredoc syntax.You can replace DELIMITER with any word of your choice. It helps in doing some things in PHP without concatenation.
function validation_errors($error_message){
	$error_message = <<<DELIMITER
       <div class="alert alert-danger alert-dismissible" role="alert">
       <button type = "button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
       <strong>Warning!</strong> $error_message
       </div> 
DELIMITER;
return $error_message;
}


function email_exists($email){
	$sql = "SELECT id FROM users WHERE email = '$email'";
	$result = query($sql);  // Note this query function have already been created in db.php
     
     if(row_count($result) == 1) {
     	return true;
     } else {
     	return false;
     }
}

function username_exists($username){
	$sql = "SELECT id FROM users WHERE username = '$username'";
	$result = query($sql);  // Note this query function have already been created in db.php
     
     if(row_count($result) == 1) {
     	return true;
     } else {
     	return false;
     }
}

function send_email($email=null, $subject=null, $msg=null, $headers=null){

$mail = new PHPMailer(); //This is instantiation while if a variable is given to the phpmailer(), it is called initialization

//$mail->SMTPDebug = 3;                               // Enable verbose debug output
//TO SEE where Config was defined,go to config.php
$mail->isSMTP();                                      // Set mailer to use SMTP
$mail->Host = Config::SMTP_HOST;                 // Specify main and backup SMTP servers
$mail->Username = Config::SMTP_USER;                 // SMTP username
$mail->Password = Config::SMTP_PASSWORD;                           // SMTP password
$mail->Port = Config::SMTP_PORT;                                    // TCP port to connect to
$mail->SMTPAuth = true;                               // Enable SMTP authentication
$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
$mail->isHTML(true);                                 // Telling phpmailer to send the message using html
$mail->Charset = 'UTF-8';            // Setting characters that are not English to English i.e Setting Multilanguage email body 

$mail->setFrom('Onyejefucollins@gmail.com', 'Webmaster');
$mail->addAddress($email);     // Add a recipient 

$mail->Subject = $subject;
$mail->Body    = $msg;
$mail->AltBody = $msg;

if(!$mail->send()) {
    echo 'Message could not be sent.';
    echo 'Mailer Error: ' . $mail->ErrorInfo;
} else {
    echo 'Message has been sent';
}

	 // return mail($email,$subject,$msg,$headers);

	}

                               // VALIDATION FUNCTIONS

function validate_user_registration(){   // Here also contain email activation messege that reads :please check your email for activation link
    $errors = [];

    $min = 3;
    $max = 20;

	if($_SERVER['REQUEST_METHOD'] == "POST") {
        $first_name             = clean($_POST['first_name']);
        $last_name              = clean($_POST['last_name']);
        $username               = clean($_POST['username']);
        $email                  = clean($_POST['email']);
        $password               = clean($_POST['password']);
        $confirm_password       = clean($_POST['confirm_password']);

        if(strlen($first_name) < $min) {
        	$errors[] = "Your first name cannot be less than {$min} characters";  //Please note that $errors[] is an index arrays
        }

        if(strlen($first_name) > $max) {
        	$errors[] = "Your first name cannot be more than {$max} characters";
        }


         if(strlen($last_name) < $min) {
        	$errors[] = "Your last name cannot be less than {$min} characters";
        }

        if(strlen($last_name) > $max) {
        	$errors[] = "Your last name cannot be more than {$max} characters";
        }

        if(strlen($username) < $min) {
        	$errors[] = "Your username cannot be less than {$min} characters";
        }

        if(strlen($username) > $max) {
        	$errors[] = "Your username cannot be more than {$max} characters";
        }

        if (username_exists($username)){
        	$errors[] = "Sorry,username already taken"; 
        }

        if (email_exists($email)){
        	$errors[] = "Sorry,this email already exists"; 
        }

        if(strlen($email) < $min) {
        	$errors[] = "Your email cannot be less than {$min} characters";
        }

        if($password !== $confirm_password) {
        	$errors[] = "Your password fields do not match";
        }
        //This is heredoc syntax.You can replace DELIMITER with any word of your choice. It helps in doing some things in PHP without concatenation.
        if(!empty($errors)) {
        	foreach ($errors as $error){  //syntax is looping through array e.g foreach($array as $value)
        	echo validation_errors($error); //invoked function syntax located on helper functions and store looping throug the values
        	}
        } else {
        	if(register_user($first_name,$last_name,$username,$email,$password)){
                 set_message("<p class='bg-success text-center'> Please check your email or spam folder for activation link</p>");
                 redirect("index.php"); // to show this go to index.php and do something. It is found under the jumbertron,class text-center

        	} else{
        		set_message("<p class='bg-danger text-center'>Sorry we could not register the user</p>");
            redirect("index.php");
        	}
        }

			} // Post request
}// function
 
                        // REGISTER USER FUNCTION
function register_user($first_name,$last_name,$username,$email,$password){

    $first_name = escape($first_name);
    $last_name  = escape($last_name);
    $username  = escape($username);
    $email      = escape($email);
    $password   = escape($password);

	if(email_exists($email)) {
		return false;
	} else if (username_exists($username)) {
	    return false;
	} else { 
		$password = password_hash($password, PASSWORD_BCRYPT,array('cost'=>12));
		$validation_code = md5($username . microtime());
		$sql = "INSERT INTO users(first_name,last_name,username,email,password,validation_code,active)";
		$sql.= " VALUES('$first_name','$last_name','$username','$email','$password','$validation_code',0)";
		$result = query($sql);

		$subject = "Activate Account";
		$msg = " Please click the link below to activate your Account      
    <a href=http://localhost/login/activate.php?email=$email&code=$validation_code>

    LINK HERE</a>";
		$headers="From: norreply@yourwebsite.com";  //Please Note on a live server, localhost = Your website address and login = the folder name of your files.In live server  norreply@yourwebsite.com can be = norreply@KreativeNerd.com

		send_email($email,$subject,$msg,$headers); //Note this is mail function syntax. We also have phpmailer

		return true; //if this function is true go on top of echo "user registered" and set a message

	}
}
  

                                                // ACTIVATE USER FUNCTIONS
// Note: Go to activate.php and invoke that function
function activate_user(){
	if($_SERVER['REQUEST_METHOD'] =="GET"){
		if(isset($_GET['email'])){
	    $email = clean($_GET['email']);
		  $validation_code = clean($_GET['code']);
      $sql = "SELECT id FROM users WHERE email = '".escape($_GET['email'])."' AND validation_code ='".escape($_GET['code'])."'";
      $result = query($sql);

        if(row_count($result) == 1) {
        $sql2 = "UPDATE users SET active = 1,validation_code = 0 WHERE email = '".escape($email)."' AND validation_code = '".escape($validation_code)."'";
        $result2 = query($sql2);
        set_message("<p class='bg-success'>Your account has been activated please login</p>");
        redirect("login.php");
		} else {
		    set_message("<p class='bg-danger'>Sorry,your account could not be activated</p>");
        redirect("login.php");
		    }
	   }
  }
} // function

                                  // VALIDATE USER LOGIN FUNCTIONS
function validate_user_login(){
    $errors = [];
     
    $min = 3;
    $max = 20;

    if($_SERVER['REQUEST_METHOD'] == "POST") {
   
         $email            = clean($_POST['email']);
         $password         = clean($_POST['password']);
         $remember         = isset($_POST['remember']); //this help to set a cookie sothat even if we close the browser, we can still access the page once we open the browser unlike session. Continue remember on top of redirect("admin.php")

         if(empty($email)){
         	 $errors[] = "Email field cannot be empty";
         }

         if(empty($password)){
         	 $errors[] = "password field cannot be empty";
         }


          if(!empty($errors)) {
                	foreach ($errors as $error){  //syntax is looping through array e.g foreach($array as $value)
                	echo validation_errors($error); //invoked function syntax located on helper functions and store looping throug the values
                	}
        } else{
                	if(login_user($email,$password,$remember )){
                		redirect("admin.php");
        	} else {
        		      echo validation_errors("Your credentials are not correct");
        	}
        }
    }
} //function

                                           //USER LOGIN FUNCTIONS
 function login_user($email,$password,$remember) {
       $sql = "SELECT password, id FROM users WHERE email = '".escape($email)."' AND active = 1";
       $result = query($sql);
       if(row_count($result) == 1) {
           $row = fetch_array($result);
           $db_password = $row['password'];

           if(password_verify($password, $db_password)){
                if($remember == "on") {
                  setcookie('email',$email, time() + 86400);
                }
                $_SESSION['email'] = $email;
                return true;
           } else {
           	   return false;
           }
         }
 }  //End of functions


                                           // LOGGED IN FUNCTION
 function logged_in(){
 	if(isset($_SESSION['email']) || isset($_COOKIE['email'])){

 		return true;
 	} else {
 		return false;
 	}
 }


                                                // Recover password functions
function recover_password() {
    if($_SERVER['REQUEST_METHOD'] == "POST") {

      if(isset($_SESSION['token']) && $_POST['token'] === $_SESSION['token']) { // token id gotten from recover.php which has <input type="hidden" class="hide" name="token" id="token" value="<?php echo token_generator();
          $email = clean($_POST['email']); //email coming from inputed email of the user

            if(email_exists($email)) {

              $validation_code = md5($email . microtime());

              setcookie('temp_access_code', $validation_code, time()+ 900); //this makes our code.php page unavailable all the time

              $sql = "UPDATE users SET validation_code = '".escape($validation_code)."' WHERE email = '".escape($email)."'";
              $result = query($sql);

              $subject = "Please reset your password";
              $message = "<h2> Here is your password reset code,click the link below or paste in your browser</h2> <h1>{$validation_code}</h1>

              <a href= \"http://localhost/login/code.php?email={$email}&code={$validation_code}\">http://localhost/code.php?email={$email}&code={$validation_code}

              ";
              $headers = "From: noreply@yourwebsite.com";
              
             send_email($email, $subject, $message, $headers);

              set_message("<p class='bg-success text-center'>Please check your email or spam folder for password reset code.</p>");
              redirect("index.php");

            } else {

              echo validation_errors("This email does not exist");
            }

      } else {

        redirect("index.php"); //this redirection occurs if the cookie is already expired
      } //token checks

        if(isset($_POST['cancel_submit'])) { //This works on cancel button to make sure it does the right thing
            set_message("<p class='bg-success text-center'>Cancelled sucessfully,please Login or Register</p>");
            redirect("login.php");

        }

  } //Post request

}//functions


                              // Code Validation

function validate_code () {

      if(isset($_COOKIE['temp_access_code'])) { //this makes the code.php page inaccessible until it is set else we be redirected to recover.php. This also checks if the cookie is expired or not
              if(!isset($_GET['email']) && !isset($_GET['code'])) {
                    redirect("index.php");

              } else if (empty($_GET['email']) || empty($_GET['code'])) {
                    redirect("index.php");

              } else {

                  if(isset($_POST['code'])) {  // code coming from the input in code.php with name="code"
                    $email = clean($_GET['email']);
                    $validation_code = clean(($_POST['code']));
                    $sql = "SELECT id FROM users WHERE validation_code = '".escape($validation_code)."' AND email = '".escape($email)."'";
                    $result = query($sql);

                    if(row_count($result) == 1) {
                        setcookie('temp_access_code', $validation_code, time()+ 900);//This sets validation code in code.php page for 5 mins 
                        redirect("reset.php?email=$email&code=$validation_code");

                    } else {
                      echo validation_errors("Sorry wrong validation code");
                    }

                  }

              }
          
      } else {
          set_message("<p class='bg-success text-center'> Sorry your validation cookie has expired </p>");
          redirect("recover.php");
      }

}


                                   // Password Reset Function

function password_reset() {

  if(isset($_COOKIE['temp_access_code'])) { //This checks for code expiration set in recover.php

    if(isset($_GET['email']) && isset($_GET['code'])) { //checks for the get request email and code of the url

       if(isset($_SESSION['token']) && isset($_POST['token'])) {

         if($_POST['token'] === $_SESSION['token']) { //checks for token inside the form

            if($_POST['password'] === $_POST['confirm_password']) {

              $updated_password = md5($_POST['password']);

               $sql = "UPDATE users SET password ='".escape($updated_password)."', validation_code = 0, active=1 WHERE email = '".escape($_GET['email'])."'";     
               query($sql);

              set_message("<p class='bg-success text-center'>Your password  has been updated please log in </p>");
              redirect("login.php");

            } else {

              echo validation_errors("Password fields don't match");

            }
         }
       }
    }  else {
      set_message("<p class='bg-danger text-center'>Sorry your time has expired</p>");
      redirect("recover.php");
         }
    }
}