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
