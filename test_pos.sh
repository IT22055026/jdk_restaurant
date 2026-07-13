#!/bin/bash

# Get login page to extract CSRF token
LOGIN_PAGE=$(curl -s http://127.0.0.1:8000/login)
CSRF=$(echo "$LOGIN_PAGE" | grep -oP 'name="_token" value="\K[^"]+' | head -1)

echo "CSRF Token: $CSRF"

# Try to login (you may need to adjust credentials)
curl -s -c /tmp/cookies.txt -b /tmp/cookies.txt \
  -X POST http://127.0.0.1:8000/login \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=admin@example.com&password=password&_token=$CSRF" \
  | head -20

echo "---"
echo "Checking if login was successful..."
curl -s -b /tmp/cookies.txt http://127.0.0.1:8000/pos | grep -q "POS" && echo "✓ POS page accessible" || echo "✗ Not logged in"
