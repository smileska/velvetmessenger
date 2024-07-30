<?php
$title = "Chat with " . htmlspecialchars($chatUser['username']);
require('parts/head.php');
require('parts/navbar.php');
?>
<main>
    <div class="container-chat">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900">Chat with <?= htmlspecialchars($chatUser['username']); ?></h1>
        <div id="chat-box" class="chat-box h-96 overflow-y-scroll border border-gray-300 p-4 bg-white rounded-lg shadow-sm">
            <!-- poraki -->
        </div>

        <form method="post" id="chat-form" class="mt-4 flex">
            <input type="hidden" id="recipient" value="<?= htmlspecialchars($chatUser['username']); ?>">
            <input type="text" id="message" placeholder="Type your message here" required class="flex-grow p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="btn-primary ml-3 px-4 py-1 bg-blue-500 text-white rounded-lg shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">Send</button>
        </form>
    </div>
</main>
<script type="text/javascript">
    var conn = new WebSocket('ws://localhost:8080');

    conn.onopen = function(e) {
        console.log("Connection established!");
    };

    conn.onmessage = function(e) {
        console.log("Received message:", e.data);
        const messageData = JSON.parse(e.data);
        const chatBox = document.getElementById('chat-box');
        const messageElement = createMessageElement(messageData);
        chatBox.appendChild(messageElement);
        chatBox.scrollTop = chatBox.scrollHeight;
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
        const recipient = document.getElementById('recipient').value;
        const message = messageInput.value;

        const messageData = JSON.stringify({
            sender: '<?= htmlspecialchars($_SESSION['username']); ?>',
            recipient: recipient,
            message: message
        });

        conn.send(messageData);
        messageInput.value = '';
    });

    document.addEventListener('DOMContentLoaded', function() {
        const chatBox = document.getElementById('chat-box');
        const recipient = document.getElementById('recipient').value;

        fetch(`/get-messages/${recipient}`)
            .then(response => response.json())
            .then(messages => {
                messages.forEach(message => {
                    const messageElement = createMessageElement(message);
                    chatBox.appendChild(messageElement);
                });
                chatBox.scrollTop = chatBox.scrollHeight;
            });
    });

    function createMessageElement(messageData) {
        const messageElement = document.createElement('div');
        messageElement.classList.add('p-2', 'mb-2', 'rounded-lg');

        if (document.body.classList.contains('dark-mode')) {
            if (messageData.sender === '<?= htmlspecialchars($_SESSION['username']); ?>') {
                messageElement.classList.add('bg-blue-200', 'self-end');
                messageElement.textContent = `You: ${messageData.message}`;
            } else {
                messageElement.classList.add('bg-gray-200', 'self-start');
                messageElement.textContent = `${messageData.sender}: ${messageData.message}`;
            }
        } else {
            if (messageData.sender === '<?= htmlspecialchars($_SESSION['username']); ?>') {
                messageElement.classList.add('bg-blue-100', 'self-end');
                messageElement.textContent = `You: ${messageData.message}`;
            } else {
                messageElement.classList.add('bg-gray-100', 'self-start');
                messageElement.textContent = `${messageData.sender}: ${messageData.message}`;
            }
        }

        return messageElement;
    }

</script>