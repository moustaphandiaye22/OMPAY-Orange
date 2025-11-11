#!/bin/bash

# Script to build Docker image, tag it, and push to Docker Hub

# Default values (can be overridden with environment variables)
DOCKER_USERNAME=${DOCKER_USERNAME:-moustaphafullstacker}
DOCKER_REPO=${DOCKER_REPO:-ompay-orange-money}
DOCKER_TAG=${DOCKER_TAG:-latest}
DOCKER_PASSWORD=${DOCKER_PASSWORD}

# Check if password is set
if [ -z "$DOCKER_PASSWORD" ]; then
    echo "Error: DOCKER_PASSWORD environment variable is not set."
    echo "Please set it with: export DOCKER_PASSWORD=your_docker_hub_token"
    exit 1
fi

# Full image name
IMAGE_NAME="$DOCKER_USERNAME/$DOCKER_REPO:$DOCKER_TAG"

echo "Building Docker image: $IMAGE_NAME"

# Build the image
docker build -t "$IMAGE_NAME" .

if [ $? -ne 0 ]; then
    echo "Error: Docker build failed."
    exit 1
fi

echo "Logging in to Docker Hub..."

# Login to Docker Hub
echo "$DOCKER_PASSWORD" | docker login -u "$DOCKER_USERNAME" --password-stdin

if [ $? -ne 0 ]; then
    echo "Error: Docker login failed."
    exit 1
fi

echo "Pushing image to Docker Hub..."

# Push the image
docker push "$IMAGE_NAME"

if [ $? -ne 0 ]; then
    echo "Error: Docker push failed."
    exit 1
fi

echo "Successfully built and pushed $IMAGE_NAME to Docker Hub."