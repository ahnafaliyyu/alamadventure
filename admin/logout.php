<?php
require_once __DIR__ . '/../config/config.php';

auth_logout();
redirect('login.php');
