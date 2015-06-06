#!/bin/sh

BUILD=`php index.php build | tail -1`

rm -r /tmp/jerry-build
mkdir /tmp/jerry-build
cp -r $BUILD/* /tmp/jerry-build

git checkout gh-pages
cp -r /tmp/jerry-build/* .
git add .
git commit -m "New build"
git push origin gh-pages
git checkout master
