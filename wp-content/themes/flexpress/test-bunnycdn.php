<?php
// Load WordPress to get access to settings
require_once('../../../wp-load.php');

// Get the BunnyCDN settings
$video_settings = get_option('flexpress_video_settings', array());
$library_id = isset($video_settings['bunnycdn_library_id']) ? $video_settings['bunnycdn_library_id'] : '';
$token_key = isset($video_settings['bunnycdn_token_key']) ? $video_settings['bunnycdn_token_key'] : '';

// Test video ID (using the same one from your homepage)
$video_id = '82654572-f22d-429e-8dad-731b6c0d80e7';

// Generate tokens with different expiry times for testing
$expires1 = time() + 3600; // 1 hour
$token1 = hash('sha256', $token_key . $video_id . $expires1);

$expires2 = time() + 3600;
$token2 = hash('sha256', $token_key . $video_id . $expires2);

$expires3 = time() + 3600;
$token3 = hash('sha256', $token_key . $video_id . $expires3);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BunnyCDN Controls Test</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            background: #000;
            color: #fff;
            font-family: Arial, sans-serif;
        }
        .video-container {
            position: relative;
            width: 100%;
            max-width: 800px;
            margin: 0 auto 40px;
            padding-top: 56.25%; /* 16:9 aspect ratio */
        }
        iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }
        h2 {
            text-align: center;
            margin: 20px 0;
        }
        .info {
            text-align: center;
            margin: 10px 0;
            font-size: 14px;
            color: #888;
        }
        .debug {
            background: #333;
            padding: 10px;
            margin: 20px auto;
            max-width: 800px;
            font-family: monospace;
            font-size: 12px;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <h1 style="text-align: center;">BunnyCDN Controls Test</h1>
    
    <div class="debug">
        <strong>Debug Info:</strong><br>
        Library ID: <?php echo esc_html($library_id); ?><br>
        Video ID: <?php echo esc_html($video_id); ?><br>
        Token Key: <?php echo !empty($token_key) ? 'Present' : 'Missing'; ?><br>
    </div>
    
    <h2>Test 1: With controls=false (Should NOT show controls)</h2>
    <div class="info">URL: ?autoplay=true&loop=true&muted=true&controls=false&token=...</div>
    <div class="video-container">
        <iframe 
            src="https://iframe.mediadelivery.net/embed/<?php echo $library_id; ?>/<?php echo $video_id; ?>?autoplay=true&loop=true&muted=true&controls=false&token=<?php echo $token1; ?>&expires=<?php echo $expires1; ?>"
            allow="accelerometer; gyroscope; autoplay; encrypted-media;"
            allowfullscreen="false">
        </iframe>
    </div>
    
    <h2>Test 2: Without controls parameter (Should show controls)</h2>
    <div class="info">URL: ?autoplay=true&loop=true&muted=true&token=...</div>
    <div class="video-container">
        <iframe 
            src="https://iframe.mediadelivery.net/embed/<?php echo $library_id; ?>/<?php echo $video_id; ?>?autoplay=true&loop=true&muted=true&token=<?php echo $token2; ?>&expires=<?php echo $expires2; ?>"
            allow="accelerometer; gyroscope; autoplay; encrypted-media;"
            allowfullscreen="true">
        </iframe>
    </div>
    
    <h2>Test 3: With only controls=false (minimal test)</h2>
    <div class="info">URL: ?controls=false&token=...</div>
    <div class="video-container">
        <iframe 
            src="https://iframe.mediadelivery.net/embed/<?php echo $library_id; ?>/<?php echo $video_id; ?>?controls=false&token=<?php echo $token3; ?>&expires=<?php echo $expires3; ?>"
            allow="accelerometer; gyroscope; autoplay; encrypted-media;"
            allowfullscreen="false">
        </iframe>
    </div>
    
    <div class="debug">
        <strong>Full URLs for manual testing:</strong><br><br>
        Test 1:<br>
        <code>https://iframe.mediadelivery.net/embed/<?php echo $library_id; ?>/<?php echo $video_id; ?>?autoplay=true&loop=true&muted=true&controls=false&token=<?php echo $token1; ?>&expires=<?php echo $expires1; ?></code><br><br>
        Test 2:<br>
        <code>https://iframe.mediadelivery.net/embed/<?php echo $library_id; ?>/<?php echo $video_id; ?>?autoplay=true&loop=true&muted=true&token=<?php echo $token2; ?>&expires=<?php echo $expires2; ?></code><br><br>
        Test 3:<br>
        <code>https://iframe.mediadelivery.net/embed/<?php echo $library_id; ?>/<?php echo $video_id; ?>?controls=false&token=<?php echo $token3; ?>&expires=<?php echo $expires3; ?></code>
    </div>
    
    <script>
        console.log('[Test Page] Loaded at:', new Date().toISOString());
        
        // Log all iframes on the page
        document.querySelectorAll('iframe').forEach((iframe, index) => {
            console.log(`[Test ${index + 1}] iframe src:`, iframe.src);
        });
    </script>
</body>
</html> 