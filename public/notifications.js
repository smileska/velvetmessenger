let unreadCount = 0;

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
    const dropdown = document.getElementById('notificationDropdown');
    if (dropdown) {
        const notificationElement = document.createElement('a');
        notificationElement.href = '#';
        notificationElement.classList.add('block', 'px-4', 'py-2', 'text-sm', 'text-gray-700', 'hover:bg-gray-100');
        notificationElement.textContent = message;
        dropdown.insertBefore(notificationElement, dropdown.firstChild);
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

            if (dropdown.style.display === 'none' || dropdown.style.display === '') {
                dropdown.style.display = 'block';
                unreadCount = 0;
                updateNotificationCount();
            } else {
                dropdown.style.display = 'none';
            }
        });

        document.addEventListener('click', function(event) {
            if (!bell.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.style.display = 'none';
            }
        });
    }

    updateNotificationCount();
});

const conn = new WebSocket('ws://localhost:8080');

conn.onopen = function(e) {
    console.log("Connection established!");
};

conn.onmessage = function(e) {
    const messageData = JSON.parse(e.data);
    if (messageData.type === 'notification' || (messageData.recipient === currentUsername)) {
        addNotification(`New message from ${messageData.sender}`);
    }
};