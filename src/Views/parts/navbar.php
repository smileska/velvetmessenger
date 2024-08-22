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
                    <div class="relative">
                        <button id="notificationBell" class="relative text-white focus:outline-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span id="notificationCount" class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full hidden">0</span>
                        </button>
                        <div id="notificationDropdown" class="absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg overflow-hidden z-20 hidden">
                            <div class="py-2" id="notificationList">
                                <div class="px-4 py-2 text-sm text-gray-700 no-notifications">No new notifications</div>
                            </div>
                        </div>
                    </div>
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