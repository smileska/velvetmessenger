<?php $title = "Log In"; ?>
<?php require('parts/head.php') ?>
<link rel="stylesheet" href="/ui/styles.css">
<div class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-sm">
        <img class="mx-auto custom-height w-auto" src="ui/icons/Pink_Y2k_Flower_Cute_Streetwear_Logo_1_-removebg-preview.png" alt="Velvet_logo">
        <h2 class="mt-10 text-center text-2xl font-bold leading-9 tracking-tight text-gray-900">Log in</h2>
    </div>
    <?php if (isset($errors) && !empty($errors)): ?>
        <div class="rounded-md bg-red-50 p-4">
            <div class="flex">
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">There were errors with your submission:</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul role="list" class="list-disc pl-5 space-y-1">
                                <li><?= htmlspecialchars($errors) ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
        <form class="space-y-6 form-class" action="/login" method="post">
            <div class="form-item">
                <label for="username" class="block text-sm font-medium leading-6 text-gray-900 form-label">Username</label>
                <div class="mt-2 input-container">
                    <input id="username" type="text" name="username" required class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                </div>
            </div>

            <div class="form-item">
                <div class="flex items-center justify-between">
                    <label for="password" class="block text-sm font-medium leading-6 text-gray-900 form-label">Password</label>
                </div>
                <div class="mt-2 relative input-container">
                    <input id="password" name="password" type="password" autocomplete="current-password" required class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    <button type="button" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-600" onclick="togglePasswordVisibility()">
                        <i id="eye-icon-open" class="fas fa-eye"></i>
                        <i id="eye-icon-closed" class="fas fa-eye-slash hidden"></i>
                    </button>
                </div>
            </div>

            <div>
                <button type="submit" name="submit" class="flex w-full justify-center rounded-md btn-primary px-3 py-1.5 text-sm font-semibold leading-6 text-white shadow-sm btn-primary:hover focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Log in</button>
            </div>
        </form>
    <div class="link-container">
        <a href="/register" class="link">Haven't registered yet?</a>
    </div>
    </div>
</div>
<script>
    function togglePasswordVisibility() {   
        var passwordInput = document.getElementById('password');
        var eyeIconOpen = document.getElementById('eye-icon-open');
        var eyeIconClosed = document.getElementById('eye-icon-closed');

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
</html>

