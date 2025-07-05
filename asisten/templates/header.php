<?php
// header.php for asisten
require_once __DIR__ . '/../../config.php'; // Adjust path as necessary to reach config.php

check_login_and_role("asisten"); // Check if logged in as asisten
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asisten Dashboard - SIMPRAK</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Custom styles for sidebar */
        .sidebar {
            width: 256px; /* Tailwind's w-64 */
            transition: transform 0.3s ease-in-out;
            transform: translateX(0); /* Default: sidebar visible */
        }
        .main-content-wrapper {
            margin-left: 256px; /* Push content to the right by sidebar width */
            transition: margin-left 0.3s ease-in-out;
        }
        /* For smaller screens, hide the sidebar and use a toggle */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%); /* Hide sidebar by default on mobile */
                position: fixed;
                z-index: 10;
            }
            .sidebar.open {
                transform: translateX(0); /* Show sidebar when open */
            }
            .main-content-wrapper {
                margin-left: 0; /* No margin on mobile */
            }
        }
    </style>
</head>
<body class="bg-gray-100 flex h-screen">
    <!-- Sidebar -->
    <aside class="sidebar fixed h-full bg-blue-700 text-white p-4 flex flex-col shadow-lg">
        <div class="text-2xl font-bold mb-8 text-center">SIMPRAK Asisten</div>
        <ul class="flex flex-col space-y-4">
            <li>
                <a href="<?php echo BASE_URL; ?>asisten/dashboard.php" class="block py-2 px-4 rounded hover:bg-blue-600">
                    Dashboard
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>asisten/kelola_praktikum.php" class="block py-2 px-4 rounded hover:bg-blue-600">
                    Kelola Praktikum
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>asisten/kelola_modul.php" class="block py-2 px-4 rounded hover:bg-blue-600">
                    Kelola Modul
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>asisten/laporan_masuk.php" class="block py-2 px-4 rounded hover:bg-blue-600">
                    Laporan Masuk
                </a>
            </li>

            <!-- [Tambahan] Kelola Laporan -->
            <li>
                <a href="<?php echo BASE_URL; ?>asisten/kelola_laporan.php" class="block py-2 px-4 rounded hover:bg-blue-600">
                    Kelola Laporan
                </a>
            </li>
            <!-- [End Tambahan] -->

            <li>
                <a href="<?php echo BASE_URL; ?>asisten/kelola_akun.php" class="block py-2 px-4 rounded hover:bg-blue-600">
                    Kelola Akun
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>logout.php" class="block py-2 px-4 rounded hover:bg-blue-600">
                    Logout
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="main-content-wrapper flex-1 overflow-auto p-8">
        <!-- Optional: Mobile menu toggle button -->
        <button id="menu-toggle" class="md:hidden fixed top-4 left-4 bg-blue-600 text-white p-2 rounded-md shadow-md z-20">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>

        <script>
            // Simple JavaScript for mobile menu toggle
            document.addEventListener('DOMContentLoaded', function() {
                const menuToggle = document.getElementById('menu-toggle');
                const sidebar = document.querySelector('.sidebar');
                if (menuToggle && sidebar) {
                    menuToggle.addEventListener('click', function() {
                        sidebar.classList.toggle('open');
                    });
                }
            });
        </script>
