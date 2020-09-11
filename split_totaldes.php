<?php
 function getdataTotalDes($inputData, $pattern){
        $array_totalDes = explode(" ", $inputData);
        $totalDes_rebit = "";
        $totalDes_debit = "";
        $Check_totalDes_rebit = false;
        $Check_totalDes_debit = false;
        for($i=3; $i< count($array_totalDes); $i++){
            while (!$Check_totalDes_debit) {			
                $totalDes_debit .= $array_totalDes[$i];
                if(preg_match($pattern, $array_totalDes[$i])){
                    $Check_totalDes_debit = true;
                }
                $i++;
            }
            $totalDes_rebit .= $array_totalDes[$i];
        }
        $nameTotalDes="";
        for($i=0; $i < 3; $i++){
            $nameTotalDes .= $array_totalDes[$i]." ";
        }
        $nameTotalDes = trim($nameTotalDes);
        $arrayOutputData = array("nameTotalDes" => $nameTotalDes, "totalDes_debit" => $totalDes_debit, "totalDes_crebit" => $totalDes_rebit);
        return $arrayOutputData;
    }
?> 
