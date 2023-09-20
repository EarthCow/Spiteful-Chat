// Instant worker activation
self.addEventListener("install", (evt) => self.skipWaiting());

// Claims control instantly
self.addEventListener("activate", (evt) => self.clients.claim());

// Listens for a notification push
self.addEventListener("push", (evt) => {
  // Check if any client is focused and visible
  let push = true;
  self.clients.matchAll().then((clients) => {

    clients.forEach((client) => {
      if (client.visibilityState === 'visible') {
        push = false;
        return;
      }
    });

    // If no clients are visible send a notification
    if (push) {
      const data = evt.data.json();
      self.registration.showNotification(data.title, {
        body: data.body,
        icon: data.icon,
        image: data.image,
      });
    }
  });

});

// Listens for a notification click
self.addEventListener("notificationclick", (event) => {
  // Closes the notification
  event.notification.close();

  // Focuses the first found client or opens a new tab if none are found
  event.waitUntil(
    clients
      .matchAll({
        type: "window",
      })
      .then((clientList) => {
        if (clientList[0]) {
          return clientList[0].focus();
        } else {
          if (clients.openWindow) return clients.openWindow("/spiteful-chat/");
        }
      }),
  );
});