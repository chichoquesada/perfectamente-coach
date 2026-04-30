<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Invitación a PerfectaMENTE Coach</title>
</head>
<body style="font-family: Georgia, 'Times New Roman', serif; background:#0b0b0c; color:#ececec; margin:0; padding:24px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; margin:0 auto; background:#141416; border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:32px;">
        <tr>
            <td>
                <p style="font-size:11px; letter-spacing:0.25em; text-transform:uppercase; color:#c9a64a; margin:0 0 12px;">PerfectaMENTE Coach</p>
                <h1 style="font-size:22px; line-height:1.3; margin:0 0 16px; color:#f5f5f5;">
                    {{ $nutritionist->name }} le invita a llevar su plan en PerfectaMENTE.
                </h1>
                <p style="font-size:15px; line-height:1.6; color:#bdbdbd; margin:0 0 16px;">
                    {{ $patientName ? 'Hola '.$patientName.',' : 'Hola,' }}
                </p>
                <p style="font-size:15px; line-height:1.6; color:#bdbdbd; margin:0 0 16px;">
                    Su nutricionista, <strong style="color:#f5f5f5;">{{ $nutritionist->name }}</strong>,
                    le agregó a su cartera dentro de PerfectaMENTE Coach. Acepte la invitación
                    para crear su cuenta y empezar a registrar su adherencia diaria.
                </p>

                <p style="text-align:center; margin:28px 0;">
                    <a href="{{ $acceptUrl }}"
                       style="background:#c9a64a; color:#0b0b0c; text-decoration:none; padding:14px 28px; border-radius:999px; font-weight:bold; font-size:15px; font-family: Helvetica, Arial, sans-serif;">
                        Aceptar invitación
                    </a>
                </p>

                <p style="font-size:13px; line-height:1.6; color:#8f8f8f; margin:0 0 8px;">
                    Si el botón no funciona, copie y pegue este enlace en su navegador:
                </p>
                <p style="font-size:12px; line-height:1.5; color:#c9a64a; word-break:break-all; margin:0 0 24px;">
                    {{ $acceptUrl }}
                </p>

                <hr style="border:0; border-top:1px solid rgba(255,255,255,0.08); margin:24px 0;">
                <p style="font-size:12px; color:#7a7a7a; line-height:1.5; margin:0;">
                    Si usted no esperaba esta invitación, puede ignorar este correo.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
