<!DOCTYPE html>
<html lang="<?php echo isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adidas Clone - Home</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }
        .dropdown {
            display: none;
        }
    </style>
    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById('language-dropdown');
            dropdown.classList.toggle('hidden');
        }
    </script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-black text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <h1 class="text-2xl font-bold">ADIDAS</h1>
                    </div>
                    <div class="hidden md:block">
                        <div class="ml-10 flex items-baseline space-x-4">
                            <a href="#" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">Home</a>
                            <a href="#" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">Men</a>
                            <a href="#" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">Women</a>
                            <a href="#" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">Kids</a>
                            <a href="#" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">Sale</a>
                        </div>
                    </div>
                </div>
                <div class="hidden md:block">
                    <div class="ml-4 flex items-center md:ml-6">
                        <a href="#" class="p-2 rounded-md text-gray-200 hover:text-white">
                            <i class="fas fa-search"></i>
                        </a>
                        <a href="#" class="p-2 rounded-md text-gray-200 hover:text-white">
                            <i class="fas fa-user"></i>
                        </a>
                        <a href="#" class="p-2 rounded-md text-gray-200 hover:text-white">
                            <i class="fas fa-shopping-cart"></i>
                        </a>
                        <div class="relative inline-block text-left">
                            <div>
                                <button type="button" onclick="toggleDropdown()" class="inline-flex justify-center w-full rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black" id="options-menu" aria-haspopup="true" aria-expanded="true">
                                    <i class="fas fa-globe"></i>
                                </button>
                            </div>
                            <div id="language-dropdown" class="absolute right-0 z-10 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 dropdown" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                                <div class="py-1" role="none">
                                    <a href="change_language.php?lang=en" class="text-gray-700 block px-4 py-2 text-sm" role="menuitem">English</a>
                                    <a href="change_language.php?lang=vi" class="text-gray-700 block px-4 py-2 text-sm" role="menuitem">Tiếng Việt</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <!-- Rest of the content -->
</body>
</html>
