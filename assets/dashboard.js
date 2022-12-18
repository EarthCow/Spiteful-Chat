const myPfp = $("#myPfp").prop("src");

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
  if (!result.includes("{")) {
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
        //window.location.reload()
      })
    }
    return false
  } else return JSON.parse(result)
}

let activeDms = []
function getActiveDms() {
  $.post("processes", { process: "getActiveDms", data: "896" }).then(result => {
    if (result = verifyResultJSON(result)) {
      if (!result.ok) {
        Toast.fire({
          icon: "error",
          title: "Error",
          text: result.statusText
        })
      } else {
        if (!result.chats) {
          activeDms = []
          return false
        } else {
          activeDms = []

          // Hide the loading animation
          $(".profiles-block .loader").hide()

          result.chats.forEach(chat => {
            // uses the time from the ordered array to get the key for the chat

            $("#profiles-list").append(`
            <li data-identifier="dm-${chat.username}">
              <div class="wrapper">
                <img src="${chat.picture}">
                <span>
                  <strong>${chat.username}</strong>
                  <br>
                  <span></span>
                </span>
              </div>
            </li>
            `)

            $("#profiles-list li:last-child").on("click", function () {
              focusDm(chat.username, chat.name)
            })

            // This must be done right now to prevent html
            $(`[data-identifier=dm-${chat.username}]`).find("span").eq(1).text(((chat.last_message) ? chat.last_message : ""))

            // Push the username to the array of all DMs
            activeDms.push(chat.username)
          })
        }
      }
    }
  });
}


$(function () {
  $(".main").css("transform", "scale(1)")
  getActiveDms();
})

function actallyHideMsgBlock() {
  $(".profiles-block").width("calc(250px + 50vw)")
  $(".messages-block").css({"min-width":"unset","width":0,"opacity":0})
}

function showMsgBlock() {
  $(".profiles-block").width(250)
  $(".messages-block").css({"width":"50vw","opacity":1})
  // need to add min width somewhere
}

function profileEditor() {
  Swal.fire({
    title: "Edit Profile"
  })
}

function settings() {
  Swal.fire({
    title: "Settings"
  })
}

function fixMessageBoxHeight(msgBox) {
  msgBox[0].style.height = 0;
  msgBox[0].style.height = ((msgBox[0].scrollHeight > 1000) ? 1000 : msgBox[0].scrollHeight) + "px";

  if ((msgBox[0].scrollHeight - 10) > msgBox.height()) {
    msgBox.css("overflow-y", "scroll")
  } else msgBox.css("overflow-y", "hidden")
}

let msgId = 0, isSending = false

function sendMessage(recipient) {
  // won't send a message if another one is being sent
  if (isSending) {
    Toast.fire({
      icon: "info",
      text: "wait til your previous message sends"
    })
  } else {
    // define the textarea with the message and the content (the message itself)
    let msgBox = $(".msg"), message = msgBox.val()
    if (message === "") {
      // will not send an empty message
      Toast.fire({ icon: "info", text: "You can't send an empty message" })
    } else {
      // clears the textarea of content and fixes its height
      msgBox.val("")
      fixMessageBoxHeight(msgBox)

      // appends the new message to the .messages div
      $(".messages").append(`
        <div class="messageWrapper myMessage" id="msg${msgId}" style="opacity:.5">
          <img src="${myPfp}">
          <span></span>
        </div>
      `)

      // ensures shift enter whitespace is html complient
      $("#msg" + msgId).find("span").text(message)
      $("#msg" + msgId).find("span").html($("#msg" + msgId).find("span").text().replaceAll("\n", "<br>"))

      // move dm to the top of the list
      let $dmElm = $(`[data-identifier=dm-${recipient}]`)

      if (!$dmElm.is(":first-child")) {
        $dmElm.prependTo("#profiles-list")
      }

      // set last message
      let lastMsgSpan = $dmElm.find("span").eq(1)
      lastMsgSpan.addClass("mute italic").text("Sending")

      // sets isSending to true before sending the message
      isSending = true
      $.post("processes", { process: "sendMessage", data: JSON.stringify([recipient, message]) })
        .then((result) => {
          // resets the isSending to false
          isSending = false

          // set last message
          lastMsgSpan.removeClass("mute italic").html("&nbsp;")

          // verify the result is indeed json and sets the result to the json value
          if (result = verifyResultJSON(result)) {
            if (result.ok) {
              // set last message
              lastMsgSpan.text(message)
              // if the message sent it changes the transparency of the message element in the .messages div
              $("#msg" + msgId).css("opacity", 1)
            } else {
              // if the message failed to send it fires an error toast
              Toast.fire({ icon: "error", text: result.statusText })
            }
            msgId++
          }
        })
    }
  }
}

function showProfileList() {
  $(".profiles-block").show()
}

function focusDm(recipient, recipientName) {
  // verify the recipient was added to the activeDms array
  if (!activeDms.includes(recipient)) {
    Toast.fire({
      icon: "error",
      title: "Error"
    })
  } else {
    // this is purely for the mobile version
    // on the mobile version the .profiles-list collapses whenever a dm is selected
    if ($(".profiles-block").css("position") == "absolute") {
      $(".profiles-block").hide()
    }
    // create the .message-block elements
    let recipientImage = $(`[data-identifier=dm-${recipient}] img`).prop("src")
    $(".messages").html(`
      <div class="loader"></div>
      <label>Loading Messages</label>
    `)
    if ($(".recipientBlock img").prop("src") != recipientImage) {
      $(".recipientBlock img").prop("src", recipientImage)
      $(".recipientBlock span").eq(0).text(recipientName)
      $(".recipientBlock span").eq(1).text("@" + recipient)

      // set active dm
      if ($(".active-dm")[0]) $(".active-dm").removeClass("active-dm")
      $(`[data-identifier=dm-${recipient}]`).addClass("active-dm")

      // set listeners

      let msgBox = $(".msg")

      msgBox.off()

      msgBox.on("keyup", function (e) {
        fixMessageBoxHeight(msgBox)
      })
      msgBox.on("keydown", function (e) {
        // if the user presses enter without shif it will submit
        if (e.which == 13 && !e.shiftKey) {
          // prevent a new line from being inputed
          e.preventDefault()
          msgBox.next().click()
        }
      })

      $(".sendBtn").off()

      $(".sendBtn").on("click", () => {sendMessage(recipient)})
    }

    // hide the "nothing to see here" text
    $(".no-profile-selection").hide()

    showMsgBlock()

    setTimeout (function (){
      $(".recipientBlock").show(400)
      $(".messagesContainer").show(500)
      $(".messageBar").show(400)
    }, 500)

    

    $(".msg").focus()

    

    // initiate the request for the messages
    $.post("processes", { process: "getMessages", data: recipient })
      .then(result => {
        // check for valid json and get json encoded var
        if (result = verifyResultJSON(result)) {
          // verify the result of the request
          if (!result.ok) {
            Swal.fire(
              "Error",
              result.statusText,
              "error"
            )
          } else {
            // set variable for the html messages to be iterated and appended to
            let messageElements = ""
            // run the iteration on each message
            result.messages.forEach(message => {
              // if the message doesn't have a subsequent media variable it is NOT media
              if (!message.type) {
                // generate the html text message
                messageElements += `
                  <div class="messageWrapper${((message.mine) ? " myMessage" : "")}" title="${message.date}">
                    <img src="${((message.mine) ? myPfp : recipientImage)}">
                    <span>${message.content.replaceAll("\n", "<br>")}</span>
                  </div>
                `
              } else {
                // this is used for messages that ARE media

                // split the mime type and get the first type (video/mp4 -> "video")
                var generalType = message.type.split("/")[0]
                // switch through the general types to create type specific message elements
                switch (generalType) {
                  case "image":
                    var spanContent = `<img src="${message.content}">`
                    break;
                  case "video":
                    var spanContent = `
                      <video controls>
                        <source src="${message.content}" type="${message.type}">
                      </video>
                    `
                    break;
                  default:
                    // for any media (files) that are not listed above
                    var spanContent = `
                      <div class="fileMsg">
                        <span>
                          <button><i class="fa-solid fa-eye"></i></button>
                          <i class="fa-solid fa-file"></i>
                          &nbsp;
                          ${message.original}
                        </span>
                        &nbsp;
                        <a href="${message.content}&download"><i class="fa-solid fa-download"></i></a>
                      </div>
                    `
                    break;
                }
                
                messageElements += `
                  <div class="messageWrapper${((message.mine) ? " myMessage" : "")}" title="${message.date}">
                    <img src="${((message.mine) ? myPfp : recipientImage)}">
                    <span>${spanContent}</span>
                  </div>
                `
              }
            });

            // render the messages and remove the loader from .messages
            $(".messages").html(messageElements)

          }

        }
      }
      )}
}

function byteConverter(bytes) {
  var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
  if (bytes == 0) return 'n/a';
  var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
  if (i == 0) return bytes + ' ' + sizes[i];
  return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + sizes[i];
};

function fileOnchange() {
  let file = $("#fileInput")[0].files[0],
    html = (file) ? `${file.name}<br><span style="color:orange">${byteConverter(file.size)}</span>` : "Click here or drag and drop"
  $("#uploadBoxP").html(html)
}

function currentRecipient() {
  return (($(".active-dm")[0]) ? $(".active-dm").attr("data-identifier").replace("dm-", "") : false)
}


function uploadModel() {
  Swal.fire({
    title: 'Upload',

    html: `
    <div class="uploadField" id='uploaderDiv' onclick="$('#fileInput').click();">
      <p id="uploadBoxP">Click here or drag and drop</p>
    </div>
    <form style='display:none;' class='uploadForm' method='post' enctype='multipart/form-data'>
      <input style='display:none;' type='file' id='fileInput' onchange='fileOnchange()'>
    </form>
    `,

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
      } else {

        let innerBar, status, fileIndex = 1;
        function upload(file) {
          newUploadDiv = document.createElement('div');
          newUploadDiv.id = "uploaderDiv";
          newUploadDiv.innerHTML = `
            <div class="uploadingContainer">
              <div class="inlineRow">
                <label for='outerBar' id='fileNameLabel'>${file.name}</label>
                <div id='bytes' class='status' style='float:right;'>0B/${byteConverter(file.size)}</div>
              </div>
              <div id='outerBar' class='outerBar'>
                <div id='innerBar' class='innerBar'></div>
              </div>
              <div id='status' class='status'>waiting...</div>
            </div>
          `
          $(Swal.getHtmlContainer()).html(newUploadDiv)
          innerBar = $("#innerBar")
          status = $("#status")

          var formdata = new FormData()
          formdata.append("file", file)
          formdata.append("data", currentRecipient())
          formdata.append("process", "sendFile")

          var ajax = new XMLHttpRequest()
          ajax.upload.addEventListener("progress", progressHandler, false)
          ajax.addEventListener("load", completeHandler, false)
          ajax.addEventListener("error", errorHandler, false)
          ajax.addEventListener("abort", abortHandler, false)
          ajax.onerror = function (e) {
            notify("A network error has occurred. Some services may be unavailable.", "error")
          };
          ajax.open("POST", "processes")
          ajax.send(formdata)
        }

        function progressHandler(event) {
          $("#bytes").html(`Uploaded ${byteConverter(event.loaded)}/${byteConverter(event.total)}`)

          var percent = Math.round((event.loaded / event.total) * 100)

          innerBar.animate({ "width": percent + "%" }, .15)
          innerBar.html(percent + "%")

          if (percent == 100) {
            status.css({ fontWeight: "bold", textDecoration: "underline" })
            status.html("Finalizing, please wait just a little longer...")
          } else {
            status.html(percent + "% uploaded... please wait");
          }
        }
        function completeHandler(event) {
          let result = event.target.responseText
          console.log(result)
          if (!result.includes("{")) {
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
                window.location.reload()
              })
            }
          }

          result = JSON.parse(result)

          if (!result) {
            Swal.fire(
              "Error",
              "Failed to send file",
              "error"
            )
          } else {
            if (!result.ok) {
              Swal.fire(
                "Error",
                result.statusText,
                "error"
              )
            } else {
              Toast.fire({
                title: "Successfully sent file",
                icon: "success"
              })

              var generalType = result.type.split("/")[0]
              switch (generalType) {
                case "image":
                  var spanContent = `<img src="${result.src}">`
                  break;
                case "video":
                  var spanContent = `
                    <video controls>
                      <source src="${result.src}" type="${result.type}">
                    </video>
                  `
                  break;
                default:
                  var spanContent = `
                  <div class="fileMsg">
                    <span>
                      <i class="fa-solid fa-file"></i>
                      ${result.og}
                    </span>
                    <a href="${result.src}&download"><i class="fa-solid fa-download"></i></a>
                  </div>
                    `
                  break;
              }
              $(".messages").append(`
                  <div class="messageWrapper myMessage" title="${result.lm}">
                    <img src="${myPfp}">
                    <span>${spanContent}</span>
                  </div>
                `)
            }
          }
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

        upload(file)
        return false
        // prevents popup from closing
      }
    },
    backdrop: true,
    allowOutsideClick: () => !Swal.isLoading()
  })
}

function previewMedia(source, generalType) {

}

function newMessage() {
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
      if (username === "") {
        Swal.showValidationMessage(
          `Type something bro`
        )
      } else {
        if (activeDms.includes(username)) {
          Toast.fire({
            icon: "info",
            text: "you already have a converstation with " + username
          })
          focusDm(username)
        } else {
          if (RegExp(/[-!#@$%^&*()_+|~=`{}\[\]:";'<>?,.\\\/\s]/g).test(username)) {
            Swal.showValidationMessage(
              `Usernames cannot contain special characters or spaces`
            )
          } else {
            return $.post("processes", { process: "newRecipient", data: username })
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
          }
        }
      }
    },
    backdrop: true,
    allowOutsideClick: () => !Swal.isLoading()
  }).then((result) => {
    if (result.isConfirmed) {
      if (typeof result.value == "object") {
        let receiver = result.value.receiver.username
        if (result.value.alreadyExists) {
          Toast.fire({
            icon: "info",
            title: "You already have a conversation with @" + receiver
          })
          $("[data-identifier=dm-"+receiver+"]").click()
          return false
        }
        $("#profiles-list").prepend(`
          <li data-identifier="dm-${receiver}">
            <div class="wrapper">
              <img src="${result.value.receiver.picture}">
              <span>
                <strong>${receiver}</strong>
                <br>
                <span></span>
              </span>
            </div>
          </li>
        `)
        activeDms.push(receiver)
        $("#profiles-list li:first-child").on("click", function (event) {
          focusDm(receiver, result.value.receiver.name)
        })
  
        focusDm(receiver, result.value.receiver.name)
      }
    }
  })
}

function adminRequest(request = "clear", data = 896) {
  $.post("processes", {
    process:"administrator",
    data:JSON.stringify({request: request, data: data})
  })
  .then(function(result){
    console.log(result)
  })
}