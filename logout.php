<?php
require __DIR__ . '/vyro-config.php';

unset($_SESSION['user_id']);
session_regenerate_id(true);
flash('info', 'Vous êtes déconnecté.');
redirect('index.php');
