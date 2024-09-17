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
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <img class="profile-image mb-6" src="<?= !empty($_SESSION['image']) ? htmlspecialchars($_SESSION['image']) : "ui/icons/default.png" ?>" alt="User Image">
    <form action="/update-profile-picture" method="post" enctype="multipart/form-data" class="container-pfp mb-6">
        <label for="profile_picture" class="block mb-2">Change Profile Picture:</label>
        <input type="file" name="profile_picture" id="profile_picture" accept="image/*" required class="mb-2">
        <button type="submit" class="flex w-full justify-center rounded-md btn-primary px-3 py-1.5 text-sm font-semibold leading-6 shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2">Upload</button>
    </form>
    <div class="status-container mb-6">
        <img class="status-icon" src="ui/icons/onlajn.png" alt="status_img">
        <span>Online</span>
    </div>

    <div class="mb-6">
        <h2 class="text-2xl font-bold mb-4">Change Username</h2>
        <form action="/update-username" method="post" class="space-y-4">
            <div>
                <label for="new_username" class="block mb-2">New Username:</label>
                <input type="text" name="new_username" id="new_username" required class="w-full px-3 py-2 border rounded-md">
            </div>
            <div>
                <label for="password_for_username" class="block mb-2">Confirm Password:</label>
                <input type="password" name="password_for_username" id="password_for_username" required class="w-full px-3 py-2 border rounded-md">
            </div>
            <button type="submit" class="flex w-full justify-center rounded-md btn-msg px-3 py-1.5 text-sm font-semibold leading-6 shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2">Change Username</button>
        </form>
    </div>

    <div>
        <h2 class="text-2xl font-bold mb-4">Change Password</h2>
        <form action="/update-password" method="post" class="space-y-4">
            <div>
                <label for="current_password" class="block mb-2">Current Password:</label>
                <input type="password" name="current_password" id="current_password" required class="w-full px-3 py-2 border rounded-md">
            </div>
            <div>
                <label for="new_password" class="block mb-2">New Password:</label>
                <input type="password" name="new_password" id="new_password" required class="w-full px-3 py-2 border rounded-md">
            </div>
            <div>
                <label for="confirm_new_password" class="block mb-2">Confirm New Password:</label>
                <input type="password" name="confirm_new_password" id="confirm_new_password" required class="w-full px-3 py-2 border rounded-md">
            </div>
            <button type="submit" class="flex w-full justify-center rounded-md btn-msg px-3 py-1.5 text-sm font-semibold leading-6 shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2">Change Password</button>
        </form>
    </div>
</div>
