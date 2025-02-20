<link rel="stylesheet" href="/ui/styles.css">

<nav class="bg-navbar custom-navbar-height">
    <div class="mx-auto max-w-7xl px-2 sm:px-6 lg:px-8 h-full">
        <div class="relative flex h-full items-center justify-between">
            <div class="flex items-center space-x-4 h-full">
                <img class="custom-height w-auto -ml-8" src="/ui/icons/Pink_Y2k_Flower_Cute_Streetwear_Logo_1_-removebg-preview.png" alt="Velvet_logo">
                <a href="/" class="active-link rounded-md px-3 py-2 text-sm font-medium text-navbar hover-bg-navbar" aria-current="page">Home</a>
            </div>
            <div class="flex items-center space-x-4 h-full">
                <button id="darkModeToggle" class="toggle-icon">
                    <img id="moonIcon" src="https://img.icons8.com/ios/50/FFFFFF/crescent-moon.png" alt="Moon Icon" />
                    <img id="sunIcon" src="https://img.icons8.com/ios/50/FFFFFF/sun--v1.png" alt="Sun Icon" class="hidden" />
                </button>
                <?php if(isset($_SESSION['username'])) : ?>
                    <a href="/profile">
                        <img class="rounded-icon"
                             src="<?= !empty($_SESSION['image']) ? '/' . htmlspecialchars($_SESSION['image']) : "/ui/icons/default.png" ?>"
                             alt="User Icon">
                    </a>
                    <form action="/logout" method="get">
                        <button type="submit" name="logout" class="flex w-full justify-center rounded-md btn-primary px-3 py-1.5 text-sm font-semibold leading-6 shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2">Log out</button>
                    </form>
                <?php else : ?>
                    <form action="/login" method="get">
                        <button type="submit" name="login" class="flex w-full justify-center rounded-md btn-primary px-3 py-1.5 text-sm font-semibold leading-6 shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2">Log in</button>
                    </form>
                    <form action="/register" method="get">
                        <button type="submit" name="register" class="flex w-full justify-center rounded-md btn-primary px-3 py-1.5 text-sm font-semibold leading-6 shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2">Register</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>