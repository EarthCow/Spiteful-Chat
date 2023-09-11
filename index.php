<?php

require_once "./assets/configuration.php";
require_once "./assets/languages.php";
require_once "$privateFolder/google-variables.php";

if (isset($_SESSION["id"])) {
  header("Location: ./dashboard");
  die();
}

?>
<!DOCTYPE html>
<html lang="<?php echo $htmlLang; ?>">

<head>
  <title><?php echo word("spiteful-chat"); ?></title>
  <?php echo metaTags(2); ?>
  <meta name="google-signin-client_id" content="<?php echo $googleClientId; ?>">
  <style>
    html,
    body {
      width: 100%;
      height: 100%;
      margin: 0;
      padding: 0;
    }

    .container {
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      flex-flow: column;
    }

    .wrapper {
      display: flex;
      flex-flow: column;
      gap: 5px;
      padding: 1rem;
      border-radius: 12px;
      box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px;
    }

    h2,
    h3,
    h4,
    h5,
    h6 {
      margin: 0;
      padding: 0;
    }

    /* MEDIA TAGS */

    @media (prefers-color-scheme: dark) {
      :root {
        --text: #f2f2f2;
        --outside: #303030;
        --inside: #383838;
        --inside-detail: #505050;
      }

      body {
        background-color: var(--outside);
        color: var(--text);
      }

      .wrapper {
        background-color: var(--inside);
      }
    }
  </style>
</head>

<body>
  <div class="container">
    <h1><?php echo word("welcome-to-spiteful-chat"); ?></h1>
    <div class="wrapper">
      <h3><?php echo word("hello"); ?></h3>
      <h5><?php echo word("please-login"); ?></h5>
      <div id="g_id_onload" data-client_id="<?php echo $googleClientId; ?>" data-context="signin" data-ux_mode="redirect" data-login_uri="<?php echo $googleLoginUri; ?>" data-nonce="" data-auto_prompt="false"></div>
      <div class="g_id_signin" data-type="standard" data-shape="pill" data-theme="filled_black" data-text="continue_with" data-size="large" data-logo_alignment="left"></div>
    </div>
  </div>
  <script src="https://accounts.google.com/gsi/client" async defer></script>
</body>

</html>