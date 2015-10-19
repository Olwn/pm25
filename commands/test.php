<?php

echo file_get_contents('http://www.pm25.in/api/querys/station_names.json?token=' . require(__DIR__ . '/token.php'));

