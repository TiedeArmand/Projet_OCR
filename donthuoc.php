<?php
//bien cuc bo
$find_nameCustomer = false; // cai nay khi tim dc ten roi mình cho true
$find_nameMedicine = false;
$endLine_nameMedicine = false; //lưu khi hết 1 line trong file (-1)
$nameMedicine = '';
$guide = '';
$endLine_guide = false;
$array_nameMedicine_guide = array(); //cai nay luu key value di anh, key la name , value là guide

if ($find_nameCustomer) // khi nao thấy tên rồi mình mới tim đến tên thuốc
{
    if (true) {
        while (!$endLine_nameMedicine) {
            $nameMedicine .= $content;
        }
        if ($endLine_nameMedicine) {
            while (!$endLine_guide) {
                $guide .= $content;
            }
        }

        $array_nameMedicine_guide = '';
    }
}
