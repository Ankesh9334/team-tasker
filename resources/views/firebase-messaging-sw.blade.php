// Scripts for firebase and firebase messaging
importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js');

// Initialize the Firebase app in the service worker by passing in the
// messagingSenderId.
// These values will be filled dynamically if we use a better approach, 
// but for now, we expect them to be set or the user can manually edit this file.
const firebaseConfig = {
    apiKey: "{{ config('services.firebase.api_key') }}",
    authDomain: "{{ config('services.firebase.auth_domain') }}",
    projectId: "{{ config('services.firebase.project_id') }}",
    storageBucket: "{{ config('services.firebase.storage_bucket') }}",
    messagingSenderId: "{{ config('services.firebase.messaging_sender_id') }}",
    appId: "{{ config('services.firebase.app_id') }}",
    measurementId: "{{ config('services.firebase.measurement_id') }}"
};

firebase.initializeApp(firebaseConfig);

const messaging = firebase.messaging();

messaging.onBackgroundMessage(function (payload) {
    const notificationTitle = payload.notification ? payload.notification.title : (payload.data?.title || 'New Notification');
    const notificationOptions = {
        body: payload.notification ? payload.notification.body : (payload.data?.body || ''),
        icon: '/images/logo.png',
        data: payload.data || {}
    };

    return self.registration.showNotification(notificationTitle, notificationOptions);
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    const data = event.notification.data || {};
    let targetUrl = '/';
    if (data.conversation_id) {
        targetUrl = '/admin/chat?conversation_id=' + data.conversation_id;
    } else if (data.task_id) {
        targetUrl = '/admin/tasks/details/' + data.task_id;
    } else if (data.ticket_id) {
        targetUrl = '/admin/tickets/' + data.ticket_id;
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(clientList) {
            for (let i = 0; i < clientList.length; i++) {
                let client = clientList[i];
                if ('focus' in client) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
