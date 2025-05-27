$version=$args[0]
if ($version -eq $null) {
  echo "MUST specify the deployment method as the first argument: docker or vagrant";
  exit 1;
}
# paths are project root relative
Copy-Item (Join-Path src/install/podman/example.dev-env "*") .dev-env -Exclude (Get-ChildItem ./)
Copy-Item (Join-Path src/install/saf/example.$version.global.config.php "*") config/local-instance.global.config.php -Exclude (Get-ChildItem config/)
Copy-Item (Join-Path src/install/saf/example.local-instance.$version.root.php "*") config/kickstart/local-instance.root.php -Exclude (Get-ChildItem config/)
Copy-Item (Join-Path src/install/saf/example.local-dev.debug.root.php "*") local-dev.debug.root.php -Exclude (Get-ChildItem ./)