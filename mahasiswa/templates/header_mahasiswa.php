<?php
// header_mahasiswa.php for mahasiswa
require_once __DIR__ . '/../../config.php'; // Adjust path as necessary to reach config.php

// Check if user is logged in and has the 'mahasiswa' role
check_login_and_role("mahasiswa");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mahasiswa Dashboard - KELASKU</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Custom styles for sidebar */
        .sidebar {
            width: 256px; /* 64 * 4px = 256px */
            transition: transform 0.3s ease-in-out;
            transform: translateX(0); /* Default: sidebar visible */
        }
        .main-content-wrapper {
            margin-left: 256px; /* Push content to the right by sidebar width */
            transition: margin-left 0.3s ease-in-out;
        }
        /* For smaller screens, hide the sidebar by default */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                z-index: 10;
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .main-content-wrapper {
                margin-left: 0;
            }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

    <!-- Sidebar -->
    <aside class="sidebar fixed h-full bg-blue-300 text-white p-4 flex flex-col shadow-lg">
        <div class="text-2xl font-bold mb-8 text-center">KELASKU</div>
        <ul class="flex flex-col space-y-4">
            <li>
                <a href="<?php echo BASE_URL; ?>mahasiswa/dashboard.php"
                   class="block py-2 px-4 rounded hover:bg-blue-300">
                    Dashboard
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>mahasiswa/katalog_praktikum.php"
                   class="block py-2 px-4 rounded hover:bg-blue-300">
                    Katalog Praktikum
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>mahasiswa/praktikum_saya.php"
                   class="block py-2 px-4 rounded hover:bg-blue-300">
                    Praktikum Saya
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>logout.php"
                   class="block py-2 px-4 rounded hover:bg-blue-300">
                    Logout
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="main-content-wrapper flex-1 p-8">
        <!-- Optional: Mobile menu toggle button -->
        <button id="menu-toggle" class="md:hidden fixed top-4 left-4 bg-indigo-600 text-white p-2 rounded-md shadow-md z-20">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                 xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>

        <script>
            // Simple JavaScript for mobile menu toggle
            document.addEventListener('DOMContentLoaded', function () {
                const menuToggle = document.getElementById('menu-toggle');
                const sidebar = document.querySelector('.sidebar');
                if (menuToggle && sidebar) {
                    menuToggle.addEventListener('click', function () {
                        sidebar.classList.toggle('open');
                    });
                }
            });
        </script>
