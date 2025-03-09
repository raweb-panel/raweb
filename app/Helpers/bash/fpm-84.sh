#!/bin/bash

WATCH_DIR="/etc/php/8.4/fpm/pool.d/"
TMP_DIR="/tmp"
PHP_FPM_SERVICE="php8.4-fpm"
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
        echo "Event detected: $EVENT on ${WATCH_DIR}${FILE}"
        EVENT_OCCURRED=1
        continue
    else
        # Timeout occurred
        if [ $EVENT_OCCURRED -eq 1 ]; then
            # Events have settled; proceed to validate and reload
            if php-fpm8.4 -t 2>/dev/null; then
                echo "Configuration is valid. Reloading PHP-FPM..."
                systemctl reload "$PHP_FPM_SERVICE"
            else
                echo "PHP-FPM configuration test failed."
                echo "Moving ${WATCH_DIR}${FILE} to $TMP_DIR"
                mv "${WATCH_DIR}${FILE}" "$TMP_DIR/"
                systemctl reload "$PHP_FPM_SERVICE"
                echo "Reloading PHP-FPM..."
            fi
            # Reset the event flag
            EVENT_OCCURRED=0
        fi
        # No events occurred; continue monitoring
    fi
done
