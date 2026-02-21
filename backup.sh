#!/bin/bash

# MongoDB Backup Script
# Usage: ./backup.sh [mongo_uri]

MONGO_URI=${1:-$DB_URI}
BACKUP_DIR="/var/backups/mongo"
DATE=$(date +%Y-%m-%d_%H-%M-%S)

if [ -z "$MONGO_URI" ]; then
    echo "Error: Mongo URI not provided and DB_URI env var not set."
    exit 1
fi

mkdir -p $BACKUP_DIR

echo "📦 Backing up MongoDB to $BACKUP_DIR/$DATE..."
mongodump --uri="$MONGO_URI" --out="$BACKUP_DIR/$DATE"

# Keep only last 7 days
find $BACKUP_DIR -maxdepth 1 -type d -mtime +7 -exec rm -rf {} +

echo "✅ Backup complete!"
