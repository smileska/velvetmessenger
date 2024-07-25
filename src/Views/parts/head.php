    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= isset($title) ? $title : 'Default Title' ?></title>
        <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
        <link rel="stylesheet" href="/ui/styles.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    </head>
    <body>

    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            const body = document.body;
            const darkModeToggle = document.getElementById('darkModeToggle');
            const moonIcon = document.getElementById('moonIcon');
            const sunIcon = document.getElementById('sunIcon');

            darkModeToggle.addEventListener('click', () => {
                body.classList.toggle('dark-mode');

                moonIcon.classList.toggle('hidden');
                sunIcon.classList.toggle('hidden');
            });
        });
    </script>
    </body>
    </html>
