if [ -z "$1" ]; then
  echo "MUST specify the main application name as the first argument";
  exit 1;
fi
for c in $@; do
podman network connect $1 $c
done