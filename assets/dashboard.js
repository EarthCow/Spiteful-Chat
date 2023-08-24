'use strict';
let my = {
  username: $("#myUsername").text().replace("@", ""),
  name: $("#myName").text(),
  picture: $("#myPicture").prop("src"),
  chats: [],
  openChat: null,
  socket: null,

  getChats() {
    $.post("processes", {
      process: "getChats",
      data: "896"
    })
      .then(result => {
        result = verifyResultJSON(result)
        if (result === false) {
          return;
        }

        if (!result.ok) {
          Toast.fire({
            icon: "error",
            title: "Error",
            text: result.statusText
          })
          return;
        }

        if (!result.chats) {
          return;
        }

        // Hide the loading animation
        $(".profiles-block .loader").hide()

        result.chats.forEach(chat => {

          let $element = $(document.createElement("li"));
          $element.append(`
          <div class="wrapper">
            <img src="${chat.picture}">
            <span>
              <strong>${chat.username}</strong>
              <br>
              <span class="lastMsg"></span>
            </span>
          </div>
        `)

          // encodes html
          $element.find(".lastMsg").text(((chat.last_message) ? chat.last_message : ""))

          $("#profiles-list").append($element)

          let chatObj = new Chat(chat.username, chat.name, chat.picture, $element)

          chatObj.$element.on("click", () => chatObj.init())

          // Push the username to the array of all DMs
          this.chats[chat.username] = chatObj
        })

      });
  },

  newChat() {
    Swal.fire({
      title: "Who is the recipient?",
      input: 'text',
      inputAttributes: {
        autocapitalize: 'off',
        spellcheck: "false",
        placeholder: "Username"
      },
      showCancelButton: true,
      reverseButtons: true,
      confirmButtonText: 'Continue',
      showLoaderOnConfirm: true,
      // do not want to return focus to the new message button
      returnFocus: false,
      preConfirm: (username) => {
        // check if the field is empty
        if (username === "") {
          Swal.showValidationMessage(
            `Type something bro`
          )
          return;
        }
        // check if the username is already in the list of chats
        if (my.chats[username]) {
          Toast.fire({
            icon: "info",
            text: "you already have a conversation with @" + username
          })
          my.chats[username].init()
          return;
        }
        // verify the integrity of the entered username
        if (RegExp(/[-!#@$%^&*()_+|~=`{}\[\]:";'<>?,.\\\/\s]/g).test(username)) {
          Swal.showValidationMessage(
            `Usernames cannot contain special characters or spaces`
          )
          return;
        }
        // return the post request
        return $.post("processes", {
          process: "newChat",
          data: username
        })
          .then(response => {
            if (response = verifyResultJSON(response)) {
              if (!response.ok) {
                throw new Error(response.statusText)
              }
              response.username = username
              return response
            }
          })
          .catch(error => {
            Swal.showValidationMessage(
              error
            )
          })
      },
      backdrop: true,
      allowOutsideClick: () => !Swal.isLoading()
    })
      .then((result) => {
        if (!result.isConfirmed) {
          console.log("not confirmed")
          return;
        }
        if (typeof result.value != "object") {
          console.log("not an object")
          return;
        }
        let receiver = result.value.receiver.username
        if (result.value.alreadyExists) {
          Toast.fire({
            icon: "info",
            title: "You already have a conversation with @" + receiver
          })
          my.chats[receiver].init()
          console.log("already exists")
          return;
        }
        // create the new chat
        let $element = $(document.createElement("li"));
        $element.append(`
        <div class="wrapper">
          <img src="${result.value.receiver.picture}">
          <span>
            <strong>${receiver}</strong>
            <br>
            <span class="lastMsg"></span>
          </span>
        </div>
      `)

        $("#profiles-list").prepend($element)

        let chatObj = new Chat(receiver, result.value.receiver.name, result.value.receiver.picture, $element)

        chatObj.$element.on("click", () => chatObj.init())

        // Push the username to the array of all DMs
        this.chats[receiver] = chatObj

        chatObj.init()
      })
  },

  profile: {
    modal() {
      Swal.fire({
        title: "Edit Profile"
      })
    }
  },

  settings: {
    modal() {
      Swal.fire({
        title: "Settings"
      })
    },

    preferredColorScheme: "systemPreference" // options 'dark' 'light' 'systemPreference'
  }
}

class Chat {
  constructor(username, name, picture, $element) {
    this.username = username
    this.name = name
    this.picture = picture
    this.$element = $element

    this.isSending = false
    this.msgId = 0
  }
  createMediaMsg(type, src, date, original, mine, append = false) {
    // split the mime type and get the first type (video/mp4 -> "video")
    var generalType = type.split("/")[0], spanContent
    // switch through the general types to create type specific message elements
    switch (generalType) {
      case "image":
        spanContent = `<img src="${src}">`
        break;
      case "video":
        spanContent = `
          <video controls>
            <source src="${src}" type="${((type == "video/quicktime") ? "video/mp4" : type)}">
          </video>
        `
        break;
      default:
        // for any media (files) that are not listed above
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
        `
        break;
    }

    let msg = `
      <div class="messageWrapper${((mine) ? " myMessage" : "")}" title="${date}">
        <img src="${((mine) ? my.picture : this.picture)}">
        <span>${spanContent}</span>
      </div>
    `

    if (append) {
      $(".messages").append(msg)
      return;
    }
    return msg
  }
  init() {
    // verify the chat is in the array of chats
    if (!my.chats[this.username]) {
      Toast.fire({
        icon: "error",
        title: "Error"
      })
      return;
    }

    // this is purely for the mobile version
    // on the mobile version the .profiles-list collapses whenever a chat is selected
    if ($(".profiles-block").css("position") == "absolute") {
      $(".profiles-block").hide()
    }

    // create the .message-block elements ---

    // add a the loader to .messages
    $(".messages").html(`
      <div class="loader"></div>
      <label>Loading Messages</label>
    `)

    if ($(".recipientBlock img").prop("src") != this.picture) {

      $(".recipientBlock img").prop("src", this.picture)
      $(".recipientBlock span").eq(0).text(this.name)
      $(".recipientBlock span").eq(1).text("@" + this.username)

      // set active dm
      if ($(".active-dm")[0]) $(".active-dm").removeClass("active-dm")
      this.$element.addClass("active-dm")

      // set listeners

      let $msgBox = $(".msg")

      $msgBox.off()

      $msgBox.on("keyup", function (e) {
        fixMessageBoxHeight($msgBox)
      })
      $msgBox.on("keydown", function (e) {
        // if the user presses enter without shift it will submit
        if (e.which == 13 && !e.shiftKey) {
          // prevent a new line from being inputed
          e.preventDefault()
          $msgBox.next().click()
        }
      })

      $(".sendBtn").off()
      $(".sendBtn").on("click", () => this.message())

      $(".mediaBtn").off()
      $(".mediaBtn").on("click", () => upload(this))
    }

    // hide the "nothing to see here" text
    $(".no-profile-selection").hide()

    showMsgBlock()

    setTimeout(function () {
      $(".recipientBlock").show(400)
      $(".messagesContainer").show(500, function () {
        $(".messageWrapper span img, .messageWrapper span video").css("max-height", $(".messagesContainer").height())
      })
      $(".messageBar").show(400)
    }, 500)

    $(".msg").focus()

    // initiate the request for the messages
    $.post("processes", {
      process: "getMessages",
      data: this.username
    })
      .then(result => {
        result = verifyResultJSON(result)
        if (result === false) {
          return;
        }

        // verify the result of the request
        if (!result.ok) {
          Swal.fire(
            "Error",
            result.statusText,
            "error"
          )
        }

        // set variable for the html messages to be iterated and appended to
        let messageElements = ""
        // run the iteration on each message
        result.messages.forEach(message => {
          // if the message doesn't have a subsequent type variable it is NOT media
          if (!message.type) {
            // generate the html text message
            messageElements += `
              <div class="messageWrapper${((message.mine) ? " myMessage" : "")}" title="${message.date}">
                <img src="${((message.mine) ? my.picture : this.picture)}">
                <span>${message.content.replaceAll("\n", "<br>")}</span>
              </div>
            `
          } else {
            // this is used for messages that ARE media
            messageElements += this.createMediaMsg(message.type, message.content, message.date, message.original, message.mine)
          }

          this.msgId++
        });

        // render the messages and remove the loader from .messages
        $(".messages").html(messageElements)
        $(".messageWrapper span img, .messageWrapper span video").css("max-height", $(".messagesContainer").height())

        my.openChat = this;

      }
      )
  }
  message() {
    // won't send a message if another one is being sent
    if (this.isSending) {
      Toast.fire({
        icon: "info",
        text: "wait til your previous message sends"
      })
      return;
    }

    // define the textarea with the message and the content (the message itself)
    let $msgBox = $(".msg"), message = $msgBox.val()
    if (message === "") {
      // will not send an empty message
      Toast.fire({ icon: "info", text: "You can't send an empty message" })
      return;
    }

    // clears the textarea of content and fixes its height
    $msgBox.val("")
    fixMessageBoxHeight($msgBox)


    let $newMsg = $(document.createElement("div"))
    $newMsg[0].className = "messageWrapper myMessage"
    $newMsg.css("opacity", .5)

    $newMsg.append(`
      <img src="${my.picture}">
      <span></span>
    `)

    // ensures shift enter whitespace is html complient
    $newMsg.find("span").text(message)
    $newMsg.find("span").html($newMsg.find("span").text().replaceAll("\n", "<br>"))

    // appends the new message to the .messages div
    $(".messages").append($newMsg)

    // move dm to the top of the list
    if (!this.$element.is(":first-child")) {
      this.$element.prependTo("#profiles-list")
    }

    // set last message
    let $lastMsgSpan = this.$element.find(".lastMsg")
    $lastMsgSpan.addClass("mute italic").text("Sending")

    // sets isSending to true before sending the message
    this.isSending = true
    // if the socket is open then we send through the socket but if it's not then the message gets sent post like normal
    if (my.socket.socket.readyState == my.socket.socket.OPEN) {
      my.socket.send("M", [this.username, {
        type: false,
        date: new Date().toLocaleTimeString(),
        content: message,
      }], (result) => {
          // resets the isSending to false
          this.isSending = false

          // set last message
          $lastMsgSpan.removeClass("mute italic").html("&nbsp;")

          if (result.ok) {
            // set last message
            $lastMsgSpan.text(message)
            // if the message sent it changes the transparency of the message element in the .messages div
            $newMsg.css("opacity", 1)
          } else {
            // if the message failed to send it fires an error toast
            Toast.fire({ icon: "error", title: result.statusText })
          }
          this.msgId++
        })
    } else {
      $.post("processes", {
        process: "sendMessage",
        data: JSON.stringify([this.username, message])
      })
      .then((result) => {
        // resets the isSending to false
        this.isSending = false
  
        // set last message
        $lastMsgSpan.removeClass("mute italic").html("&nbsp;")
  
        // verify the result is indeed json and sets the result to the json value
        if (result = verifyResultJSON(result)) {
          if (result.ok) {
            // set last message
            $lastMsgSpan.text(message)
            // if the message sent it changes the transparency of the message element in the .messages div
            $newMsg.css("opacity", 1)
          } else {
            // if the message failed to send it fires an error toast
            Toast.fire({ icon: "error", text: result.statusText })
          }
          this.msgId++
        }
      })
    }
  }
  receive(message) {
    let $newMsg;
    let $lastMsgSpan = this.$element.find(".lastMsg")
    // if the message doesn't have a subsequent type variable it is NOT media
    if (!message.type) {
      // generate the html text message
      $newMsg = $(`
        <div class="messageWrapper" title="${message.date}">
          <img src="${this.picture}">
          <span>${message.content.replaceAll("\n", "<br>")}</span>
        </div>
      `);
      // set last message
      $lastMsgSpan.text(message.content);
    } else {
      // this is used for messages that ARE media
      $newMsg = $(this.createMediaMsg(message.type, message.src, message.date, message.original, false))
      // set last message
      $lastMsgSpan.text(message.original)
    }

    if (my.openChat === undefined || my.openChat.username != this.username) return;

    // render the messages and remove the loader from .messages
    $(".messages").append($newMsg);
    $(".messageWrapper span img, .messageWrapper span video").css("max-height", $(".messagesContainer").height())
  }
}

function upload(chat) {
  let $status, $innerBar
  function byteConverter(bytes) {
    var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    if (bytes == 0) return 'n/a';
    var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
    if (i == 0) return bytes + ' ' + sizes[i];
    return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + sizes[i];
  }
  function modal() {
    Swal.fire({
      title: 'Upload',

      html: `
      <div class="uploadField" id='uploaderDiv' onclick="$('#fileInput').click();">
        <p id="uploadBoxP">Click here or drag and drop</p>
      </div>
      <form style='display:none;' class='uploadForm' method='post' enctype='multipart/form-data'>
        <input style='display:none;' type='file' id='fileInput'>
      </form>
      `,

      didOpen: () => {
        $("#fileInput").on("change", function () {
          let file = this.files[0]
          $("#uploadBoxP").html(((file) ? `${file.name}<br><span style="color:orange">${byteConverter(file.size)}</span>` : "Click here or drag and drop"))
        })
      },

      showCancelButton: true,
      reverseButtons: true,
      allowEscapeKey: () => !Swal.isLoading(),
      confirmButtonText: 'Upload and Send',
      showLoaderOnConfirm: true,
      preConfirm: () => {
        let file = $("#fileInput")[0].files[0]
        if (!file) {
          Swal.showValidationMessage(
            `gotta select somethin my boy`
          )
          return;
        }

        send(file)

        // prevents popup from closing
        return false
      },
      backdrop: true,
      allowOutsideClick: () => !Swal.isLoading()
    })
  }
  function send(file) {
    let newUploadDiv = document.createElement('div');
    newUploadDiv.id = "uploaderDiv";
    newUploadDiv.innerHTML = `
      <div class="uploadingContainer">
        <div class="inlineRow">
          <label for="outerBar" id="fileNameLabel">${file.name}</label>
          <div id="bytes" class="status" style="float:right;">0B/${byteConverter(file.size)}</div>
        </div>
        <div id="outerBar" class="outerBar">
          <div id="innerBar" class="innerBar"></div>
        </div>
        <div id="status" class="status">waiting...</div>
      </div>
    `
    $(Swal.getHtmlContainer()).html(newUploadDiv)
    $innerBar = $("#innerBar")
    $status = $("#status")

    var formdata = new FormData()
    formdata.append("file", file)
    formdata.append("data", chat.username)
    formdata.append("process", "sendFile")

    var ajax = new XMLHttpRequest()
    ajax.upload.addEventListener("progress", progressHandler, false)
    ajax.addEventListener("load", completeHandler, false)
    ajax.addEventListener("error", errorHandler, false)
    ajax.addEventListener("abort", abortHandler, false)
    ajax.onerror = function (e) {
      Swal.fire(
        "Error",
        "A network error has occurred. Some services may be unavailable.",
        "error"
      )
    }
    ajax.open("POST", "processes")
    ajax.send(formdata)
  }
  function progressHandler(event) {
    $("#bytes").html(`Uploaded ${byteConverter(event.loaded)}/${byteConverter(event.total)}`)

    var percent = Math.round((event.loaded / event.total) * 100)

    $innerBar.animate({ "width": percent + "%" }, .15)
    $innerBar.html(percent + "%")

    if (percent == 100) {
      $status.css({ fontWeight: "bold", textDecoration: "underline" })
      $status.html("Finalizing, please wait just a little longer...")
    } else {
      $status.html(percent + "% uploaded... please wait");
    }
  }
  function completeHandler(event) {
    let result = event.target.responseText
    console.log(result)

    result = verifyResultJSON(result)

    if (!result) {
      Swal.fire(
        "Error",
        "Failed to send file",
        "error"
      )
      return;
    }
    if (!result.ok) {
      Swal.fire(
        "Error",
        result.statusText,
        "error"
      )
      return;
    }
    Toast.fire({
      title: "Successfully sent file",
      icon: "success"
    })

    my.socket.send("M", [chat.username, result])

    chat.createMediaMsg(result.type, result.src, result.date, result.original, true, true)
    chat.$element.find(".lastMsg").text(result.lastMsg)
    // move dm to the top of the list
    if (!chat.$element.is(":first-child")) {
      chat.$element.prependTo("#profiles-list")
    }
    chat.msgId++
  }
  function errorHandler(event) {
    Swal.fire(
      "Error",
      "Failed to send file",
      "error"
    )
  }
  function abortHandler(event) {
    Swal.fire(
      "Error",
      "Failed to send file",
      "error"
    )
  }

  modal()
}

const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
  didOpen: (toast) => {
    toast.addEventListener('mouseenter', Swal.stopTimer)
    toast.addEventListener('mouseleave', Swal.resumeTimer)
  }
})

function verifyResultJSON(result) {
  let parsed
  try {
    parsed = JSON.parse(result)
  }
  catch (error) {
    console.log(error)
    if (result == "SESS") {
      Swal.fire(
        "Info",
        "Your session has expired you will be reloaded momentarily",
        "info"
      )
      setTimeout(() => {
        window.location.reload()
      }, 3000);
    } else {
      Swal.fire({
        title: "Error",
        text: "Error code: " + result,
        icon: "error",
        timer: 5000,
        timerProgressBar: true
      }).then(function () {
        // window.location.reload()
      })
    }
    return false
  }
  return parsed
}

function receiveMessage(msg) {
  console.log("MSG: ", msg);

  const parsed = verifyResultJSON(msg.data);

  if (parsed.sendId) {
    if (typeof my.socket.waitingActions[parsed.sendId] == "function") {
      my.socket.waitingActions[parsed.sendId](parsed);
      delete my.socket.waitingActions[parsed.sendId];
    }
  }

  if (parsed.sender) {
    my.chats[parsed.sender].receive(parsed.message)
  }
}

class MyWebSocket {
  constructor(onopen = false) {
    this.connectionAttempts = 0;
    this.sendId = 0;
    this.waitingActions = {};

    if (typeof onopen == "function") this.onopen = onopen;
  }

  init() {
    const host = "wss://earthcow.xyz/_ws_/";
    try {
      this.socket = new WebSocket(host);
      console.log('WebSocket - status ' + this.socket.readyState);
      this.socket.onopen = (msg) => {
        this.connectionAttempts = 0;
        Swal.close();
        if (this.onopen) this.onopen();
        console.log("Welcome - status " + this.readyState);
      };
      this.socket.onmessage = receiveMessage;
      this.socket.onclose = (msg) => {
        console.log("Disconnected from websocket will try to reconnect");
        this.reconnect();
      };
    }
    catch (ex) {
      console.log(ex);
    }
  }

  send(instruction, content, callback) {
    if (this.socket.readyState != this.socket.OPEN) return false;
    try {
      if (typeof callback == "function") this.waitingActions[this.sendId] = callback;
      this.socket.send(JSON.stringify({ instruction: instruction, content: content, sendId: this.sendId++ }));
    } catch (ex) {
      console.log(ex);
    }
  }

  reconnect() {
    if (this.socket.readyState != this.socket.CLOSED) return;
    if (this.connectionAttempts++ > 1) {
      Toast.fire({ title: "Disconnected! Attempting to reconnect...", icon: "info", didOpen: () => Swal.showLoading(), timer: false });
      setTimeout(() => {
        console.log("Attempting to reconnect");
        this.init()
      }, 5000)
      return;
    }
    console.log("Attempting to reconnect");
    this.init()
  }

}

$(function () {
  $(".main").css("transform", "scale(1)")

  document.addEventListener("visibilitychange", function () {
    if (document.visibilityState === 'visible') {
      my.socket.reconnect();
    }
  });

  $.post("processes", { process: "getLogin", data: 869 }, function (result) {
    const parsed = verifyResultJSON(result);
    my.socket = new MyWebSocket(function () {
      my.socket.send("L", parsed)
    });
    my.socket.init();

  })

  my.getChats();
})

window.onresize = function (event) {
  $(".messageWrapper span img, .messageWrapper span video").css("max-height", $(".messagesContainer").height())
}

function actallyHideMsgBlock() {
  $(".profiles-block").width("calc(250px + 50vw)")
  $(".messages-block").css({ "min-width": "unset", "width": 0, "opacity": 0 })
}

function showMsgBlock() {
  $(".profiles-block").width(250)
  $(".messages-block").css({ "width": "50vw", "opacity": 1 })
  // need to add min width somewhere
}

function fixMessageBoxHeight(msgBox) {
  msgBox[0].style.height = 0;
  msgBox[0].style.height = ((msgBox[0].scrollHeight > 1000) ? 1000 : msgBox[0].scrollHeight) + "px";

  if ((msgBox[0].scrollHeight - 10) > msgBox.height()) {
    msgBox.css("overflow-y", "scroll")
  } else msgBox.css("overflow-y", "hidden")
}

function showProfileList() {
  $(".profiles-block").show()
}

function previewMedia(type, src) {

}

function adminRequest(request = "clear", data = 896) {
  $.post("processes", {
    process: "administrator",
    data: JSON.stringify({ request: request, data: data })
  })
    .then(function (result) {
      console.log(result)
    })
}