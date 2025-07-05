    </div> <!-- Closes main-content-wrapper -->
</body>
</html>
<?php
// Close database connection if it was opened in config.php and not needed further
if (isset($link) && is_object($link)) {
    mysqli_close($link);
}
?>
