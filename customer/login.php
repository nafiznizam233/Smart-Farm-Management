<?php

require_once __DIR__ . '/../config/app.php';

header(
    'Location: ' . url_path('login.php?type=customer')
);

exit;

?>