<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Offer - {{ $positionTitle }}</title>
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
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            padding: 32px 40px;
            text-align: center;
        }
        .email-header .icon { font-size: 48px; margin-bottom: 16px; }
        .email-header h1 { color: #ffffff; font-size: 24px; font-weight: 600; }
        .email-header .subtitle { color: rgba(255,255,255,0.9); font-size: 14px; margin-top: 8px; }
        .celebration-banner {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            padding: 16px;
            text-align: center;
            font-size: 18px;
        }
        .email-body { padding: 40px; }
        .greeting { font-size: 18px; font-weight: 600; color: #1a1a2e; margin-bottom: 16px; }
        .content { font-size: 15px; color: #4a4a4a; line-height: 1.7; }
        .content p { margin-bottom: 16px; }
        .offer-details {
            background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
            border: 1px solid #ddd6fe;
            border-radius: 8px;
            padding: 24px;
            margin: 24px 0;
        }
        .offer-details h3 {
            font-size: 14px;
            font-weight: 600;
            color: #7c3aed;
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
        .salary-highlight {
            font-size: 24px;
            font-weight: 700;
            color: #4F46E5;
        }
        .expiry-warning {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 16px 20px;
            margin: 24px 0;
            border-radius: 0 6px 6px 0;
        }
        .expiry-warning p { font-size: 14px; color: #991b1b; margin: 0; }
        .benefits-box {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 20px;
            margin: 24px 0;
        }
        .benefits-box h4 {
            font-size: 14px;
            font-weight: 600;
            color: #15803d;
            margin-bottom: 12px;
        }
        .benefits-box p { font-size: 14px; color: #166534; margin: 0; }
        .button-wrapper { text-align: center; margin: 32px 0; }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
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
                <div class="icon">🎉</div>
                <h1>Congratulations!</h1>
                <p class="subtitle">You've received a job offer</p>
            </div>
            
            <div class="celebration-banner">
                🌟 <strong>{{ $positionTitle }}</strong> at <strong>{{ $companyName }}</strong> 🌟
            </div>
            
            <div class="email-body">
                <p class="greeting">Dear {{ $candidateName }},</p>
                
                <div class="content">
                    <p>We are thrilled to extend an official job offer to you for the position of <strong>{{ $positionTitle }}</strong> at <strong>{{ $companyName }}</strong>!</p>
                    
                    <p>After careful consideration of your qualifications, experience, and the positive impression you made during the interview process, we believe you would be an excellent addition to our team.</p>
                    
                    <div class="offer-details">
                        <h3>💼 Offer Details</h3>
                        <div class="detail-row">
                            <span class="detail-label">Position:</span>
                            <span class="detail-value">{{ $positionTitle }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Salary:</span>
                            <span class="detail-value salary-highlight">{{ $salaryOffered }}</span>
                        </div>
                        @if($startDate)
                        <div class="detail-row">
                            <span class="detail-label">Start Date:</span>
                            <span class="detail-value">{{ $startDate }}</span>
                        </div>
                        @endif
                    </div>
                    
                    @if($benefits)
                    <div class="benefits-box">
                        <h4>✨ Benefits Package</h4>
                        <p>{{ $benefits }}</p>
                    </div>
                    @endif
                    
                    @if($expiryDate)
                    <div class="expiry-warning">
                        <p><strong>⏰ Important:</strong> This offer is valid until <strong>{{ $expiryDate }}</strong>. Please respond before this date.</p>
                    </div>
                    @endif
                    
                    @if($notes)
                    <p><em>{{ $notes }}</em></p>
                    @endif
                    
                    @if($portalUrl)
                    <div class="button-wrapper">
                        <a href="{{ $portalUrl }}" class="button">View & Respond to Offer</a>
                    </div>
                    @endif
                    
                    <p>Please review the offer details carefully and let us know your decision at your earliest convenience. If you have any questions or would like to discuss any aspects of this offer, please don't hesitate to reach out.</p>
                    
                    <p>We sincerely hope you'll join us and look forward to welcoming you to the team!</p>
                </div>
                
                <div class="signature">
                    <p>Warm regards,</p>
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
