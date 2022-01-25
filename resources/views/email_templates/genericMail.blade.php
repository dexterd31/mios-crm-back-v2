<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0; padding:0; background-color:#F2F2F2;">
<center>
    <table width="640" cellpadding="0" cellspacing="0" border="0" class="wrapper" bgcolor="#FFFFFF">
        <tr>
            <td height="10" style="font-size:10px; line-height:10px;">&nbsp;</td>
        </tr>
        <tr>
            <td height="10" style="font-size:10px; line-height:10px;">&nbsp;</td>
        </tr>
        <tr>
            <td height="10" style="font-size:10px; line-height:10px;">&nbsp;</td>
        </tr>
    </table>
    <table width="640" cellpadding="0" cellspacing="0" border="0" class="wrapper" bgcolor="#FFFFFF">
        <tr>
            <td height="10" style="font-size:10px; line-height:10px;">&nbsp;</td>
        </tr>
        <tr>
            <td align="center" valign="top">

                <table width="600" cellpadding="0" cellspacing="0" border="0" class="container">
                    <tr>
                        <td align="left" valign="top">
                            {!! html_entity_decode($emailBody) !!}
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
        <tr>
            <td height="10" style="font-size:10px; line-height:10px;">&nbsp;</td>
        </tr>
    </table>
    <table width="640" cellpadding="0" cellspacing="0" border="0" class="wrapper" bgcolor="#FFFFFF">
        <tr>
            <td height="10" style="font-size:10px; line-height:10px;">&nbsp;</td>
        </tr>
       @if(!is_null($signature))
            {{ $signature }}
       @endif 
        <tr>
            <td height="10" style="font-size:10px; line-height:10px;">&nbsp;</td>
        </tr>
    </table>
    <table width="640" cellpadding="0" cellspacing="0" border="0" class="wrapper" bgcolor="#FFFFFF">
        <tr>
            <td height="10" style="font-size:10px; line-height:10px;">&nbsp;</td>
        </tr>
        <tr>
            <td height="10" style="font-size:10px; line-height:10px;">&nbsp;</td>
        </tr>
        <tr>
            <td height="10" style="font-size:10px; line-height:10px;">&nbsp;</td>
        </tr>
    </table>
</center>
</body>
</html>
