if [ -z "$1" ]; then
  echo "MUST specify repository URI as the first argument";
  exit 1;
fi
if [ -z "$2" ]; then
  echo "MUST specify repository username as the second argument";
  exit 1;
fi
if [ -z "$3" ]; then
  echo "MUST specify repository password as the third argument";
  exit 1;
fi
podman login -u $2 -p $3 $1