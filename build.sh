#!/bin/sh

BUILD=`php index.php build | tail -1`
cp -r $BUILD /tmp

git checkout gh-pages
cp -r /tmp/$BUILD/* .
git add .
git commit -m "New build"
git push origin gh-pages
git checkout master
