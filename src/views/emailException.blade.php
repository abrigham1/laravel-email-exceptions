<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet">
</head>
<body style="color: #000000; font-family: 'Open Sans', sans-serif;">

<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTable">
    <tr>
        <td align="center" valign="top">
            <table border="0" cellpadding="20" cellspacing="0" width="600" id="emailContainer">
                <tr>
                    <td align="center" valign="top">
                        <h3 style="height: 40px; line-height: 40px; background-color: #f56857; color: #ffffff;">There has been an exception thrown on {{ env('APP_URL', 'unknown') }}</h3>
                        <table class="emailExceptionTable" style="text-align: left;" border="0" cellspacing="0" cellpadding="3">
                            <tr>
                                <td>
                                    <strong>Environment:</strong>
                                </td>
                                <td>
                                    {{ env('APP_ENV', 'unknown') }}
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>Exception Url:</strong>
                                </td>
                                <td>
                                    {!! Request::fullUrl() !!}
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>Exception Class:</strong>
                                </td>
                                <td>
                                    {{ get_class($exception) }}
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>Exception Message:</strong>
                                </td>
                                <td>
                                    {{ $exception->getMessage() }}
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>Exception Code:</strong>
                                </td>
                                <td>
                                    {{ $exception->getCode() }}
                                </td>
                            </tr>
                        </table>
                        <hr style="color: #f6f6f6;">
                        <table align="center" style="text-align: center;" border="0" cellspacing="0" cellpadding="0">
                            <tr>
                                <td>
                                    In {{ $exception->getFile() }} on line {{  $exception->getLine() }}
                                </td>
                            </tr>
                        </table>
                        <hr style="color: #f6f6f6;">
                        <table align="center" style="text-align: center;" border="0" cellspacing="0" cellpadding="0">
                            <tr>
                                <td>
                                    <strong>Stack Trace:</strong>
                                </td>
                            </tr>
                            <tr>
                                <td align="left" style="text-align: left;">
                                    {!! nl2br($exception->getTraceAsString()) !!}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>