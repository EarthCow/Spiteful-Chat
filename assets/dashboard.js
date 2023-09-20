"use strict";

var $ = jQuery;

/*
function enableNotifications() {
  if (Notification.permission === "default") {
    Notification.requestPermission().then(perm => {
      if (Notification.permission === "granted") {
        regWorker().catch(err => console.error(err));
      } else {
        console.error("Notification access has been declined.");
      }
    });
  } else if (Notification.permission === "granted") {
    regWorker().catch(err => console.error(err));
  } else {
    console.error("Notification access has been declined.");
  }
}

// Register service worker
async function regWorker() {
  const publicKey = "BGaXrka4qKrrpnVk0wGn2BZHnE3m2jRVJf7tlGAI__O7SHstOhmkHmmOvKSLG9nBhAvOlsJ1h4d7_cyqQe8H0ak";
  navigator.serviceWorker.register("services.js", {
    scope: "./"
  }); // assuming domain.com/spiteful-chat/
  navigator.serviceWorker.ready
    .then(reg => {
      reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: publicKey
      }).then(
        sub => {
          var data = new FormData();
          data.append("sub", JSON.stringify(sub));
          fetch("assets/push.php", {
              method: "POST",
              body: data
            })
            .then(res => res.text())
            .then(txt => console.log(txt))
            .catch(err => console.error(err));
        },
        err => console.error(err)
      );
    });
}

const check = () => {
  if (!('serviceWorker' in navigator)) {
    $("#notificationEnabler").prop("disabled", true);
    console.error("Service workers not supported by browser.");
  }
  if (!('PushManager' in window)) {
    $("#notificationEnabler").prop("disabled", true);
    console.error("Push API not supported by browser.");
  }
};
*/

// Fetch translations
var translations;
$.getJSON("./assets/languages.php", (data) => {
  translations = data;
}).fail(() => {
  console.error("Translations failed to load");
});

function word(word) {
  return translations[word];
}

document.onmousemove = function () {
  // Revert to default title
  document.title = word("dashboard");
};

const SwalLoading = Swal.mixin({
  title: "Loading...",
  allowOutsideClick: false,
  allowEscapeKey: false,
  allowEnterKey: false,
  didOpen: () => Swal.showLoading(),
});

function openChat(user) {
  if (my.openChat && my.openChat.username == user) {
    // Do nothing if the username is the currently opened chat
    return;
  }
  if (user == my.username) {
    Swal.fire({
      title: word("this-is-you"),
      text: word("cannot-message-yourself"),
      confirmButtonText: word("ok"),
      icon: "info",
    });
    return;
  }
  if (my.chats[user]) {
    // If they have a chat already open with that user go to it
    my.chats[user].init();
    return;
  }
  SwalLoading.fire();
  $.post("processes", {
    process: "newChat",
    data: user,
  }).then((response) => {
    Swal.close();
    if ((response = verifyResultJSON(response))) {
      if (!response.ok) {
        Swal.fire("Error", response.statusText, "error");
        return;
      }
      let receiver = response.receiver.username;
      let $element = $(document.createElement("li"));
      $element.append(`
          <div class="wrapper">
            <div class="profile-picture-wrapper">
              <img src="${response.receiver.picture}">
              <div class="status-circle"></div>
            </div>
            <span>
              <strong>${receiver}</strong>
              <br>
              <span class="lastMsg"></span>
            </span>
          </div>
        `);
      $("#profiles-list").prepend($element);
      let chatObj = new Chat(
        receiver,
        response.receiver.name,
        response.receiver.picture,
        $element,
      );
      chatObj.$element.on("click", () => chatObj.init());
      my.chats[receiver] = chatObj;
      chatObj.init();
      // This is to get the current status of the new chat user
      if (my.socket.available()) my.socket.send("S", receiver);
    }
  });
}

let my = {
  username: $("#myUsername").text().replace("@", ""),
  name: $("#myName").text(),
  picture: $("#myPicture").prop("src"),
  chats: [],
  openChat: null,
  socket: null,

  getChats() {
    if (this.socket.available()) {
      this.socket.send("C", "", (parsed) => {
        if (!parsed.ok) {
          Toast.fire({
            icon: "error",
            title: word("error"),
            text: parsed.statusText,
            confirmButtonText: word("ok"),
          });
          return;
        }

        if (!parsed.chats) {
          return;
        }

        // Hide the loading animation
        $(".profiles-block .loader").hide();
        $("#profiles-list").html("");

        parsed.chats.forEach((chat) => {
          let $element = $(document.createElement("li"));
          $element.append(`
            <div class="wrapper">
                <div class="profile-picture-wrapper">
                    <img src="${chat.picture}">
                    <div class="status-circle ${chat.status ?? ""}"></div>
                </div>
                <span>
                    <strong>${chat.username}</strong>
                    <br>
                    <span class="lastMsg"></span>
                </span>
            </div>
          `);

          // Will already be escaped html
          $element.find(".lastMsg").html(chat.last_message ?? "");

          $("#profiles-list").append($element);

          let chatObj = new Chat(
            chat.username,
            chat.name,
            chat.picture,
            $element,
          );

          chatObj.$element.on("click", () => chatObj.init());

          // Push the username to the array of all DMs
          this.chats[chat.username] = chatObj;
        });
      });
    } else {
      $.post("processes", {
        process: "getChats",
        data: "896",
      }).then((result) => {
        result = verifyResultJSON(result);
        if (result === false) {
          return;
        }

        if (!result.ok) {
          Toast.fire({
            icon: "error",
            title: word("error"),
            text: result.statusText,
            confirmButtonText: word("ok"),
          });
          return;
        }

        if (!result.chats) {
          return;
        }

        // Hide the loading animation
        $(".profiles-block .loader").hide();
        $("#profiles-list").html("");

        result.chats.forEach((chat) => {
          let $element = $(document.createElement("li"));
          $element.append(`
            <div class="wrapper">
                <div class="profile-picture-wrapper">
                    <img src="${chat.picture}">
                    <div class="status-circle"></div>
                </div>
                <span>
                    <strong>${chat.username}</strong>
                    <br>
                    <span class="lastMsg"></span>
                </span>
            </div>
          `);

          // Will already be escaped html
          $element
            .find(".lastMsg")
            .html(chat.last_message ? chat.last_message : "");

          $("#profiles-list").append($element);

          let chatObj = new Chat(
            chat.username,
            chat.name,
            chat.picture,
            $element,
          );

          chatObj.$element.on("click", () => chatObj.init());

          // Push the username to the array of all DMs
          this.chats[chat.username] = chatObj;
        });
      });
    }
  },

  newChat() {
    Swal.fire({
      title: word("who-recipient"),
      input: "text",
      inputAttributes: {
        autocapitalize: "off",
        spellcheck: "false",
        placeholder: word("username"),
      },
      showCancelButton: true,
      reverseButtons: true,
      confirmButtonText: word("continue"),
      cancelButtonText: word("cancel"),
      showLoaderOnConfirm: true,
      // Do not want to return focus to the new message button
      returnFocus: false,
      preConfirm: (username) => {
        // Check if the field is empty
        if (username === "") {
          Swal.showValidationMessage(word("blank-username"));
          return;
        }
        // Check if the username is already in the list of chats
        if (my.chats[username]) {
          Toast.fire({
            icon: "info",
            text: word("conversation-exists") + username,
            confirmButtonText: word("ok"),
          });
          my.chats[username].init();
          return;
        }
        // Verify the integrity of the entered username
        if (
          new RegExp(/[-!#@$%^&*()_+|~=`{}\[\]:";'<>?,.\\\/\s]/g).test(username)
        ) {
          Swal.showValidationMessage(word("invalid-username-characters-to"));
          return;
        }
        // Return the post request
        return $.post("processes", {
          process: "newChat",
          data: username,
        })
          .then((response) => {
            if ((response = verifyResultJSON(response))) {
              if (!response.ok) {
                throw new Error(response.statusText);
              }
              response.username = username;
              return response;
            }
          })
          .catch((error) => {
            Swal.showValidationMessage(error);
          });
      },
      backdrop: true,
      allowOutsideClick: () => !Swal.isLoading(),
    }).then((result) => {
      if (!result.isConfirmed) {
        console.log(word("not-confirmed"));
        return;
      }
      if (typeof result.value != "object") {
        console.log(word("not-an-object"));
        return;
      }
      let receiver = result.value.receiver.username;
      if (result.value.alreadyExists) {
        Toast.fire({
          icon: "info",
          title: word("conversation-exists") + receiver,
          confirmButtonText: word("ok"),
        });
        my.chats[receiver].init();
        console.log(word("already-exists"));
        return;
      }
      // Create the new chat
      let $element = $(document.createElement("li"));
      $element.append(`
          <div class="wrapper">
              <div class="profile-picture-wrapper">
                  <img src="${result.value.receiver.picture}">
                  <div class="status-circle"></div>
              </div>
              <span>
                  <strong>${receiver}</strong>
                  <br>
                  <span class="lastMsg"></span>
              </span>
          </div>
        `);

      $("#profiles-list").prepend($element);

      let chatObj = new Chat(
        receiver,
        result.value.receiver.name,
        result.value.receiver.picture,
        $element,
      );

      chatObj.$element.on("click", () => chatObj.init());

      // Push the username to the array of all DMs
      this.chats[receiver] = chatObj;

      chatObj.init();

      if (my.socket.available()) my.socket.send("S", receiver);
    });
  },

  profile: {
    modal() {
      Swal.fire({
        title: word("edit-profile"),
        confirmButtonText: word("ok"),
      });
    },
  },

  settings: {
    modal() {
      Swal.fire({
        title: word("settings"),
        footer:
          '<a href="logout" style="text-decoration:none">' +
          word("logout") +
          '</a<!--br><button id="notificationEnabler" onclick="enableNotifications()">Enable Notifications</button>-->',
        confirmButtonText: word("ok"),
      });
    },

    preferredColorScheme: "systemPreference", // Options: 'dark', 'light', 'systemPreference'
  },
};

class Chat {
  constructor(username, name, picture, $element) {
    this.username = username;
    this.name = name;
    this.picture = picture;
    this.$element = $element;

    this.isSending = false;
    this.msgId = 0;

    this.msgSection = 1;
    this.isGettingMessages = false;
    this.hasAllMessages = false;
  }
  createMediaMsg(type, src, date, original, mine, append = false) {
    //Split the mime type and get the first type (video/mp4 -> "video")
    var generalType = type.split("/")[0],
      spanContent;
    // Switch through the general types to create type specific message elements
    switch (generalType) {
      case "image":
        spanContent = `<img src="${src}">`;
        break;
      case "video":
        spanContent = `
          <video controls>
              <source src="${src}" type="${
                type == "video/quicktime" ? "video/mp4" : type
              }">
          </video>
        `;
        break;
      default:
        // For any media (files) that are not listed above
        spanContent = `
          <div class="fileMsg">
              <span>
                  <i class="fa-solid fa-file"></i>
                  &nbsp;
                  ${original}
              </span>
              &nbsp;
              <a href="${src}&download"><i class="fa-solid fa-download"></i></a>
          </div>
        `;
        break;
    }

    let msg = `
      <div class="messageWrapper${mine ? " myMessage" : ""}" title="${date}">
          <img src="${mine ? my.picture : this.picture}">
          <span>${spanContent}</span>
      </div>
    `;

    if (append) {
      $(".messages").append(msg);
      return;
    }
    return msg;
  }
  getMessages(prepend = false) {
    this.isGettingMessages = true;
    if (prepend) {
      $(".messages").prepend(
        $(`
        <div class="loader"></div>
        <label>${word("loading-messages")}</label>
      `),
      );
    }
    console.log(word("now-loading-section"), this.msgSection);
    $.post("processes", {
      process: "getMessages",
      data: JSON.stringify([this.username, this.msgSection++]),
    }).then((result) => {
      result = verifyResultJSON(result);
      if (result === false) {
        return;
      }

      // Verify the result of the request
      if (!result.ok) {
        Swal.fire({
          title: word("error"),
          text: result.statusText,
          icon: "error",
          confirmButtonText: word("ok"),
        });
      }

      // Set variable for the html messages to be iterated and appended to
      let messageElements = "";
      // Run the iteration on each message
      result.messages.forEach((message, index) => {
        // If the message doesn't have a subsequent type variable it is NOT media
        if (!message.type) {
          my.openChat.username = this.username;
          // Generate the html text message
          var convertedMsg = convertHandle(
            convertUri(message.content.replaceAll("\n", "<br>")),
          );
          messageElements += `
              <div class="messageWrapper${
                message.mine ? " myMessage" : ""
              }" title="${message.date}">
                  <img src="${message.mine ? my.picture : this.picture}">
                  <span>${convertedMsg}</span>
              </div>
            `;
        } else {
          // This is used for messages that ARE media
          messageElements += this.createMediaMsg(
            message.type,
            message.content,
            message.date,
            message.original,
            message.mine,
          );
        }

        // Determine whether a date line should be added
        if (result.messages[index + 1]) {
          let msgDate = new Date(message.date),
            nextMsgDate = new Date(result.messages[index + 1].date);
          if (
            nextMsgDate.getMonth() != msgDate.getMonth() ||
            nextMsgDate.getDate() != msgDate.getDate() ||
            nextMsgDate.getFullYear() != msgDate.getFullYear()
          ) {
            messageElements += `
                <p class="centeredWithLines" style="color: #808080;">
                  ${
                    nextMsgDate.getMonth() + 1
                  }/${nextMsgDate.getDate()}/${nextMsgDate.getFullYear()} ${nextMsgDate.getHours()}:${
                    String(nextMsgDate.getMinutes()).length != 2
                      ? "0" + nextMsgDate.getMinutes()
                      : nextMsgDate.getMinutes()
                  }
                </p>
              `;
          }
        } else if (prepend) {
          let msgDate = new Date(message.date),
            nextMsgDate = new Date(
              $(".messages .messageWrapper").first()[0].title,
            );
          if (
            nextMsgDate.getMonth() != msgDate.getMonth() ||
            nextMsgDate.getDate() != msgDate.getDate() ||
            nextMsgDate.getFullYear() != msgDate.getFullYear()
          ) {
            messageElements += `
                <p class="centeredWithLines" style="color: #808080;">
                  ${
                    nextMsgDate.getMonth() + 1
                  }/${nextMsgDate.getDate()}/${nextMsgDate.getFullYear()} ${nextMsgDate.getHours()}:${
                    String(nextMsgDate.getMinutes()).length != 2
                      ? "0" + nextMsgDate.getMinutes()
                      : nextMsgDate.getMinutes()
                  }
                </p>
              `;
          }
        }

        this.msgId++;
      });

      if (prepend) {
        $(".messages .loader ~ label, .messages .loader").remove();
        $(".messages").prepend(messageElements);
      } else {
        // Render the messages and remove the loader from .messages
        $(".messages").html(messageElements);
      }
      $(".messageWrapper span img, .messageWrapper span video").css(
        "max-height",
        $(".messagesContainer").height(),
      );

      this.hasAllMessages = messageElements.length < 25;

      this.isGettingMessages = false;
    });
  }
  init() {
    // Verify the chat is in the array of chats
    if (!my.chats[this.username]) {
      Toast.fire({
        icon: "error",
        title: word("error"),
        confirmButtonText: word("ok"),
      });
      return;
    }

    // This is purely for the mobile version
    // On the mobile version the .profiles-list collapses whenever a chat is selected
    if ($(".profiles-block").css("position") == "absolute") {
      $(".profiles-block").hide();
    }

    // Create the .message-block elements ---

    // Add a the loader to .messages
    $(".messages").html(`
      <div class="loader"></div>
      <label>${word("loading-messages")}</label>
    `);

    if ($(".recipientBlock img").prop("src") != this.picture) {
      $(".recipientBlock img").prop("src", this.picture);
      $(".recipientBlock span").eq(0).text(this.name);
      $(".recipientBlock span")
        .eq(1)
        .text("@" + this.username);

      // Set active chat
      if ($(".active-dm")[0]) $(".active-dm").removeClass("active-dm");
      this.$element.addClass("active-dm");

      // Set listeners

      let $msgBox = $(".msg");

      $msgBox.off();

      $msgBox.on("keyup", function () {
        fixMessageBoxHeight($msgBox);
      });
      $msgBox.on("keydown", function (e) {
        // If the user presses enter without shift it will submit
        if (e.which == 13 && !e.shiftKey) {
          // Prevent a new line from being inputed
          e.preventDefault();
          $msgBox.next().click();
        }
      });

      $(".sendBtn").off();
      $(".sendBtn").on("click", () => this.message());

      $(".mediaBtn").off();
      $(".mediaBtn").on("click", () => upload(this));
    }

    // Hide the "nothing to see here" text
    $(".no-profile-selection").hide();

    showMsgBlock();

    setTimeout(function () {
      $(".recipientBlock").show(400);
      $(".messagesContainer").show(500, function () {
        $(".messageWrapper span img, .messageWrapper span video").css(
          "max-height",
          $(".messagesContainer").height(),
        );
      });
      $(".messageBar").show(400);
      $(".msg").focus();
    }, 500);

    // Initiate the request for the messages
    this.msgSection = 1;
    this.getMessages();

    my.openChat = this;
  }
  message() {
    // Won't send a message if another one is being sent
    if (this.isSending) {
      Toast.fire({
        icon: "info",
        text: word("wait-message-sending"),
        confirmButtonText: word("ok"),
      });
      return;
    }

    // Define the textarea with the message and the content (the message itself)
    let $msgBox = $(".msg"),
      message = $msgBox.val().trim();
    if (message === "") {
      // Will not send an empty message
      Toast.fire({
        icon: "info",
        text: word("empty-message"),
        confirmButtonText: word("ok"),
      });
      return;
    }

    // Clears the textarea of content and fixes its height
    $msgBox.val("");
    fixMessageBoxHeight($msgBox);

    let $newMsg = $(document.createElement("div"));
    $newMsg[0].className = "messageWrapper myMessage";
    $newMsg.css("opacity", 0.5);

    $newMsg.append(`
      <img src="${my.picture}">
      <span></span>
    `);

    // Ensures shift enter whitespace is html compliant
    let visualMsg = convertHandle(
      convertUri(escapeHtml(message).replaceAll("\n", "<br>")),
    );
    $newMsg.find("span").html(visualMsg);

    // Determine whether a date line should be added beforehand
    if ($(".messages .messageWrapper").last()[0]) {
      let msgDate = new Date(),
        lastMsgDate = new Date($(".messages .messageWrapper").last()[0].title);
      if (
        lastMsgDate.getMonth() != msgDate.getMonth() ||
        lastMsgDate.getDate() != msgDate.getDate() ||
        lastMsgDate.getFullYear() != msgDate.getFullYear()
      )
        $(".messages").append(`
          <p class="centeredWithLines" style="color: #808080;">
            ${
              msgDate.getMonth() + 1
            }/${msgDate.getDate()}/${msgDate.getFullYear()} ${msgDate.getHours()}:${
              String(msgDate.getMinutes()).length != 2
                ? "0" + msgDate.getMinutes()
                : msgDate.getMinutes()
            }
          </p>
        `);
    }

    // Appends the new message to the .messages div
    $(".messages").append($newMsg);

    // Move chat to the top of the list
    if (!this.$element.is(":first-child")) {
      this.$element.prependTo("#profiles-list");
    }

    // Set last message
    let $lastMsgSpan = this.$element.find(".lastMsg");
    $lastMsgSpan.addClass("mute italic").text(word("sending"));

    // Sets isSending to true before sending the message
    this.isSending = true;
    // If the socket is open then we send through the socket but if it's not then the message gets sent post like normal
    if (my.socket.available()) {
      my.socket.send(
        "M",
        [
          this.username,
          {
            type: false,
            content: message,
          },
        ],
        (result) => {
          // Resets the isSending to false
          this.isSending = false;

          // Set last message
          $lastMsgSpan.removeClass("mute italic").html("&nbsp;");

          if (result.ok) {
            // Set last message - this one can use .text() because it is not already escaped :)
            $lastMsgSpan.text(message);
            // If the message sent it changes the transparency of the message element in the .messages div
            $newMsg.css("opacity", 1);
            // Set date
            $newMsg.attr("title", result.lm);
          } else {
            // If the message failed to send it fires an error toast
            Toast.fire({
              icon: "error",
              title: result.statusText,
              confirmButtonText: word("ok"),
            });
          }
          this.msgId++;
        },
      );
    } else {
      $.post("processes", {
        process: "sendMessage",
        data: JSON.stringify([this.username, message]),
      }).then((result) => {
        // Resets the isSending to false
        this.isSending = false;

        // Set last message
        $lastMsgSpan.removeClass("mute italic").html("&nbsp;");

        // Verify the result is indeed json and sets the result to the json value
        if ((result = verifyResultJSON(result))) {
          if (result.ok) {
            // Set last message
            $lastMsgSpan.text(message);
            // If the message sent it changes the transparency of the message element in the .messages div
            $newMsg.css("opacity", 1);
            // Set date
            $newMsg.attr("title", result.lm);
          } else {
            // If the message failed to send it fires an error toast
            Toast.fire({
              icon: "error",
              text: result.statusText,
              confirmButtonText: word("ok"),
            });
          }
          this.msgId++;
        }
      });
    }
  }

  receive(message) {
    let $newMsg;
    let $lastMsgSpan = this.$element.find(".lastMsg");
    // If the message doesn't have a subsequent type variable it is NOT media
    if (!message.type) {
      // Generate the html text message
      var visualMsg = convertHandle(
        convertUri(message.content.replaceAll("\n", "<br>")),
      );
      document.title = "💬 " + word("dashboard");
      $newMsg = $(`
        <div class="messageWrapper" title="${message.date}">
            <img src="${this.picture}">
            <span>${visualMsg}</span>
        </div>
      `);
      // Set last message
      $lastMsgSpan.html(message.content);
    } else {
      // This is used for messages that ARE media
      $newMsg = $(
        this.createMediaMsg(
          message.type,
          message.src,
          message.date,
          message.original,
          false,
        ),
      );
      // Set last message
      $lastMsgSpan.html(message.original);
    }

    // Move chat to the top of the list
    if (!this.$element.is(":first-child")) {
      this.$element.prependTo("#profiles-list");
    }

    if (my.openChat === null || my.openChat.username != this.username) return;

    // Render the messages and remove the loader from .messages
    $(".messages").append($newMsg);
    $(".messageWrapper span img, .messageWrapper span video").css(
      "max-height",
      $(".messagesContainer").height(),
    );
  }
}

function upload(chat) {
  let $status, $innerBar;

  function byteConverter(bytes) {
    var sizes = ["Bytes", "KB", "MB", "GB", "TB"];
    if (bytes === 0) return "n/a";
    var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
    if (i === 0) return bytes + " " + sizes[i];
    return (bytes / Math.pow(1024, i)).toFixed(1) + " " + sizes[i];
  }

  function modal() {
    Swal.fire({
      title: "Upload",
      confirmButtonText: word("upload-and-send"),

      html: `
        <div class="uploadField" id='uploaderDiv' onclick="$('#fileInput').click();">
            <p id="uploadBoxP">${word("click-here-or-drag-drop")}</p>
        </div>
        <form style='display:none;' class='uploadForm' method='post' enctype='multipart/form-data'>
            <input style='display:none;' type='file' id='fileInput'>
        </form>
      `,

      didOpen: () => {
        $("#fileInput").on("change", function () {
          let file = this.files[0];
          $("#uploadBoxP").html(
            file
              ? `${file.name}<br><span style="color:orange">${byteConverter(
                  file.size,
                )}</span>`
              : word("click-here-or-drag-drop"),
          );
        });
      },

      showCancelButton: true,
      reverseButtons: true,
      allowEscapeKey: () => !Swal.isLoading(),
      showLoaderOnConfirm: true,
      preConfirm: () => {
        let file = $("#fileInput")[0].files[0];
        if (!file) {
          Swal.showValidationMessage(word("select-file"));
          return;
        }

        send(file);

        // Prevents popup from closing
        return false;
      },
      backdrop: true,
      allowOutsideClick: () => !Swal.isLoading(),
    });
  }

  function send(file) {
    let newUploadDiv = document.createElement("div");
    newUploadDiv.id = "uploaderDiv";
    newUploadDiv.innerHTML = `
      <div class="uploadingContainer">
          <div class="inlineRow">
              <label for="outerBar" id="fileNameLabel">${file.name}</label>
              <div id="bytes" class="status" style="float:right;">0B/${byteConverter(
                file.size,
              )}</div>
          </div>
          <div id="outerBar" class="outerBar">
              <div id="innerBar" class="innerBar"></div>
          </div>
          <div id="status" class="status">${word("waiting")}</div>
      </div>
    `;
    $(Swal.getHtmlContainer()).html(newUploadDiv);
    $innerBar = $("#innerBar");
    $status = $("#status");

    var formdata = new FormData();
    formdata.append("file", file);
    formdata.append("data", chat.username);
    formdata.append("process", "sendFile");

    var ajax = new XMLHttpRequest();
    ajax.upload.addEventListener("progress", progressHandler, false);
    ajax.addEventListener("load", completeHandler, false);
    ajax.addEventListener("error", errorHandler, false);
    ajax.addEventListener("abort", abortHandler, false);
    ajax.onerror = function () {
      Swal.fire({
        icon: "error",
        title: word("error"),
        confirmButtonText: word("ok"),
        text: word("network-error"),
      });
    };
    ajax.open("POST", "processes");
    ajax.send(formdata);
  }

  function progressHandler(event) {
    $("#bytes").html(
      `Uploaded ${byteConverter(event.loaded)}/${byteConverter(event.total)}`,
    );

    var percent = Math.round((event.loaded / event.total) * 100);

    $innerBar.animate(
      {
        width: percent + "%",
      },
      0.15,
    );
    $innerBar.html(percent + "%");

    if (percent == 100) {
      $status.css({
        fontWeight: "bold",
        textDecoration: "underline",
      });
      $status.html(word("finalizing"));
    } else {
      $status.html(percent + word("uploaded-progress"));
    }
  }

  function completeHandler(event) {
    let result = event.target.responseText;
    console.log(result);

    result = verifyResultJSON(result);

    if (!result) {
      Swal.fire({
        icon: "error",
        title: word("error"),
        text: word("failed-to-send-file"),
        confirmButtonText: word("ok"),
      });
      return;
    }
    if (!result.ok) {
      Swal.fire({
        icon: "error",
        title: word("error"),
        text: result.statusText,
        confirmButtonText: word("ok"),
      });
      return;
    }
    Toast.fire({
      title: word("successfully-sent-file"),
      icon: "success",
      confirmButtonText: word("ok"),
    });

    my.socket.send("M", [chat.username, result]);

    chat.createMediaMsg(
      result.type,
      result.src,
      result.date,
      result.original,
      true,
      true,
    );
    chat.$element.find(".lastMsg").text(result.lastMsg);
    // Move chat to the top of the list
    if (!chat.$element.is(":first-child")) {
      chat.$element.prependTo("#profiles-list");
    }
    chat.msgId++;
  }

  function errorHandler() {
    Swal.fire({
      title: word("error"),
      text: word("failed-to-send-file"),
      icon: "error",
      confirmButtonText: word("ok"),
    });
  }

  function abortHandler() {
    Swal.fire({
      title: word("error"),
      text: word("failed-to-send-file"),
      icon: "error",
      confirmButtonText: word("ok"),
    });
  }

  modal();
}

const Toast = Swal.mixin({
  toast: true,
  position: "top-end",
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
  didOpen: (toast) => {
    toast.addEventListener("mouseenter", Swal.stopTimer);
    toast.addEventListener("mouseleave", Swal.resumeTimer);
  },
});

function verifyResultJSON(result) {
  let parsed;
  try {
    parsed = JSON.parse(result);
  } catch (error) {
    console.log(error);
    if (result == "SESS") {
      Swal.fire({
        icon: "info",
        title: word("info"),
        text: word("session-expired"),
        confirmButtonText: word("ok"),
      });
      setTimeout(() => {
        window.location.reload();
      }, 3000);
    } else {
      Swal.fire({
        title: word("error"),
        text: word("error-code") + " " + result,
        icon: "error",
        timer: 5000,
        timerProgressBar: true,
      }).then(function () {
        // window.location.reload()
      });
    }
    return false;
  }
  return parsed;
}

function receiveMessage(msg) {
  const parsed = verifyResultJSON(msg.data);
  console.log(word("message") + ": ", parsed);

  if (!parsed.ok) {
    console.warn(parsed.statusText);
    if (parsed.statusText.split(":")[0] == "SESS") {
      window.location.reload(); // TODO: add a way to retrieve the error code
    }
  }

  if (parsed.status !== undefined) {
    if (my.chats[parsed.username]) {
      my.chats[parsed.username].$element.find(".status-circle")[0].className =
        "status-circle " + parsed.status;
    }
    return;
  }

  if (parsed.sendId !== undefined) {
    if (typeof my.socket.waitingActions[parsed.sendId] == "function") {
      my.socket.waitingActions[parsed.sendId](parsed);
      delete my.socket.waitingActions[parsed.sendId];
    }
  }

  if (parsed.sender) {
    if (my.chats[parsed.sender]) {
      my.chats[parsed.sender].receive(parsed.message);
    } else {
      // create the new chat
      // status will always be online since they just messaged
      // this logic will change when user set statuses are added
      let $element = $(document.createElement("li"));
      $element.append(`
        <div class="wrapper">
            <div class="profile-picture-wrapper">
                <img src="${parsed.info.picture}">
                <div class="status-circle online"></div>
            </div>
            <span>
                <strong>${parsed.sender}</strong>
                <br>
                <span class="lastMsg"></span>
            </span>
        </div>
      `);

      $("#profiles-list").prepend($element);

      let chatObj = new Chat(
        parsed.sender,
        parsed.info.name,
        parsed.info.picture,
        $element,
      );

      chatObj.$element.on("click", () => chatObj.init());

      // Push the username to the array of all DMs
      my.chats[parsed.sender] = chatObj;
      my.chats[parsed.sender].receive(parsed.message);
    }
  }
}

class MyWebSocket {
  constructor(credentials) {
    this.credentials = credentials;
    this.connectionAttempts = 0;
    this.sendId = 0;
    this.waitingActions = {};
    this.recurringPing;

    this.available = () => {
      return my.socket.socket.readyState == my.socket.socket.OPEN;
    };
  }

  init() {
    const host =
      "wss://" + window.location.hostname + "/_ws_/" + this.credentials;
    try {
      this.socket = new WebSocket(host);
      this.socket.onopen = () => {
        this.connectionAttempts = 0;
        my.getChats();
        console.log(word("websocket-connected"));
        this.recurringPing = setInterval(() => {
          this.send("P", word("ping"));
        }, 4000);
      };
      this.socket.onmessage = receiveMessage;
      this.socket.onclose = () => {
        if (this.connectionAttempts === 0) my.getChats();
        clearInterval(this.recurringPing);
        console.warn(word("websocket-disconnected"));
        this.reconnect();
      };
    } catch (ex) {
      console.log(ex);
    }
  }

  send(instruction, content, callback) {
    if (!this.available()) return false;
    try {
      if (typeof callback == "function")
        this.waitingActions[this.sendId] = callback;
      this.socket.send(
        JSON.stringify({
          instruction: instruction,
          content: content,
          sendId: instruction == "P" ? 0 : this.sendId++,
        }),
      );
    } catch (ex) {
      console.log(ex);
    }
  }

  reconnect() {
    if (this.socket.readyState != this.socket.CLOSED) return;
    if (document.visibilityState !== "visible") return;
    if (this.connectionAttempts++ > 1) {
      Toast.fire({
        icon: "info",
        title: word("disconnected-reconnect"),
        didOpen: () => Swal.showLoading(),
      });
      setTimeout(() => {
        console.log(word("disconnected-reconnect"));
        this.init();
      }, 5000);
      return;
    }
    console.log(word("disconnected-reconnect"));
    this.init();
  }
}

/* DOCUMENT ONLOAD FUNCTION */

$(function () {
  $(".main").css("transform", "scale(1)");

  // Fix for long names
  $("#myName").quickfit();

  document.addEventListener("visibilitychange", function () {
    if (document.visibilityState === "visible") {
      my.socket.reconnect();
    }
  });

  $.post(
    "processes",
    {
      process: "getLogin",
      data: 869,
    },
    function (result) {
      const parsed = verifyResultJSON(result);
      my.socket = new MyWebSocket(`${parsed.id}.${parsed.token}`);
      my.socket.init();
    },
  );
  // Message container scroll
  $(".messagesContainer").scroll(function () {
    if (my.openChat.hasAllMessages) return;
    if (
      $(".messagesContainer").scrollTop() -
        $(".messagesContainer")[0].clientHeight +
        $(".messagesContainer")[0].scrollHeight <
        200 &&
      !my.openChat.isGettingMessages
    ) {
      my.openChat.getMessages(true);
    }
  });
});

window.onresize = function () {
  $(".messageWrapper span img, .messageWrapper span video").css(
    "max-height",
    $(".messagesContainer").height(),
  );
};

function actallyHideMsgBlock() {
  $(".profiles-block").width("calc(250px + 50vw)");
  $(".messages-block").css({
    "min-width": "unset",
    width: 0,
    opacity: 0,
  });
}

function showMsgBlock() {
  $(".profiles-block").width(250);
  $(".messages-block").css({
    width: "50vw",
    opacity: 1,
  });
  // need to add min width somewhere
}

function showProfileList() {
  $(".profiles-block").show();
}

function fixMessageBoxHeight(msgBox) {
  msgBox[0].style.height = 0;
  msgBox[0].style.height =
    (msgBox[0].scrollHeight > 1000 ? 1000 : msgBox[0].scrollHeight + 1) + "px";

  if (msgBox[0].scrollHeight - 10 > msgBox.outerHeight()) {
    msgBox.css("overflow-y", "scroll");
  } else msgBox.css("overflow-y", "hidden");
}

/* MESSAGE FORMATTING FUNCTIONS */

const escapeHtml = (unsafe) => {
  return unsafe
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
};

function convertUri(text) {
  // Converts string only
  // Regex may be changed to do maybe www.google.com and not just https://www.google.com or https://google.com
  // exp2 may be trying to do that but it just doesn't
  var exp =
    /(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/gi;
  var ctext = text.replaceAll(
    exp,
    '<a target="_blank" style="text-decoration:none;font-weight:bold" href="$1">$1</a>',
  );
  var exp2 = /(^|[^\/])(www\.[\S]+(\b|$))/gim;
  return ctext.replaceAll(
    exp2,
    '$1<a target="_blank" style="text-decoration:none;font-weight:bold" href="https://$2">$2</a>',
  );
}

function convertHandle(text) {
  var mentionRegex = /@(\w+)/g;

  function replaceMentions(match, username) {
    return `<a onclick="openChat('${username}')" href="javascript:void(0)" style="text-decoration:none;font-weight:bold">@${username}</a>`;
  }
  return text.replace(mentionRegex, replaceMentions);
}

function previewMedia(type, src) {}

function adminRequest(request = "clear", data = 896) {
  $.post("processes", {
    process: "administrator",
    data: JSON.stringify({
      request: request,
      data: data,
    }),
  }).then(function (result) {
    console.log(result);
  });
}
