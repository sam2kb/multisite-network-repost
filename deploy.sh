#!/bin/bash

rm -rf svn/trunk/*
rm -rf svn/assets/*
cp -a ./LICENSE  multisite-network-repost.php readme.txt svn/trunk/
cp -a ./assets/* svn/assets/

cd svn
svn cp trunk tags/$1
svn ci -m "Released v$1"