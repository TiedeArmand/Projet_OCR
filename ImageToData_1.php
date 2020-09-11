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
        $define_factures = array();
        $define_factures['FacturesName'] = "";
        $define_factures['HT'] = "";
        $define_factures['TVA'] = "";
        $define_factures['TTC'] = "";
        $define_factures['Date'] = "";
        //end factures
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
            $strJsonFileContents = file_get_contents(dirname(__FILE__) . "/bank_define.json");
            // Convert to array
            $arrayJson = json_decode($strJsonFileContents, true);
            foreach ($arrayJson as $name => $value) {
                if (strpos($StringFirstPage, $name) != false || strpos($StringLastPage, $name) != false) {
                    $define['ptndateComptable'] = $value["dateComptable"];
                    $define['ptndateValeur'] = $value["dateValeur"];
                    $define['Company_Name'] = $value["bankName"];
                    if ($define['Company_Name'] != "BNP PARIBAS"
                        && $define['Company_Name'] != "Crédit Industriel et Commercial"
                        && $define['Company_Name'] != "LCL"
                        && $define['Company_Name'] != "Societe Generale"
                        && $define['Company_Name'] != "BRED BANQUE POPULAIRE"
                        && $define['Company_Name'] != "CAISSE D'EPARGNE") {
                        return "This Bank is not support in this time";
                    }
                    $define['ptn_IBAN'] = $value["IBAN"];
                    $keyWord_customerName = explode(' ', $value["customerName"]);
                    $define['ptnFname'] = $keyWord_customerName[0];
                    $define['ptnLname'] = $keyWord_customerName[1];
                    $define['ptnNextPage_first'] = $value["breakPage"];

                    $keyWord_soustotal = explode(" ", $value["soustotal"]);
                    $define['ptnFsoustotal'] = $keyWord_soustotal[0]; //ptn First soustotal
                    $define['ptnLsoustotal'] = $keyWord_soustotal[1]; //ptn Last soustotal

                    $keyWord_totaldes = explode(" ", $value["totaldes"]);
                    $define['ptnFtotal'] = $keyWord_totaldes[0]; //ptn First totaldes
                    $define['ptnLtotal'] = $keyWord_totaldes[1];
                    $define['title_table'] = $value["title_table"];
                    $define['pattern_splitTotalDes'] = $value['pattern_splitTotalDes'];
                    $define['ptn_currency'] = $value['ptn_currency'];
                    break;
                }
            }
            //end read json
            //end define parameter

            //read file json config bill
            $strJsonFileContents = file_get_contents(dirname(__FILE__) . "/factures.json");
            // Convert to array
            $arrayJson = json_decode($strJsonFileContents, true);
            foreach ($arrayJson as $name => $value) {
                if (strpos($StringFirstPage, $name) != false || strpos($StringLastPage, $name) != false) {
                    $define_factures['FacturesName'] = $value["FacturesName"];
                    $define_factures['HT'] = $value["HT"];
                    $define_factures['TVA'] = $value["TVA"];
                    $define_factures['TTC'] = $value["TTC"];
                    $define_factures['Date'] = $value["Date"];
                    if ($define_factures['FacturesName'] != "ALBERT ET FILS"
                        && $define_factures['FacturesName'] != "Nathalie BAUDOIN" && $define_factures['FacturesName'] != "SCI DES DAUPHINS" && $define_factures['FacturesName'] != "tills") {
                        return "This Bank is not support in this time";
                    } else if ($define_factures['FacturesName'] == $name_customer_inFacture) {
                        $isdebit = false;
                    }
                    break;
                }
            }
            //end read json bll
            $is_dt = false;
            if (preg_match('/docteur/i', $StringFirstPage) || preg_match('/doct/i', $StringFirstPage) || preg_match('/medecine/i', $StringFirstPage) || preg_match('/médecin/i', $StringFirstPage) || preg_match('/RPPS/i', $StringFirstPage)) {
                $is_dt = true;
            }
            if (empty($define['Company_Name'])) {
                //return "This file is not exist Bank name";
                if ($is_dt) {
                    $rs = self::processDT($file_pdf);//for prescription
                    return $rs;
                } elseif (!empty($define_factures['FacturesName'])) {
                    $arr_csv = self::processHD($numPage, $file_pdf, $tesseractInstance, $define_factures, $isdebit);
                }
            } else if ($define['Company_Name'] === "BNP PARIBAS") {
                $arr_csv = self::processBNP($tesseractInstance, $file_pdf, $numPage, $define, $isFirstPage, $StringFirstPage, $isLastPage, $StringLastPage);
            } else if ($define['Company_Name'] === "Crédit Industriel et Commercial") {
                $arr_csv = self::processCIC($tesseractInstance, $file_pdf, $numPage, $define, $isFirstPage, $StringFirstPage, $isLastPage, $StringLastPage);
            } else if ($define['Company_Name'] === "LCL") {
                $arr_csv = self::processLCL($tesseractInstance, $file_pdf, $numPage, $define, $isFirstPage, $StringFirstPage, $isLastPage, $StringLastPage);
            } else if ($define['Company_Name'] === "Societe Generale") {
                $arr_csv = self::processSG($tesseractInstance, $file_pdf, $numPage, $define, $isFirstPage, $StringFirstPage, $isLastPage, $StringLastPage);
            } else if ($define['Company_Name'] === "BRED BANQUE POPULAIRE") {
                $arr_csv = self::processBRED($tesseractInstance, $file_pdf, $numPage, $define, $isFirstPage, $StringFirstPage, $isLastPage, $StringLastPage);
            } else if ($define['Company_Name'] === "CAISSE D'EPARGNE") {
                $arr_csv = self::processCAISSE($tesseractInstance, $file_pdf, $numPage, $define, $isFirstPage, $StringFirstPage, $isLastPage, $StringLastPage);
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
			
			//$im->writeImages('F:\pageone.tiff', false);
			//$msg = array();
			//$msg[] = 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
			//$msg[] = '';
			//$msg[] = 'File Name:';
			//$msg[] = "$file_pdf";
			//$msg = join(PHP_EOL, $msg);
			//throw new ImageNotFoundException($msg);
            //file_put_contents("images/" . basename($file_pdf, ".pdf") . "_p{$i}.tiff", $im);

            //Read data image from Object
            $data = $im->getImageBlob();
            $size = $im->getImageLength();
            $tesseractInstance->imageData($data, $size);
            $StringFirstPage = $tesseractInstance->lang('eng', 'fra')->oem(1)->psm(4)
                ->configFile('tsv')->run();
            $isFirstPage = true;
            //read file json config
            $strJsonFileContents = file_get_contents(dirname(__FILE__) . "/bank_define.json");
            // Convert to array
            $arrayJson = json_decode($strJsonFileContents, true);
            foreach ($arrayJson as $name => $value) {
                if (strpos($StringFirstPage, $name) != false || strpos($StringLastPage, $name) != false) {
                    $define['ptndateComptable'] = $value["dateComptable"];
                    $define['ptndateValeur'] = $value["dateValeur"];
                    $define['Company_Name'] = $value["bankName"];
                    if ($define['Company_Name'] != "BNP PARIBAS"
                        && $define['Company_Name'] != "Crédit Industriel et Commercial"
                        && $define['Company_Name'] != "LCL" 
						&& $define['Company_Name'] != "Societe Generale"
						&& $define['Company_Name'] != "BRED BANQUE POPULAIRE"
                        && $define['Company_Name'] != "CAISSE D'EPARGNE"){
                        return "This Bank is not support in this time";
                    }
                    $define['ptn_IBAN'] = $value["IBAN"];
                    $keyWord_customerName = explode(' ', $value["customerName"]);
                    $define['ptnFname'] = $keyWord_customerName[0];
                    $define['ptnLname'] = $keyWord_customerName[1];
                    $define['ptnNextPage_first'] = $value["breakPage"];

                    $keyWord_soustotal = explode(" ", $value["soustotal"]);
                    $define['ptnFsoustotal'] = $keyWord_soustotal[0]; //ptn First soustotal
                    $define['ptnLsoustotal'] = $keyWord_soustotal[1]; //ptn Last soustotal

                    $keyWord_totaldes = explode(" ", $value["totaldes"]);
                    $define['ptnFtotal'] = $keyWord_totaldes[0]; //ptn First totaldes
                    $define['ptnLtotal'] = $keyWord_totaldes[1];
                    $define['title_table'] = $value["title_table"];
                    $define['pattern_splitTotalDes'] = $value['pattern_splitTotalDes'];
                    $define['ptn_currency'] = $value['ptn_currency'];
                    break;
                }
            }
            //end read json
            //end define parameter
			
			//read file json config bill
            $strJsonFileContents = file_get_contents(dirname(__FILE__) . "/factures.json");
            // Convert to array
            $arrayJson = json_decode($strJsonFileContents, true);
            foreach ($arrayJson as $name => $value) {
                if (strpos($StringFirstPage, $name) != false || strpos($StringLastPage, $name) != false) {
                    $define_factures['FacturesName'] = $value["FacturesName"];
                    $define_factures['HT'] = $value["HT"];
                    $define_factures['TVA'] = $value["TVA"];
                    $define_factures['TTC'] = $value["TTC"];
                    $define_factures['Date'] = $value["Date"];
                    if ($define_factures['FacturesName'] != "ALBERT ET FILS"
                        && $define_factures['FacturesName'] != "Nathalie BAUDOIN" && $define_factures['FacturesName'] != "SCI DES DAUPHINS" && $define_factures['FacturesName'] != "tills") {
                        return "This Bank is not support in this time";
                    } else if ($define_factures['FacturesName'] == $name_customer_inFacture) {
                        $isdebit = false;
                    }
                    break;
                }
            }
            //end read json bll
			
			$is_dt = false;
            if (preg_match('/docteur/i', $StringFirstPage) || preg_match('/doct/i', $StringFirstPage) || preg_match('/medecine/i', $StringFirstPage) || preg_match('/médecin/i', $StringFirstPage) || preg_match('/RPPS/i', $StringFirstPage)) {
                $is_dt = true;
            }
            if (empty($define['Company_Name'])) {
                //return "This file is not exist Bank name";
                if ($is_dt) {
                    $rs = self::processDT($file_pdf);//for prescription(Ordonnance Medicale)
                    return $rs;
                } elseif (!empty($define_factures['FacturesName'])) {
                    $arr_csv = self::processHD($numPage, $file_pdf, $tesseractInstance, $define_factures, $isdebit);
                }
            //}
					
            //if (empty($define['Company_Name'])) {
                //return "This file is not exist Bank name";
                //$arr_csv = self::processHD($numPage, $file_pdf, $tesseractInstance);
				//$arr_csv = self::processHD($numPage, $file_pdf, $tesseractInstance, $define_factures, $isdebit);
				//$rs = self::processDT($file_pdf);
                    //return $rs;
            } else if ($define['Company_Name'] === "BNP PARIBAS") {
                $arr_csv = self::processBNP($tesseractInstance, $file_pdf, $numPage, $define, $isFirstPage, $StringFirstPage, $isLastPage, $StringLastPage);
            } else if ($define['Company_Name'] === "Crédit Industriel et Commercial") {
                $arr_csv = self::processCIC($tesseractInstance, $file_pdf, $numPage, $define, $isFirstPage, $StringFirstPage, $isLastPage, $StringLastPage);
            } else if ($define['Company_Name'] === "LCL") {
                $arr_csv = self::processLCL($tesseractInstance, $file_pdf, $numPage, $define, $isFirstPage, $StringFirstPage, $isLastPage, $StringLastPage);
            } else if ($define['Company_Name'] === "Societe Generale") {
                $arr_csv = self::processSG($tesseractInstance, $file_pdf, $numPage, $define, $isFirstPage, $StringFirstPage, $isLastPage, $StringLastPage);
            } else if ($define['Company_Name'] === "BRED BANQUE POPULAIRE") {
                $arr_csv = self::processBRED($tesseractInstance, $file_pdf, $numPage, $define, $isFirstPage, $StringFirstPage, $isLastPage, $StringLastPage);
            } else if ($define['Company_Name'] === "CAISSE D'EPARGNE") {
                $arr_csv = self::processCAISSE($tesseractInstance, $file_pdf, $numPage, $define, $isFirstPage, $StringFirstPage, $isLastPage, $StringLastPage);
            }
        } else {
            return "This file is not support in this time";
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
    public static function processHD($numPage, $file_pdf, $tesseractInstance, $define_factures, $isdebit)
    {
        $arrRS = array();
        $arrRS["hoadon"] = true;
        $arrRS["data"] = array();
        $arrRS["data"]['isdebit'] = $isdebit;
        for ($i = 0; $i < $numPage; $i++) {
            $im = new Imagick();
            $im->setResolution(200, 200);
            $im->readImage(realpath($file_pdf) . "[{$i}]");
            $im->setImageFormat("tiff");
            $im->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
            $im->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
            $im->transformImageColorSpace(\Imagick::COLORSPACE_GRAY);
            $im->brightnessContrastImage(0, 60);
            $max = $im->getQuantumRange();
            $max = $max["quantumRangeLong"];
            $im->thresholdimage($max * 0.9);
            //$im->adaptiveThresholdImage(40, $im->getImageHeight() / 5, $max * 0.5);
            //file_put_contents("images/" . basename($file_pdf, ".pdf") . "_p{$i}.tiff", $im);
            $data = $im->getImageBlob();
            $size = $im->getImageLength();
            $tesseractInstance->imageData($data, $size);
            $result = $tesseractInstance->lang('eng', 'fra')->oem(1)->psm(4)->configFile('tsv')->run();
            //  echo '<pre>'.$result.'</pre>';
            //  die();
            $arr_output = preg_split("/(\\n|\\t)/", $result);
            $stringData = implode(" ", $arr_output);
            $stringData = str_replace("'", "", $stringData);
            $arrRS["data"]['stringData'] = $stringData;
            $totalRow = count($arr_output) / 12;
            $get_HT = false;
            $get_TVA = false;
            $get_TTC = false;
            $isTotal_ht = false;
            $getdate = false;
            $getnum_facture = false;
            for ($idxRow = 0; $idxRow < $totalRow; $idxRow++) {
                $idx = $idxRow * 12;
                $row = array();
                $content = "";
                $content_next = "";

                if ($idx + 11 < $totalRow * 12) {
                    $content = $arr_output[$idx + 11];
                }
                if ($idx + 11 + 12 < $totalRow * 12) {
                    $content_next = $arr_output[$idx + 11 + 12];
                }
                if (empty($content)) //Don;t process when $content is empty
                {
                    continue;
                }
                //numfacture
                if ((!$getnum_facture && (strpos($content, 'FACTURE:') !== false)) || (!$getnum_facture && (strpos($content, 'Facture') !== false))) {
                    $idx += 11 + 12 + 12;
                    $stop = false;
                    $tring_numfacture = '';
                    while (!$stop) {
                        if (preg_match("/^\D{0,5}\d{1,10}$/i", $arr_output[$idx])) {
                            $stop = true;
                            $getnum_facture = true;
                            $tring_numfacture = $arr_output[$idx];
                        }
                        $idx += 12;
                    }
                    $arrRS["data"]['numfacture'] = trim($tring_numfacture);

                }

                //date
                $keyWord_date = explode(" ", $define_factures['Date']);
                if (!$getdate && (strpos($content, $keyWord_date[0]) !== false) && (strpos($arr_output[$idx + 11 + 12], $keyWord_date[1]) !== false)) {
                    $idx += 11 + 12 + 12;
                    $stop = false;
                    $tring_date = '';
                    while (!$stop) {
                        $tring_date .= $arr_output[$idx] . ' ';
                        if (preg_match("/^\d\d\/\d\d\/\d\d\d\d$/i", $arr_output[$idx]) || preg_match("/^\d\d\d\d$/i", $arr_output[$idx])) {
                            $stop = true;
                            $getdate = true;
                        }
                        $idx += 12;
                    }
                    $arrRS["data"]['date'] = trim($tring_date);
                }
                //end date
                //TOTAL HT
                $keyWord_TOTAL_HT = explode(" ", $define_factures['HT']);
                $value_Total_ht = '';
                if ((strpos($content, $keyWord_TOTAL_HT[0]) !== false) && (strpos($arr_output[$idx + 11 + 12], $keyWord_TOTAL_HT[1]) !== false) && !$isTotal_ht) {
                    $endline = $arr_output[$idx + 10];
                    $idx += 12;
                    while ($endline != "-1") {
                        // echo $arr_output[$idx + 11];
                        $idx += 12;
                        $value_Total_ht .= $arr_output[$idx + 11];
                        $endline = $arr_output[$idx + 10];
                    }
                    $arrRS["data"]['TOTAL HT'] = $value_Total_ht;
                    $isTotal_ht = true;
                }
                if ($isTotal_ht) { //if find have TOTAL HT, continue find all information
                    //find tva
                    $value_tva = '';
                    if (!$get_HT && $content === $define_factures['TVA']) {
                        $tmp_index = $idx + 11 + 12;
                        while (!$get_TVA) {
                            $value_tva .= $arr_output[$tmp_index];
                            if (preg_match("/\d{1,8},\d{1,2}/", $arr_output[$tmp_index])) {
                                $get_TVA = true;
                            }
                        }
                        $arrRS["data"]['TVA'] = $value_tva;
                    }
                    //end find tva

                    //find TOTAL TTC
                    //
                    $keyWord_TOTAL_TTC = explode(" ", $define_factures['TTC']);
                    $isTotal_ttc = false;
                    $value_Total_ttc = '';
                    if ((strpos($content, $keyWord_TOTAL_TTC[0]) !== false) && (strpos($arr_output[$idx + 11 + 12], $keyWord_TOTAL_TTC[1]) !== false) && !$isTotal_ttc) {
                        $endline = $arr_output[$idx + 10];
                        $idx += 12;
                        while ($endline != "-1") {
                            $idx += 12;
                            $value_Total_ttc .= $arr_output[$idx + 11];
                            $endline = $arr_output[$idx + 10];
                        }
                        $arrRS["data"]['TOTAL TTC'] = $value_Total_ttc;
                    }
                    //end find TOTAL TTC
                }

            }
        }
        $arrRS["data"]['name'] = $define_factures['FacturesName'];
        return $arrRS;
    }
    public static function processBNP($tesseractInstance, $file_pdf, $numPage, &$define, $isFirstPage, $StringFirstPage, $isLastPage, $StringLastPage)
    {
        $num_detect = -1; //Detect row number with date
        //read first page and last page for define parameter
        $arr_csv = array();
        for ($i = 0; $i < $numPage; $i++) {
            $result = "";
            if (($isFirstPage && $i === 0) || ($i === $numPage - 1 && $isLastPage)) {
                if ($i === 0) {
                    $result = $StringFirstPage;
                } else {
                    $result = $StringLastPage;
                }

            } else {
                $im = new Imagick();
                $im->setResolution(200, 200);
                $im->readImage(realpath($file_pdf) . "[{$i}]");
                $im->setImageFormat("tiff");
                $im->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
                $im->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                $im->transformImageColorSpace(\Imagick::COLORSPACE_GRAY);
                $im->brightnessContrastImage(0, 60);
                $max = $im->getQuantumRange();
                $max = $max["quantumRangeLong"];

                $im->thresholdimage($max * 0.9);
                //$im->adaptiveThresholdImage(40, $im->getImageHeight() / 5, $max * 0.5);
                //file_put_contents("images/" . basename($file_pdf, ".pdf") . "_p{$i}.tiff", $im);
                $data = $im->getImageBlob();
                $size = $im->getImageLength();

                $tesseractInstance->imageData($data, $size);
                $result = $tesseractInstance->lang('eng', 'fra')->oem(1)->psm(4)
                    ->configFile('tsv')->run();
            }
            $arr_output = preg_split("/(\\n|\\t)/", $result);
            $isFirst = true;
            $title = array();
            $arr_rs = array();

            $title = array("level", "page_num", "block_num", "par_num", "line_num", "word_num", "left", "top", "width", "height", "conf", "text");
            $totalRow = count($arr_output) / 12;
            $startBlock = -1;
            $endBlock = -1;
            $findnewBlock = true;

            $line = -1;
            $block = -1;
            $isFirstDate = true;
            $isEndDate = false;
            $isFind = false;
            $isExpand = false;

            $tmp = array("DATE_FIRST" => "", "REFERENCE" => "", "DATE_END" => "", "DEBIT" => "", "CREDIT" => "");
            $idxEndDate = -1;

            for ($idxRow = 0; $idxRow < $totalRow; $idxRow++) {
                $idx = $idxRow * 12;
                $row = array();
                $content = "";

                if ($idx + 11 < $totalRow * 12) {
                    $content = $arr_output[$idx + 11];
                }
                if (empty($content)) //Don;t process when $content is empty
                {
                    continue;
                }

                if ($line != $arr_output[$idx + 4]) {
                    if ($isFind) {
                        $arr_csv[$num_detect] = $tmp;
                        $isFind = false;
                        $isExpand = true;
                    }
                    $line = $arr_output[$idx + 4];
                    $isFirstDate = true;
                    $isEndDate = false;
                    $tmp = array("DATE_FIRST" => "", "REFERENCE" => "", "DATE_END" => "", "DEBIT" => "", "CREDIT" => "");
                    $idxEndDate = -1;
                }
                if ((preg_match($define['ptndateComptable'], $content) || preg_match($define['ptndateValeur'], $content)) && !preg_match("/^\d+$/i", $content)) {
                    $isFind = true;
                    if ($isFirstDate) {
                        $num_detect++;
                        $tmp["DATE_FIRST"] = $content;
                        $isFirstDate = false;
                        $isExpand = false;
                    } else {
                        $idxEndDate = $idx;
                        $tmp["DATE_END"] = $content;
                        $isEndDate = true;
                    }
                } else {
                    if ($isEndDate) {
                        if ($arr_output[$idx + 6] - ($arr_output[$idxEndDate + 6] + $arr_output[$idxEndDate + 8]) > 200) {
                            $tmp["CREDIT"] .= $content . " ";
                        } else {
                            $tmp["DEBIT"] .= $content . " ";
                        }
                    } else {
                        $tmp["REFERENCE"] .= $content . " ";
                    }
                }
                if ($i == 0) {
                    if (strcmp($define['ptn_IBAN'], $content) == 0 && !$define['IBAN']) {
                        $define['IBAN'] = true;
                        $define['IBAN_Number'] .= $arr_output[($idx + 11) + 12 * 2] . " " . $arr_output[($idx + 11) + 12 * 3] . " " . $arr_output[($idx + 11) + 12 * 4] . " " . $arr_output[($idx + 11) + 12 * 5] . " " . $arr_output[($idx + 11) + 12 * 6] . " " . $arr_output[($idx + 11) + 12 * 7] . " " . $arr_output[($idx + 11) + 13 * 7];
                    }
                    $netxWod = "";
                    if ($idx + 11 + 12 < $totalRow * 12) {
                        $netxWod = $arr_output[$idx + 11 + 12];
                    }

                    if ((strpos($content, $define['ptnFname']) !== false || strpos($content, $define['ptnFname'] . " ") !== false) && (strpos($netxWod, $define['ptnLname']) !== false || strpos($netxWod, $define['ptnLname'] . " ") !== false) && !$define['namefind']) {
                        $count = 0;
                        $test1 = true;
                        $test2 = true;
                        $tmp_idx = $idx;
                        while ($test1 || $test2) {
                            if ($arr_output[$tmp_idx + 10] == -1) {
                                $count++;
                                $test1 = false;
                                if ($count > 1) {
                                    $test2 = false;
                                }
                            }
                            if (!$test1 && $test2) {

                                $define['customerName'] .= $arr_output[$tmp_idx + 11] . " ";
                            }
                            $tmp_idx = $tmp_idx + 12;
                        }
                        $define['namefind'] = true;
                    }
                }
                if ((strpos($content, $define['ptnFsoustotal']) !== false) && (strpos($arr_output[$idx + 11 + 12], $define['ptnLsoustotal']) !== false)) {
                    $isExpand = false;
                    $tmp_soustotal = array("DATE_FIRST" => "", "REFERENCE" => "", "DATE_END" => "", "DEBIT" => "", "CREDIT" => "");
                    $endline = $arr_output[$idx + 10];
                    $line_content = $content;
                    $tmp_soustotal["REFERENCE"] .= $line_content . " ";
                    while ($endline != "-1") {
                        $idx += 12;
                        $line_content = $arr_output[$idx + 11];
                        $endline = $arr_output[$idx + 10];
                        $tmp_soustotal["REFERENCE"] .= $line_content . " ";
                    }
                    $num_detect++;
                    $arr_csv[$num_detect] = $tmp_soustotal;
                    $idxRow++;
                }
                //Process TOTAL DES
                if ((strpos($content, $define['ptnFtotal']) !== false) && (strpos($arr_output[$idx + 11 + 12], $define['ptnLtotal']) !== false)) {
                    $isExpand = false;
                    $tmp_total = array("DATE_FIRST" => "", "REFERENCE" => "", "DATE_END" => "", "DEBIT" => "", "CREDIT" => "");
                    $endline = $arr_output[$idx + 10];
                    $line_content = $content;
                    $tmp_total["REFERENCE"] .= $line_content . " ";
                    while ($endline != "-1") {
                        $idx += 12;
                        $line_content = $arr_output[$idx + 11];
                        $endline = $arr_output[$idx + 10];
                        $tmp_total["REFERENCE"] .= $line_content . " ";
                    }
                    $num_detect++;
                    $array_total_des = self::getdataTotalDes($tmp_total["REFERENCE"], $define['pattern_splitTotalDes']);
                    $tmp_total["REFERENCE"] = $array_total_des["nameTotalDes"];
                    $tmp_total["DEBIT"] = $array_total_des["totalDes_debit"];
                    $tmp_total["CREDIT"] = $array_total_des["totalDes_crebit"];
                    $arr_csv[$num_detect] = $tmp_total;
                    $idxRow++;
                }
                if ($isExpand && $block === $arr_output[$idx + 2]) {
                    if ($isExpand && strpos($content, $define['ptnNextPage_first']) === false) {
                        $arr_csv[$num_detect]["REFERENCE"] .= $content . " ";
                    } else {
                        $isExpand = false;
                    }

                } else {
                    $isExpand = false;
                }

                $block = $arr_output[$idx + 2];
            }
        }
        return $arr_csv;
    }
    public static function processCIC($tesseractInstance, $file_pdf, $numPage, &$define, $isFirstPage, $StringFirstPage, $isLastPage, $StringLastPage)
    {
        $num_detect = -1; //Detect row number with date
        //read first page and last page for define parameter
        $arr_csv = array();
        $getWordBefore_name = "";
        $result = "";
        // end get customer name
        for ($i = 0; $i < $numPage; $i++) {

            if (($isFirstPage && $i === 0) || ($i === $numPage - 1 && $isLastPage)) {
                if ($i === 0) {
                    $result = $StringFirstPage;
                } else {
                    $result = $StringLastPage;
                }

            } else {
                $im = new Imagick();
                $im->setResolution(200, 200);
                $im->readImage(realpath($file_pdf) . "[{$i}]");
                $im->setImageFormat("tiff");
                $im->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
                $im->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                $im->transformImageColorSpace(\Imagick::COLORSPACE_GRAY);
                $im->brightnessContrastImage(0, 60);
                $max = $im->getQuantumRange();
                $max = $max["quantumRangeLong"];

                $im->thresholdimage($max * 0.9);
                //$im->adaptiveThresholdImage(40, $im->getImageHeight() / 5, $max * 0.5);
                //file_put_contents("images/" . basename($file_pdf, ".pdf") . "_p{$i}.tiff", $im);
                $data = $im->getImageBlob();
                $size = $im->getImageLength();

                $tesseractInstance->imageData($data, $size);
                $result = $tesseractInstance->lang('eng', 'fra')->oem(1)->psm(4)
                    ->configFile('tsv')->run();
            }
            $arr_output = preg_split("/(\\n|\\t)/", $result);
            if ($i === 0) {
                for ($j = 0; $j < count($arr_output); $j += 12) {
                    if ((strpos($arr_output[$j + 11], $define['ptnFname']) !== false || strpos($arr_output[$j + 11], $define['ptnFname'] . " ") !== false) && preg_match($define['ptnLname'], $arr_output[$j + 11 + 12])) {
                        $getWordBefore_name = trim($arr_output[$j + 11 + 12]);
                        break;
                    }
                }
            }
            $isFirst = true;

            $arr_rs = array();
            $title = array("level", "page_num", "block_num", "par_num", "line_num", "word_num", "left", "top", "width", "height", "conf", "text");
            $totalRow = count($arr_output) / 12;
            $startBlock = -1;
            $endBlock = -1;
            $findnewBlock = true;
            $line = -1;
            $block = -1;
            $isFirstDate = true;
            $isEndDate = false;
            $isFind = false;
            $isExpand = false;

            $tmp = array("DATE_FIRST" => "", "REFERENCE" => "", "DATE_END" => "", "DEBIT" => "", "CREDIT" => "");
            $idxEndDate = -1;
            $pixel_start_ref = 0;
            $pixel_end_ref = 0;

            $pixel_start_debit = 0;
            $pixel_start_credit = 0;

            for ($idxRow = 0; $idxRow < $totalRow; $idxRow++) {
                $idx = $idxRow * 12;
                $row = array();
                $content = "";
                $content_next = "";

                if ($idx + 11 < $totalRow * 12) {
                    $content = $arr_output[$idx + 11];
                }
                if ($idx + 11 + 12 < $totalRow * 12) {
                    $content_next = $arr_output[$idx + 11 + 12];
                }
                if (empty($content)) //Don;t process when $content is empty
                {
                    continue;
                }
                if (strpos($define['title_table'][0], strtolower($content)) !== false && !empty($content_next) && strpos($define['title_table'][1], strtolower($content_next)) !== false) {
                    $idx_tmp = $idx;
                    $endline = $arr_output[$idx_tmp + 10];
                    $line_content = $content;
                    while ($endline != "-1") {
                        $idx_tmp += 12;
                        $endline = $arr_output[$idx_tmp + 10];
                        $line_content = $arr_output[$idx_tmp + 11];
                        if (!empty($line_content)) {
                            //End Opération
                            if (strpos($define['title_table'][3], strtolower($line_content)) !== false && !empty($arr_output[$idx_tmp + 11 + 12]) && strpos($define['title_table'][3], strtolower($arr_output[$idx_tmp + 11 + 12])) !== false) {
                                $pixel_start_debit = (int) $arr_output[$idx_tmp - 12 + 6] + (int) $arr_output[$idx_tmp - 12 + 8];
                            } else if (strpos($define['title_table'][4], strtolower($line_content)) !== false && !empty($arr_output[$idx_tmp + 11 + 12]) && strpos($define['title_table'][4], strtolower($arr_output[$idx_tmp + 11 + 12])) !== false) {
                                //Start Credit
                                $pixel_start_credit = (int) $arr_output[$idx_tmp - 12 + 6] + (int) $arr_output[$idx_tmp - 12 + 8];
                            }
                        }
                    }
                }
                if (preg_match($define['ptndateComptable'], $content)) {
                    $tmp = array("DATE_FIRST" => "", "REFERENCE" => "", "DATE_END" => "", "DEBIT" => "", "CREDIT" => "");
                    $tmp["DATE_FIRST"] = $content;
                    $pixel_start_ref = (int) $arr_output[$idx + 6];
                    $endline = $arr_output[$idx + 10];
                    $line_content = "";
                    $isSkip = false;
                    //$ptnCurency = "/^(\d{1,3}\.)*\d{1,3},\d{2}$/i";
                    while ($endline != "-1") {
                        $idxRow++;
                        $idx += 12;
                        $line_content = $arr_output[$idx + 11];
                        $endline = $arr_output[$idx + 10];
                        if (preg_match($define['ptndateValeur'], $line_content)) {
                            $tmp["DATE_END"] = $line_content;
                        } else {
                            if (preg_match($define['ptn_currency'], $line_content)) {
                                //check in range Debit
                                if ((int) $arr_output[$idx + 6] > $pixel_start_debit && (int) $arr_output[$idx + 6] < $pixel_start_credit) {
                                    $tmp["DEBIT"] = $line_content;
                                } else {
                                    $tmp["CREDIT"] = $line_content;
                                }
                                $pixel_end_ref = (int) $arr_output[$idx - 12 + 6] + (int) $arr_output[$idx - 12 + 8];
                                $isSkip = true;
                            } else if (!$isSkip) {
                                $tmp["REFERENCE"] .= $line_content . " ";
                            }
                        }
                    }
                    if ($tmp["DATE_END"] !== "") {
                        $num_detect++;
                        $arr_csv[$num_detect] = $tmp;
                        $isExpand = true;
                        continue;
                    }
                }

                if (!$define['IBAN'] && strcmp($define['ptn_IBAN'], $content) == 0) {
                    $define['IBAN'] = true;
                    $define['IBAN_Number'] .= $arr_output[($idx + 11) + 12 * 2] . " " . $arr_output[($idx + 11) + 12 * 3] . " " . $arr_output[($idx + 11) + 12 * 4] . " " . $arr_output[($idx + 11) + 12 * 5] . " " . $arr_output[($idx + 11) + 12 * 6] . " " . $arr_output[($idx + 11) + 12 * 7] . " " . $arr_output[($idx + 11) + 13 * 7];
                }

                if ($i == 0) {
                    if ($getWordBefore_name !== "" && ($getWordBefore_name === $content || $getWordBefore_name === $content . " ") && !$define['namefind']) {
                        $count = 0;
                        $test1 = true;
                        $test2 = true;
                        $tmp_idx = $idx + 11 + 12;
                        while ($test1) {
                            while ($arr_output[$tmp_idx - 7] == 1) {
                                $define['customerName'] .= $arr_output[$tmp_idx] . " ";
                                $tmp_idx = $tmp_idx + 12;
                                $test1 = false;
                            }
                            $tmp_idx = $tmp_idx + 12;
                        }
                        $define['customerName'] = trim($define['customerName']);
                        $define['namefind'] = true;
                    }
                }
                if ($define['ptnFsoustotal'] != "" && $define['ptnLsoustotal'] != "" && (strpos($content, $define['ptnFsoustotal']) !== false) && (strpos($content_next, $define['ptnLsoustotal']) !== false)) {
                    $isExpand = false;
                    $tmp_soustotal = array("DATE_FIRST" => "", "REFERENCE" => "", "DATE_END" => "", "DEBIT" => "", "CREDIT" => "");
                    $endline = $arr_output[$idx + 10];
                    $line_content = $content;
                    $tmp_soustotal["REFERENCE"] .= $line_content . " ";
                    while ($endline != "-1") {
                        $idxRow++;
                        $idx += 12;
                        $line_content = $arr_output[$idx + 11];
                        $endline = $arr_output[$idx + 10];
                        $tmp_soustotal["REFERENCE"] .= $line_content . " ";
                    }
                    $num_detect++;
                    $arr_csv[$num_detect] = $tmp_soustotal;
                }
                //Process Total Des
                if ((strpos($content, $define['ptnFtotal']) !== false) && (strpos($arr_output[$idx + 11 + 12], $define['ptnLtotal']) !== false)) {
                    $isExpand = false;
                    $tmp_total = array("DATE_FIRST" => "", "REFERENCE" => "", "DATE_END" => "", "DEBIT" => "", "CREDIT" => "");
                    $endline = $arr_output[$idx + 10];
                    $line_content = $content;
                    $tmp_total["REFERENCE"] .= $line_content . " ";
                    while ($endline != "-1") {
                        $idxRow++;
                        $idx += 12;
                        $line_content = $arr_output[$idx + 11];
                        $endline = $arr_output[$idx + 10];
                        $tmp_total["REFERENCE"] .= $line_content . " ";
                    }
                    $num_detect++;
                    $array_total_des = self::getdataTotalDes($tmp_total["REFERENCE"], $define['pattern_splitTotalDes']);
                    $tmp_total["REFERENCE"] = $array_total_des["nameTotalDes"];
                    $tmp_total["DEBIT"] = $array_total_des["totalDes_debit"];
                    $tmp_total["CREDIT"] = $array_total_des["totalDes_crebit"];
                    $arr_csv[$num_detect] = $tmp_total;
                }
                if ($isExpand && strpos($content, $define['ptnNextPage_first']) === false) {
                    if ((int) $arr_output[$idx + 6] > $pixel_start_ref && (int) $arr_output[$idx + 6] < $pixel_end_ref) {
                        $arr_csv[$num_detect]["REFERENCE"] .= $content . " ";
                    }
                } else {
                    $pixel_start_ref = 0;
                    $pixel_end_ref = 0;
                    $isExpand = false;
                }
            }
        }
        return $arr_csv;
    }
    public static function processLCL($tesseractInstance, $file_pdf, $numPage, &$define, $isFirstPage, $StringFirstPage, $isLastPage, $StringLastPage)
    {
        $num_detect = -1; //Detect row number with date
        //read first page and last page for define parameter
        $arr_csv = array();
        for ($i = 0; $i < $numPage; $i++) {
            $result = "";

            if (($isFirstPage && $i === 0) || ($i === $numPage - 1 && $isLastPage)) {
                if ($i === 0) {
                    $result = $StringFirstPage;
                } else {
                    $result = $StringLastPage;
                }

            } else {
                $im = new Imagick();
                $im->setResolution(200, 200);
                $im->readImage(realpath($file_pdf) . "[{$i}]");
                $im->setImageFormat("tiff");
                $im->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
                $im->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                $im->transformImageColorSpace(\Imagick::COLORSPACE_GRAY);
                $im->brightnessContrastImage(0, 60);
                $max = $im->getQuantumRange();
                $max = $max["quantumRangeLong"];

                $im->thresholdimage($max * 0.9);
                //$im->adaptiveThresholdImage(40, $im->getImageHeight() / 5, $max * 0.5);
                //file_put_contents("images/" . basename($file_pdf, ".pdf") . "_p{$i}.tiff", $im);
                $data = $im->getImageBlob();
                $size = $im->getImageLength();

                $tesseractInstance->imageData($data, $size);
                $result = $tesseractInstance->lang('eng', 'fra')->oem(1)->psm(4)
                    ->configFile('tsv')->run();
            }
            // echo "<pre>{$result}</pre>";
            // continue;
            $arr_output = preg_split("/(\\n|\\t)/", $result);
            $isFirst = true;
            $title = array();
            $arr_rs = array();

            $title = array("level", "page_num", "block_num", "par_num", "line_num", "word_num", "left", "top", "width", "height", "conf", "text");
            $totalRow = count($arr_output) / 12;
            $startBlock = -1;
            $endBlock = -1;
            $findnewBlock = true;

            $line = -1;
            $block = -1;

            $isFoundTitleTable = false;
            $isFind = false;
            $isExpand = false;

            $tmp = array("DATE_FIRST" => "", "REFERENCE" => "", "DATE_END" => "", "DEBIT" => "", "CREDIT" => "");
            $idxEndDate = -1;

            for ($idxRow = 0; $idxRow < $totalRow; $idxRow++) {
                $idx = $idxRow * 12;
                $row = array();
                $content = "";

                if ($idx + 11 < $totalRow * 12) {
                    $content = $arr_output[$idx + 11];
                }
                if ($idx + 11 + 12 < $totalRow * 12) {
                    $content_next = $arr_output[$idx + 11 + 12];
                }
                if (empty($content)) //Don;t process when $content is empty
                {
                    continue;
                }
                if (!$isFoundTitleTable && strpos($define['title_table'][0], $content) !== false && !empty($content_next) && strpos($define['title_table'][1], $content_next) !== false) {
                    $idx_tmp = $idx;
                    $endline = $arr_output[$idx_tmp + 10];
                    $line_content = $content;
                    while ($endline != "-1") {
                        $idx_tmp += 12;
                        $endline = $arr_output[$idx_tmp + 10];
                        $line_content = $arr_output[$idx_tmp + 11];
                        if (!empty($line_content)) {
                            //End VALEUR
                            if (strpos($define['title_table'][3], $line_content) !== false) {
                                $pixel_start_debit = (int) $arr_output[$idx_tmp - 12 + 6] + (int) $arr_output[$idx_tmp - 12 + 8];
                            } else if (strpos($define['title_table'][4], $line_content) !== false) {
                                //Start Credit
                                $pixel_start_credit = (int) $arr_output[$idx_tmp + 6];
                            }
                        }
                    }
                    $isFoundTitleTable = true;
                }

                if ($isFoundTitleTable && preg_match($define['ptndateComptable'], $content)) {
                    $tmp = array("DATE_FIRST" => "", "REFERENCE" => "", "DATE_END" => "", "DEBIT" => "", "CREDIT" => "");
                    $tmp["DATE_FIRST"] = $content;
                    $pixel_start_ref = (int) $arr_output[$idx + 6];
                    $endline = $arr_output[$idx + 10];
                    $line_content = "";

                    while ($endline != "-1") {
                        $idxRow++;
                        $idx += 12;
                        $line_content = $arr_output[$idx + 11];
                        $endline = $arr_output[$idx + 10];
                        if (preg_match($define['ptndateValeur'], $line_content)) {
                            $tmp["DATE_END"] = $line_content;
                        } else {
                            $position_start = (int) $arr_output[$idx + 6];
                            $position_end = (int) $arr_output[$idx + 6] + (int) $arr_output[$idx + 8];
                            if ($position_start < $pixel_start_debit) {
                                $tmp["REFERENCE"] .= $line_content . " ";
                            } else if ($position_start < $pixel_start_credit && $position_end < $pixel_start_credit) {
                                $tmp["DEBIT"] .= $line_content . " ";
                            } else {
                                $tmp["CREDIT"] .= $line_content . " ";
                            }
                        }
                    }
                    $num_detect++;
                    $arr_csv[$num_detect] = $tmp;
                    if ($tmp["DATE_END"] !== "") {
                        $isExpand = true;
                        continue;
                    }
                }
                if ($i == 0) {
                    if (strcmp(trim($define['ptn_IBAN']), trim($content)) == 0 && !$define['IBAN']) {
                        $define['IBAN'] = true;
                        $define['IBAN_Number'] .= $arr_output[($idx + 11) + 12 * 2] . " " . $arr_output[($idx + 11) + 12 * 3] . " " . $arr_output[($idx + 11) + 12 * 4] . " " . $arr_output[($idx + 11) + 12 * 5] . " " . $arr_output[($idx + 11) + 12 * 6] . " " . $arr_output[($idx + 11) + 12 * 7] . " " . $arr_output[($idx + 11) + 12 * 8];
                    }
                    $netxWod = "";
                    if ($idx + 11 + 12 < $totalRow * 12) {
                        $netxWod = $arr_output[$idx + 11 + 12];
                    }
                    //var_dump($netxWod);
                    if (strpos(strtolower($content), trim($define['ptnFname'])) !== false && strpos(strtolower($netxWod), trim($define['ptnLname'])) !== false && !$define['namefind']) {
                        $count = 0;
                        $test1 = true;
                        $test2 = true;
                        $tmp_idx = $idx;
                        while ($test1 || $test2) {
                            //var_dump($arr_output[$tmp_idx + 10]);
                            if ($arr_output[$tmp_idx + 10] == "-1") {
                                $count++;
                                $test1 = false;
                                if ($count > 1) {
                                    $test2 = false;
                                }
                            }
                            if (!$test1 && $test2) {
                                $define['customerName'] .= $arr_output[$tmp_idx + 11] . " ";
                            }
                            $tmp_idx = $tmp_idx + 12;
                        }
                        $define['namefind'] = true;
                    }
                }
                //process Sous total
                if ((strpos($content, $define['ptnFsoustotal']) !== false) && (strpos($arr_output[$idx + 11 + 12], $define['ptnLsoustotal']) !== false)) {
                    $isExpand = false;
                    $tmp_soustotal = array("DATE_FIRST" => "", "REFERENCE" => "", "DATE_END" => "", "DEBIT" => "", "CREDIT" => "");
                    $endline = $arr_output[$idx + 10];
                    $line_content = $content;
                    $tmp_soustotal["REFERENCE"] .= $line_content . " ";
                    while ($endline != "-1") {
                        $idx += 12;
                        $line_content = $arr_output[$idx + 11];
                        $endline = $arr_output[$idx + 10];
                        $tmp_soustotal["REFERENCE"] .= $line_content . " ";
                    }
                    $num_detect++;
                    $arr_csv[$num_detect] = $tmp_soustotal;
                    $idxRow++;
                }
                //Process TOTAUX
                if (strpos($content, $define['ptnFtotal']) !== false) {
                    $isExpand = false;
                    $tmp_total = array("DATE_FIRST" => "", "REFERENCE" => "", "DATE_END" => "", "DEBIT" => "", "CREDIT" => "");
                    $endline = $arr_output[$idx + 10];
                    $line_content = $content;
                    $tmp_total["REFERENCE"] .= $line_content . " ";
                    while ($endline != "-1") {
                        $idx += 12;
                        $line_content = $arr_output[$idx + 11];
                        $endline = $arr_output[$idx + 10];
                        $isFoundDebit = false;
                        $tmp_total["REFERENCE"] .= $line_content . " ";
                    }
                    $num_detect++;
                    $matches = array();
                    preg_match_all($define['pattern_splitTotalDes'], $tmp_total["REFERENCE"], $matches);
                    $tmp_total["REFERENCE"] = $matches[1][0];
                    $tmp_total["DEBIT"] = $matches[3][0];
                    $tmp_total["CREDIT"] = $matches[3][1];
                    $arr_csv[$num_detect] = $tmp_total;
                    $idxRow++;
                }
                if ($isExpand && $block === $arr_output[$idx + 2]) {
                    if ($isExpand && strpos($content, $define['ptnNextPage_first']) === false) {
                        $arr_csv[$num_detect]["REFERENCE"] .= $content . " ";
                    } else {
                        $isExpand = false;
                    }

                } else {
                    $isExpand = false;
                }

                $block = $arr_output[$idx + 2];
            }
        }
        return $arr_csv;
    }
    public static function processSG($tesseractInstance, $file_pdf, $numPage, &$define, $isFirstPage, $StringFirstPage, $isLastPage, $StringLastPage)
    {
        $num_detect = -1; //Detect row number with date
        //read first page and last page for define parameter
        $arr_csv = array();
        $getWordBefore_name = "";
        $result = "";
        // end get customer name
        for ($i = 0; $i < $numPage; $i++) {

            if (($isFirstPage && $i === 0) || ($i === $numPage - 1 && $isLastPage)) {
                if ($i === 0) {
                    $result = $StringFirstPage;
                } else {
                    $result = $StringLastPage;
                }

            } else {
                $im = new Imagick();
                $im->setResolution(200, 200);
                $im->readImage(realpath($file_pdf) . "[{$i}]");
                $im->setImageFormat("tiff");
                $im->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
                $im->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                $im->transformImageColorSpace(\Imagick::COLORSPACE_GRAY);
                $im->brightnessContrastImage(0, 60);
                $max = $im->getQuantumRange();
                $max = $max["quantumRangeLong"];

                $im->thresholdimage($max * 0.9);
                //$im->adaptiveThresholdImage(40, $im->getImageHeight() / 5, $max * 0.5);
                //file_put_contents("images/" . basename($file_pdf, ".pdf") . "_p{$i}.tiff", $im);
                $data = $im->getImageBlob();
                $size = $im->getImageLength();

                $tesseractInstance->imageData($data, $size);
                $result = $tesseractInstance->lang('eng', 'fra')->oem(1)->psm(4)
                    ->configFile('tsv')->run();
            }

            $arr_output = preg_split("/(\\n|\\t)/", $result);
            $totalRow = count($arr_output) / 12;
            $isFirst = true;
            $arr_rs = array();
            $title = array("level", "page_num", "block_num", "par_num", "line_num", "word_num", "left", "top", "width", "height", "conf", "text");
            $startBlock = -1;
            $endBlock = -1;
            $findnewBlock = true;
            $line = -1;
            $block = -1;
            $isFirstDate = true;
            $isEndDate = false;
            $isFind = false;
            $isExpand = false;
            $isFoundTable = false;

            $tmp = array("DATE_FIRST" => "", "REFERENCE" => "", "DATE_END" => "", "DEBIT" => "", "CREDIT" => "");
            $idxEndDate = -1;
            $pixel_start_ref = 0;
            $pixel_end_ref = 0;

            $pixel_start_debit = 0;
            $pixel_start_credit = 0;

            for ($idxRow = 0; $idxRow < $totalRow; $idxRow++) {
                $idx = $idxRow * 12;
                $row = array();
                $content = "";
                $content_next = "";

                if ($idx + 11 < $totalRow * 12) {
                    $content = $arr_output[$idx + 11];
                }
                if ($idx + 11 + 12 < $totalRow * 12) {
                    $content_next = $arr_output[$idx + 11 + 12];
                }
                if (empty($content)) //Don;t process when $content is empty
                {
                    continue;
                }
                if (strpos($define['title_table'][0], strtolower($content)) !== false && !empty($content_next) && strpos($define['title_table'][1], strtolower($content_next)) !== false) {
                    $isFoundTable = true;
                    $endline = $arr_output[$idx + 10];
                    $line_content = $content;
                    while ($endline != "-1") {
                        $idx += 12;
                        $endline = $arr_output[$idx + 10];
                        $line_content = $arr_output[$idx + 11];
                        if (!empty($line_content)) {
                            //End Opération
                            if (strpos($define['title_table'][3], strtolower($line_content)) !== false) {
                                $pixel_start_debit = (int) $arr_output[$idx - 12 + 6] + (int) $arr_output[$idx - 12 + 8];
                            } else if (strpos($define['title_table'][4], strtolower($line_content)) !== false) {
                                //Start Credit
                                $pixel_start_credit = (int) $arr_output[$idx + 6];
                            }
                        }
                    }
                }
                if ($isFoundTable && preg_match($define['ptndateComptable'], $content)) {
                    $tmp = array("DATE_FIRST" => "", "REFERENCE" => "", "DATE_END" => "", "DEBIT" => "", "CREDIT" => "");
                    $tmp["DATE_FIRST"] = $content;
                    $pixel_start_ref = (int) $arr_output[$idx + 6];
                    $endline = $arr_output[$idx + 10];
                    $line_content = "";
                    $isSkip = false;
                    while ($endline != "-1") {
                        $idxRow++;
                        $idx += 12;
                        $line_content = $arr_output[$idx + 11];
                        $endline = $arr_output[$idx + 10];
                        if (preg_match($define['ptndateValeur'], $line_content)) {
                            $tmp["DATE_END"] = $line_content;
                        } else {
                            if (preg_match($define['ptn_currency'], $line_content)) {
                                //check in range Debit
                                if ((int) $arr_output[$idx + 6] > $pixel_start_debit && (int) $arr_output[$idx + 6] < $pixel_start_credit) {
                                    $tmp["DEBIT"] = $line_content;
                                } else {
                                    $tmp["CREDIT"] = $line_content;
                                }
                                $pixel_end_ref = (int) $arr_output[$idx - 12 + 6] + (int) $arr_output[$idx - 12 + 8];
                                $isSkip = true;
                            } else if (!$isSkip) {
                                $tmp["REFERENCE"] .= $line_content . " ";
                            }
                        }
                    }
                    if ($tmp["DATE_END"] !== "") {
                        $num_detect++;
                        $arr_csv[$num_detect] = $tmp;
                        $isExpand = true;
                        continue;
                    }
                }
                if (!$define['IBAN'] && strcmp($define['ptn_IBAN'], $content) == 0) {
                    $define['IBAN'] = true;
                    $define['IBAN_Number'] .= $arr_output[($idx + 11) + 12 * 1] . " " . $arr_output[($idx + 11) + 12 * 2] . " " . $arr_output[($idx + 11) + 12 * 3] . " " . $arr_output[($idx + 11) + 12 * 4] . " " . $arr_output[($idx + 11) + 12 * 5] . " " . $arr_output[($idx + 11) + 12 * 6] . " " . $arr_output[($idx + 11) + 12 * 7];
                }
                if ($i === $numPage - 1) {
                    if (strpos($define['ptnFname'], strtolower($content)) !== false && strpos($define['ptnLname'], strtolower($content_next)) !== false && !$define['namefind']) {
                        $tmp_idx = $idx + 12 + 12;
                        while ($arr_output[$tmp_idx + 10] !== "-1") {
                            $define['customerName'] .= $arr_output[$tmp_idx + 11] . " ";
                            $tmp_idx = $tmp_idx + 12;
                        }
                        $define['customerName'] = trim($define['customerName']);
                        $define['namefind'] = true;
                    }
                }
                if ($define['ptnFsoustotal'] != "" && $define['ptnLsoustotal'] != "" && (strpos($content, $define['ptnFsoustotal']) !== false) && (strpos($content_next, $define['ptnLsoustotal']) !== false)) {
                    $isExpand = false;
                    $tmp_soustotal = array("DATE_FIRST" => "", "REFERENCE" => "", "DATE_END" => "", "DEBIT" => "", "CREDIT" => "");
                    $endline = $arr_output[$idx + 10];
                    $line_content = $content;
                    $tmp_soustotal["REFERENCE"] .= $line_content . " ";
                    while ($endline != "-1") {
                        $idxRow++;
                        $idx += 12;
                        $line_content = $arr_output[$idx + 11];
                        $endline = $arr_output[$idx + 10];
                        $tmp_soustotal["REFERENCE"] .= $line_content . " ";
                    }
                    $num_detect++;
                    $arr_csv[$num_detect] = $tmp_soustotal;
                }
                //Process Total Des
                if ((strpos($content, $define['ptnFtotal']) !== false) && (strpos($arr_output[$idx + 11 + 12], $define['ptnLtotal']) !== false)) {
                    $isExpand = false;
                    $tmp_total = array("DATE_FIRST" => "", "REFERENCE" => "", "DATE_END" => "", "DEBIT" => "", "CREDIT" => "");
                    $endline = $arr_output[$idx + 10];
                    $line_content = $content;
                    $tmp_total["REFERENCE"] .= $line_content . " ";
                    while ($endline != "-1") {
                        $idxRow++;
                        $idx += 12;
                        $line_content = $arr_output[$idx + 11];
                        $endline = $arr_output[$idx + 10];
                        $tmp_total["REFERENCE"] .= $line_content . " ";
                    }
                    $num_detect++;
                    $array_total_des = self::getdataTotalDes($tmp_total["REFERENCE"], $define['pattern_splitTotalDes']);
                    $tmp_total["REFERENCE"] = $array_total_des["nameTotalDes"];
                    $tmp_total["DEBIT"] = $array_total_des["totalDes_debit"];
                    $tmp_total["CREDIT"] = $array_total_des["totalDes_crebit"];
                    $arr_csv[$num_detect] = $tmp_total;
                }
                if ($isExpand && strpos($content, $define['ptnNextPage_first']) === false) {
                    if ((int) $arr_output[$idx + 6] > $pixel_start_ref && (int) $arr_output[$idx + 6] < $pixel_end_ref) {
                        $arr_csv[$num_detect]["REFERENCE"] .= $content . " ";
                    }
                } else {
                    $pixel_start_ref = 0;
                    $pixel_end_ref = 0;
                    $isExpand = false;
                }
            }
        }
        return $arr_csv;
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
    public static function processBRED($tesseractInstance, $file_pdf, $numPage, &$define, $isFirstPage, $StringFirstPage, $isLastPage, $StringLastPage)
    {
        $num_detect = -1; //Detect row number with date
        //read first page and last page for define parameter
        $arr_csv = array();
        $getWordBefore_name = "";
        $result = "";
        // end get customer name
        for ($i = 0; $i < $numPage; $i++) {

            if (($isFirstPage && $i === 0) || ($i === $numPage - 1 && $isLastPage)) {
                if ($i === 0) {
                    $result = $StringFirstPage;
                } else {
                    $result = $StringLastPage;
                }

            } else {
                $im = new Imagick();
                $im->setResolution(200, 200);
                $im->readImage(realpath($file_pdf) . "[{$i}]");
                $im->setImageFormat("tiff");
                $im->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
                $im->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                $im->transformImageColorSpace(\Imagick::COLORSPACE_GRAY);
                $im->brightnessContrastImage(0, 50);

                // $max = $im->getQuantumRange();
                // $max = $max["quantumRangeLong"];

                // $im->thresholdimage($max * 0.8);
                // $im->adaptiveThresholdImage(40, $im->getImageHeight() / 5, $max * 0.5);
                // file_put_contents("images/" . basename($file_pdf, ".pdf") . "_p{$i}.tiff", $im);
                $data = $im->getImageBlob();
                $size = $im->getImageLength();

                $tesseractInstance->imageData($data, $size);
                $result = $tesseractInstance->lang('eng', 'fra')->oem(1)->psm(4)
                    ->configFile('tsv')->run();
                // echo "<pre>{$result}</pre>";
                // continue;
            }
            $arr_output = preg_split("/(\\n|\\t)/", $result);
            $isFirst = true;

            $arr_rs = array();
            $title = array("level", "page_num", "block_num", "par_num", "line_num", "word_num", "left", "top", "width", "height", "conf", "text");
            $totalRow = count($arr_output) / 12;
            $startBlock = -1;
            $endBlock = -1;
            $findnewBlock = true;
            $line = -1;
            $block = -1;
            $isFirstDate = true;
            $isEndDate = false;
            $isFind = false;
            $isExpand = false;

            $tmp = array("DATE_FIRST" => "", "REFERENCE" => "", "DATE_END" => "", "DEBIT" => "", "CREDIT" => "");
            $idxEndDate = -1;
            $pixel_start_ref = 0;
            $pixel_end_ref = 0;

            $pixel_start_debit = 0;
            $pixel_start_credit = 0;
            $isFoundTitleTable = false;
            for ($idxRow = 0; $idxRow < $totalRow; $idxRow++) {
                $idx = $idxRow * 12;
                $row = array();
                $content = "";
                $content_next = "";

                if ($idx + 11 < $totalRow * 12) {
                    $content = trim($arr_output[$idx + 11]);
                }
                if ($idx + 11 + 12 < $totalRow * 12) {
                    $content_next = trim($arr_output[$idx + 11 + 12]);
                }
                if (empty($content)) //Don;t process when $content is empty
                {
                    continue;
                }
                if (strpos($define['title_table'][0], strtolower($content)) !== false && !empty($content_next) && strpos($define['title_table'][1], strtolower($content_next)) !== false) {
                    $isFoundTitleTable = true;
                    $idx_tmp = $idx;
                    $endline = $arr_output[$idx_tmp + 10];
                    $line_content = $content;
                    while ($endline != "-1") {
                        $idx_tmp += 12;
                        $endline = $arr_output[$idx_tmp + 10];
                        $line_content = $arr_output[$idx_tmp + 11];
                        if (!empty($line_content)) {
                            //End Opération
                            if (strpos($define['title_table'][2], strtolower($line_content)) !== false) {
                                $pixel_start_debit = (int) $arr_output[$idx_tmp - 12 + 6] + (int) $arr_output[$idx_tmp - 12 + 8];
                            } else if (strpos($define['title_table'][3], strtolower($line_content)) !== false) {
                                //Start Credit
                                $pixel_start_credit = (int) $arr_output[$idx_tmp + 6];
                            }
                        }
                    }
                }
                if (preg_match($define['ptndateComptable'], $content) && $isFoundTitleTable) {
                    $tmp = array("DATE_FIRST" => "", "REFERENCE" => "", "DATE_END" => "", "DEBIT" => "", "CREDIT" => "");
                    $tmp["DATE_FIRST"] = $content;
                    $pixel_start_ref = (int) $arr_output[$idx + 6];
                    $endline = $arr_output[$idx + 10];
                    $line_content = "";
                    $isSkip = false;
                    //$ptnCurency = "/^(\d{1,3}\.)*\d{1,3},\d{2}$/i";
                    while ($endline != "-1") {
                        $idxRow++;
                        $idx += 12;
                        $line_content = $arr_output[$idx + 11];
                        $endline = $arr_output[$idx + 10];
                        if (preg_match($define['ptndateValeur'], $line_content)) {
                            $tmp["DATE_END"] = $line_content;
                        } else {
                            if (preg_match($define['ptn_currency'], trim(str_replace("|", "", $line_content)))) {
                                //check in range Debit
                                if ((int) $arr_output[$idx + 6] > $pixel_start_debit && (int) $arr_output[$idx + 6] + (int) $arr_output[$idx + 8] < $pixel_start_credit) {
                                    $tmp["DEBIT"] = trim(str_replace("|", "", $line_content));
                                } else {
                                    $tmp["CREDIT"] = trim(str_replace("|", "", $line_content));
                                }
                                $pixel_end_ref = (int) $arr_output[$idx - 12 + 6] + (int) $arr_output[$idx - 12 + 8];
                            } else {
                                $tmp["REFERENCE"] .= $line_content . " ";
                            }
                        }
                    }
                    $num_detect++;
                    $arr_csv[$num_detect] = $tmp;
                    if ($tmp["DATE_END"] === "") {
                        $isExpand = false;
                    }
                    $idxRow++;
                    $idx += 12;
                    $line_content = trim($arr_output[$idx + 11]);
                    while ($line_content === ""
                        || trim($arr_output[$idx + 12 + 11]) === ""
                        || (!preg_match($define['ptndateComptable'], $line_content)
                            && $idx + 12 < $totalRow * 12 && $idx + 12 + 11 < $totalRow * 12
                            && strpos($define["ptnNextPage_first"], $line_content) === false
                            && strpos($define["ptnNextPage_first"], trim($arr_output[$idx + 12 + 11])) === false
                            && strpos($content, $define['ptnFtotal']) === false
                            && strpos($arr_output[$idx + 11 + 12], $define['ptnLtotal']) === false)) {
                        if ($line_content !== "") {
                            $arr_csv[$num_detect]["REFERENCE"] .= $line_content . " ";
                        }
                        $idxRow++;
                        $idx += 12;
                        $line_content = trim($arr_output[$idx + 11]);
                    }
                    $idxRow--;
                    $idx -= 12;
                }

                if (!$define['IBAN'] && strcmp($define['ptn_IBAN'], $content) == 0) {
                    $define['IBAN'] = true;
                    $idx += 12;
                    $define['IBAN_Number'] = $arr_output[($idx + 11) + 12 * 2] .
                        " " . $arr_output[($idx + 11) + 12 * 3] .
                        " " . $arr_output[($idx + 11) + 12 * 4] .
                        " " . $arr_output[($idx + 11) + 12 * 5] .
                        " " . $arr_output[($idx + 11) + 12 * 6] .
                        " " . $arr_output[($idx + 11) + 12 * 7] .
                        " " . $arr_output[($idx + 11) + 12 * 8];
                }

                if ($i == 0) {
                    if (preg_match("/" . $define["ptnFname"] . "/", trim($content))
                        && preg_match("/" . $define["ptnLname"] . "/", trim($content_next))
                        && !$define['namefind']) {
                        $endLine = $arr_output[$idx + 10];
                        $left = intval($arr_output[$idx + 6]);
                        while ($endLine !== "-1") {
                            $idxRow++;
                            $idx += 12;
                            $endLine = $arr_output[$idx + 10];
                            $content = $arr_output[$idx + 11];
                            if (intval($arr_output[$idx + 6]) - $left > 0.1 * self::$width) {
                                $define['customerName'] .= $content . " ";
                            } else {
                                $left = intval($arr_output[$idx + 6]);
                            }
                        }
                        $define['namefind'] = true;
                    }
                }
                // if ($define['ptnFsoustotal'] != "" && $define['ptnLsoustotal'] != "" && (strpos($content, $define['ptnFsoustotal']) !== false) && (strpos($content_next, $define['ptnLsoustotal']) !== false)) {
                //     $isExpand = false;
                //     $tmp_soustotal = array("DATE_FIRST" => "", "REFERENCE" => "", "DATE_END" => "", "DEBIT" => "", "CREDIT" => "");
                //     $endline = $arr_output[$idx + 10];
                //     $line_content = $content;
                //     $tmp_soustotal["REFERENCE"] .= $line_content . " ";
                //     while ($endline != "-1") {
                //         $idxRow++;
                //         $idx += 12;
                //         $line_content = $arr_output[$idx + 11];
                //         $endline = $arr_output[$idx + 10];
                //         $tmp_soustotal["REFERENCE"] .= $line_content . " ";
                //     }
                //     $num_detect++;
                //     $arr_csv[$num_detect] = $tmp_soustotal;
                // }
                //Process Total Des
                if ((strpos($content, $define['ptnFtotal']) !== false) && (strpos($arr_output[$idx + 11 + 12], $define['ptnLtotal']) !== false)) {
                    $isExpand = false;
                    $tmp_total = array("DATE_FIRST" => "", "REFERENCE" => "", "DATE_END" => "", "DEBIT" => "", "CREDIT" => "");
                    $endline = $arr_output[$idx + 10];
                    $line_content = $content;
                    $tmp_total["REFERENCE"] .= $line_content . " ";
                    while ($endline != "-1") {
                        $idxRow++;
                        $idx += 12;
                        $line_content = $arr_output[$idx + 11];
                        $endline = $arr_output[$idx + 10];
                        if (preg_match($define['ptn_currency'], trim(str_replace("|", "", $line_content)))) {
                            //check in range Debit
                            if ((int) $arr_output[$idx + 6] > $pixel_start_debit && (int) $arr_output[$idx + 6] < $pixel_start_credit) {
                                $tmp_total["DEBIT"] = trim(str_replace("|", "", $line_content));
                            } else {
                                $tmp_total["CREDIT"] = trim(str_replace("|", "", $line_content));
                            }
                        } else {
                            $tmp_total["REFERENCE"] .= $line_content . " ";
                        }
                    }
                    $num_detect++;
                    $arr_csv[$num_detect] = $tmp_total;
                }
            }
        }
        return $arr_csv;
    }
    public static function processCAISSE($tesseractInstance, $file_pdf, $numPage, &$define, $isFirstPage, $StringFirstPage, $isLastPage, $StringLastPage)
    {
        $num_detect = -1; //Detect row number with date
        //read first page and last page for define parameter
        $arr_csv = array();
        $getWordBefore_name = "";
        $result = "";
        // end get customer name
        for ($i = 0; $i < $numPage; $i++) {

            if (($isFirstPage && $i === 0) || ($i === $numPage - 1 && $isLastPage)) {
                if ($i === 0) {
                    $result = $StringFirstPage;
                } else {
                    $result = $StringLastPage;
                }

            } else {
                $im = new Imagick();
                $im->setResolution(200, 200);
                $im->readImage(realpath($file_pdf) . "[{$i}]");
                $im->setImageFormat("tiff");
                $im->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
                $im->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                $im->transformImageColorSpace(\Imagick::COLORSPACE_GRAY);
                $im->brightnessContrastImage(0, 60);
                $max = $im->getQuantumRange();
                $max = $max["quantumRangeLong"];

                $im->thresholdimage($max * 0.9);
                //$im->adaptiveThresholdImage(40, $im->getImageHeight() / 5, $max * 0.5);
                //file_put_contents("images/" . basename($file_pdf, ".pdf") . "_p{$i}.tiff", $im);
                $data = $im->getImageBlob();
                $size = $im->getImageLength();

                $tesseractInstance->imageData($data, $size);
                $result = $tesseractInstance->lang('eng', 'fra')->oem(1)->psm(4)
                    ->configFile('tsv')->run();
            }
            //echo "<pre>{$result}</pre>";
            //die();
            $arr_output = preg_split("/(\\n|\\t)/", $result);
            $isFirst = true;

            $arr_rs = array();
            $title = array("level", "page_num", "block_num", "par_num", "line_num", "word_num", "left", "top", "width", "height", "conf", "text");
            $totalRow = count($arr_output) / 12;
            $startBlock = -1;
            $endBlock = -1;
            $findnewBlock = true;
            $line = -1;
            $block = -1;
            $isFirstDate = true;
            $isEndDate = false;
            $isFind = false;
            $isExpand = false;

            $tmp = array("DATE_FIRST" => "", "REFERENCE" => "", "DATE_END" => "", "DEBIT" => "", "CREDIT" => "");
            $idxEndDate = -1;

            $pixel_end_debit = 0;
            $pixel_start_ref = 0;
            $pixel_end_ref = 0;
            $pixel_start_credit = 0;

            for ($idxRow = 0; $idxRow < $totalRow; $idxRow++) {
                $idx = $idxRow * 12;
                $row = array();
                $content = "";
                $content_next = "";

                if ($idx + 11 < $totalRow * 12) {
                    $content = $arr_output[$idx + 11];
                }
                if ($idx + 11 + 12 < $totalRow * 12) {
                    $content_next = $arr_output[$idx + 11 + 12];
                }
                if (empty($content)) //Don;t process when $content is empty
                {
                    continue;
                }
                if (preg_match("/{$define['title_table'][0]}/i", strtolower($content)) && !empty($content_next) && preg_match("/{$define['title_table'][1]}/i", strtolower($content_next))) {

                    $idx_tmp = $idx + 12;
                    $endline = $arr_output[$idx_tmp + 10];
                    $line_content = $content;
                    while ($endline != "-1") {
                        $idx_tmp += 12;
                        $endline = $arr_output[$idx_tmp + 10];
                        $line_content = $arr_output[$idx_tmp + 11];
                        if (!empty($line_content)) {
                            if (strlen($line_content) > 2 && strpos($define['title_table'][2], $line_content) !== false)
                            //start ref
                            {
                                $pixel_end_ref = (int) $arr_output[$idx_tmp + 6] + (int) $arr_output[$idx_tmp + 8];
                            } elseif (preg_match("/{$define['title_table'][3]}/i", strtolower($line_content))) {
                                //End debit
                                $pixel_end_debit = (int) $arr_output[$idx_tmp + 6] + (int) $arr_output[$idx_tmp + 8];
                            } elseif (preg_match("/{$define['title_table'][4]}/i", strtolower($line_content))) {
                                //Start Credit
                                $pixel_start_credit = (int) $arr_output[$idx_tmp + 6];
                            }
                        }
                    }
                }
                if (preg_match($define['ptndateComptable'], $content)) {
                    $tmp = array("DATE_FIRST" => "", "REFERENCE" => "", "DATE_END" => "", "DEBIT" => "", "CREDIT" => "");
                    $tmp["DATE_FIRST"] = $content;
                    $endline = $arr_output[$idx + 10];
                    $line_content = "";
                    $isSkip = false;
                    //$ptnCurency = "/^(\d{1,3}\.)*\d{1,3},\d{2}$/i";
                    while ($endline != "-1") {
                        $idxRow++;
                        $idx += 12;
                        $line_content = $arr_output[$idx + 11];
                        $endline = $arr_output[$idx + 10];
                        if (preg_match($define['ptndateValeur'], $line_content)) {
                            $tmp["DATE_END"] = $line_content;
                        } else {
                            if ((int) $arr_output[$idx + 6] < ($pixel_end_ref + $pixel_end_debit) / 2.0 + 200) {
                                $tmp["REFERENCE"] .= $line_content . " ";
                            } elseif ((int) $arr_output[$idx + 6] > ($pixel_end_debit + $pixel_start_credit) / 2) { //10 do lech
                                $tmp["CREDIT"] .= $line_content . " ";
                            } else {
                                $tmp["DEBIT"] .= $line_content . " ";
                            }
                        }
                    }
                    $num_detect++;
                    $arr_csv[$num_detect] = $tmp;
                    if ($tmp["DATE_END"] === "") {
                        $isExpand = false;
                    }
                    $idxRow++;
                    $idx += 12;
                    $line_content = trim($arr_output[$idx + 11]);
                    while ($line_content === ""
                        || trim($arr_output[$idx + 12 + 11]) === ""
                        || (!preg_match($define['ptndateComptable'], $line_content)
                            && $idx + 12 < $totalRow * 12 && $idx + 12 + 11 < $totalRow * 12
                            && strpos($define["ptnNextPage_first"], $line_content) === false
                            && strpos($define["ptnNextPage_first"], trim($arr_output[$idx + 12 + 11])) === false
                            && strpos($content, $define['ptnFtotal']) === false
                            && strpos($arr_output[$idx + 11 + 12], $define['ptnLtotal']) === false)) {
                        if ($line_content !== "") {
                            $arr_csv[$num_detect]["REFERENCE"] .= $line_content . " ";
                        }
                        $idxRow++;
                        $idx += 12;
                        $line_content = trim($arr_output[$idx + 11]);
                    }
                    $idxRow--;
                    $idx -= 12;
                }

                if (!$define['IBAN'] && strcmp($define['ptn_IBAN'], $content) == 0) {
                    $define['IBAN'] = true;
                    $define['IBAN_Number'] .= $arr_output[($idx + 11) + 12 * 2] . " " . $arr_output[($idx + 11) + 12 * 3] . " " . $arr_output[($idx + 11) + 12 * 4] . " " . $arr_output[($idx + 11) + 12 * 5] . " " . $arr_output[($idx + 11) + 12 * 6] . " " . $arr_output[($idx + 11) + 12 * 7] . " " . $arr_output[($idx + 11) + 13 * 7];
                }

                if ($i == 0 && !$define['namefind']) {
                    if ($idx - 12 > 0 && $arr_output[$idx + 11 - 12] === "-" && preg_match("/" . $define["ptnFname"] . "/", trim($content))
                        && preg_match("/" . $define["ptnLname"] . "/", trim($content_next))) {
                        $idxStart = self::getIdxStartLine($idx, $arr_output);
                        while ($idxStart + 11 < $totalRow * 12 && $arr_output[$idxStart + 11] !== "-") {
                            $define["customerName"] .= $arr_output[$idxStart + 11] . " ";
                            $idxStart += 12;
                        }
                        $define['namefind'] = true;
                    }
                }
                if ($define['ptnFsoustotal'] != "" && $define['ptnLsoustotal'] != "" && (strpos($content, $define['ptnFsoustotal']) !== false) && (strpos($content_next, $define['ptnLsoustotal']) !== false)) {
                    $isExpand = false;
                    $tmp_soustotal = array("DATE_FIRST" => "", "REFERENCE" => "", "DATE_END" => "", "DEBIT" => "", "CREDIT" => "");
                    $endline = $arr_output[$idx + 10];
                    $line_content = $content;
                    $tmp_soustotal["REFERENCE"] .= $line_content . " ";
                    while ($endline != "-1") {
                        $idxRow++;
                        $idx += 12;
                        $line_content = $arr_output[$idx + 11];
                        $endline = $arr_output[$idx + 10];
                        if ((int) $arr_output[$idx + 6] < ($pixel_end_ref + $pixel_end_debit) / 2.0 + 200) {
                            $tmp_soustotal["REFERENCE"] .= $line_content . " ";
                        } elseif ((int) $arr_output[$idx + 6] > ($pixel_end_debit + $pixel_start_credit) / 2) { //10 do lech
                            $tmp_soustotal["CREDIT"] .= $line_content . " ";
                        } else {
                            $tmp_soustotal["DEBIT"] .= $line_content . " ";
                        }
                    }
                    $num_detect++;
                    $arr_csv[$num_detect] = $tmp_soustotal;
                }
                //Process Total Des
                if ((strpos($content, $define['ptnFtotal']) !== false) && (strpos($arr_output[$idx + 11 + 12], $define['ptnLtotal']) !== false)) {
                    $isExpand = false;
                    $tmp_total = array("DATE_FIRST" => "", "REFERENCE" => "", "DATE_END" => "", "DEBIT" => "", "CREDIT" => "");
                    $endline = $arr_output[$idx + 10];
                    $line_content = $content;
                    $tmp_total["REFERENCE"] .= $line_content . " ";
                    while ($endline != "-1") {
                        $idxRow++;
                        $idx += 12;
                        $line_content = $arr_output[$idx + 11];
                        $endline = $arr_output[$idx + 10];
                        if ((int) $arr_output[$idx + 6] < ($pixel_end_ref + $pixel_end_debit) / 2.0 + 200) {
                            $tmp_total["REFERENCE"] .= $line_content . " ";
                        } elseif ((int) $arr_output[$idx + 6] > ($pixel_end_debit + $pixel_start_credit) / 2) { //10 do lech
                            $tmp_total["CREDIT"] .= $line_content . " ";
                        } else {
                            $tmp_total["DEBIT"] .= $line_content . " ";
                        }
                    }
                    $num_detect++;
                    $arr_csv[$num_detect] = $tmp_total;
                }
                if ($isExpand && strpos($content, $define['ptnNextPage_first']) === false) {
                    if ((int) $arr_output[$idx + 6] > $pixel_start_ref && (int) $arr_output[$idx + 6] < $pixel_end_ref) {
                        $arr_csv[$num_detect]["REFERENCE"] .= $content . " ";
                    }
                }
            }
        }
        return $arr_csv;
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
		}else {
            return "This file is not support in this time";
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
