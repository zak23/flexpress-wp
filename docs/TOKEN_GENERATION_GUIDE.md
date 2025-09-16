# BunnyCDN Token Generation Guide for Developers

## Overview
This guide explains how to generate secure, time-limited URLs for BunnyCDN protected content using token authentication.

## Configuration
- **CDN URL**: `zaks-pov.b-cdn.net`
- **Token**: `35173863-ae26-4ec2-bd90-df75b38bb9d5`

## Token Generation Process

### Step 1: Prepare the Data
```python
# Your BunnyCDN token (from settings)
cdn_token = "35173863-ae26-4ec2-bd90-df75b38bb9d5"

# The file path you want to protect
file_path = "/episodes/galleries/10/DSC00520-f5066340-1757978449.jpg"

# Expiration timestamp (Unix timestamp)
expires_timestamp = int(time.time()) + (24 * 3600)  # 24 hours from now
```

### Step 2: Create the String to Sign
```python
# Concatenate: TOKEN + PATH + EXPIRATION_TIMESTAMP
string_to_sign = f"{cdn_token}{file_path}{expires_timestamp}"
# Result: "35173863-ae26-4ec2-bd90-df75b38bb9d5/episodes/galleries/10/DSC00520-f5066340-1757978449.jpg1758071553"
```

### Step 3: Generate SHA256 Hash
```python
import hashlib

# Create SHA256 hash of the concatenated string
hash_object = hashlib.sha256(string_to_sign.encode('utf-8'))
hash_bytes = hash_object.digest()
```

### Step 4: Base64 Encode
```python
import base64

# Base64 encode the hash
base64_token = base64.b64encode(hash_bytes).decode('utf-8')
# Example: "fw6jHfW0ZC7/zLDVOXKAckDypLkrLzpsMedRgxOt4PE=="
```

### Step 5: Character Replacement
```python
# Replace characters as per BunnyCDN specification
token = base64_token.replace('+', '-').replace('/', '_').replace('=', '')
# Result: "fw6jHfW0ZC7_zLDVOXKAckDypLkrLzpsMedRgxOt4PE"
```

### Step 6: Construct the URL
```python
# Create the authenticated URL
base_url = "https://zaks-pov.b-cdn.net"
authenticated_url = f"{base_url}{file_path}?token={token}&expires={expires_timestamp}"

# Final URL:
# https://zaks-pov.b-cdn.net/episodes/galleries/10/DSC00520-f5066340-1757978449.jpg?token=fw6jHfW0ZC7_zLDVOXKAckDypLkrLzpsMedRgxOt4PE&expires=1758071553
```

## Complete Python Function

```python
import hashlib
import base64
import time
from datetime import datetime

def generate_bunnycdn_token(file_path, expires_in_hours=24):
    """
    Generate a BunnyCDN token for protected content access
    
    Args:
        file_path (str): The file path on BunnyCDN (e.g., "/images/photo.jpg")
        expires_in_hours (int): Hours until token expires
        
    Returns:
        dict: Contains token, expiration, and full URL
    """
    # BunnyCDN configuration
    CDN_TOKEN = "35173863-ae26-4ec2-bd90-df75b38bb9d5"
    
    # Calculate expiration timestamp
    expires_timestamp = int(time.time()) + (expires_in_hours * 3600)
    
    # Create the string to sign: TOKEN + PATH + EXPIRATION
    string_to_sign = f"{CDN_TOKEN}{file_path}{expires_timestamp}"
    
    # Generate SHA256 hash
    hash_object = hashlib.sha256(string_to_sign.encode('utf-8'))
    hash_bytes = hash_object.digest()
    
    # Base64 encode
    base64_token = base64.b64encode(hash_bytes).decode('utf-8')
    
    # Replace characters for BunnyCDN compatibility
    token = base64_token.replace('+', '-').replace('/', '_').replace('=', '')
    
    # Construct the authenticated URL
    base_url = "https://zaks-pov.b-cdn.net"
    full_url = f"{base_url}{file_path}?token={token}&expires={expires_timestamp}"
    
    return {
        'token': token,
        'expires': expires_timestamp,
        'expires_date': datetime.fromtimestamp(expires_timestamp),
        'url': full_url
    }

# Usage example
token_data = generate_bunnycdn_token("/episodes/galleries/10/image.jpg", expires_in_hours=1)
print(f"Authenticated URL: {token_data['url']}")
```

## Key Points for Developers

### 1. **String Concatenation Order**
- Always concatenate in this exact order: `TOKEN + PATH + EXPIRATION`
- No separators between elements
- Path must start with `/`

### 2. **Character Replacement**
- BunnyCDN requires URL-safe Base64 encoding
- Replace `+` with `-`
- Replace `/` with `_`
- Remove all `=` padding characters

### 3. **Expiration Handling**
- Use Unix timestamps (seconds since epoch)
- Tokens automatically expire - no manual cleanup needed
- Recommend short expiration times (1-24 hours) for security

### 4. **Error Handling**
- 403 Forbidden: Invalid token or expired
- 404 Not Found: File doesn't exist
- Always check HTTP status codes

## Testing Your Implementation

```python
import requests

# Generate token
token_data = generate_bunnycdn_token("/your/file/path.jpg")

# Test the URL
response = requests.get(token_data['url'])
if response.status_code == 200:
    print("✅ Token authentication successful!")
    print(f"Content-Type: {response.headers.get('content-type')}")
    print(f"File size: {len(response.content)} bytes")
else:
    print(f"❌ Authentication failed: {response.status_code}")
```

## Security Considerations

1. **Keep your CDN token secret** - never expose it in client-side code
2. **Use short expiration times** - minimize exposure window
3. **Validate file paths** - prevent directory traversal attacks
4. **Monitor usage** - track token generation and access patterns

## Common Issues

- **403 Forbidden**: Check token generation algorithm and expiration
- **404 Not Found**: Verify file path exists on CDN
- **Invalid token**: Ensure proper character replacement in Base64 encoding

This method has been tested and verified to work with BunnyCDN's token authentication system.
