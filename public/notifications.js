let unreadCount = 0;
const currentUsername = document.getElementById('current-username').value;

function updateNotificationCount() {
    const countElement = document.getElementById('notificationCount');
    if (countElement) {
        if (unreadCount > 0) {
            countElement.textContent = unreadCount;
            countElement.classList.remove('hidden');
        } else {
            countElement.classList.add('hidden');
        }
    }
}

function addNotification(message) {
    const list = document.getElementById('notificationList');
    if (list) {
        if (list.firstChild.textContent === 'No new notifications') {
            list.innerHTML = '';
        }
        const notificationElement = document.createElement('div');
        notificationElement.classList.add('px-4', 'py-2', 'text-sm', 'text-gray-700', 'hover:bg-gray-100');
        notificationElement.textContent = message;
        list.insertBefore(notificationElement, list.firstChild);

        if (list.children.length > 5) {
            list.removeChild(list.lastChild);
        }
    }
    unreadCount++;
    updateNotificationCount();
}

document.addEventListener('DOMContentLoaded', function() {
    const bell = document.getElementById('notificationBell');
    const dropdown = document.getElementById('notificationDropdown');

    if (bell && dropdown) {
        bell.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            console.log('Notification bell clicked');

            dropdown.classList.toggle('hidden');
            if (!dropdown.classList.contains('hidden')) {
                unreadCount = 0;
                updateNotificationCount();
            }
        });

        document.addEventListener('click', function(event) {
            if (!bell.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });
    }

    updateNotificationCount();
});

const conn = new WebSocket('ws://localhost:8080');

conn.onopen = function(e) {
    console.log("Connection established!");
    conn.send(JSON.stringify({
        type: 'authentication',
        username: currentUsername
    }));
};

conn.onmessage = function(e) {
    const messageData = JSON.parse(e.data);
    console.log("Received message:", messageData);

    if (messageData.type === 'notification' || (messageData.type === 'message' && messageData.recipient === currentUsername)) {
        addNotification(`New message from ${messageData.sender}`);

        const chatBox = document.getElementById('chat-box');
        if (chatBox && typeof createMessageElement === 'function') {
            const messageElement = createMessageElement(messageData);
            chatBox.appendChild(messageElement);
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    }
};