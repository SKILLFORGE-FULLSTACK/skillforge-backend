<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 24px;
            size: A4 landscape;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: "DejaVu Sans", sans-serif;
            color: #1f2430;
        }

        .border-outer {
            border: 2px solid {{ $accentColor }};
            padding: 6px;
        }

        .border-inner {
            border: 1px solid #c9ccd6;
            padding: 36px 60px 24px;
            box-sizing: border-box;
            text-align: center;
        }

        .brand {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 3px;
            color: {{ $accentColor }};
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .brand-sub {
            font-size: 9px;
            letter-spacing: 2px;
            color: #8a8f9c;
            text-transform: uppercase;
            margin-bottom: 30px;
        }

        .kicker {
            font-size: 12px;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: #8a8f9c;
            margin-bottom: 14px;
        }

        .recipient {
            font-family: "DejaVu Serif", serif;
            font-size: 40px;
            font-weight: bold;
            color: #1f2430;
            margin-bottom: 6px;
            padding-bottom: 14px;
            border-bottom: 1px solid #d8dae2;
            display: inline-block;
            min-width: 480px;
        }

        .statement {
            font-size: 13px;
            color: #4a4f5c;
            margin-top: 22px;
            line-height: 1.7;
        }

        .cert-title {
            font-family: "DejaVu Serif", serif;
            font-size: 24px;
            font-weight: bold;
            color: {{ $accentColor }};
            margin-top: 8px;
        }

        .meta-row {
            margin-top: 34px;
            width: 100%;
        }

        .meta-cell {
            display: inline-block;
            width: 30%;
            vertical-align: top;
        }

        .meta-label {
            font-size: 9px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #8a8f9c;
            margin-bottom: 4px;
        }

        .meta-value {
            font-size: 14px;
            font-weight: bold;
            color: #1f2430;
        }

        .footer {
            margin-top: 60px;
        }

        .signature {
            display: inline-block;
            width: 45%;
            text-align: center;
            vertical-align: bottom;
        }

        .signature-line {
            border-top: 1px solid #1f2430;
            padding-top: 6px;
            font-size: 11px;
            font-weight: bold;
        }

        .signature-sub {
            font-size: 9px;
            color: #8a8f9c;
            margin-top: 2px;
        }

        .qr-block {
            display: inline-block;
            width: 45%;
            text-align: center;
            vertical-align: bottom;
        }

        .qr-block img {
            width: 64px;
            height: 64px;
        }

        .verify-code {
            font-size: 8px;
            color: #8a8f9c;
            margin-top: 4px;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="border-outer">
        <div class="border-inner">
            <div class="brand">SkillForge</div>
            <div class="brand-sub">Plateforme de certification technique</div>

            <div class="kicker">Certificat de réussite</div>

            <div class="recipient">{{ $recipientName }}</div>

            <div class="statement">
                a démontré avec succès sa maîtrise des compétences évaluées et obtenu la certification
            </div>
            <div class="cert-title">{{ $certificationTitle }}</div>

            <div class="meta-row">
                <div class="meta-cell">
                    <div class="meta-label">Score obtenu</div>
                    <div class="meta-value">{{ $score }}/100</div>
                </div>
                <div class="meta-cell">
                    <div class="meta-label">Niveau</div>
                    <div class="meta-value">{{ $level }}</div>
                </div>
                <div class="meta-cell">
                    <div class="meta-label">Date de délivrance</div>
                    <div class="meta-value">{{ $issuedAt }}</div>
                </div>
            </div>

            <div class="footer">
                <div class="signature">
                    <div class="signature-line">SkillForge</div>
                    <div class="signature-sub">Organisme de certification</div>
                </div>
                <div class="qr-block">
                    <img src="{{ $qrDataUri }}" alt="QR de vérification">
                    <div class="verify-code">{{ $verifyCode }}</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
