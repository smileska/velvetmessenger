    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= isset($title) ? $title : 'Default Title' ?></title>
        <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
        <link rel="stylesheet" href="/ui/styles.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    </head>
    <body>

    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            const body = document.body;
            const darkModeToggle = document.getElementById('darkModeToggle');
            const moonIcon = document.getElementById('moonIcon');
            const sunIcon = document.getElementById('sunIcon');
            const isDarkMode = <?php echo isset($_SESSION['dark_mode']) && $_SESSION['dark_mode'] ? 'true' : 'false'; ?>;
            if (isDarkMode) {
                body.classList.add('dark-mode');
                moonIcon.classList.add('hidden');
                sunIcon.classList.remove('hidden');
            }

            darkModeToggle.addEventListener('click', () => {
                fetch('/toggle-dark-mode', { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        body.classList.toggle('dark-mode', data.dark_mode);
                        moonIcon.classList.toggle('hidden', data.dark_mode);
                        sunIcon.classList.toggle('hidden', !data.dark_mode);
                    });
            });
        });
    </script>
    </body>
    </html>
