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
        return messageElement;
    }

    document.getElementById('add-user-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const username = document.getElementById('user-to-add').value;
        fetch(`/chatroom/${chatroomId}/add-user`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({username: username})
        }).then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    fetchAndDisplayChatroomUsers();
                }
            });
    });

    document.getElementById('remove-user-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const username = document.getElementById('user-to-remove').value;
        fetch(`/chatroom/${chatroomId}/remove-user`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({username: username})
        }).then(response => response.json())
            .then(data => {
                alert(data.message);
                fetchAndDisplayChatroomUsers();
            });
    });

    document.getElementById('leave-chatroom-form').addEventListener('submit', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to leave this chatroom?')) {
            fetch(`/chatroom/${chatroomId}/leave`, {
                method: 'POST'
            }).then(() => window.location.href = '/');
        }
    });

    function fetchAndDisplayChatroomUsers() {
        fetch(`/chatroom/${chatroomId}/users`)
            .then(response => response.json())
            .then(users => {
                const usersList = document.getElementById('chatroom-users-list');
                usersList.innerHTML = '';
                users.forEach(user => {
                    const userItem = document.createElement('li');
                    userItem.textContent = user.username;
                    usersList.appendChild(userItem);
                });
            })
            .catch(error => console.error('Error fetching chatroom users:', error));
    }
</script>