if [ -z "$1" ]; then
  echo "MUST specify repository URI as the first argument";
  exit 1;
fi
if [ -z "$2" ]; then
  echo "MUST specify repository project/image as the second argument";
  exit 1;
fi
podman pull $1/$2/${3:-$2}:latest