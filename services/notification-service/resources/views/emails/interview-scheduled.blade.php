<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Scheduled - {{ $positionTitle }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f4f7;
        }
        .email-wrapper { max-width: 600px; margin: 0 auto; padding: 20px; }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        .email-header {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            padding: 32px 40px;
            text-align: center;
        }
        .email-header .icon { font-size: 48px; margin-bottom: 16px; }
        .email-header h1 { color: #ffffff; font-size: 24px; font-weight: 600; }
        .email-header .subtitle { color: rgba(255,255,255,0.9); font-size: 14px; margin-top: 8px; }
        .email-body { padding: 40px; }
        .greeting { font-size: 18px; font-weight: 600; color: #1a1a2e; margin-bottom: 16px; }
        .content { font-size: 15px; color: #4a4a4a; line-height: 1.7; }
        .content p { margin-bottom: 16px; }
        .interview-details {
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
            border: 1px solid #d1fae5;
            border-radius: 8px;
            padding: 24px;
            margin: 24px 0;
        }
        .interview-details h3 {
            font-size: 14px;
            font-weight: 600;
            color: #059669;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .detail-row {
            display: flex;
            margin-bottom: 12px;
            font-size: 14px;
        }
        .detail-label {
            font-weight: 600;
            color: #374151;
            min-width: 120px;
        }
        .detail-value { color: #4b5563; }
        .highlight-box {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 16px 20px;
            margin: 24px 0;
            border-radius: 0 6px 6px 0;
        }
        .highlight-box p { font-size: 14px; color: #92400e; margin: 0; }
        .button-wrapper { text-align: center; margin: 32px 0; }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 15px;
        }
        .email-footer {
            background-color: #f8f9fc;
            padding: 24px 40px;
            text-align: center;
            border-top: 1px solid #eaeaea;
        }
        .email-footer p { font-size: 13px; color: #888888; margin-bottom: 8px; }
        .signature { margin-top: 32px; padding-top: 24px; border-top: 1px solid #eaeaea; }
        @media only screen and (max-width: 600px) {
            .email-wrapper { padding: 10px; }
            .email-header, .email-body, .email-footer { padding: 24px 20px; }
            .detail-row { flex-direction: column; }
            .detail-label { margin-bottom: 4px; }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header">
                <div class="icon">📅</div>
                <h1>Interview Scheduled</h1>
                <p class="subtitle">{{ $positionTitle }} at {{ $companyName }}</p>
            </div>
            
            <div class="email-body">
                <p class="greeting">Hello {{ $candidateName }},</p>
                
                <div class="content">
                    <p>Great news! We're excited to invite you for an interview for the <strong>{{ $positionTitle }}</strong> position at <strong>{{ $companyName }}</strong>.</p>
                    
                    <div class="interview-details">
                        <h3>📋 Interview Details</h3>
                        <div class="detail-row">
                            <span class="detail-label">📆 Date:</span>
                            <span class="detail-value">{{ $interviewDate }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">🕐 Time:</span>
                            <span class="detail-value">{{ $interviewTime }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">🎯 Type:</span>
                            <span class="detail-value">{{ ucfirst(str_replace('_', ' ', $interviewType)) }}</span>
                        </div>
                        @if($interviewLocation)
                        <div class="detail-row">
                            <span class="detail-label">📍 Location:</span>
                            <span class="detail-value">{{ $interviewLocation }}</span>
                        </div>
                        @endif
                        @if($interviewerNames)
                        <div class="detail-row">
                            <span class="detail-label">👥 Interviewers:</span>
                            <span class="detail-value">{{ $interviewerNames }}</span>
                        </div>
                        @endif
                    </div>
                    
                    @if($notes)
                    <div class="highlight-box">
                        <p><strong>Note:</strong> {{ $notes }}</p>
                    </div>
                    @endif
                    
                    <p>Please ensure you're available at the scheduled time. If you need to reschedule, please contact us as soon as possible.</p>
                    
                    <p>We look forward to meeting you!</p>
                </div>
                
                <div class="signature">
                    <p>Best regards,</p>
                    <p><strong>The {{ $companyName }} Recruitment Team</strong></p>
                </div>
            </div>
            
            <div class="email-footer">
                <p>This email was sent by <strong>{{ $companyName }}</strong></p>
                <p>If you have any questions, please contact our recruitment team.</p>
            </div>
        </div>
    </div>
</body>
</html>
