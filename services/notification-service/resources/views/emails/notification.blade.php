<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Candidacy') }}</title>
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f4f7;
            -webkit-font-smoothing: antialiased;
        }
        
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        /* Header */
        .email-header {
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            padding: 32px 40px;
            text-align: center;
        }
        
        .email-header h1 {
            color: #ffffff;
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }
        
        .email-header .logo {
            font-size: 28px;
            font-weight: 700;
            color: #ffffff;
            text-decoration: none;
            letter-spacing: -0.5px;
        }
        
        /* Body */
        .email-body {
            padding: 40px;
        }
        
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 16px;
        }
        
        .content {
            font-size: 15px;
            color: #4a4a4a;
            line-height: 1.7;
        }
        
        .content p {
            margin-bottom: 16px;
        }
        
        /* Button */
        .button-wrapper {
            text-align: center;
            margin: 32px 0;
        }
        
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 15px;
            transition: transform 0.2s ease;
        }
        
        .button:hover {
            transform: translateY(-1px);
        }
        
        /* Info box */
        .info-box {
            background-color: #f8f9fc;
            border-left: 4px solid #4F46E5;
            padding: 20px;
            margin: 24px 0;
            border-radius: 0 6px 6px 0;
        }
        
        .info-box h3 {
            font-size: 14px;
            font-weight: 600;
            color: #4F46E5;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-box p {
            margin: 0;
            font-size: 14px;
            color: #555555;
        }
        
        /* Footer */
        .email-footer {
            background-color: #f8f9fc;
            padding: 24px 40px;
            text-align: center;
            border-top: 1px solid #eaeaea;
        }
        
        .email-footer p {
            font-size: 13px;
            color: #888888;
            margin-bottom: 8px;
        }
        
        .email-footer .company-name {
            font-weight: 600;
            color: #4F46E5;
        }
        
        .social-links {
            margin-top: 16px;
        }
        
        .social-links a {
            display: inline-block;
            margin: 0 8px;
            color: #888888;
            text-decoration: none;
        }
        
        /* Signature */
        .signature {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #eaeaea;
        }
        
        .signature p {
            margin-bottom: 4px;
        }
        
        /* Utilities */
        .text-muted {
            color: #888888;
            font-size: 13px;
        }
        
        .highlight {
            color: #4F46E5;
            font-weight: 600;
        }
        
        /* Responsive */
        @media only screen and (max-width: 600px) {
            .email-wrapper {
                padding: 10px;
            }
            
            .email-header,
            .email-body,
            .email-footer {
                padding: 24px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <!-- Header -->
            <div class="email-header">
                <span class="logo">{{ config('app.name', 'Candidacy') }}</span>
            </div>
            
            <!-- Body -->
            <div class="email-body">
                @if($recipientName)
                <p class="greeting">Hello {{ $recipientName }},</p>
                @else
                <p class="greeting">Hello,</p>
                @endif
                
                <div class="content">
                    {!! nl2br(e($body)) !!}
                </div>
                
                <!-- Signature -->
                <div class="signature">
                    <p>Best regards,</p>
                    <p><strong>The {{ config('app.name', 'Candidacy') }} Team</strong></p>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="email-footer">
                <p>This email was sent by <span class="company-name">{{ config('app.name', 'Candidacy') }}</span></p>
                <p class="text-muted">If you have any questions, please contact our support team.</p>
            </div>
        </div>
    </div>
</body>
</html>
