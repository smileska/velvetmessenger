<?php $title = "Profile"; ?>
<?php require('parts/head.php') ?>
<?php require('parts/navbar.php') ?>
<header class="bg-color banner-shadow">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900 banner-text">
            Your profile
        </h1>
    </div>
</header>
<img class="profile-image" src="<?= !empty($_SESSION['image']) ? htmlspecialchars($_SESSION['image']) : "ui/icons/default.png" ?>" alt="User Image">
<form action="/update-profile-picture" method="post" enctype="multipart/form-data" class="container-pfp">
    <label for="profile_picture">Change Profile Picture:</label>
    <input type="file" name="profile_picture" id="profile_picture" accept="image/*" required>
    <button type="submit" class="flex w-full justify-center rounded-md btn-primary px-3 py-1.5 text-sm font-semibold leading-6 shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2">Upload</button>
</form>
<div class="status-container">
    <img class="status-icon" src="ui/icons/onlajn.png" alt="status_img">
    <span>Online</span>
</div>

