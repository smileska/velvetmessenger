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
    document.addEventListener('DOMContentLoaded', loadChatrooms);
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



</script>
