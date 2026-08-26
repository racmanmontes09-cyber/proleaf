<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Greenhouse Offline Alert</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #F4F6F4; margin: 0; padding: 24px; color: #1B4332;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; border: 1px solid #e0e7e1; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <!-- Header -->
        <tr>
            <td style="background: linear-gradient(135deg, #1B4332 0%, #2D6A4F 100%); padding: 24px 32px; text-align: left;">
                <h1 style="color: #ffffff; margin: 0; font-size: 20px; font-weight: 700; letter-spacing: -0.5px;">Project L.E.A.F.</h1>
                <p style="color: #95D5B2; margin: 4px 0 0 0; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Critical Device Alert</p>
            </td>
        </tr>

        <!-- Alert Badge Banner -->
        <tr>
            <td style="padding: 24px 32px 12px 32px;">
                <div style="background-color: #FEF2F2; border: 1px solid #FCA5A5; border-radius: 12px; padding: 16px; margin-bottom: 20px;">
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td width="28" valign="top">
                                <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background-color: #DC2626; margin-top: 3px;"></span>
                            </td>
                            <td>
                                <strong style="color: #991B1B; font-size: 15px; display: block;">Greenhouse Hardware Disconnected</strong>
                                <span style="color: #7F1D1D; font-size: 13px;">The ESP32 controller node has stopped reporting telemetry.</span>
                            </td>
                        </tr>
                    </table>
                </div>

                <p style="font-size: 14px; line-height: 1.5; color: #2D6A4F; margin: 0 0 16px 0;">
                    Hello Super Admin,
                </p>
                <p style="font-size: 14px; line-height: 1.5; color: #374151; margin: 0 0 20px 0;">
                    A greenhouse in Project L.E.A.F. has transitioned from <strong>ONLINE &rarr; OFFLINE</strong>. Please find the details below:
                </p>

                <!-- Details Table -->
                <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #F8FAF8; border-radius: 12px; border: 1px solid #E5E7EB; margin-bottom: 24px;">
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #E5E7EB; font-size: 13px; color: #6B7280; font-weight: 600; width: 35%;">Greenhouse:</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #E5E7EB; font-size: 14px; color: #1B4332; font-weight: 700;">{{ $greenhouse->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #E5E7EB; font-size: 13px; color: #6B7280; font-weight: 600;">Location:</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #E5E7EB; font-size: 14px; color: #374151;">{{ $greenhouse->location ?? 'Unspecified' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #E5E7EB; font-size: 13px; color: #6B7280; font-weight: 600;">Assigned Farmer:</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #E5E7EB; font-size: 14px; color: #374151;">{{ $farmerName }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #E5E7EB; font-size: 13px; color: #6B7280; font-weight: 600;">Device ID:</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #E5E7EB; font-size: 14px; font-family: monospace; color: #2D6A4F; font-weight: 600;">{{ $device->device_id }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #E5E7EB; font-size: 13px; color: #6B7280; font-weight: 600;">Last Seen:</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #E5E7EB; font-size: 14px; color: #374151;">{{ $lastSeen }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 16px; font-size: 13px; color: #6B7280; font-weight: 600;">Alert Timestamp:</td>
                        <td style="padding: 12px 16px; font-size: 14px; color: #374151;">{{ now()->toDateTimeString() }} (UTC)</td>
                    </tr>
                </table>

                <div style="text-align: center; margin-bottom: 24px;">
                    <a href="{{ route('admin.dashboard') }}" style="display: inline-block; background-color: #2D6A4F; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: 600;">Open Super Admin Console</a>
                </div>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="background-color: #F8FAF8; padding: 16px 32px; border-top: 1px solid #E5E7EB; text-align: center;">
                <p style="color: #6B7280; font-size: 12px; margin: 0;">&copy; {{ date('Y') }} Project L.E.A.F. &bull; Automated Hydroponic Cultivation System</p>
            </td>
        </tr>
    </table>
</body>
</html>
