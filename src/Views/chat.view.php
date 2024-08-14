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
            <input type="text" id="message" placeholder="Type your message here" required class="flex-grow p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-100">
            <button type="button" id="upload-audio-btn" class="ml-2 p-2 rounded-full hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z" />
                </svg>
            </button>
            <button type="submit" class="btn-primary ml-3 px-4 py-1 text-white rounded-lg shadow hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-100 focus:ring-opacity-50">Send</button>
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

    conn.onopen = function(e) {
        console.log("Connection established!");
    };

    conn.onmessage = function(e) {
        const messageData = JSON.parse(e.data);
        console.log("Received message:", e.data);
        if (messageData.type === 'reaction') {
            updateReactionDisplay(messageData.message_id, messageData.reaction_type);
        }
        else {
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
        messageElement.classList.add('p-2', 'mb-2', 'rounded-lg', 'flex', 'justify-between', 'items-start');
        messageElement.dataset.messageId = messageData.id;
        messageElement.dataset.reaction = messageData.reaction || '';

        const textElement = document.createElement('span');
        textElement.classList.add('mr-2');

        if (document.body.classList.contains('dark-mode')) {
            if (messageData.sender === '<?= htmlspecialchars($_SESSION['username']); ?>') {
                messageElement.classList.add('bg-blue-100', 'self-end');
                textElement.textContent = `You: ${messageData.message}`;
            } else {
                messageElement.classList.add('bg-gray-100', 'self-start');
                textElement.textContent = `${messageData.sender}: ${messageData.message}`;
            }
        } else {
            if (messageData.sender === '<?= htmlspecialchars($_SESSION['username']); ?>') {
                messageElement.classList.add('bg-blue-100', 'self-end');
                textElement.textContent = `You: ${messageData.message}`;
            } else {
                messageElement.classList.add('bg-gray-100', 'self-start');
                textElement.textContent = `${messageData.sender}: ${messageData.message}`;
            }
        }

        messageElement.appendChild(textElement);

        const reactionContainer = createReactionContainer(messageData.id, true, messageData.reaction_type);
        const reactionPopup = reactionContainer.querySelector('.reaction-popup');
        if (messageElement.classList.contains('bg-blue-100')) {
            reactionPopup.classList.add('bg-blue-100');
        } else {
            reactionPopup.classList.add('bg-gray-100');
        }
        messageElement.appendChild(reactionContainer);

        return messageElement;
    }
    function createReactionContainer(messageId, isPrivateChat, initialReaction = null) {
        console.log('Creating reaction container:', { messageId, isPrivateChat, initialReaction });

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

        console.log('Reaction container created:', reactionContainer);
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
            console.log('Reaction button clicked');
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
            console.log('Emoji button clicked');
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
            console.log('Reaction button clicked');
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
        console.log('Sending reaction:', { messageId, reactionType, isPrivateChat });
        if (!messageId) {
            console.error('Invalid message ID');
            return;
        }
        const url = isPrivateChat ? `/message/${messageId}/react` : `/chatroom-message/${messageId}/react`;
        console.log('Sending reaction:', { messageId, reactionType, isPrivateChat, url });
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
                console.log('Reaction response:', data);
                if (data.success) {
                    updateReactionDisplay(messageId, reactionType);
                    conn.send(JSON.stringify({
                        type: 'reaction',
                        message_id: messageId,
                        reaction_type: reactionType
                    }));
                } else {
                    console.error('Failed to send reaction:', data.error);
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
        console.log('Updating reaction display:', { messageId, reactionType });
        const svgIcon = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M15.5 11C16.3284 11 17 10.3284 17 9.5C17 8.67157 16.3284 8 15.5 8C14.6716 8 14 8.67157 14 9.5C14 10.3284 14.6716 11 15.5 11Z" fill="currentColor" />
        <path d="M8.5 11C9.32843 11 10 10.3284 10 9.5C10 8.67157 9.32843 8 8.5 8C7.67157 8 7 8.67157 7 9.5C7 10.3284 7.67157 11 8.5 11Z" fill="currentColor" />
        <path d="M12 13.5C13.1046 13.5 14 14.3954 14 15.5C14 16.6046 13.1046 17.5 12 17.5C10.8954 17.5 10 16.6046 10 15.5C10 14.3954 10.8954 13.5 12 13.5Z" fill="currentColor" />
        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22ZM12 20C16.4183 20 20 16.4183 20 12C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12C4 16.4183 7.58172 20 12 20Z" fill="currentColor" />
    </svg>`;
        console.log('Updating reaction display:', { messageId, reactionType });
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
        console.log('DOM fully loaded, initializing reaction containers');
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

                mediaRecorder.ondataavailable = event => {
                    audioChunks.push(event.data);
                };

                mediaRecorder.onstop = async () => {
                    const audioBlob = new Blob(audioChunks, { type: 'audio/mpeg' });
                    console.log('Audio Blob:', audioBlob);

                    // const audioUrl = URL.createObjectURL(audioBlob);
                    // const a = document.createElement('a');
                    // a.href = audioUrl;
                    // a.download = 'recorded_audio.mp3';
                    // document.body.appendChild(a);
                    // a.click();
                    // URL.revokeObjectURL(audioUrl);

                    const formData = new FormData();
                    formData.append('audio', audioBlob, 'speech.mp3');

                    try {
                        const response = await fetch('/speech-to-text', {
                            method: 'POST',
                            body: formData
                        });

                        const transcribedText = await response.text();
                        console.log('Transcription Response:', transcribedText);

                        if (response.ok) {
                            messageInput.value = transcribedText;
                        } else {
                            console.error('Transcription failed:', transcribedText);
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
                } else {
                    console.error('Transcription failed:', transcribedText);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    });

</script>