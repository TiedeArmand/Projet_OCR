<?php
require_once dirname(__FILE__) . "/ImageToData.php";
// Check if image file is a actual image or fake image
if (isset($_POST["btnSubmit"])) {
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["pdf_file"]["name"]);
    if (move_uploaded_file($_FILES["pdf_file"]["tmp_name"], $target_file)) {

        $time_pre = microtime(true);
        $result = ImageToData::extractBill($target_file);
        $time = microtime(true) - $time_pre;
        $base_filename = basename($target_file, ".pdf");
        $dtJson = @json_decode($result, true);
        if ($dtJson == null) {
            header('Content-type: text/plain');
            header("Content-Disposition: attachment; filename={$base_filename}_{$time}s.csv");
            echo $result;
        } else {
            $result = "";
            
            foreach ($dtJson as $item) {
                $isFirstRow = true;
                foreach ($item as $key => $value) {
                    if (is_array($value)) {
                        $isFirst = true;
                        $strTitle = "";
                        $strItem = "";
                        foreach ($value as $k => $i) {
                            if ($isFirst) {
                                $strTitle .= $k;
                                $strItem .= $i;
                                $isFirst = false;
                            } else {
                                $strTitle .= ";" . $k;
                                $strItem .= ";" . $i . ";";
                            } 
                        }
                        if ($isFirstRow) {
                            $result .= $strTitle . "\n";
                            $isFirstRow = false;
                        }
                        $result .= $strItem . "\n";
                    } else {
                        $result .= "{$key}:;{$value}\n";
                    }
                }

            }
            header('Content-type: text/plain');
            header("Content-Disposition: attachment; filename={$base_filename}_{$time}s.csv");
            echo $result;
        }
    }
}
