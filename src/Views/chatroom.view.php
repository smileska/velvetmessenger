<?php
$title = "Chatroom: " . htmlspecialchars($chatroomName);
require('parts/head.php');
require('parts/navbar.php');
?>
<?php
$currentUserId = $_SESSION['user_id'];
?>
<main>
    <input type="hidden" id="current-username" value="<?= htmlspecialchars($_SESSION['username']); ?>">
    <input type="hidden" id="current-user-id" value="<?= htmlspecialchars($_SESSION['user_id']); ?>">
    <div class="container-chat">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900">Chatroom: <?= htmlspecialchars($chatroomName); ?></h1>
        <div id="chat-box" class="chat-box h-96 overflow-y-scroll border border-gray-300 p-4 bg-white rounded-lg shadow-sm">
            <!-- poraki -->
        </div>

        <form method="post" id="chat-form" class="mt-4 mb-3 flex items-center" enctype="multipart/form-data">
            <input type="hidden" id="chatroom_id" value="<?= htmlspecialchars($chatroomId); ?>">
            <div class="flex-grow">
                <input type="text" id="message" placeholder="Type your message here" required class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="button" id="upload-audio-btn" class="ml-2 p-2 rounded-full hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z" />
                </svg>
            </button>
            <label for="image-upload" class="ml-2 p-2 rounded-full hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                </svg>
            </label>
            <input type="file" id="image-upload" accept="image/*" class="hidden" name="image">
            <div id="image-preview" class="mb-2 hidden">
                <img id="preview-image" src="" alt="Preview" class="max-w-xs max-h-40 rounded-lg">
                <button type="button" id="remove-image" class="ml-2 text-red-500 hover:text-red-700">Remove</button>
            </div>
            <button type="submit" class="btn-primary ml-2 px-4 py-2 bg-blue-500 text-white rounded-lg shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">Send</button>
        </form>

        <input type="file" id="audio-file-input" accept="audio/*" style="display:none;">
        <div id="manage-users-section" style="display: none;">
            <h3 class="text-xl font-bold mb-2">Manage Users</h3>
            <form id="add-user-form" class="mb-2 flex">
                <input type="text" id="user-to-add" placeholder="Username to add" required class="flex-grow p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="btn-primary ml-3 px-4 py-2 bg-blue-500 text-white rounded-lg shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">Add User</button>
            </form>
        </div>

        <div id="suggest-users-section" style="display: none;">
            <h3 class="text-xl font-bold mb-2">Suggest Users</h3>
            <form id="suggest-user-form" class="mb-2 flex">
                <input type="text" id="suggested-username" placeholder="Username to suggest" required class="flex-grow p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="btn-primary ml-3 px-4 py-2 bg-green-500 text-white rounded-lg shadow hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50">Suggest</button>
            </form>
        </div>

        <div class="mt-4">
            <h3 class="text-xl font-bold mb-2">Chatroom Users</h3>
            <ul id="chatroom-users-list" class="list-disc pl-5">
                <!-- korisnici -->
            </ul>
        </div>
        <div class="mt-4">
            <h3 class="text-xl font-bold mb-2">Suggested Users</h3>
            <ul id="suggested-users-list" class="list-disc pl-5">
                <!-- suggested -->
            </ul>
        </div>

        <div class="mt-4">
            <form id="leave-chatroom-form">
                <button type="submit" class="btn-primary px-4 py-2 bg-red-500 text-white rounded-lg shadow hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50">Leave Chatroom</button>
            </form>
        </div>
    </div>
</main>

<script type="text/javascript">
    const REACTION_TYPES = {
        1: '👍',
        2: '❤️',
        3: '😂',
        4: '😮',
        5: '😢',
        6: '🐴',
        7: '🍮',
        8: '🌹'
    };
    var conn = new WebSocket('ws://localhost:8080');
    var chatroomId = <?= json_encode($chatroomId) ?>;
    const currentUserId = <?= json_encode($currentUserId); ?>;
    const currentUsername = document.getElementById('current-username').value;

    conn.onopen = function(e) {
        console.log("WebSocket connection established");
        const authMessage = {
            type: 'authentication',
            username: currentUsername,
            chatroomId: chatroomId
        };
        console.log("Sending authentication message:", authMessage);
        conn.send(JSON.stringify(authMessage));
    };

    conn.onmessage = function(e) {
        console.log("Raw message received:", e.data);
        try {
            const messageData = JSON.parse(e.data);
            console.log("Parsed message data:", messageData);

            if (messageData.type === 'reaction') {
                updateReactionDisplay(messageData.message_id, messageData.reaction_type);
            }
            else if (messageData.type === 'message') {
                const existingMessage = document.querySelector(`[data-message-id="${messageData.id}"]`);
                if (!existingMessage) {
                    const chatBox = document.getElementById('chat-box');
                    const messageElement = createMessageElement(messageData);
                    chatBox.appendChild(messageElement);
                    chatBox.scrollTop = chatBox.scrollHeight;
                }
            }
        } catch (error) {
            console.error("Error processing received message:", error);
        }
    };

    window.addEventListener('load', loadChatroomUsers);
    window.addEventListener('load', loadPreviousMessages);
    document.addEventListener('DOMContentLoaded', function() {
        checkUserRole();
    });
    document.addEventListener('DOMContentLoaded', function() {
        loadSuggestedUsers();
    });

    conn.onerror = function(error) {
    };

    conn.onclose = function(e) {
        setTimeout(function() {
            conn = new WebSocket('ws://localhost:8080');
        }, 1000);
    };

    document.getElementById('chat-form').addEventListener('submit', async function(event) {
        event.preventDefault();
        const messageInput = document.getElementById('message');
        const message = messageInput.value.trim();
        const imageInput = document.getElementById('image-upload');
        const chatroomId = document.getElementById('chatroom_id').value;

        if (message === '' && imageInput.files.length === 0) return;

        let imageUrl = null;
        if (imageInput.files.length > 0) {
            const formData = new FormData();
            formData.append('image', imageInput.files[0]);
            formData.append('chatroom_id', chatroomId);

            try {
                const response = await fetch('/upload-image', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    imageUrl = result.image_url;
                } else {
                    console.error('Error uploading image:', result.error);
                    return;
                }
            } catch (error) {
                console.error('Error uploading image:', error);
                return;
            }
        }

        const messageData = {
            type: 'message',
            chatroom_id: chatroomId,
            sender: currentUsername,
            message: message,
            image_url: imageUrl,
            timestamp: new Date().toISOString()
        };

        conn.send(JSON.stringify(messageData));

        messageInput.value = '';
        imageInput.value = '';
        document.getElementById('image-preview').classList.add('hidden');
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
    document.getElementById('suggest-user-form').addEventListener('submit', function(event) {
        event.preventDefault();
        const suggestedUsername = document.getElementById('suggested-username').value;

        fetch(`/chatroom/${chatroomId}/suggest-user`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ username: suggestedUsername }),
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    loadSuggestedUsers();
                    document.getElementById('suggested-username').value = '';
                } else {
                    alert(`Error: ${data.message}`);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(`An error occurred: ${error.message}`);
            });
    });

    function loadSuggestedUsers() {
        fetch(`/chatroom/${chatroomId}/suggested-users`)
            .then(response => response.json())
            .then(users => {
                const usersList = document.getElementById('suggested-users-list');
                usersList.innerHTML = '';

                users.forEach(user => {
                    const li = document.createElement('li');
                    li.textContent = user.username;

                    if (userRole === 'admin') {
                        const approveButton = createButton('fa-check', 'text-gray-500', () => approveSuggestion(user.id));
                        const deleteButton = createButton('fa-times', 'text-gray-500', () => deleteSuggestion(user.id));

                        li.appendChild(approveButton);
                        li.appendChild(deleteButton);
                    }

                    usersList.appendChild(li);
                });
            })
            .catch(error => console.error('Error loading suggested users:', error));
    }

    function approveSuggestion(userId) {
        fetch(`/chatroom/${chatroomId}/approve-suggestion`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ user_id: userId }),
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    loadSuggestedUsers();
                    loadChatroomUsers();
                } else {
                    alert(`Error: ${data.message}`);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(`An error occurred: ${error.message}`);
            });
    }

    function deleteSuggestion(userId) {
        fetch(`/chatroom/${chatroomId}/delete-suggestion`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ user_id: userId }),
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    loadSuggestedUsers();
                } else {
                    alert(`Error: ${data.message}`);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(`An error occurred: ${error.message}`);
            });
    }

    function loadChatroomUsers() {
        fetch(`/chatroom/${chatroomId}/users`)
            .then(response => response.json())
            .then(users => {
                const usersList = document.getElementById('chatroom-users-list');
                usersList.innerHTML = '';
                users.forEach(user => {
                    const li = document.createElement('li');
                    li.textContent = user.username;

                    if (userRole === 'admin' && user.username !== currentUsername) {
                        const removeButton = createButton('fa-times', 'text-gray-500', () => removeUser(user.username));
                        const grantAdminButton = createButton('fa-user-shield', 'text-blue-500', () => grantAdminPrivileges(user.username));

                        li.appendChild(removeButton);
                        if (!user.is_admin) {
                            li.appendChild(grantAdminButton);
                        }
                    }

                    usersList.appendChild(li);
                });
            })
            .catch(error => {
                console.error('Error loading chatroom users:', error);
            });
    }

    function grantAdminPrivileges(username) {
        if (confirm(`Are you sure you want to grant admin privileges to ${username}?`)) {
            fetch(`/chatroom/${chatroomId}/grant-admin`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ username: username }),
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        loadChatroomUsers();
                    } else {
                        alert(`Error: ${data.message}`);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while granting admin privileges');
                });
        }
    }

    function createButton(iconClass, colorClass, clickHandler) {
        const button = document.createElement('button');
        button.classList.add('ml-2', colorClass, 'hover:' + colorClass.replace('500', '700'));
        button.innerHTML = `<i class="fa ${iconClass}"></i>`;
        button.onclick = clickHandler;
        return button;
    }

    function removeUser(username) {
        if (confirm(`Are you sure you want to remove ${username} from the chatroom?`)) {
            fetch(`/chatroom/${chatroomId}/remove-user`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ username: username }),
            })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    loadChatroomUsers();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while removing the user');
                });
        }
    }

    function loadPreviousMessages() {
        fetch(`/chatroom/${chatroomId}/messages`)
            .then(response => response.json())
            .then(messages => {
                const chatBox = document.getElementById('chat-box');
                chatBox.innerHTML = '';
                messages.forEach(message => {
                    const messageElement = createMessageElement(message);
                    chatBox.appendChild(messageElement);
                });
                chatBox.scrollTop = chatBox.scrollHeight;
            })
            .catch(error => console.error('Error loading previous messages:', error));
    }

    function createMessageElement(messageData) {
        const messageElement = document.createElement('div');
        const hasImage = !!messageData.image_url;
        if (hasImage) {
            messageElement.classList.add('p-2', 'mb-2', 'rounded-lg', 'flex', 'flex-col', 'items-start');
        } else {
            messageElement.classList.add('p-2', 'mb-2', 'rounded-lg', 'flex', 'justify-between', 'items-start');
        }
        messageElement.dataset.messageId = messageData.id || `temp-${messageData.timestamp}`;
        messageElement.dataset.reaction = messageData.reaction || '';

        const textElement = document.createElement('span');
        textElement.classList.add('mb-2');

        const sender = messageData.username || messageData.sender;
        const isCurrentUser = sender === currentUsername;

        if (isCurrentUser) {
            messageElement.classList.add('bg-blue-100', 'self-end');
            textElement.textContent = `You: ${messageData.message}`;
        } else {
            messageElement.classList.add('bg-gray-100', 'self-start');
            textElement.textContent = `${sender}: ${messageData.message}`;
        }

        messageElement.appendChild(textElement);

        if (hasImage) {
            const imageElement = document.createElement('img');
            imageElement.src = messageData.image_url;
            imageElement.classList.add('max-w-xs', 'max-h-40', 'rounded-lg', 'mt-2');
            messageElement.appendChild(imageElement);
        }

        const reactionContainer = createReactionContainer(messageElement.dataset.messageId, true, messageData.reaction_type);
        const reactionPopup = reactionContainer.querySelector('.reaction-popup');
        if (messageElement.classList.contains('bg-blue-100')) {
            reactionPopup.classList.add('bg-blue-100');
        } else {
            reactionPopup.classList.add('bg-gray-100');
        }
        messageElement.appendChild(reactionContainer);

        return messageElement;
    }

    document.addEventListener('DOMContentLoaded', function() {
        loadPreviousMessages();
        checkUserRole();
    });

    document.addEventListener('DOMContentLoaded', function() {
        const leaveChatroomForm = document.getElementById('leave-chatroom-form');

        if (leaveChatroomForm) {
            leaveChatroomForm.addEventListener('submit', function(event) {
                event.preventDefault();
                leaveChatroom();
            });
        } else {
            console.error('Leave chatroom form not found');
        }

        const leaveButton = document.querySelector('#leave-chatroom-form button');
        if (leaveButton) {
            leaveButton.addEventListener('click', function(event) {
                event.preventDefault();
                leaveChatroom();
            });
        }
    });

    function leaveChatroom() {
        fetch(`/chatroom/${chatroomId}/leave`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                alert(data.message);
                window.location.href = '/';
            })
            .catch(error => {
                alert('An error occurred while leaving the chatroom: ' + error.message);
            });
    }
    let isAdmin = false;
    let userRole = 'guest';

    function checkUserRole() {
        fetch(`/chatroom/${chatroomId}/user-role`)
            .then(response => response.json())
            .then(data => {
                userRole = data.role;
                isAdmin = (userRole === 'admin');
                updateUIBasedOnRole();
                loadChatroomUsers();
            })
            .catch(error => console.error('Error checking user role:', error));
    }

    function updateUIBasedOnRole() {
        const manageUsersSection = document.getElementById('manage-users-section');
        const suggestUsersSection = document.getElementById('suggest-users-section');

        if (userRole === 'admin') {
            manageUsersSection.style.display = 'block';
            suggestUsersSection.style.display = 'none';
        } else if (userRole === 'user') {
            manageUsersSection.style.display = 'none';
            suggestUsersSection.style.display = 'block';
        } else {
            manageUsersSection.style.display = 'none';
            suggestUsersSection.style.display = 'none';
        }

        loadChatroomUsers();
        loadSuggestedUsers();
    }
    function addReactionToMessage(messageElement, messageId, isPrivateChat, initialReaction = null) {
        const reactionContainer = createReactionContainer(messageId, isPrivateChat, initialReaction);
        messageElement.appendChild(reactionContainer);
    }

    function updateReactionButton(button, reactionType) {
        if (reactionType && REACTION_TYPES[reactionType]) {
            button.textContent = REACTION_TYPES[reactionType];
            button.classList.remove('text-gray-500', 'hover:text-gray-700');
            button.classList.add('text-blue-500', 'hover:text-blue-700');
        } else {
            button.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M15.5 11C16.3284 11 17 10.3284 17 9.5C17 8.67157 16.3284 8 15.5 8C14.6716 8 14 8.67157 14 9.5C14 10.3284 14.6716 11 15.5 11Z" fill="currentColor" />
            <path d="M8.5 11C9.32843 11 10 10.3284 10 9.5C10 8.67157 9.32843 8 8.5 8C7.67157 8 7 8.67157 7 9.5C7 10.3284 7.67157 11 8.5 11Z" fill="currentColor" />
            <path d="M12 13.5C13.1046 13.5 14 14.3954 14 15.5C14 16.6046 13.1046 17.5 12 17.5C10.8954 17.5 10 16.6046 10 15.5C10 14.3954 10.8954 13.5 12 13.5Z" fill="currentColor" />
            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM12 20C16.4183 20 20 16.4183 20 12C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12C4 16.4183 7.58172 20 12 20Z" fill="currentColor" />
        </svg>`;
            button.classList.remove('text-blue-500', 'hover:text-blue-700');
            button.classList.add('text-gray-500', 'hover:text-gray-700');
        }
    }
    function sendReaction(messageId, reactionType, isPrivateChat) {
        const url = `/chatroom-message/${messageId}/react`;
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ reactionType: reactionType }),
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateReactionDisplay(messageId, reactionType);
                    updateMessageReaction(messageId, reactionType);
                    conn.send(JSON.stringify({
                        type: 'reaction',
                        message_id: messageId,
                        reaction_type: reactionType,
                        chatroom_id: chatroomId
                    }));
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function updateMessageReaction(messageId, reactionType) {
        const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
        if (messageElement) {
            messageElement.dataset.reaction = reactionType;
        }
    }
    function updateReactionDisplay(messageId, reactionType) {
        const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
        if (messageElement) {
            messageElement.dataset.reaction = reactionType;
            const reactionButton = messageElement.querySelector('.reaction-button');
            if (reactionButton) {
                updateReactionButton(reactionButton, reactionType);
            }
        }
    }
    function positionReactionPopup(button, popup) {
        popup.style.visibility = 'hidden';
        popup.style.display = 'block';
        popup.style.opacity = '0';
        popup.offsetHeight;

        const rect = button.getBoundingClientRect();
        const popupRect = popup.getBoundingClientRect();

        let top = rect.top - popupRect.height - 10;
        let left = rect.left;

        if (top < 0) {
            top = rect.bottom + 10;
        }

        if (left + popupRect.width > window.innerWidth) {
            left = window.innerWidth - popupRect.width - 10;
        }

        popup.style.position = 'fixed';
        popup.style.top = `${top}px`;
        popup.style.left = `${left}px`;

        popup.style.visibility = 'visible';
        popup.style.opacity = '1';
    }
    function createReactionContainer(messageId, isPrivateChat, initialReaction = null) {
        const reactionContainer = document.createElement('div');
        reactionContainer.classList.add('reaction-container', 'flex', 'items-center', 'relative');

        const reactionButton = document.createElement('button');
        reactionButton.classList.add('reaction-button', 'ml-2', 'text-gray-500', 'hover:text-gray-700');

        updateReactionButton(reactionButton, initialReaction);

        const reactionPopup = document.createElement('div');
        reactionPopup.classList.add('reaction-popup', 'hidden', 'fixed', 'bg-white', 'rounded-lg', 'shadow-md', 'p-2', 'z-50');
        reactionPopup.style.transition = 'opacity 0.2s ease-in-out';

        Object.entries(REACTION_TYPES).forEach(([type, emoji]) => {
            const emojiButton = document.createElement('button');
            emojiButton.textContent = emoji;
            emojiButton.classList.add('mr-1', 'hover:bg-gray-200', 'rounded');
            emojiButton.onclick = function(event) {
                event.stopPropagation();
                sendReaction(messageId, type, isPrivateChat);
                reactionPopup.classList.add('hidden');
            };
            reactionPopup.appendChild(emojiButton);
        });

        reactionButton.addEventListener('click', function(event) {
            event.stopPropagation();
            if (reactionPopup.classList.contains('hidden')) {
                reactionPopup.classList.remove('hidden');
                const messageElement = this.closest('[data-message-id]');
                if (messageElement.classList.contains('bg-blue-100')) {
                    reactionPopup.classList.add('bg-blue-100');
                } else if (messageElement.classList.contains('bg-gray-100')) {
                    reactionPopup.classList.add('bg-gray-100');
                }
                positionReactionPopup(reactionButton, reactionPopup);
            } else {
                reactionPopup.classList.add('hidden');
            }
        });

        reactionContainer.appendChild(reactionButton);
        reactionContainer.appendChild(reactionPopup);

        return reactionContainer;
    }
    document.addEventListener('click', function(event) {
        const reactionPopups = document.querySelectorAll('.reaction-popup');
        reactionPopups.forEach(popup => {
            if (!popup.classList.contains('hidden') && !popup.contains(event.target)) {
                popup.classList.add('hidden');
            }
        });
    });

    document.addEventListener('DOMContentLoaded', checkUserRole);
    document.addEventListener('DOMContentLoaded', function () {
        const uploadAudioBtn = document.getElementById('upload-audio-btn');
        const audioFileInput = document.getElementById('audio-file-input');
        const messageInput = document.getElementById('message');
        let mediaRecorder;
        let audioChunks = [];
        const svgIcon = uploadAudioBtn.querySelector('svg').outerHTML;

        uploadAudioBtn.addEventListener('click', async () => {
            if (uploadAudioBtn.textContent.trim() === '') {
                uploadAudioBtn.textContent = '⏹️';
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream);

                audioChunks = [];

                mediaRecorder.ondataavailable = event => {
                    audioChunks.push(event.data);
                };

                mediaRecorder.onstop = async () => {
                    messageInput.value = '';
                    const audioBlob = new Blob(audioChunks, { type: 'audio/mpeg' });
                    const formData = new FormData();
                    formData.append('audio', audioBlob, 'speech.mp3');

                    try {
                        const response = await fetch('/speech-to-text', {
                            method: 'POST',
                            body: formData
                        });

                        const transcribedText = await response.text();
                        if (response.ok) {
                            messageInput.value = transcribedText;
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    } finally {
                        uploadAudioBtn.innerHTML = svgIcon;
                    }
                };

                mediaRecorder.start();
            } else {
                uploadAudioBtn.innerHTML = svgIcon;
                mediaRecorder.stop();
            }
        });

        audioFileInput.addEventListener('change', async function () {
            messageInput.value = '';
            const audioFile = this.files[0];
            const formData = new FormData();
            formData.append('audio', audioFile);

            try {
                const response = await fetch('/speech-to-text', {
                    method: 'POST',
                    body: formData
                });

                const transcribedText = await response.text();

                if (response.ok) {
                    messageInput.value = transcribedText;
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    });
    document.getElementById('image-upload').addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview-image').src = e.target.result;
                document.getElementById('image-preview').classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    });

    document.getElementById('remove-image').addEventListener('click', function() {
        document.getElementById('image-upload').value = '';
        document.getElementById('preview-image').src = '';
        document.getElementById('image-preview').classList.add('hidden');
    });
    // let unreadCount = 0;
    //
    // document.addEventListener('DOMContentLoaded', function() {
    //     setupNotifications();
    // });
    // function setupNotifications() {
    //     const bell = document.getElementById('notificationBell');
    //     const dropdown = document.getElementById('notificationDropdown');
    //
    //     if (bell && dropdown) {
    //         bell.addEventListener('click', function(event) {
    //             event.preventDefault();
    //             event.stopPropagation();
    //             console.log('Notification bell clicked');
    //
    //             dropdown.classList.toggle('hidden');
    //             if (!dropdown.classList.contains('hidden')) {
    //                 unreadCount = 0;
    //                 updateNotificationCount();
    //             }
    //         });
    //
    //         document.addEventListener('click', function(event) {
    //             if (!bell.contains(event.target) && !dropdown.contains(event.target)) {
    //                 dropdown.classList.add('hidden');
    //             }
    //         });
    //     }
    //
    //     updateNotificationCount();
    //
    //     var conn = new WebSocket('ws://localhost:8080');
    //
    //     conn.onopen = function(e) {
    //         console.log("Connection established!");
    //         conn.send(JSON.stringify({
    //             type: 'authentication',
    //             username: currentUsername
    //         }));
    //     };
    //
    //     conn.onmessage = function(e) {
    //         console.log("Received message:", e.data);
    //         const messageData = JSON.parse(e.data);
    //
    //         if (messageData.type === 'notification') {
    //             addNotification(messageData.content);
    //         } else if (messageData.type === 'message' && messageData.recipient === currentUsername) {
    //         } else if (messageData.type === 'reaction' && messageData.recipientUsername === currentUsername) {
    //             addNotification(`${messageData.senderUsername} reacted to your message`);
    //         }
    //     };
    // }
    //
    // function updateNotificationCount() {
    //     const countElement = document.getElementById('notificationCount');
    //     if (countElement) {
    //         if (unreadCount > 0) {
    //             countElement.textContent = unreadCount;
    //             countElement.classList.remove('hidden');
    //         } else {
    //             countElement.classList.add('hidden');
    //         }
    //     }
    // }
    //
    // function addNotification(message) {
    //     const list = document.getElementById('notificationList');
    //     if (list) {
    //         const noNotificationsElement = list.querySelector('.no-notifications');
    //         if (noNotificationsElement) {
    //             list.removeChild(noNotificationsElement);
    //         }
    //
    //         const notificationElement = document.createElement('div');
    //         notificationElement.classList.add('px-4', 'py-2', 'text-sm', 'text-gray-700', 'hover:bg-gray-100');
    //         notificationElement.textContent = message;
    //         list.insertBefore(notificationElement, list.firstChild);
    //
    //         if (list.children.length > 5) {
    //             list.removeChild(list.lastChild);
    //         }
    //     }
    //     unreadCount++;
    //     updateNotificationCount();
    // }
</script>