#!/bin/bash

targetBranch=$1
currentReleasePath=$2

cd "$currentReleasePath" || exit 0

currentBranch=$(git branch --show-current)
readarray -t currentBranchMigrations <<< "$(git ls-tree -r --name-only "$currentBranch" src/Migrations)"
readarray -t targetBranchMigrations <<< "$(git ls-tree -r --name-only "origin/$targetBranch" src/Migrations)"

mapfile -t migrationsToDown < <(grep -vxFf <(printf '%s\n' "${targetBranchMigrations[@]}") <(printf '%s\n' "${currentBranchMigrations[@]}"))

#reverse order
for ((i=${#migrationsToDown[@]} - 1; i >= 0; i--));
do
  php bin/console doctrine:migrations:execute "$(echo "${migrationsToDown[$i]}" | tr -dc '0-9')" --down
done
