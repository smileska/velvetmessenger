<?php $title = htmlspecialchars($user['username']) . "'s Profile"; ?>
<?php require('parts/head.php') ?>
<?php require('parts/navbar.php') ?>

<header class="bg-color banner-shadow">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900 banner-text">
            <?= htmlspecialchars($user['username']) ?>'s Profile
        </h1>
    </div>
</header>

<div class="profile-container mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <img class="profile-image" src="<?= !empty($user['image']) ? htmlspecialchars($user['image']) : "ui/icons/default.png" ?>" alt="User Image">
    <div class="status-container">
        <img class="status-icon" src="ui/icons/onlajn.png" alt="status_img">
        <span>Online</span>
    </div>
</div>