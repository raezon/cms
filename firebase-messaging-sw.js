importScripts('https://www.gstatic.com/firebasejs/8.10.0/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.10.0/firebase-messaging.js');
firebase.initializeApp({apiKey: "AIzaSyAiflt3Nr-jVGA0Mrhfxp6HmDoVSYlrGh8",authDomain: "overdoseshawarma-8b8ba.firebaseapp.com",projectId: "overdoseshawarma-8b8ba",storageBucket: "overdoseshawarma-8b8ba.appspot.com", messagingSenderId: "701237630047", appId: "1:701237630047:web:70d86707648613f29a77ea"});
const messaging = firebase.messaging();
messaging.setBackgroundMessageHandler(function (payload) { return self.registration.showNotification(payload.data.title, { body: payload.data.body ? payload.data.body : '', icon: payload.data.icon ? payload.data.icon : '' }); });
