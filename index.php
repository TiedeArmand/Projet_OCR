<html>
    <head>
        <title>Convert PDF to Image</title>
        <meta charset="UTF-8"/>
        <style>
            td,tr{
                padding: 5px;
            }
        </style>
    </head>
    <body>
        <h3 align="center">SYSTEM PDF CONVERT TO JPG</h3>
        <form action="ProcessImage.php" method="POST"  enctype="multipart/form-data">
            <table align="center">
            <tr><td>File PDF:</td><td> <input type="file" name="pdf_file"/> </td></tr>
            <tr><td colspan="2" align="center" /><input type="submit" name="btnSubmit" value="Proccess"/></td></tr>
            </table>
        </form>
    </body>
</html>