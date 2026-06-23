<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="format-detection" content="telephone=no, date=no, email=no, address=no, url=no">
    <title>Got your message</title>
</head>
<body style="margin: 0; padding: 48px 16px; background: #0d1117;">
    <table style="width: 100%; background: #0d1117; border-collapse: collapse; border-spacing: 0;">
        <tr>
            <td>
                <table style="width: 100%; max-width: 520px; margin: 0 auto; border-collapse: collapse; border-spacing: 0;">

                    {{-- Header --}}
                    <tr>
                        <td style="padding-bottom: 24px;">
                            <p style="margin: 0; font-size: 14px; font-family: monospace;"><span style="color: {{ $usernameColor }}; font-weight: bold;">{{ $username }}</span><span style="color: {{ $sepColor }};">@</span><span style="color: {{ $hostnameColor }}; font-weight: bold;">{!! $hostnameDisplay !!}</span><span style="color: {{ $sepColor }};">{{ $suffix }}</span></p>
                        </td>
                    </tr>

                    {{-- Card --}}
                    <tr>
                        <td style="background: #161b22; border: 1px solid #21262d; border-radius: 8px; padding: 32px;">

                            {{-- Status --}}
                            <p style="margin: 0 0 24px; font-size: 13px; color: #4ade80; letter-spacing: 0.05em; font-family: monospace;">✓ message received</p>

                            {{-- Greeting --}}
                            <p style="margin: 0 0 8px; font-size: 15px; color: #e2e8f0; line-height: 1.7; font-family: monospace;">Hey — got your message.</p>
                            <p style="margin: 0 0 24px; font-size: 15px; color: #9ca3af; line-height: 1.7; font-family: monospace;">I'll review it and get back to you as soon as possible.</p>

                            {{-- Submitted message --}}
                            <div style="background: #0d1117; border: 1px solid #21262d; border-radius: 6px; padding: 16px; margin-bottom: 24px;">
                                <p style="margin: 0 0 6px; font-size: 11px; color: #4b5563; text-transform: uppercase; letter-spacing: 0.1em; font-family: monospace;">your message</p>
                                <p style="margin: 0; font-size: 14px; color: #9ca3af; line-height: 1.7; font-family: monospace;">{{ $contactMessage->message }}</p>
                            </div>

                            {{-- Signature --}}
                            <p style="margin: 0; font-size: 14px; color: #4b5563; line-height: 1.7; font-family: monospace;">— <span style="color: #60a5fa;">{{ $name }}</span></p>

                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding-top: 20px; padding-bottom: 48px;">
                            <p style="margin: 0; font-size: 11px; color: #6b7280; font-family: monospace;">You received this because you used the <span style="color: #9ca3af;">notify</span> command on <a href="https://{{ $hostname }}" style="color: #9ca3af; text-decoration: underline; font-family: monospace;">{{ $hostname }}</a></p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
