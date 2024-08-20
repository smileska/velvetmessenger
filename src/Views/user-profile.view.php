<?php $title = htmlspecialchars($profileUser['username']) . "'s Profile"; ?>
<?php require('parts/head.php') ?>
<?php require('parts/navbar.php') ?>

<header class="bg-color banner-shadow">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900 banner-text">
            <?= htmlspecialchars($profileUser['username']) ?>'s Profile
        </h1>
    </div>
</header>

<div class="profile-container mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <img class="profile-image" src="<?= !empty($profileUser['image']) ? htmlspecialchars($profileUser['image']) : "ui/icons/default.png" ?>" alt="User Image">
    <div class="status-container">
        <?php
        $status = isset($profileUser['status']) ? $profileUser['status'] : 'Offline';
        $statusIcon = ($status === 'Online') ? 'onlajn.png' : 'oflajn.png';
        ?>
        <img class="status-icon" src="ui/icons/<?= $statusIcon ?>" alt="status_img">
        <span><?= htmlspecialchars($status) ?></span>
    </div>
    <?php if ($_SESSION['username'] !== $profileUser['username']): ?>
        <div class="mt-4 flex justify-center">
            <a href="/chat/<?= htmlspecialchars($profileUser['username']) ?>" class="btn-msg rounded-md px-6 py-3 text-sm font-semibold shadow-sm inline-block">
                Message
            </a>
        </div>
    <?php endif; ?>
</div>
<script>
    let unreadCount = 0;
    const currentUsername = '<?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : ''; ?>';

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

        var conn = new WebSocket('ws://localhost:8080');

        conn.onopen = function(e) {
            console.log("Connection established!");
        };

        conn.onmessage = function(e) {
            const messageData = JSON.parse(e.data);
            console.log("Received message:", messageData);

            if (messageData.type === 'reaction') {
            } else if (messageData.recipient === currentUsername) {
                addNotification(`New message from ${messageData.sender}`);

                const chatBox = document.getElementById('chat-box');
                if (chatBox) {
                    const messageElement = createMessageElement(messageData);
                    chatBox.appendChild(messageElement);
                    chatBox.scrollTop = chatBox.scrollHeight;
                }
            }
        };
    });
</script>