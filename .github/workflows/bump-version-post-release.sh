#!/usr/bin/env bash
set -e

function bump_version() {
	BASE_VERSION="$1"

	if [[ "$BASE_VERSION" =~ -$ ]]; then
		echo "error: invalid version format: $BASE_VERSION"
		exit 1
	fi

	if [[ "$BASE_VERSION" == *"-"* ]]; then
		major_minor_patch="${BASE_VERSION%%-*}"
		suffix="${BASE_VERSION##*-}"

		if [[ "$suffix" =~ ^([a-zA-Z]+)([0-9]*)$ ]]; then
			type="${BASH_REMATCH[1]}"
			num="${BASH_REMATCH[2]}"

			if [[ "$num" == "" ]]; then
				new_version="$major_minor_patch-$type1"
			else
				new_version="$major_minor_patch-$type$((num+1))"
			fi
		else
			echo "error: unknown suffix format: $suffix"
			exit 1
		fi
	else
		IFS='.' read -r major minor patch <<< "$BASE_VERSION"
		new_version="$major.$minor.$((patch+1))"
	fi

	echo "$new_version"
}

cd "$1"
additional_info="$2"

version_regex='^(\s*public const VERSION = ")([^"]+)(";)'

BASE_VERSION="$(sed -nE "s/$version_regex/\2/p" "./src/ProxyServer.php")"

if [[ -z "$BASE_VERSION" ]]; then
	echo "error: VERSION not found in ProxyServer.php"
	exit 1
fi

NEW_VERSION="$(bump_version "$BASE_VERSION")"

sed -i -E "s/$version_regex/\1$NEW_VERSION\3/" "./src/ProxyServer.php"

git commit -m "Next: $NEW_VERSION" -m "$additional_info" --only "./src/ProxyServer.php"
