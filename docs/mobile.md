# Mobile App Integration

The VoiceChat API is fully ready for native mobile clients (Android / iOS).

## Android (Kotlin) Example

```kotlin
// Login
val response = httpClient.post("https://api.voicechat.app/api/auth/login") {
    contentType(ContentType.Application.Json)
    body = mapOf("login" to "demo", "password" to "Demo@12345", "device" to "Android")
}
val tokens = response.data
// Store access_token, refresh_token in EncryptedSharedPreferences

// Authenticated request
val user = httpClient.get("https://api.voicechat.app/api/me") {
    headers { append("Authorization", "Bearer $accessToken") }
}

// WebSocket
val ws = WebSocketClient("wss://api.voicechat.app:8080/?token=$accessToken")
ws.send("""{"type":"hello","room_id":1}""")

// WebRTC
val rtcClient = RtcClient(iceServers = listOf("stun:stun.l.google.com:19302"))
rtcClient.startMic()  // requests RECORD_AUDIO permission
```

## iOS (Swift) Example

```swift
// Login
let url = URL(string: "https://api.voicechat.app/api/auth/login")!
var req = URLRequest(url: url)
req.httpMethod = "POST"
req.addValue("application/json", forHTTPHeaderField: "Content-Type")
req.httpBody = try? JSONSerialization.data(withJSONObject: [
    "login": "demo", "password": "Demo@12345", "device": "iOS"
])
let (data, _) = try await URLSession.shared.data(for: req)
let tokens = try JSONDecoder().decode(LoginResponse.self, from: data)
// Store in Keychain

// WebRTC
let config = RTCConfiguration()
config.iceServers = [RTCIceServer(urlStrings: ["stun:stun.l.google.com:19302"])]
let peer = RTCPeerConnectionFactory().peerConnection(with: config, constraints: nil, delegate: nil)
```

## Push Notifications (FCM)

To enable push notifications for messages/gifts/invites, integrate FCM:

1. Add FCM credentials to `.env`:
   ```
   FCM_SERVER_KEY=...
   FCM_PROJECT_ID=...
   ```

2. The mobile app registers the device token via:
   ```
   POST /api/devices/register
   { "token": "fcm-token", "platform": "android|ios" }
   ```

3. The `NotificationService` will send pushes through FCM when the user is offline.

## Recommended Mobile Features

- **Biometric login** — Store JWT in Keychain / EncryptedSharedPreferences, unlock with FaceID / TouchID / fingerprint
- **Background audio** — Use `MediaSession` (Android) / `AVAudioSession` (iOS) to keep voice room alive when screen is off
- **CallKit integration** (iOS) for native call UI on incoming room invites
- **ConnectionService** (Android) for ongoing call notification
- **Picture-in-Picture** for the room UI

## Permissions

### Android
```xml
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.RECORD_AUDIO" />
<uses-permission android:name="android.permission.MODIFY_AUDIO_SETTINGS" />
<uses-permission android:name="android.permission.FOREGROUND_SERVICE" />
<uses-permission android:name="android.permission.POST_NOTIFICATIONS" />
```

### iOS
```xml
<key>NSMicrophoneUsageDescription</key>
<string>VoiceChat needs the microphone to talk in voice rooms.</string>
<key>NSCameraUsageDescription</key>
<string>VoiceChat may use the camera for profile pictures.</string>
```
