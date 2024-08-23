<?php $title='Home' ?>
<?php require('parts/head.php') ?>
<?php require('parts/navbar.php') ?>
<?php require('parts/banner.php') ?>

<main>
    <div class="container mx-auto px-4 py-8">
        <?php if (isset($_SESSION['username'])): ?>
            <div class="mb-8">
                <h2 class="moving text-2xl font-bold mb-4">Create a Chatroom</h2>
                <form action="/create-chatroom" method="post" class="flex items-center">
                    <input type="text" name="name" placeholder="Chatroom Name" required class="flex-grow p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="btn-primary ml-3 px-4 py-2 bg-blue-500 text-white rounded-lg shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">Create Chatroom</button>
                </form>
            </div>

            <div>
                <h2 class="text-2xl font-bold mb-4">Your Chatrooms</h2>
                <div id="chatrooms-list">
                    <!-- Chatrooms will be loaded here via JavaScript -->
                </div>
            </div>
        <?php endif; ?>

        <!-- Existing search functionality -->
        <?php if (isset($_SESSION['username'])): ?>
            <div class="mt-8">
                <h2 class="moving-2 text-2xl font-bold mb-4">Search Users</h2>
                <form action="/search" method="post" class="flex items-center">
                    <input type="text" name="search_user" placeholder="Search for users" required class="flex-grow p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="btn-primary ml-3 px-4 py-2 bg-blue-500 text-white rounded-lg shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">Search</button>
                </form>
            </div>
        <?php else: ?>
            <div class="mt-8">
                <p>Log in or sign up to start talking!</p>
            </div>
        <?php endif; ?>
        <?php if (isset($users)): ?>
            <div class="mt-8">
                <h3 class="text-xl font-bold mb-4">Search Results:</h3>
                <ul class="space-y-4">
                    <?php foreach ($users as $user): ?>
                        <li class="flex items-center justify-between p-4 bg-white rounded-lg shadow">
                            <div class="flex items-center">
                                <img src="/<?= htmlspecialchars($user['image']) ?>" alt="<?= htmlspecialchars($user['username']) ?>" class="w-10 h-10 rounded-full mr-4">
                                <span class="font-semibold"><a class="link" href="/<?= htmlspecialchars($user['username'])?>"><?= htmlspecialchars($user['username']) ?></a></span>
                            </div>
                            <a href="/chat/<?= htmlspecialchars($user['username']) ?>" class="btn-primary px-4 py-2 bg-blue-500 text-white rounded-lg shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">Message</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
    let unreadCount = 0;
    const currentUsername = '<?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : ''; ?>';

    document.addEventListener('DOMContentLoaded', function() {
        loadChatrooms();
        setupNotifications();
    });
    function loadChatrooms() {
        fetch('/get-chatrooms')
            .then(response => response.json())
            .then(chatrooms => {
                const chatroomsList = document.getElementById('chatrooms-list');
                chatroomsList.innerHTML = '';
                const isDarkMode = document.body.classList.contains('dark-mode');

                if (chatrooms.length === 0) {
                    chatroomsList.innerHTML = '<p>You are in no chatrooms. Create one or join to get started!</p>';
                } else {
                    chatrooms.forEach(chatroom => {
                        const chatroomElement = document.createElement('div');
                        chatroomElement.className = `chatroom flex items-center justify-between p-4 rounded-lg shadow mb-4 ${isDarkMode ? 'bg-violet-900 text-white' : 'bg-white text-black'}`;
                        chatroomElement.innerHTML = `
                        <span class="font-semibold">${chatroom.name}</span>
                        <a href="/chatroom/${chatroom.id}" class="btn-primary px-4 py-2 bg-blue-500 text-white rounded-lg shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">Join</a>
                    `;
                        chatroomsList.appendChild(chatroomElement);
                    });
                }
            });
    }
    const observer = new MutationObserver(loadChatrooms);
    observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });


    function setupNotifications() {
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

        var conn = new WebSocket('ws://localhost:8080');

        conn.onopen = function(e) {
            console.log("Connection established!");
            conn.send(JSON.stringify({
                type: 'authentication',
                username: currentUsername
            }));
        };

        conn.onmessage = function(e) {
            console.log("Received message:", e.data);
            const messageData = JSON.parse(e.data);

            if (messageData.type === 'notification') {
                addNotification(messageData.content);
            } else if (messageData.type === 'message' && messageData.recipient === currentUsername) {
            } else if (messageData.type === 'reaction' && messageData.recipientUsername === currentUsername) {
                addNotification(`${messageData.senderUsername} reacted to your message`);
            }
        };
    }

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
            const noNotificationsElement = list.querySelector('.no-notifications');
            if (noNotificationsElement) {
                list.removeChild(noNotificationsElement);
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

</script>