<?php

// restrict access only to administrators
$administrators = [1];
$restrictAccess = false;

// start the session
session_start();
// check if user is logged in
if (!isset($_SESSION['id']) || !isset($_SESSION['token'])) {
  header("Location: ."); // Redirects to /spiteful-chat/
  die();
}

$userId = $_SESSION['id'];
if ($restrictAccess && !in_array($userId, $administrators)) {
  header("Location: maintenance");
  die();
}

$token = $_SESSION['token'];

require_once("/var/www/private/spiteful-chat/database.php");
$connection = $GLOBALS['connection'];

$sql = "SELECT * FROM `profiles` WHERE `user_id`=?";
$statement = $connection->prepare($sql);
$statement->bind_param("i", $userId);
$statement->execute() or die("An error occurred CSDB10"); // Code select database 10 double digits for distinction
$result = $statement->get_result();

if ($result->num_rows == 0) {
  session_destroy();
  header("Location: ."); // Redirects to /spiteful-chat/
  die();
}

$row = $result->fetch_assoc();

if ($row['token'] != $token) {
  session_destroy();
  header("Location: ."); // Redirects to /spiteful-chat/
  die();
}

$token_generated = strtotime($row['token_generated']);
$timeBetween = time() - $token_generated;

if ($timeBetween > 28800) { // 28800 is 8 hours
  session_destroy();
  header("Location: ."); // Redirects to /spiteful-chat/
  die();
}

$sql = "UPDATE `profiles` SET `last_active`=CURRENT_TIMESTAMP WHERE `user_id`=$userId";
$connection->query($sql) or die("An error occurred CULA1"); // Code update last active 1

?>
<!DOCTYPE html>
<html>

<head>
  <title>Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="assets/dashboard.css?<?php echo filemtime("assets/dashboard.css"); ?>">
  <script src="https://kit.fontawesome.com/d6e7bd37b5.js" crossorigin="anonymous"></script>
</head>

<body>


  <div class="container">
    <div class="main">
      <div class="profiles-block">
        <div class="newMsgBtnWrapper">
          <button class="newMsgBtn" onclick="my.newChat()"><i class="fa-regular fa-pen-to-square"></i>&nbsp;New Message</button>
        </div>
        <div class="profiles-list-wrapper">
          <ul id="profiles-list"></ul>
          <div class="loader"></div>
        </div>
        <div class="my-profile-section">
          <div class="my-profile-block" onclick="my.profile.modal()">
            <img id="myPicture" src="<?php echo $row["picture"]; ?>">
            <div>
              <span id="myName"><?php echo $row["name"]; ?></span>
              <br>
              <span id="myUsername">@<?php echo $row["username"]; ?></span>
            </div>
          </div>

          <button onclick="my.settings.modal()"><i class="fas fa-gear"></i></button>
        </div>
      </div>
      <div class="messages-block">
        <div class="recipientBlock" style="display:none;">
          <button class="backBtn" onclick="showProfileList()"><i class="fa-solid fa-chevron-left"></i></button>
          <img>
          <div>
            <span></span>
            <br>
            <span></span>
          </div>
        </div>
        <div class="messagesContainer" style="display: none;">
          <div class="messages">
            <div class="loader"></div>
            <label>Loading Messages</label>
          </div>
        </div>
        <div class="messageBar" style="display:none;">
          <button class="mediaBtn"><i class="fa-solid fa-upload"></i></button>
          <textarea class="msg" placeholder="message"></textarea>
          <button class="sendBtn"><i class="fa-solid fa-arrow-right"></i></button>
        </div>
        <div class="no-profile-selection">
          <p>Nothing to see here...</p>
        </div>
      </div>
    </div>
  </div>


  <script src="node_modules/jquery/dist/jquery.min.js"></script>
  <script src="node_modules/sweetalert2/dist/sweetalert2.all.min.js?v2"></script>
  <link rel="stylesheet" href="assets/swal-dark.css">
  <script>
    <?php if ($row["username"] === null) { ?>
      Swal.fire({
        title: 'Choose a username!',
        input: 'text',
        inputAttributes: {
          autocapitalize: 'off',
          spellcheck: "false"
        },
        showCancelButton: false,
        allowEscapeKey: false,
        allowOutsideClick: false,
        confirmButtonText: 'Continue',
        showLoaderOnConfirm: true,
        preConfirm: (username) => {
          if (username === "") {
            Swal.showValidationMessage(
              `Type something bro`
            )
          } else {
            if (RegExp(/[-!#@$%^&*()_+|~=`{}\[\]:";'<>?,.\\\/\s]/g).test(username)) {
              Swal.showValidationMessage(
                `Your username cannot contain special characters or spaces`
              )
            } else {
              return $.post("processes", {
                  process: "updateUsername",
                  data: username
                })
                .then(response => {
                  console.log(response);
                  response = JSON.parse(response);
                  if (!response.ok) {
                    throw new Error(response.statusText)
                  }
                  return response
                })
                .catch(error => {
                  Swal.showValidationMessage(
                    error
                  )
                })
            }
          }
        }
        //allowOutsideClick: () => !Swal.isLoading()
      }).then((result) => {
        if (result.isConfirmed) {
          console.log(result.value)
          Toast.fire({
            title: `Welcome ${result.value.username}`,
            icon: "success"
          })
        }
      })
    <?php } ?>
  </script>
  <script src="assets/dashboard.js?<?php echo filemtime("assets/dashboard.js"); ?>"></script>
</body>

</html>