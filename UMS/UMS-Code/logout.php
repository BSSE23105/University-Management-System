<?php
session_start();
session_unset();
session_destroy();
header("Location: index.php"); // Now we can use direct path
exit;
?>