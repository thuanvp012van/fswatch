<?php

sleep(1);

if(!file_exists(__DIR__ . '/add')) {
    fopen(__DIR__ . '/add', 'w');
}
