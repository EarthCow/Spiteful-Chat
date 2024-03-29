<?php

// Restrict access only to administrators
$administrators = [1];

require_once "./configuration.php";
require_once "./languages.php";

// Check if user is logged in
$row = verifySession(true);

if (!$row) {
  logout();
  die();
}

$userId = $row["user_id"];
if ($maintenance && !in_array($userId, $administrators)) {
  header("Location: ./maintenance");
  die();
}

$sql = "UPDATE `profiles` SET `last_active`=CURRENT_TIMESTAMP WHERE `user_id`=$userId";
$connection->query($sql) or die(word("error-occurred") . " CULA1"); // Code update last active 1

?>
<!DOCTYPE html>
<html lang="<?php echo $htmlLang; ?>">

<head>
  <title><?php echo word("dashboard"); ?></title>
  <?php echo metaTags(2); ?>
  <link rel="stylesheet" href="<?php echo fileLink("assets/dashboard.css"); ?>">
  <script src="https://kit.fontawesome.com/d6e7bd37b5.js" crossorigin="anonymous"></script>
</head>

<body>
  <div class="container">
    <div class="main">
      <div class="profiles-block">
        <div class="newMsgBtnWrapper">
          <button class="newMsgBtn" onclick="my.newChat()"><i class="fa-regular fa-pen-to-square"></i>&nbsp;<?php echo word("new-message"); ?></button>
        </div>
        <div class="profiles-list-wrapper">
          <ul id="profiles-list"></ul>
          <div class="loader"></div>
        </div>
        <div class="my-profile-section">
          <div class="my-profile-block" onclick="my.profile.modal()">
            <!-- Later on there will be a row value for dnd, online, or appear offline -->
            <div class="profile-picture-wrapper">
              <img id="myPicture" class="online" src="<?php echo $row["picture"]; ?>">
              <div class="status-circle online"></div>
            </div>
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
            <label><?php echo word("loading-messages"); ?></label>
          </div>
        </div>
        <div class="messageBar" style="display:none;">
          <button class="mediaBtn"><i class="fa-solid fa-upload"></i></button>
          <textarea class="msg" placeholder="Message"></textarea>
          <button class="sendBtn"><i class="fa-solid fa-arrow-right"></i></button>
        </div>
        <div class="no-profile-selection">
          <p><?php echo word("nothing-to-see"); ?></p>
        </div>
      </div>
    </div>
  </div>
  <script src="<?php echo fileLink("node_modules/jquery/dist/jquery.min.js"); ?>"></script>
  <script src="<?php echo fileLink("node_modules/sweetalert2/dist/sweetalert2.all.min.js"); ?>"></script>
  <link rel="stylesheet" href="<?php echo fileLink("assets/swal-dark.css"); ?>">
  <script>
    const vapidPublic = <?php echo '"' . $vapidPublic . '";'; if ($row["username"] === null) { ?>
      Swal.fire({
        title: "<?php echo word("pick-username"); ?>",
        input: "text",
        inputAttributes: {
          autocapitalize: "off",
          spellcheck: "false"
        },
        showCancelButton: false,
        allowEscapeKey: false,
        allowOutsideClick: false,
        confirmButtonText: "<?php echo word("continue"); ?>",
        showLoaderOnConfirm: true,
        preConfirm: (username) => {
          if (username === "") {
            Swal.showValidationMessage(
              `<?php echo word("blank-username"); ?>`
            )
          } else {
            if (RegExp(/[-!#@$%^&*()_+|~=`{}\[\]:";"<>?,.\\\/\s]/g).test(username)) {
              Swal.showValidationMessage(
                `<?php echo word("invalid-username-characters"); ?>`
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
            icon: "success",
            title: `<?php echo word("welcome,"); ?> ${result.value.username}`,
          })
        }
      })
    <?php } ?>
  </script>
  <script src="<?php echo fileLink("assets/jquery.quickfit.js"); ?>"></script>
  <script src="<?php echo fileLink("assets/dashboard.js"); ?>"></script>
</body>

</html>