#!/bin/bash

SUBDIRECTORY=eZ/Publish/Core/REST/Client
GIT_REMOTE=

git filter-branch -f --tag-name-filter cat --prune-empty --subdirectory-filter $SUBDIRECTORY -- --all
#[ ! -z "$GIT_REMOTE" ] || git push --force --tags $GIT_REMOTE master

