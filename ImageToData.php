<?php
require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/database.php';
use thiagoalessio\TesseractOCR\TesseractOCR;

class ImageNotFoundException extends \Exception {}

class ImageToData extends Database
{
    private static $width;
    private static $height;
    /////////////////////////////////////////////////////////////////////////
    // Name: extractBill
    // Input: full file name(path+filename+extend)
    // Output: String with format VCS
    // Example: extractBill(serveX/abc.pdf) >> String format CSV
    // Process: use Tesseract OCR and .... to do:
    // 1.File PDF:convert file pdf to images tiff, export data to csv format
    /////////////////////////////////////////////////////////////////////////
    public static function extractBill($file_pdf)
    {
        $name_customer_inFacture = 'tills'; //define for nam customer in facture
        $isdebit = true; //define facture is debit;
        $time_pre = microtime(true);
        $fileType = strtolower(pathinfo($file_pdf, PATHINFO_EXTENSION));
        $baseimageFile = "images/" . basename($file_pdf, ".pdf");
        $numPage = 0;
        $arr_csv = array();
        //para of factures
        
        //end factures
		/*
        $define = array();
        $define['ptnFname'] = "";
        $define['ptnLname'] = "";

        $define['ptnFsoustotal'] = ""; //ptn First soustotal
        $define['ptnLsoustotal'] = ""; //ptn Last soustotal

        $define['ptnFtotal'] = ""; //ptn First totaldes
        $define['ptnLtotal'] = ""; //ptn Last totaldes

        $define['ptnNextPage_first'] = "";
        $define['ptnNextPage_second'] = "";
        $define['customerName'] = "";
        $define['namefind'] = false;
        $define['ptndateComptable'] = "";
        $define['ptndateValeur'] = "";
        $define['ptn_Company_Name'] = "/mobile\..+/i";
        $define['Company_Name'] = "";
        $define['ptn_IBAN'] = "";
        $define['IBAN'] = false;
        $define['IBAN_Number'] = "";
        $define['title_table'] = array();
        $define['pattern_splitTotalDes'] = "";
        $define['ptn_currency'] = "";
		*/

        if ($fileType == "pdf") {
            $tesseractInstance = new TesseractOCR();
            // $imtest = new Imagick();
            // $imtest->readImage(realpath($file_pdf));
            //unset($imtest);
            $numPage = self::count_pages(realpath($file_pdf));
            $lastPage = $numPage - 1;
            $StringFirstPage = "";
            $StringLastPage = "";
            $isFirstPage = false;
            $isLastPage = false;

            $max_num_page = ($numPage > 1 ? 2 : 1);
            for ($i = 0; $i < $max_num_page; $i++) {
                $im = new Imagick();
                $im->setResolution(200, 200);
				//$im->readImage('F:\1.pdf[0]');
                if ($i == 0) {
					//$im->readImage('F:\1.tiff');
					//$im->readImage('F:\1.PDF');
					$im->readImage(realpath($file_pdf));
                    //$im->readImage(realpath($file_pdf)."[{$i}]");								
                } else {
                    $im->readImage(realpath($file_pdf) . "[{$lastPage}]");
                }
				
                $im->setImageFormat("tiff");
				
                $im->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
                $im->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                $im->transformImageColorSpace(\Imagick::COLORSPACE_GRAY);
                $im->brightnessContrastImage(0, 50);

                // $max = $im->getQuantumRange()["quantumRangeLong"];
                $im->thresholdimage($max * 0.8);
				
				//$im->writeImages('F:\pageone.tiff', false);

                self::$width = $im->getImageWidth();
                self::$height = $im->getImageHeight();

                //file_put_contents("images/" . basename($file_pdf, ".pdf") . "_p{$i}.tiff", $im);

                //Read data image from Object
                $data = $im->getImageBlob();
                $size = $im->getImageLength();
                $tesseractInstance->imageData($data, $size);
                if ($i == 0) {
                    $StringFirstPage = $tesseractInstance->lang('eng', 'fra')->oem(1)->psm(4)
                        ->configFile('tsv')->run();
                    $isFirstPage = true;
                    // echo "<pre>{$StringFirstPage}</pre>";
                    //die();
                } else {
                    $StringLastPage = $tesseractInstance->lang('eng', 'fra')->oem(1)->psm(4)
                        ->configFile('tsv')->run();
                    // echo "<pre>{$StringLastPage}</pre>";
                    // die();
                    $isLastPage = true;
                }
            }

            //read file json config bank
            
            //end read json
            //end define parameter

            //read file json config bill
            //end read json bll
            $is_dt = false;
            if (preg_match('/docteur/i', $StringFirstPage) || preg_match('/doct/i', $StringFirstPage) || preg_match('/medecine/i', $StringFirstPage) || preg_match('/médecin/i', $StringFirstPage) || preg_match('/RPPS/i', $StringFirstPage)) {
                $is_dt = true;
            }
			if ($is_dt) {
                    $rs = self::processDT($file_pdf);//for prescription
                    return $rs;
            }
			
			else {
            return "This file is not a prescription";
			}
			
        } else if ($fileType == "jpg" || $fileType == "tiff" || $fileType == "png") {
            $tesseractInstance = new TesseractOCR();
            $numPage = 1;
            $lastPage = $numPage - 1;
            $StringFirstPage = "";
            $StringLastPage = "";
            $isFirstPage = false;
            $isLastPage = false;
            $im = new Imagick();
            $im->setResolution(200, 200);
            $im->readImage(realpath($file_pdf));
            $im->setImageFormat("tiff");
            $im->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
            $im->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
            $im->transformImageColorSpace(\Imagick::COLORSPACE_GRAY);
            $im->brightnessContrastImage(0, 60);
            $max = $im->getQuantumRange();
            $max = $max["quantumRangeLong"];
            $im->thresholdimage($max * 0.9);
			
            //Read data image from Object
            $data = $im->getImageBlob();
            $size = $im->getImageLength();
            $tesseractInstance->imageData($data, $size);
            $StringFirstPage = $tesseractInstance->lang('eng', 'fra')->oem(1)->psm(4)
                ->configFile('tsv')->run();
            $isFirstPage = true;
            //read file json config
            
            //end read json
            //end define parameter
			
			//read file json config bill
            //end read json bll
			
			$is_dt = false;
            if (preg_match('/docteur/i', $StringFirstPage) || preg_match('/doct/i', $StringFirstPage) || preg_match('/medecine/i', $StringFirstPage) || preg_match('/médecin/i', $StringFirstPage) || preg_match('/RPPS/i', $StringFirstPage)) {
                $is_dt = true;
            }
			if ($is_dt) {
                    $rs = self::processDT($file_pdf);//for prescription(Ordonnance Medicale)
                    return $rs;
            }
			
			else {
            return "This file is not a prescription";
			}
            
        } else {
            return "This type of file is not support in this time";
        }

        $time_post = microtime(true);
        $exec_time = $time_post - $time_pre;
        return self::convertDataToCSV($arr_csv, $exec_time, $define['Company_Name'], $define['IBAN_Number'], trim($define['customerName']));
    }
    public static function convertdate($date)
    {
        if ((preg_match("/\//",$date) || preg_match("/\./",$date ) !==false) && $date != '') {
            $time = strtotime("01/01/1900");
            $rs = date('Y-m-d', $time);
            $tmp_ar_time = preg_split("/(\.|\/)/", $date);
            if (count($tmp_ar_time) == 3) {
                $time = strtotime($tmp_ar_time[1] . '/' . $tmp_ar_time[0] . '/' . $tmp_ar_time[2]);
                $rs = date('Y-m-d', $time);
            } else if (count($tmp_ar_time) == 1) {
                $time = strtotime('01/01/' . $tmp_ar_time[0]);
                $rs = date('Y-m-d', $time);

            }
            return $rs;
        } else if ($date != '') {
            $time = strtotime("01/01/1900");
            $rs = date('Y-m-d', $time);
            $tmp_ar_time = explode(" ", $date);
            $month = 0;
            if (mb_strtolower($tmp_ar_time[1]) == "janvier") {
                $month = 1;
            } else if (mb_strtolower($tmp_ar_time[1]) == "février") {
                $month = 2;
            } else if (mb_strtolower($tmp_ar_time[1]) == "mars") {
                $month = 3;
            } else if (mb_strtolower($tmp_ar_time[1]) == "avril") {
                $month = 4;
            } else if (mb_strtolower($tmp_ar_time[1]) == "mai") {
                $month = 5;
            } else if (mb_strtolower($tmp_ar_time[1]) == "juin") {
                $month = 6;
            } else if (mb_strtolower($tmp_ar_time[1]) == "juillet") {
                $month = 7;
            } else if (mb_strtolower($tmp_ar_time[1]) == "août") {
                $month = 8;
            } else if (mb_strtolower($tmp_ar_time[1]) == "septembre") {
                $month = 9;
            } else if (mb_strtolower($tmp_ar_time[1]) == "octobre") {
                $month = 10;
            } else if (mb_strtolower($tmp_ar_time[1]) == "novembre") {
                $month = 11;
            } else if (mb_strtolower($tmp_ar_time[1]) == "décembre") {
                $month = 12;
            }
            return $tmp_ar_time[2] . '-' . $month . '-' . $tmp_ar_time[0];
        } else {
            return false;
        }

    }
    public static function convertmoney($ht, $ttc)
    {
        $ht = str_replace(",", ".", $ht);
        $ttc = str_replace(",", ".", $ttc);
        $ht = str_replace(" ", "", $ht);
        $ttc = str_replace(" ", "", $ttc);
        $ht = str_replace("€", "", $ht);
        $ttc = str_replace("€", "", $ttc);
        $ht = str_replace("|", "", $ht);
        $ttc = str_replace("|", "", $ttc);
        $tva = (float) $ttc - (float) $ht;
        $ttc = (float) $ttc;
        $ht = (float) $ht;
        $datamoney = array(
            'ht' => $ht,
            'tva' => $tva,
            'ttc' => $ttc,
        );
        return $datamoney;
    }
    public static function getStructureMoney($ht)
    {
        $ht = str_replace(".", "", $ht);
        $ht = str_replace(",", ".", $ht);
        $ht = str_replace(" ", "", $ht);
        $ht = str_replace("€", "", $ht);
        $ht = str_replace("|", "", $ht);
        $ht = (float) $ht;
        return $ht;
    }
    public static function convertDataToCSV($data_array, $time, $Company_Name, $IBAN_Number, $customerName)
    {
        $conn = self::connect();
        $csv_row = "";
        if (isset($data_array["hoadon"]) && $data_array["hoadon"] === true) {
            $datamoney = self::convertmoney($data_array['data']["TOTAL HT"], $data_array['data']["TOTAL TTC"]);
            $date = self::convertdate($data_array['data']["date"]);
            $datenow = date("Y-m-d h:i:s");
            $debit = 0;
            $credit = 0;
            $num_facture = $data_array["data"]['numfacture'];
            if ($data_array['data']['isdebit']) {
                $debit = $datamoney['ttc'];
            } else {
                $credit = $datamoney['ttc'];
            }
            $stringPro = "Call insert_invoice_customer(1,'{$date}',{$datamoney['ht']},{$datamoney['tva']},{$datamoney['ttc']},0,'{$data_array['data']["name"]}','{$data_array['data']["stringData"]}',{$debit},{$credit},'{$num_facture}')";
            $result = mysqli_query($conn, $stringPro);

        } else {
            foreach ($data_array as $row) {
                $id_bank = 1;
                $id_customer = 1;
                $date_ = self::convertdate($row['DATE_FIRST']);
                $date_valeur = self::convertdate($row['DATE_END']);
                $libelle = $row['REFERENCE'];
                $debit = self::getStructureMoney($row['DEBIT']);
                $credit = self::getStructureMoney($row['CREDIT']);
                $yesorno = 0;
                $stringPro = "Call insert_bank_customer(1,1,'{$date_}','{$date_valeur}','{$libelle}',{$debit},{$credit})";
                if ($date_ != false) {
                    $result = mysqli_query($conn, $stringPro);
                }

            }
            $csv_row = $csv_row . "Name of Bank: {$Company_Name};\n";
            $csv_row .= "Date: " . (isset($data_array[0]) ? $data_array[0]["DATE_FIRST"] : "") . ";\n";
            $csv_row .= "Name of Client: {$customerName};\n";
            $csv_row .= "Number of Account:  {$IBAN_Number};\n";
            $csv_row .= "Date;Libelle;Valuer;Debit;Credit\n";
            foreach ($data_array as $row) {
                $isFirst = true;
                foreach ($row as $item) {
                    if ($isFirst) {
                        $csv_row .= trim($item);
                        $isFirst = false;
                    } else {
                        $csv_row .= ';' . trim(preg_replace("/^(\| ?)+/", "", $item)) . '';
                    }
                }
                $csv_row .= "\n";
            }
        }
        return $csv_row;
    }
    public static function num_cpus()
    {
        $numCpus = 1;
        if (is_file('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            preg_match_all('/^processor/m', $cpuinfo, $matches);
            $numCpus = count($matches[0]);
        } else if ('WIN' == strtoupper(substr(PHP_OS, 0, 3))) {
            $process = @popen('wmic cpu get NumberOfCores', 'rb');
            if (false !== $process) {
                fgets($process);
                $numCpus = intval(fgets($process));
                pclose($process);
            }
        } else {
            $process = @popen('sysctl -a', 'rb');
            if (false !== $process) {
                $output = stream_get_contents($process);
                preg_match('/hw.ncpu: (\d+)/', $output, $matches);
                if ($matches) {
                    $numCpus = intval($matches[1][0]);
                }
                pclose($process);
            }
        }
        return $numCpus;
    }

    public static function getdataTotalDes($inputData, $pattern)
    {
        $array_totalDes = explode(" ", $inputData);
        $totalDes_rebit = "";
        $totalDes_debit = "";
        $Check_totalDes_rebit = false;
        $Check_totalDes_debit = false;
        for ($i = 3; $i < count($array_totalDes); $i++) {
            while (!$Check_totalDes_debit) {
                $totalDes_debit .= $array_totalDes[$i];
                if (preg_match($pattern, $array_totalDes[$i])) {
                    $Check_totalDes_debit = true;
                }
                $i++;
            }
            $totalDes_rebit .= $array_totalDes[$i];
        }
        $nameTotalDes = "";
        for ($i = 0; $i < 3; $i++) {
            $nameTotalDes .= $array_totalDes[$i] . " ";
        }
        $nameTotalDes = trim($nameTotalDes);
        $arrayOutputData = array("nameTotalDes" => $nameTotalDes, "totalDes_debit" => $totalDes_debit, "totalDes_crebit" => $totalDes_rebit);
        return $arrayOutputData;
    }
    private static function count_pages($pdfname)
    {
        $pdftext = file_get_contents($pdfname);
        $num = preg_match_all("/\/Page\W/", $pdftext, $dummy);
        return $num;
    }
    public static function processDT($file_pdf)
    {
        $time_pre = microtime(true);
        $fileType = strtolower(pathinfo($file_pdf, PATHINFO_EXTENSION));
        $baseimageFile = "images/" . basename($file_pdf, ".pdf");
        $numPage = 0;

        $find_nameCustomer = false; // cai nay khi tim dc ten roi mình cho true

        $arr_csv = array();

        $define = array();
        $define['docteur_name'] = "";
        $define['date'] = "";
        $define['name'] = "";
        $define['address'] = "";
        $define['RPPS'] = "";
        $define['Birthday'] = "";
        $define['NoAgrement'] = "";
        $define['NoSercuriteSociale'] = "";
        $define['age'] = "";
        $define['nameMedicine_guide'] = array();

        $array_nameMedicine_guide = array(); //cai nay luu key value di anh, key la name , value là guide

        if ($fileType == "pdf") {
            $tesseractInstance = new TesseractOCR();
            $numPage = self::count_pages(realpath($file_pdf));
            $lastPage = $numPage - 1;
            $StringFirstPage = "";
            $StringLastPage = "";
            $isFirstPage = false;
            $isLastPage = false;
            for ($i = 0; $i < $numPage; $i++) {
                $im = new Imagick();
                $im->setResolution(200, 200);
                $im->readImage(realpath($file_pdf) . "[{$i}]");
                $im->setImageFormat("tiff");
                $im->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
                $im->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                $im->transformImageColorSpace(\Imagick::COLORSPACE_GRAY);
                $im->brightnessContrastImage(0, 50);

                //file_put_contents(basename($file_pdf, ".pdf") . ".tiff", $im);

                $data = $im->getImageBlob();
                $size = $im->getImageLength();
                $tesseractInstance->imageData($data, $size);
                $result = $tesseractInstance->lang('eng', 'fra')->oem(1)->psm(3)
                    ->configFile('tsv')->run();
                // echo "<pre>{$result}</pre>";
                // die();
                $arr_output = preg_split("/(\\n|\\t)/", $result);
                $totalRow = count($arr_output) / 12;
                $isFound = false;
                $isFindRPPS = false;
                $idxRow = 0;
                $content = '';
                $top = 0; //idx column 7

                while (!$isFound && $idxRow < $totalRow) {
                    $idx = $idxRow * 12;
                    if ($idx + 11 < $totalRow * 12) {
                        $content = $arr_output[$idx + 11];
                    }
                    if ($idx + 12 + 11 < $totalRow * 12) {
                        $content_next = $arr_output[$idx + 12 + 11];
                    }
                    if (preg_match("/docteur/i", $content) || preg_match("/doct/i", $content) || preg_match("/Dr\./i", $content)) {
                        $startIdx = self::getIdxStartLine($idx, $arr_output);
                        $tmp = self::getContentLine($startIdx, $arr_output);
                        $define['docteur_name'] = $tmp["content"];
                        $idxRow += $tmp["step"];
                    }
                    if (preg_match("/^\s?[0-9]{9}\s?$/", $content)) {
                        $define['NoAgrement'] = $content;
                    }
                    if (preg_match("/allée/i", $content) || preg_match("/rue/i", $content) || preg_match("/avenue/i", $content)) {
                        $startIdx = self::getIdxStartLine($idx, $arr_output);
                        $tmp = self::getContentLine($startIdx, $arr_output);
                        $define['address'] = $tmp["content"];
                        $idxRow += $tmp["step"];
                    }
                    if (preg_match("/RPPS/i", $content) && !$isFindRPPS) {
                        $idxTmp = $idx;
                        $output_array = "";
                        while ($idxTmp + 11 + 12 < $totalRow * 12 && !$isFindRPPS) {
                            $idxTmp += 12;
                            if (preg_match("/^\s?([0-9]{11})\s?$/i", $arr_output[$idxTmp + 11])) {
                                $isFindRPPS = true;
                                $define['RPPS'] = $arr_output[$idxTmp + 11];
                            }
                        }

                    }
                    if ($idx - 12 + 11 > 0 && !preg_match("/Née/i", $arr_output[$idx - 12 + 11]) && preg_match("/^le$/i", $content) && preg_match("/^\d\d\/\d\d\/\d\d\d\d$/", $arr_output[$idx + 12 + 11])) {
                        $define['date'] = $arr_output[$idx + 12 + 11];
                        $idx += 24;
                        $endline = $arr_output[$idx + 10];
                        while ($endline == "-1") {
                            $idx += 12;
                            $idxRow++;
                            $endline = $arr_output[$idx + 10];
                        }
                        while ($endline != "-1") {
                            $content = $arr_output[$idx + 11];
                            $endline = $arr_output[$idx + 10];
                            $define['name'] .= $content . " ";
                            $idx = $idx + 12;
                            $idxRow++;
                        }
                        //$isFound = true;
                        $find_nameCustomer = true;
                    }
                    if (preg_match("/^Le$/i", $content) && preg_match("/^[a-z ]+\d\d[a-z ]+\d\d\d\d$/", $arr_output[$idx + 12 + 11] . ' ' . $arr_output[$idx + 12 + 23] . ' ' . $arr_output[$idx + 12 + 35] . ' ' . $arr_output[$idx + 12 + 47])) {

                        $define['date'] = $arr_output[$idx + 12 + 23] . ' ' . $arr_output[$idx + 12 + 35] . ' ' . $arr_output[$idx + 12 + 47];
                        $idx += 60;
                        $endline = $arr_output[$idx + 10];
                        while ($endline == "-1") {
                            $idx += 12;
                            $idxRow++;
                            $endline = $arr_output[$idx + 10];
                        }
                        while ($endline != "-1") {
                            $content = $arr_output[$idx + 11];
                            $endline = $arr_output[$idx + 10];
                            $define['name'] .= $content . " ";
                            $idx = $idx + 12;
                            $idxRow++;
                        }

                        //$isFound = true;
                        $find_nameCustomer = true;
                    }
                    if (preg_match("/^Date$/i", $content) && preg_match("/^[:]$/", $arr_output[$idx + 12 + 11]) && preg_match("/^\d\d\/\d\d\/\d\d\d\d$/", $arr_output[$idx + 12 + 23])) {
                        $define['date'] = $arr_output[$idx + 12 + 23] . ' ' . $arr_output[$idx + 12 + 35] . ' ' . $arr_output[$idx + 12 + 47];
                        $idx += 36;
                        $endline = $arr_output[$idx + 10];
                        while ($endline == "-1") {
                            $idx += 12;
                            $idxRow++;
                            $endline = $arr_output[$idx + 10];
                        }
                        while ($endline != "-1") {
                            $content = $arr_output[$idx + 11];
                            $endline = $arr_output[$idx + 10];
                            $define['name'] .= $content . " ";
                            $idx = $idx + 12;
                            $idxRow++;
                        }
                        //$isFound = true;
                        $find_nameCustomer = true;
                    }
                    //bien cuc bo

                    $find_nameMedicine = false;
                    //$endLine_nameMedicine = false; //lưu khi hết 1 line trong file (-1)
                    $nameMedicine = '';
                    $guide = '';
                    //$endLine_guide = false;
                    if ($find_nameCustomer) // khi nao thấy tên rồi mình mới tim đến tên thuốc
                    {
                        while ($idx + 10 < $totalRow * 12 && $arr_output[$idx + 10] == "-1") {
                            $idx += 12;
                            $idxRow++;
                        }
                        if ($idx + 11 < $totalRow * 12) {
                            $content = $arr_output[$idx + 11];
                        }
                        if ($idx + 12 + 11 < $totalRow * 12) {
                            $content_next = $arr_output[$idx + 12 + 11];
                        }
                        if (preg_match("/^\s?Age\s?$/", $content)) {
                            $define['age'] = $content_next;
                        }
                        if (preg_match("/^N\.S/", $content)) {
                            $define['NoSercuriteSociale'] = $arr_output[$idx + 12 + 11];
                            $idx += 24;
                            $idxRow += 2;
                            $content = $arr_output[$idx + 11];
                            $content_next = $arr_output[$idx + 12 + 11];
                        }
                        if (preg_match("/^\s?([0-9]{13})\s?$/", $content)) {
                            $define['NoSercuriteSociale'] = $content;
                        }
                        if (preg_match("/^Née/i", $content) && preg_match("/^le/i", $content_next)) {
                            $define['Birthday'] = $arr_output[$idx + 11 + 24];
                        }

                        if (preg_match("/^[A-Z]+[0-9]*[A-Z]*$/", $content) && ($top == 0 || ($top != 0 && (int) $arr_output[$idx + 7] - $top < 0.2 * $im->getImageHeight()))) {
                            while ($idx + 10 < $totalRow * 12 && $arr_output[$idx + 10] != "-1") {
                                $nameMedicine .= $arr_output[$idx + 11] . " ";
                                $idx += 12;
                                $idxRow++;
                            }
                            $idx += 12;
                            $idxRow++;
                            while ($idx + 10 < $totalRow * 12 && $arr_output[$idx + 10] == "-1") {
                                $idx += 12;
                                $idxRow++;
                            }
                            if ($top == 0 || ($top != 0 && (int) $arr_output[$idx + 7] - $top < 0.2 * $im->getImageHeight())) {
                                $top = (int) $arr_output[$idx + 7];
                                $tmp = self::getContentLine($idx, $arr_output);
                                $guide = $tmp["content"];
                                $step = $tmp["step"];
                                $idxRow += $step + 1;
                                $idx = 12 * $idxRow;
                                $idx = self::skipStepBlank($idx, $arr_output);
                                if (($idx + 11 < $totalRow * 12
                                    && (preg_match("/^[^A-Z]+$/", $arr_output[$idx + 11])
                                        && ($idx + 12 + 11 < $totalRow * 12
                                            && preg_match("/^[0-9]+$/", $arr_output[$idx + 11])
                                            && preg_match("/^[^A-Z0-9]+$/", $arr_output[$idx + 12 + 11])))
                                    && $arr_output[$idx + 11] != ""
                                    && (int) $arr_output[$idx + 7] - $top < 0.2 * $im->getImageHeight())
                                ) {
                                    $top = (int) $arr_output[$idx + 7];
                                    $tmp = self::getContentLine($idx, $arr_output);
                                    $guide .= $tmp["content"];
                                    $step = $tmp["step"];
                                    $idxRow += $step + 1;
                                    $idx = 12 * $idxRow;
                                    $idx = self::skipStepBlank($idx, $arr_output);
                                    if (($idx + 11 < $totalRow * 12
                                        && (preg_match("/^[^A-Z]+$/", $arr_output[$idx + 11])
                                            && ($idx + 12 + 11 < $totalRow * 12
                                                && preg_match("/^[0-9]+$/", $arr_output[$idx + 11])
                                                && preg_match("/^[^A-Z0-9]+$/", $arr_output[$idx + 12 + 11])))
                                        && $arr_output[$idx + 11] != ""
                                        && (int) $arr_output[$idx + 7] - $top < 0.5 * $im->getImageHeight())
                                    ) {
                                        $top = (int) $arr_output[$idx + 7];
                                        $tmp = self::getContentLine($idx, $arr_output);
                                        $guide .= $tmp["content"];
                                        $step = $tmp["step"];
                                        $idxRow += $step;
                                        $idx = 12 * $idxRow;
                                    }
                                }
                            }
                            $define['nameMedicine_guide'][] = array($nameMedicine => $guide);
                        }
                    }
                    $idxRow++;
                }
            }
        }else if ($fileType == "jpg" || $fileType == "tiff" || $fileType == "png") {
            $tesseractInstance = new TesseractOCR();
            $numPage = 1;
            $lastPage = $numPage - 1;
            $StringFirstPage = "";
            $StringLastPage = "";
            $isFirstPage = false;
            $isLastPage = false;
            $im = new Imagick();
            $im->setResolution(200, 200);
            $im->readImage(realpath($file_pdf));
            $im->setImageFormat("tiff");
            $im->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
            $im->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
            $im->transformImageColorSpace(\Imagick::COLORSPACE_GRAY);
            $im->brightnessContrastImage(0, 60);
            $max = $im->getQuantumRange();
            $max = $max["quantumRangeLong"];
            $im->thresholdimage($max * 0.9);

            //Read data image from Object
			$data = $im->getImageBlob();
			$size = $im->getImageLength();
			$tesseractInstance->imageData($data, $size);
			$result = $tesseractInstance->lang('eng', 'fra')->oem(1)->psm(3)
                ->configFile('tsv')->run();
                // echo "<pre>{$result}</pre>";
                // die();
            $arr_output = preg_split("/(\\n|\\t)/", $result);
            $totalRow = count($arr_output) / 12;
            $isFound = false;
			$isFindRPPS = false;
			$idxRow = 0;
			$content = '';
			$top = 0; //idx column 7
			while (!$isFound && $idxRow < $totalRow) {
                    $idx = $idxRow * 12;
                    if ($idx + 11 < $totalRow * 12) {
                        $content = $arr_output[$idx + 11];
                    }
                    if ($idx + 12 + 11 < $totalRow * 12) {
                        $content_next = $arr_output[$idx + 12 + 11];
                    }
                    if (preg_match("/docteur/i", $content) || preg_match("/doct/i", $content) || preg_match("/Dr\./i", $content)) {
                        $startIdx = self::getIdxStartLine($idx, $arr_output);
                        $tmp = self::getContentLine($startIdx, $arr_output);
                        $define['docteur_name'] = $tmp["content"];
                        $idxRow += $tmp["step"];
                    }
                    if (preg_match("/^\s?[0-9]{9}\s?$/", $content)) {
                        $define['NoAgrement'] = $content;
                    }
                    if (preg_match("/allée/i", $content) || preg_match("/rue/i", $content) || preg_match("/avenue/i", $content)) {
                        $startIdx = self::getIdxStartLine($idx, $arr_output);
                        $tmp = self::getContentLine($startIdx, $arr_output);
                        $define['address'] = $tmp["content"];
                        $idxRow += $tmp["step"];
                    }
                    if (preg_match("/RPPS/i", $content) && !$isFindRPPS) {
                        $idxTmp = $idx;
                        $output_array = "";
                        while ($idxTmp + 11 + 12 < $totalRow * 12 && !$isFindRPPS) {
                            $idxTmp += 12;
                            if (preg_match("/^\s?([0-9]{11})\s?$/i", $arr_output[$idxTmp + 11])) {
                                $isFindRPPS = true;
                                $define['RPPS'] = $arr_output[$idxTmp + 11];
                            }
                        }

                    }
                    if ($idx - 12 + 11 > 0 && !preg_match("/Née/i", $arr_output[$idx - 12 + 11]) && preg_match("/^le$/i", $content) && preg_match("/^\d\d\/\d\d\/\d\d\d\d$/", $arr_output[$idx + 12 + 11])) {
                        $define['date'] = $arr_output[$idx + 12 + 11];
                        $idx += 24;
                        $endline = $arr_output[$idx + 10];
                        while ($endline == "-1") {
                            $idx += 12;
                            $idxRow++;
                            $endline = $arr_output[$idx + 10];
                        }
                        while ($endline != "-1") {
                            $content = $arr_output[$idx + 11];
                            $endline = $arr_output[$idx + 10];
                            $define['name'] .= $content . " ";
                            $idx = $idx + 12;
                            $idxRow++;
                        }
                        //$isFound = true;
                        $find_nameCustomer = true;
                    }
                    if (preg_match("/^Le$/i", $content) && preg_match("/^[a-z ]+\d\d[a-z ]+\d\d\d\d$/", $arr_output[$idx + 12 + 11] . ' ' . $arr_output[$idx + 12 + 23] . ' ' . $arr_output[$idx + 12 + 35] . ' ' . $arr_output[$idx + 12 + 47])) {

                        $define['date'] = $arr_output[$idx + 12 + 23] . ' ' . $arr_output[$idx + 12 + 35] . ' ' . $arr_output[$idx + 12 + 47];
                        $idx += 60;
                        $endline = $arr_output[$idx + 10];
                        while ($endline == "-1") {
                            $idx += 12;
                            $idxRow++;
                            $endline = $arr_output[$idx + 10];
                        }
                        while ($endline != "-1") {
                            $content = $arr_output[$idx + 11];
                            $endline = $arr_output[$idx + 10];
                            $define['name'] .= $content . " ";
                            $idx = $idx + 12;
                            $idxRow++;
                        }

                        //$isFound = true;
                        $find_nameCustomer = true;
                    }
                    if (preg_match("/^Date$/i", $content) && preg_match("/^[:]$/", $arr_output[$idx + 12 + 11]) && preg_match("/^\d\d\/\d\d\/\d\d\d\d$/", $arr_output[$idx + 12 + 23])) {
                        $define['date'] = $arr_output[$idx + 12 + 23] . ' ' . $arr_output[$idx + 12 + 35] . ' ' . $arr_output[$idx + 12 + 47];
                        $idx += 36;
                        $endline = $arr_output[$idx + 10];
                        while ($endline == "-1") {
                            $idx += 12;
                            $idxRow++;
                            $endline = $arr_output[$idx + 10];
                        }
                        while ($endline != "-1") {
                            $content = $arr_output[$idx + 11];
                            $endline = $arr_output[$idx + 10];
                            $define['name'] .= $content . " ";
                            $idx = $idx + 12;
                            $idxRow++;
                        }
                        //$isFound = true;
                        $find_nameCustomer = true;
                    }
                    //bien cuc bo

                    $find_nameMedicine = false;
                    //$endLine_nameMedicine = false; //lưu khi hết 1 line trong file (-1)
                    $nameMedicine = '';
                    $guide = '';
                    //$endLine_guide = false;
                    if ($find_nameCustomer) // khi nao thấy tên rồi mình mới tim đến tên thuốc
                    {
                        while ($idx + 10 < $totalRow * 12 && $arr_output[$idx + 10] == "-1") {
                            $idx += 12;
                            $idxRow++;
                        }
                        if ($idx + 11 < $totalRow * 12) {
                            $content = $arr_output[$idx + 11];
                        }
                        if ($idx + 12 + 11 < $totalRow * 12) {
                            $content_next = $arr_output[$idx + 12 + 11];
                        }
                        if (preg_match("/^\s?Age\s?$/", $content)) {
                            $define['age'] = $content_next;
                        }
                        if (preg_match("/^N\.S/", $content)) {
                            $define['NoSercuriteSociale'] = $arr_output[$idx + 12 + 11];
                            $idx += 24;
                            $idxRow += 2;
                            $content = $arr_output[$idx + 11];
                            $content_next = $arr_output[$idx + 12 + 11];
                        }
                        if (preg_match("/^\s?([0-9]{13})\s?$/", $content)) {
                            $define['NoSercuriteSociale'] = $content;
                        }
                        if (preg_match("/^Née/i", $content) && preg_match("/^le/i", $content_next)) {
                            $define['Birthday'] = $arr_output[$idx + 11 + 24];
                        }

                        if (preg_match("/^[A-Z]+[0-9]*[A-Z]*$/", $content) && ($top == 0 || ($top != 0 && (int) $arr_output[$idx + 7] - $top < 0.2 * $im->getImageHeight()))) {
                            while ($idx + 10 < $totalRow * 12 && $arr_output[$idx + 10] != "-1") {
                                $nameMedicine .= $arr_output[$idx + 11] . " ";
                                $idx += 12;
                                $idxRow++;
                            }
                            $idx += 12;
                            $idxRow++;
                            while ($idx + 10 < $totalRow * 12 && $arr_output[$idx + 10] == "-1") {
                                $idx += 12;
                                $idxRow++;
                            }
                            if ($top == 0 || ($top != 0 && (int) $arr_output[$idx + 7] - $top < 0.2 * $im->getImageHeight())) {
                                $top = (int) $arr_output[$idx + 7];
                                $tmp = self::getContentLine($idx, $arr_output);
                                $guide = $tmp["content"];
                                $step = $tmp["step"];
                                $idxRow += $step + 1;
                                $idx = 12 * $idxRow;
                                $idx = self::skipStepBlank($idx, $arr_output);
                                if (($idx + 11 < $totalRow * 12
                                    && (preg_match("/^[^A-Z]+$/", $arr_output[$idx + 11])
                                        && ($idx + 12 + 11 < $totalRow * 12
                                            && preg_match("/^[0-9]+$/", $arr_output[$idx + 11])
                                            && preg_match("/^[^A-Z0-9]+$/", $arr_output[$idx + 12 + 11])))
                                    && $arr_output[$idx + 11] != ""
                                    && (int) $arr_output[$idx + 7] - $top < 0.2 * $im->getImageHeight())
                                ) {
                                    $top = (int) $arr_output[$idx + 7];
                                    $tmp = self::getContentLine($idx, $arr_output);
                                    $guide .= $tmp["content"];
                                    $step = $tmp["step"];
                                    $idxRow += $step + 1;
                                    $idx = 12 * $idxRow;
                                    $idx = self::skipStepBlank($idx, $arr_output);
                                    if (($idx + 11 < $totalRow * 12
                                        && (preg_match("/^[^A-Z]+$/", $arr_output[$idx + 11])
                                            && ($idx + 12 + 11 < $totalRow * 12
                                                && preg_match("/^[0-9]+$/", $arr_output[$idx + 11])
                                                && preg_match("/^[^A-Z0-9]+$/", $arr_output[$idx + 12 + 11])))
                                        && $arr_output[$idx + 11] != ""
                                        && (int) $arr_output[$idx + 7] - $top < 0.5 * $im->getImageHeight())
                                    ) {
                                        $top = (int) $arr_output[$idx + 7];
                                        $tmp = self::getContentLine($idx, $arr_output);
                                        $guide .= $tmp["content"];
                                        $step = $tmp["step"];
                                        $idxRow += $step;
                                        $idx = 12 * $idxRow;
                                    }
                                }
                            }
                            $define['nameMedicine_guide'][] = array($nameMedicine => $guide);
                        }
                    }
                    $idxRow++;
                }
		}
				
        $arr_rs = array();
        $arr_rs["infoFile"]["status"] = "yes";
        $arr_rs["infoFile"]["idUser"] = "";
        $arr_rs["infoFile"]["idFile"] = "";
        $arr_rs["infoFile"]["error"] = "";
        $arr_rs["infoDocteur"]["Cabinet"] = str_replace("Docteur", "", $define['docteur_name']);
        $arr_rs["infoDocteur"]["Address"] = $define['address'];
        $arr_rs["infoDocteur"]["NoAgrement"] = $define['NoAgrement'];
        $arr_rs["infoDocteur"]["NoRPPS"] = $define['RPPS'];
        $arr_rs["infoDocteur"]["Date"] = $define['date'];
        $arr_rs["infoPatien"]["NomPatien"] = $define['name'];
        $arr_rs["infoPatien"]["Birthday"] = $define["Birthday"];
        $arr_rs["infoPatien"]["NoSercuriteSociale"] = $define['NoSercuriteSociale'];
        $arr_rs["infoPatien"]["Age"] = $define['age'];
        foreach ($define['nameMedicine_guide'] as $item) {
            foreach ($item as $key => $value) {
                $output_array = "";
                $pdt = "";
                if (preg_match("/(pdt|pendant) ([0-9]+ [a-z]+) /i", $value, $output_array)) {
                    $pdt = $output_array[2];
                }
                $rowData = array();
                $rowData["Traitement"] = $key;
                $rowData["Posologie"] = $value;
                $rowData["Periode"] = $pdt;
                $arr_rs["infoMedical"][] = $rowData;
            }

        }
        //$arr_rs = str_replace("—", "", $arr_rs);
        return json_encode($arr_rs);
    }
	
    private static function getIdxStartLine($idxRow_current, $arr_rs)
    {
        $idxRow = $idxRow_current - 12;
        $endLine = $arr_rs[$idxRow + 10];
        while ($endLine != "-1") {
            $idxRow -= 12;
            $endLine = $arr_rs[$idxRow + 10];
        }
        return $idxRow + 12;
    }
    private static function getContentLine($idxRow_current, $arr_rs)
    {
        $idxRow = $idxRow_current;
        $strLine = $arr_rs[$idxRow + 11];
        $endLine = $arr_rs[$idxRow + 10];
        $step = 0;
        while ($endLine != "-1" && $idxRow + 12 + 11 < count($arr_rs)) {
            $idxRow += 12;
            $strLine .= " " . $arr_rs[$idxRow + 11];
            $endLine = $arr_rs[$idxRow + 10];
            $step++;
        }
        return array("step" => $step, "content" => $strLine);
    }
    private static function skipStepBlank($idxRow_current, $arr_rs)
    {
        $idxRow = $idxRow_current + 12;
        if ($idxRow + 10 < count($arr_rs)) {
            $endLine = $arr_rs[$idxRow + 10];
            while ($endLine == "-1" && $idxRow + 22 < count($arr_rs)) {
                $idxRow += 12;
                $endLine = $arr_rs[$idxRow + 10];
            }
            return $idxRow;
        }
    }
    private static function getColorStatistics($histogramElements, $colorChannel)
    {
        $colorStatistics = [];

        foreach ($histogramElements as $histogramElement) {
            $color = $histogramElement->getColorValue($colorChannel);
            $color = intval($color * 255);
            $count = $histogramElement->getColorCount();

            if (array_key_exists($color, $colorStatistics)) {
                $colorStatistics[$color] += $count;
            } else {
                $colorStatistics[$color] = $count;
            }
        }

        ksort($colorStatistics);

        return $colorStatistics;
    }
	
}
