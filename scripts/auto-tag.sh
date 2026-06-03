#!/usr/bin/env bash
#
# Tag the current src/Version.php release if it hasn't been tagged yet.
# Designed to run from CI on every push to main.
#
# Source of truth is the set of git tags on origin. If a tag matching
# the current VERSION constant already exists, this script no-ops. If
# not, it creates an annotated tag and pushes it — which triggers the
# Packagist webhook so the new release becomes installable within ~1
# minute.
#
# Requires push permission to origin. Bitbucket Pipelines injects
# credentials with repo:write scope into the cloned repo by default;
# no extra setup is needed for same-repo pushes.

set -euo pipefail

VERSION_FILE="src/Version.php"

CURRENT=$(sed -n "s/^[[:space:]]*public const VERSION = '\([^']*\)'.*/\1/p" "$VERSION_FILE")
if [ -z "$CURRENT" ]; then
    echo "ERROR: could not parse VERSION from $VERSION_FILE" >&2
    exit 1
fi

TAG="v$CURRENT"

# CI clones are typically shallow; pull all tag refs so the check below
# sees tags created from past releases.
git fetch --tags --quiet origin

if git rev-parse --verify "refs/tags/$TAG" >/dev/null 2>&1; then
    echo "Tag $TAG already exists. Nothing to do."
    exit 0
fi

git config user.email "dev@mailchannels.com"
git config user.name "MailChannels Dev"

echo "No tag for current version $CURRENT. Tagging $TAG."
git tag -a "$TAG" -m "Release $TAG"
git push origin "$TAG"

echo "Pushed $TAG."
