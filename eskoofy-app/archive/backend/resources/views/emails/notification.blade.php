<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Notification' }}</title>
    <style>
        body {
            {{ $styles['body'] ?? 'font-family: Arial, sans-serif; line-height: 1.6; color: #333;' }}
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            {{ $styles['container'] ?? 'max-width: 600px; margin: 0 auto; padding: 20px;' }}
        }
        .header {
            {{ $styles['header'] ?? 'background-color: #4a86e8; color: white; padding: 20px; text-align: center;' }}
            border-radius: 4px 4px 0 0;
        }
        .content {
            {{ $styles['content'] ?? 'background-color: #fff; padding: 30px;' }}
            border-radius: 0 0 4px 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .button {
            {{ $styles['button'] ?? 'display: inline-block; padding: 10px 20px; background-color: #4a86e8; color: white; text-decoration: none; border-radius: 4px;' }}
            margin: 20px 0;
        }
        .footer {
            {{ $styles['footer'] ?? 'margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #777;' }}
            text-align: center;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 20px;
        }
        .button-container {
            text-align: center;
            margin: 25px 0;
        }
        .highlight-box {
            background-color: #f9f9f9;
            border-left: 4px solid #4a86e8;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            @if(isset($data['logo_url']))
                <img src="{{ $data['logo_url'] }}" alt="Logo" class="logo">
            @else
                <h1>{{ config('app.name', 'School Management System') }}</h1>
            @endif
        </div>
        
        <div class="content">
            @if(isset($data['greeting']))
                <h2>{{ $data['greeting'] }}</h2>
            @else
                <h2>Hello!</h2>
            @endif
            
            <div class="email-body">
                {!! $content !!}
                
                @if(!empty($data['action_text']) && !empty($actionUrl))
                    <div class="button-container">
                        <a href="{{ $actionUrl }}" class="button">
                            {{ $data['action_text'] }}
                        </a>
                    </div>
                @elseif(!empty($actionUrl))
                    <div class="button-container">
                        <a href="{{ $actionUrl }}" class="button">
                            View Details
                        </a>
                    </div>
                @endif
                
                @if(isset($data['additional_info']))
                    <div class="highlight-box">
                        <p><strong>Additional Information:</strong></p>
                        <p>{!! nl2br(e($data['additional_info'])) !!}</p>
                    </div>
                @endif
                
                @if(isset($data['footer_text']))
                    <p>{!! nl2br(e($data['footer_text'])) !!}</p>
                @else
                    <p>If you did not expect to receive this email, you can safely ignore it.</p>
                @endif
                
                <p>Thank you,<br>{{ config('app.name', 'School Management System') }}</p>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'School Management System') }}. All rights reserved.</p>
            
            @if(isset($data['unsubscribe_url']))
                <p>
                    <a href="{{ $data['unsubscribe_url'] }}" style="color: #666; text-decoration: none;">
                        Unsubscribe from these emails
                    </a>
                </p>
            @endif
            
            <p style="font-size: 11px; color: #999; margin-top: 10px;">
                This email was sent to {{ $email ?? 'you' }}. 
                @if(isset($data['preferences_url']))
                    <a href="{{ $data['preferences_url'] }}" style="color: #666; text-decoration: none;">
                        Update your email preferences
                    </a>
                @endif
            </p>
        </div>
    </div>
</body>
</html>
