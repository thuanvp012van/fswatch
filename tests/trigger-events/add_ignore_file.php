<?php

usleep(700);

if(!file_exists(__DIR__ . '/ignore.txt')) {
    fopen(__DIR__ . '/ignore.txt', 'w');
}
