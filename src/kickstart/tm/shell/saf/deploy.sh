#!/bin/bash
if [ -z "$1" ]; then
  echo "MUST specify the deployment method as th vfirst argument: docker or vagrant";
  exit 1;
fi
# paths are project root relative
cp -n src/install/podman/example.dev-env .dev-env
cp -n src/install/saf/example.$1.global.config.php config/local-instance.global.config.php
cp -n src/install/saf/example.local-instance.$1.root.php config/kickstart/local-instance.root.php
cp -n src/install/saf/example.local-dev.debug.root.php local-dev.debug.root.php
# the --no-clobber flag/alias for -n isn't on Mac/Unix
# cp --no-clobber src/install/podman/example.dev-env local-dev.dev-env
# cp --no-clobber src/install/saf/example.$1.global.config.php config/local-instance.global.config.php
# cp --no-clobber src/install/saf/example.local-instance.$1.root.php config/kickstart/local-instance.root.php
# cp --no-clobber src/install/saf/example.local-dev.debug.root.php local-dev.debug.root.php