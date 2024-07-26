<?php $title = "Home"; ?>
<?php require('parts/head.php') ?>
<?php require('parts/navbar.php') ?>
<?php require('parts/banner.php') ?>
<main>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <?php if(isset($_SESSION['username'])) : ?>
            <form action="/search" method="post" enctype="multipart/form-data" class="container-pfp relative">
                <div class="relative mt-1">
                    <label for="search_user" class="block text-sm font-medium index-label">Who would you like to chat with?</label>
                    <input type="text" name="search_user" id="search_user" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="Search user...">
                    <button type="submit" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-600 search-button">
                        <i id="search-icon" class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            <?php if (!empty($users)) : ?>
                <h2 class="text-xl mt-6 found-users">Search Results for "<?= htmlspecialchars($searchQuery) ?>"</h2>
                <ul class="search-results">
                    <?php foreach ($users as $user) : ?>
                        <li class="search-result">
                            <img src="<?= htmlspecialchars($user['image']) ?>" alt="<?= htmlspecialchars($user['username']) ?>" class="search-result-img">
                            <a href="/chat/<?= htmlspecialchars($user['username']) ?>" class="search-result-link"><?= htmlspecialchars($user['username']) ?></a>
                            <form action="/send-message" method="post" class="inline-form">
                                <input type="hidden" name="recipient" value="<?= htmlspecialchars($user['username']) ?>">
                                <button type="submit" class="message-button">Message</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php elseif (isset($searchQuery)) : ?>
                <p class="no-users">No users found for "<?= htmlspecialchars($searchQuery) ?>"</p>
            <?php endif; ?>
        <?php else : ?>
            <p>Log in or register to start chatting!</p>
        <?php endif; ?>
    </div>
</main>
</html>
