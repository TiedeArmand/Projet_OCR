<?php
require_once 'database.php';
$date1 =  $_POST["value"];
$date1 = date('Y-m-d', strtotime($date1));
$date2 = date('Y-m-d', strtotime("+3 months", strtotime($date1)));

$database = new Database();
$conn = $database->connect();
$stringPro="Call mapdata('{$date1}','{$date2}')";
$result = mysqli_query($conn, $stringPro ) ;
    echo '<table border=2>';
    echo 
    '<tr>
        <td>date_bank</td>
        <td>libelle</td>
        <td style="background-color: yellow">debit_bank</td>
        <td style="background-color: yellow">credit_bank</td>
        <td>id_facture</td>
        <td>name_facture</td>
        <td>date_facture</td>
        <td>HT</td>
        <td>TVA</td>
        <td>TTC</td>
        <td style="background-color: yellow">debit_facture</td>
        <td style="background-color: yellow">credit_facture</td>
    </tr>';
if ($result->num_rows>0) {
    while ($post=mysqli_fetch_assoc($result)) {
       // if($post[6] != ''){
        echo 
        '<tr>
            <td>'.$post['datebank'].'</td>
            <td>'.$post['libelle'].'</td>
            <td style="background-color: yellow">'.$post['debitbank'].'</td>
            <td style="background-color: yellow">'.$post['creditbank'].'</td>
            <td>'.$post['id_facture'].'</td>
            <td>'.$post['facture'].'</td>
            <td>'.$post['date_'].'</td>
            <td>'.$post['total_ht'].'</td>
            <td>'.$post['tva'].'</td>
            <td>'.$post['total_ttc'].'</td>
            <td style="background-color: yellow">'.$post['debit'].'</td>
            <td style="background-color: yellow">'.$post['credit'].'</td>
        </tr>';
      //  }
    } 
    }
    echo '</table>';
?>