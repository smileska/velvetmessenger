<?php
$title = "Chatroom: " . htmlspecialchars($chatroomName);
require('parts/head.php');
require('parts/navbar.php');
?>

<main>
    <div class="container-chat">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900">Chatroom: <?= htmlspecialchars($chatroomName); ?></h1>
        <div id="chat-box" class="chat-box h-96 overflow-y-scroll border border-gray-300 p-4 bg-white rounded-lg shadow-sm">
            <!-- poraki -->
        </div>

        <form method="post" id="chat-form" class="mt-4 flex">
            <input type="hidden" id="chatroom_id" value="<?= htmlspecialchars($chatroomId); ?>">
            <input type="text" id="message" placeholder="Type your message here" required class="flex-grow p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="btn-primary ml-3 px-4 py-2 bg-blue-500 text-white rounded-lg shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">Send</button>
        </form>

        <div class="mt-4">
            <h3 class="text-xl font-bold mb-2">Manage Users</h3>
            <form id="add-user-form" class="mb-2 flex">
                <input type="text" id="user-to-add" placeholder="Username to add" required class="flex-grow p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="btn-primary ml-3 px-4 py-2 bg-blue-500 text-white rounded-lg shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">Add User</button>
            </form>
            <form id="remove-user-form" class="mb-2 flex">
                <input type="text" id="user-to-remove" placeholder="Username to remove" required class="flex-grow p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="btn-primary ml-3 px-4 py-2 bg-blue-500 text-white rounded-lg shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">Remove User</button>
            </form>
            <form id="leave-chatroom-form">
                <button type="submit" class="btn-primary px-4 py-2 bg-red-500 text-white rounded-lg shadow hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50">Leave Chatroom</button>
            </form>
        </div>

        <div class="mt-4">
            <h3 class="text-xl font-bold mb-2">Chatroom Users</h3>
            <ul id="chatroom-users-list" class="list-disc pl-5">
                <!-- korisnici -->
            </ul>
        </div>
    </div>
</main>

<script type="text/javascript">
    var conn = new WebSocket('ws://localhost:8080');
    var chatroomId = <?= json_encode($chatroomId) ?>;

    conn.onopen = function(e) {
        console.log("Connection established!");
    };

    conn.onmessage = function(e) {
        console.log("Received message:", e.data);
        const messageData = JSON.parse(e.data);

        if (messageData.chatroom_id == chatroomId) {
            const chatBox = document.getElementById('chat-box');
            const messageElement = createMessageElement(messageData);
            chatBox.appendChild(messageElement);
            chatBox.scrollTop = chatBox.scrollHeight;
            let messages = localStorage.getItem('chatroom-' + chatroomId);
            if (messages) {
                messages = JSON.parse(messages);
            } else {
                messages = [];
            }
            messages.push(messageData);
            localStorage.setItem('chatroom-' + chatroomId, JSON.stringify(messages));
        }
    };

    conn.onerror = function(error) {
        console.log('WebSocket Error: ' + error);
    };

    conn.onclose = function(e) {
        console.log('WebSocket Connection Closed. Reconnecting...');
        setTimeout(function() {
            conn = new WebSocket('ws://localhost:8080');
        }, 1000);
    };

    document.getElementById('chat-form').addEventListener('submit', function(event) {
        event.preventDefault();
        const messageInput = document.getElementById('message');
        const message = messageInput.value;

        const messageData = JSON.stringify({
            type: 'message',
            chatroom_id: chatroomId,
            sender_id: '<?= htmlspecialchars($_SESSION['user_id']); ?>',
            message: message
        });

        conn.send(messageData);
        messageInput.value = '';
    });

    document.getElementById('add-user-form').addEventListener('submit', function(event) {
        event.preventDefault();
        const userToAdd = document.getElementById('user-to-add').value;

        fetch(`/chatroom/${chatroomId}/add-user`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ username: userToAdd }),
        })
            .then(response => {
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    return response.json().then(data => ({status: response.status, body: data}));
                } else {
                    return response.text().then(text => ({status: response.status, body: text}));
                }
            })
            .then(result => {
                if (result.status !== 200) {
                    throw new Error(`HTTP error! status: ${result.status}, message: ${result.body.message || result.body}`);
                }
                alert(result.body.message);
                loadChatroomUsers();
            })
            .catch(error => {
                console.error('Error:', error);
                alert(`An error occurred while adding the user: ${error.message}`);
            });
    });

    function loadChatroomUsers() {
        fetch(`/chatroom/${chatroomId}/users`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(users => {
                const usersList = document.getElementById('chatroom-users-list');
                usersList.innerHTML = '';
                users.forEach(user => {
                    const li = document.createElement('li');
                    li.textContent = user.username;
                    usersList.appendChild(li);
                });
            })
            .catch(error => {
                console.error('Error loading chatroom users:', error);
            });
    }

    window.addEventListener('load', loadChatroomUsers);

    function createMessageElement(messageData) {
        const messageElement = document.createElement('div');
        messageElement.classList.add('p-2', 'mb-2', 'rounded-lg');
        if (messageData.sender_id === '<?= htmlspecialchars($_SESSION['user_id']); ?>') {
            messageElement.classList.add('bg-blue-100', 'self-end');
            messageElement.textContent = `You: ${messageData.message}`;
        } else {
            messageElement.classList.add('bg-gray-100', 'self-start');
            messageElement.textContent = `${messageData.sender_name}: ${messageData.message}`;
        }
        console.log('Creating message element...', messageElement);
        return messageElement;
    }


    function loadPreviousMessages() {
        console.log('Loading previous messages...');
        const messages = localStorage.getItem('chatroom-' + chatroomId);
        if (messages) {
            const parsedMessages = JSON.parse(messages);
            parsedMessages.forEach(messageData => {
                createMessageElement(messageData);
            });
        }
    }

    window.addEventListener('load', loadPreviousMessages);

</script>