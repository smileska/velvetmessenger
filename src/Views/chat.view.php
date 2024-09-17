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
            <div class="flex mb-2">
                <input type="text" id="message" placeholder="Type your message here" required class="flex-grow p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-100">
                <button type="button" id="upload-audio-btn" class="ml-2 p-2 rounded-full hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z" />
                    </svg>
                </button>
                <label for="image-upload" class="ml-2 p-2 rounded-full hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer mr-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                    </svg>
                </label>
                <input type="file" id="image-upload" accept="image/*" class="hidden">
            </div>
            <div id="image-preview" class="mb-2 hidden">
                <img id="preview-image" src="" alt="Preview" class="max-w-xs max-h-40 rounded-lg">
                <button type="button" id="remove-image" class="ml-2 text-red-500 hover:text-red-700">Remove</button>
            </div>
            <button type="submit" class="btn-primary px-4 py-2 text-white rounded-lg shadow hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-100 focus:ring-opacity-50">Send</button>
        </form>
        <input type="file" id="audio-file-input" accept="audio/*" style="display:none;">
    </div>
</main>
<script type="text/javascript">
    const REACTION_TYPES = {
        1: '👍',
        2: '❤️',
        3: '😂',
        4: '😮',
        5: '😢'
    };
    var conn = new WebSocket('ws://localhost:8080');
    const currentUser = '<?= htmlspecialchars($_SESSION['username']); ?>';
    const chatPartner = '<?= htmlspecialchars($chatUser['username']); ?>';

    conn.onopen = function(e) {
        console.log("WebSocket connection established");
        const authMessage = {
            type: 'authentication',
            username: currentUser
        };
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
                if (messageData.sender !== currentUser) {
                    displayNewMessage(messageData);
                }
            }
        } catch (error) {
            console.error("Error processing received message:", error);
        }
    };

    function displayNewMessage(messageData) {
        console.log("Displaying new message:", messageData);
        const chatBox = document.getElementById('chat-box');
        const messageElement = createMessageElement(messageData);
        chatBox.appendChild(messageElement);
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function updateOrAddMessageToChat(messageData) {
        const existingMessage = document.querySelector(`[data-message-id="${messageData.id}"]`);
        if (existingMessage) {
            console.log("Updating existing message:", messageData.id);
        } else {
            addMessageToChat(messageData);
        }
    }

    function addMessageToChat(messageData) {
        const chatBox = document.getElementById('chat-box');
        const messageElement = createMessageElement(messageData);
        chatBox.appendChild(messageElement);
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    conn.onclose = function(e) {
        setTimeout(function() {
            conn = new WebSocket('ws://localhost:8080');
        }, 1000);
    };

    document.getElementById('chat-form').addEventListener('submit', function(event) {
        event.preventDefault();
        const messageInput = document.getElementById('message');
        const recipient = document.getElementById('recipient').value;
        const message = messageInput.value;
        const imageInput = document.getElementById('image-upload');

        const formData = new FormData();
        formData.append('recipient', recipient);
        formData.append('message', message);

        if (imageInput.files.length > 0) {
            formData.append('image', imageInput.files[0]);
        }

        fetch('/send-message', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log("Message sent successfully:", data);
                    displayNewMessage(data);
                    messageInput.value = '';
                    imageInput.value = '';
                    document.getElementById('image-preview').classList.add('hidden');
                } else {
                    console.error('Error sending message:', data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    });

    document.addEventListener('DOMContentLoaded', function() {
        conn.onopen = function(e) {
            console.log("WebSocket connection established");
            const authMessage = {
                type: 'authentication',
                username: currentUser
            };
            console.log("Sending authentication message:", authMessage);
            conn.send(JSON.stringify(authMessage));
        };

        loadPreviousMessages();
    });

    document.addEventListener('DOMContentLoaded', function() {
        loadPreviousMessages();
    });
    function loadPreviousMessages() {
        const recipient = document.getElementById('recipient').value;
        fetch(`/get-messages/${recipient}`)
            .then(response => response.json())
            .then(messages => {
                const chatBox = document.getElementById('chat-box');
                chatBox.innerHTML = '';
                const processedMessageIds = new Set();
                messages.forEach(message => {
                    if (!processedMessageIds.has(message.id)) {
                        displayNewMessage(message);
                        processedMessageIds.add(message.id);
                    }
                });
            })
            .catch(error => console.error('Error loading previous messages:', error));
    }

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

    document.getElementById('chat-box').addEventListener('click', function(event) {
        if (event.target.closest('.reaction-button')) {
            const reactionButton = event.target.closest('.reaction-button');
            const reactionPopup = reactionButton.nextElementSibling;
            reactionPopup.classList.toggle('hidden');
            event.stopPropagation();
        }
    });

    function createMessageElement(messageData) {
        const messageElement = document.createElement('div');
        const hasImage = !!messageData.image_url;
        messageElement.classList.add('p-2', 'mb-2', 'rounded-lg', 'flex', 'items-start');

        if (hasImage) {
            messageElement.classList.add('flex-col');
        } else {
            messageElement.classList.add('justify-between');
        }

        messageElement.dataset.messageId = messageData.id;
        messageElement.dataset.reaction = messageData.reaction || '';

        const textElement = document.createElement('span');
        textElement.classList.add(hasImage ? 'mb-2' : 'mr-2');

        const isCurrentUser = messageData.sender === currentUser;

        if (isCurrentUser) {
            messageElement.classList.add('bg-blue-100', 'self-end');
            textElement.textContent = `You: ${messageData.message}`;
        } else {
            messageElement.classList.add('bg-gray-100', 'self-start');
            textElement.textContent = `${messageData.sender}: ${messageData.message}`;
        }

        messageElement.appendChild(textElement);

        if (hasImage) {
            const imageElement = document.createElement('img');
            imageElement.src = messageData.image_url;
            imageElement.classList.add('max-w-xs', 'max-h-40', 'rounded-lg', 'mt-2');
            messageElement.appendChild(imageElement);
        }

        const reactionContainer = createReactionContainer(messageData.id, true, messageData.reaction_type);
        messageElement.appendChild(reactionContainer);

        return messageElement;
    }
    document.addEventListener('DOMContentLoaded', function() {
        loadPreviousMessages();
    });
    function createReactionContainer(messageId, isPrivateChat, initialReaction = null) {
        const reactionContainer = document.createElement('div');
        reactionContainer.classList.add('reaction-container', 'flex', 'items-center', 'relative', 'ml-auto', 'rounded-lg');

        const reactionButton = document.createElement('button');
        reactionButton.classList.add('reaction-button', 'ml-2', 'text-gray-500', 'hover:text-gray-700');
        reactionButton.dataset.messageId = messageId;

        const svgIcon = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M15.5 11C16.3284 11 17 10.3284 17 9.5C17 8.67157 16.3284 8 15.5 8C14.6716 8 14 8.67157 14 9.5C14 10.3284 14.6716 11 15.5 11Z" fill="currentColor" />
        <path d="M8.5 11C9.32843 11 10 10.3284 10 9.5C10 8.67157 9.32843 8 8.5 8C7.67157 8 7 8.67157 7 9.5C7 10.3284 7.67157 11 8.5 11Z" fill="currentColor" />
        <path d="M12 13.5C13.1046 13.5 14 14.3954 14 15.5C14 16.6046 13.1046 17.5 12 17.5C10.8954 17.5 10 16.6046 10 15.5C10 14.3954 10.8954 13.5 12 13.5Z" fill="currentColor" />
        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM12 20C16.4183 20 20 16.4183 20 12C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12C4 16.4183 7.58172 20 12 20Z" fill="currentColor" />
    </svg>`;

        reactionButton.innerHTML = svgIcon;

        if (initialReaction) {
            reactionButton.textContent = REACTION_TYPES[initialReaction];
            reactionButton.classList.remove('text-gray-100', 'hover:text-gray-100');
            reactionButton.classList.add('text-blue-100', 'hover:text-blue-100');
        }

        const reactionPopup = document.createElement('div');
        reactionPopup.classList.add('reaction-popup', 'hidden', 'fixed', 'rounded-lg', 'shadow-md', 'p-2', 'z-50');
        reactionPopup.style.transition = 'opacity 0.2s ease-in-out';


        reactionButton.addEventListener('click', function(event) {
            event.stopPropagation();
            if (reactionPopup.classList.contains('hidden')) {
                reactionPopup.classList.remove('hidden');
                positionReactionPopup(reactionButton, reactionPopup);
            } else {
                reactionPopup.classList.add('hidden');
            }
        });


        Object.entries(REACTION_TYPES).forEach(([type, emoji]) => {
            const emojiButton = document.createElement('button');
            emojiButton.textContent = emoji;
            emojiButton.classList.add('mr-1', 'hover:bg-gray-100', 'rounded');
            emojiButton.dataset.reactionType = type;
            reactionPopup.appendChild(emojiButton);
        });

        reactionContainer.appendChild(reactionButton);
        reactionContainer.appendChild(reactionPopup);

        return reactionContainer;
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


    document.addEventListener('click', function(event) {
        const reactionButton = event.target.closest('.reaction-button');
        if (reactionButton) {
            event.stopPropagation();
            const reactionPopup = reactionButton.nextElementSibling;
            if (reactionPopup.classList.contains('hidden')) {
                reactionPopup.classList.remove('hidden');
                positionReactionPopup(reactionButton, reactionPopup);
            } else {
                reactionPopup.classList.add('hidden');
            }
        }
        const emojiButton = event.target.closest('.reaction-popup button');
        if (emojiButton) {
            event.stopPropagation();
            const messageId = emojiButton.closest('.reaction-container').querySelector('.reaction-button').dataset.messageId;
            const reactionType = emojiButton.dataset.reactionType;
            sendReaction(messageId, reactionType, true);
            emojiButton.closest('.reaction-popup').classList.add('hidden');
        }
    });
    window.addEventListener('resize', function() {
        document.querySelectorAll('.reaction-popup:not(.hidden)').forEach(popup => {
            const button = popup.previousElementSibling;
            positionReactionPopup(button, popup);
        });
    });
    document.addEventListener('click', function() {
        document.querySelectorAll('.reaction-popup').forEach(popup => {
            popup.classList.add('hidden');
        });
    });
    function addReactionToMessage(messageElement, messageId, isPrivateChat, initialReaction = null) {
        const reactionContainer = document.createElement('div');
        reactionContainer.classList.add('reaction-container', 'flex', 'items-center', 'relative', 'ml-auto');

        const reactionButton = document.createElement('button');
        reactionButton.classList.add('reaction-button', 'ml-2', 'text-gray-500', 'hover:text-gray-700');

        const svgIcon = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M15.5 11C16.3284 11 17 10.3284 17 9.5C17 8.67157 16.3284 8 15.5 8C14.6716 8 14 8.67157 14 9.5C14 10.3284 14.6716 11 15.5 11Z" fill="currentColor" />
        <path d="M8.5 11C9.32843 11 10 10.3284 10 9.5C10 8.67157 9.32843 8 8.5 8C7.67157 8 7 8.67157 7 9.5C7 10.3284 7.67157 11 8.5 11Z" fill="currentColor" />
        <path d="M12 13.5C13.1046 13.5 14 14.3954 14 15.5C14 16.6046 13.1046 17.5 12 17.5C10.8954 17.5 10 16.6046 10 15.5C10 14.3954 10.8954 13.5 12 13.5Z" fill="currentColor" />
        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM12 20C16.4183 20 20 16.4183 20 12C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12C4 16.4183 7.58172 20 12 20Z" fill="currentColor" />
    </svg>`;

        const emojiSpan = document.createElement('span');
        emojiSpan.classList.add('emoji-reaction', 'hidden');

        reactionButton.innerHTML = svgIcon;
        reactionButton.appendChild(emojiSpan);

        if (initialReaction) {
            emojiSpan.textContent = REACTION_TYPES[initialReaction];
            emojiSpan.classList.remove('hidden');
            reactionButton.querySelector('svg').classList.add('hidden');
            reactionButton.classList.remove('text-gray-100', 'hover:text-gray-100');
            reactionButton.classList.add('text-blue-100', 'hover:text-blue-100');
        } else {
            reactionButton.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M15.5 11C16.3284 11 17 10.3284 17 9.5C17 8.67157 16.3284 8 15.5 8C14.6716 8 14 8.67157 14 9.5C14 10.3284 14.6716 11 15.5 11Z" fill="currentColor" />
        <path d="M8.5 11C9.32843 11 10 10.3284 10 9.5C10 8.67157 9.32843 8 8.5 8C7.67157 8 7 8.67157 7 9.5C7 10.3284 7.67157 11 8.5 11Z" fill="currentColor" />
        <path d="M12 13.5C13.1046 13.5 14 14.3954 14 15.5C14 16.6046 13.1046 17.5 12 17.5C10.8954 17.5 10 16.6046 10 15.5C10 14.3954 10.8954 13.5 12 13.5Z" fill="currentColor" />
        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM12 20C16.4183 20 20 16.4183 20 12C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12C4 16.4183 7.58172 20 12 20Z" fill="currentColor" />
    </svg>`;
        }

        updateReactionButton(reactionButton, initialReaction);

        const reactionPopup = document.createElement('div');
        reactionPopup.classList.add('reaction-popup', 'hidden', 'absolute', 'rounded-lg', 'shadow-md', 'p-2', 'z-10');
        const messageBgColor = window.getComputedStyle(messageElement).backgroundColor;
        reactionPopup.style.backgroundColor = messageBgColor;
        reactionPopup.style.bottom = '100%';
        reactionPopup.style.left = '0';

        Object.entries(REACTION_TYPES).forEach(([type, emoji]) => {
            const emojiButton = document.createElement('button');
            emojiButton.textContent = emoji;
            emojiButton.classList.add('mr-1', 'hover:bg-gray-100', 'rounded');
            emojiButton.onclick = function(event) {
                event.stopPropagation();
                sendReaction(messageId, type, isPrivateChat);
                reactionPopup.classList.add('hidden');
            };
            reactionPopup.appendChild(emojiButton);
        });

        reactionButton.onclick = function(event) {
            event.stopPropagation();
            reactionPopup.classList.toggle('hidden');
        };

        reactionContainer.appendChild(reactionButton);
        reactionContainer.appendChild(reactionPopup);
        messageElement.appendChild(reactionContainer);

        document.addEventListener('click', function() {
            reactionPopup.classList.add('hidden');
        });
        reactionButton.onclick = function(event) {
            event.stopPropagation();
            reactionPopup.classList.toggle('hidden');
        };
    }

    document.addEventListener('DOMContentLoaded', function() {
        const messages = document.querySelectorAll('[data-message-id]');
        messages.forEach(message => {
            const messageId = message.dataset.messageId;
            const existingReaction = message.dataset.reaction;
            const newReactionContainer = createReactionContainer(messageId, true, existingReaction);
            const reactionPopup = newReactionContainer.querySelector('.reaction-popup');
            if (message.classList.contains('bg-blue-100')) {
                reactionPopup.classList.add('bg-blue-100');
            } else {
                reactionPopup.classList.add('bg-gray-100');
            }
            const oldReactionContainer = message.querySelector('.reaction-container');
            if (oldReactionContainer) {
                message.replaceChild(newReactionContainer, oldReactionContainer);
            } else {
                message.appendChild(newReactionContainer);
            }
        });
    });
    function sendReaction(messageId, reactionType, isPrivateChat) {
        if (!messageId) {
            return;
        }
        const url = isPrivateChat ? `/message/${messageId}/react` : `/chatroom-message/${messageId}/react`;
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ reactionType: reactionType }),
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateReactionDisplay(messageId, reactionType);
                    conn.send(JSON.stringify({
                        type: 'reaction',
                        message_id: messageId,
                        reaction_type: reactionType
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
        const svgIcon = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M15.5 11C16.3284 11 17 10.3284 17 9.5C17 8.67157 16.3284 8 15.5 8C14.6716 8 14 8.67157 14 9.5C14 10.3284 14.6716 11 15.5 11Z" fill="currentColor" />
        <path d="M8.5 11C9.32843 11 10 10.3284 10 9.5C10 8.67157 9.32843 8 8.5 8C7.67157 8 7 8.67157 7 9.5C7 10.3284 7.67157 11 8.5 11Z" fill="currentColor" />
        <path d="M12 13.5C13.1046 13.5 14 14.3954 14 15.5C14 16.6046 13.1046 17.5 12 17.5C10.8954 17.5 10 16.6046 10 15.5C10 14.3954 10.8954 13.5 12 13.5Z" fill="currentColor" />
        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM12 20C16.4183 20 20 16.4183 20 12C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12C4 16.4183 7.58172 20 12 20Z" fill="currentColor" />
    </svg>`;
        const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
        if (messageElement) {
            const reactionButton = messageElement.querySelector('.reaction-button');
            if (reactionButton) {
                if (reactionType && REACTION_TYPES[reactionType]) {
                    reactionButton.textContent = REACTION_TYPES[reactionType];
                    reactionButton.classList.remove('text-gray-100', 'hover:text-gray-100');
                    reactionButton.classList.add('text-blue-100', 'hover:text-blue-100');
                } else {
                    reactionButton.innerHTML = svgIcon;
                    reactionButton.classList.remove('text-blue-100', 'hover:text-blue-100');
                    reactionButton.classList.add('text-gray-100', 'hover:text-gray-100');
                }
            }
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        const messages = document.querySelectorAll('[data-message-id]');
        messages.forEach(message => {
            const messageId = message.dataset.messageId;
            const existingReaction = message.dataset.reaction;
            const newReactionContainer = createReactionContainer(messageId, true, existingReaction);
            const oldReactionContainer = message.querySelector('.reaction-container');
            if (oldReactionContainer) {
                message.replaceChild(newReactionContainer, oldReactionContainer);
            } else {
                message.appendChild(newReactionContainer);
            }
        });
    });
    function updateReactionButton(button, reactionType) {
        if (reactionType && REACTION_TYPES[reactionType]) {
            button.textContent = REACTION_TYPES[reactionType];
            button.classList.remove('text-gray-100', 'hover:text-gray-100');
            button.classList.add('text-blue-100', 'hover:text-blue-100');
        } else {
            button.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M15.5 11C16.3284 11 17 10.3284 17 9.5C17 8.67157 16.3284 8 15.5 8C14.6716 8 14 8.67157 14 9.5C14 10.3284 14.6716 11 15.5 11Z" fill="currentColor" />
                <path d="M8.5 11C9.32843 11 10 10.3284 10 9.5C10 8.67157 9.32843 8 8.5 8C7.67157 8 7 8.67157 7 9.5C7 10.3284 7.67157 11 8.5 11Z" fill="currentColor" />
                <path d="M12 13.5C13.1046 13.5 14 14.3954 14 15.5C14 16.6046 13.1046 17.5 12 17.5C10.8954 17.5 10 16.6046 10 15.5C10 14.3954 10.8954 13.5 12 13.5Z" fill="currentColor" />
                <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM12 20C16.4183 20 20 16.4183 20 12C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12C4 16.4183 7.58172 20 12 20Z" fill="currentColor" />
            </svg>`;
            button.classList.remove('text-blue-100', 'hover:text-blue-100');
            button.classList.add('text-gray-100', 'hover:text-gray-100');
        }
    }
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

</script>