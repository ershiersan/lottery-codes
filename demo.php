<?php
    include './service/CodesHandler.php';
    include './service/MyCodesHandler.php';
    $objCodesHandler = new MyCodesHandler();
    print_r($objCodesHandler->encode(1, 12, 20));
    print_r($objCodesHandler->decode('P3LW9KPNHGNM'));
