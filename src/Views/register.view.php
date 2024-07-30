<?php $title = "Register"; ?>
<?php require('parts/head.php') ?>
<div class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-sm">
        <img class="mx-auto custom-height w-auto" src="ui/icons/Pink_Y2k_Flower_Cute_Streetwear_Logo_1_-removebg-preview.png" alt="Velvet_logo">
        <h2 class="mt-10 text-center text-2xl font-bold leading-9 tracking-tight text-gray-900">Register</h2>
    </div>
    <form class="form-class space-y-6" action="/register" method="post" enctype="multipart/form-data">
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="rounded-md bg-red-50 p-4">
                <div class="flex">
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">There were errors with your submission:</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul role="list" class="list-disc pl-5 space-y-1">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <div class="form-item">
            <label for="username" class="form-label">Username</label>
            <div class="mt-2 input-container">
                <input id="username" type="text" name="username" required class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
            </div>
        </div>

        <div class="form-item">
            <label for="email" class="form-label">Email</label>
            <div class="mt-2 input-container">
                <input id="email" type="email" name="email" required class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
            </div>
        </div>

        <div class="form-item">
            <div class="flex items-center justify-between">
                <label for="password" class="form-label">Password</label>
                <div class="text-sm"></div>
            </div>
            <div class="mt-2 relative input-container">
                <input id="password" name="password" type="password" autocomplete="new-password" required class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                <button type="button" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-600" onclick="togglePasswordVisibility('password', 'eye-icon-open', 'eye-icon-closed')">
                    <i id="eye-icon-open" class="fas fa-eye"></i>
                    <i id="eye-icon-closed" class="fas fa-eye-slash hidden"></i>
                </button>
            </div>
        </div>

        <div class="form-item">
            <div class="flex items-center justify-between">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div class="text-sm"></div>
            </div>
            <div class="mt-2 relative input-container">
                <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                <button type="button" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-600" onclick="togglePasswordVisibility('confirm_password', 'eye-icon-open-confirm', 'eye-icon-closed-confirm')">
                    <i id="eye-icon-open-confirm" class="fas fa-eye"></i>
                    <i id="eye-icon-closed-confirm" class="fas fa-eye-slash hidden"></i>
                </button>
            </div>
        </div>

        <div class="container">
            <img src="https://img.icons8.com/nolan/64/folder-invoices.png" alt="Folder Icon">
            <h2>Profile Image</h2>
            <p>Get started by selecting a profile image.</p>
            <input id="image" type="file" name="image" accept="image/x-png,image/gif,image/jpeg,image/jpg" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100 rounded-lg cursor-pointer focus:outline-none">
        </div>

        <div class="input-container">
            <button type="submit" name="submit" class="flex w-full justify-center rounded-md btn-primary px-3 py-1.5 text-sm font-semibold leading-6 text-white shadow-sm btn-primary:hover focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Register</button>
        </div>
    </form>
    <div class="link-container">
        <a href="/login" class="link">Already registered?</a>
    </div>
</div>

<script>
    function togglePasswordVisibility(inputId, openIconId, closedIconId) {
        var passwordInput = document.getElementById(inputId);
        var eyeIconOpen = document.getElementById(openIconId);
        var eyeIconClosed = document.getElementById(closedIconId);

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIconOpen.classList.add('hidden');
            eyeIconClosed.classList.remove('hidden');
        } else {
            passwordInput.type = 'password';
            eyeIconOpen.classList.remove('hidden');
            eyeIconClosed.classList.add('hidden');
        }
    }
</script>