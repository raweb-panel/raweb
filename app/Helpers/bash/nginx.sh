#!/bin/bash

WATCH_DIR="/nginx/live"
TMP_DIR="/tmp"
NGINX_SERVICE="nginx"
DEBOUNCE_TIME=2   # seconds to wait before reloading
EVENT_OCCURRED=0  # Flag to track if an event has occurred

# Ensure inotify-tools is installed
if ! command -v inotifywait &>/dev/null; then
    echo "Error: inotify-tools is not installed. Install it using: apt install inotify-tools"
    exit 1
fi

echo "Watching $WATCH_DIR for changes..."

while true; do
    EVENT_OUTPUT=$(inotifywait -t "$DEBOUNCE_TIME" \
        -e create -e modify -e delete \
        --format "%T %e %f" \
        --timefmt "%s" \
        "$WATCH_DIR" 2>/dev/null)
    
    if [ $? -eq 0 ]; then
        IFS=" " read -r TIMESTAMP EVENT FILE <<< "$EVENT_OUTPUT"
        echo "Event detected: $EVENT on ${WATCH_DIR}/${FILE}"
        EVENT_OCCURRED=1
        continue
    else
        # Timeout occurred
        if [ $EVENT_OCCURRED -eq 1 ]; then
            # Events have settled; proceed to validate and reload
            if nginx -t 2>/dev/null; then
                echo "Configuration is valid. Reloading Nginx..."
                systemctl reload "$NGINX_SERVICE"
            else
                echo "Nginx configuration test failed. Moving ${WATCH_DIR}/${FILE} to $TMP_DIR"
                mv "${WATCH_DIR}/${FILE}" "$TMP_DIR/"
                systemctl reload "$NGINX_SERVICE"
                echo "Reloading Nginx..."
            fi
            # Reset the event flag
            EVENT_OCCURRED=0
        fi
        # No events occurred; continue monitoring
    fi
done
