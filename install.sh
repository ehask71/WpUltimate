#!/bin/sh
#########################################################
# WP-Ultimate                                           #
# Copyright 2012-2013, Rackspeed Web Development        #
# URL: http://www.wp-ultimate.com                       #
# Email: sales@rackspeed.net                            #
#########################################################

echo 
echo "Installing WP-Ultimate"
echo
echo "copyright 2012-2013 RackSPEED"
echo 

echo "Checking that we're running as root"
if [ ! `id -u` = 0 ]; then
        echo
        echo "FAILED: You have to be logged in as root (UID:0) to install WP-Ultimate"
        exit
fi
echo

if [ ! -e "/etc/wpu" ]; then
        mkdir -v /etc/wpu
fi

##
##  Copy Files & Dirs
##
cp -avf apps /etc/wpu/
cp -avf inc /etc/wpu/
cp -avf skel /etc/wpu/
cp -avf wpu.conf /etc/wpu/
cp -avf hooks.php /etc/wpu/
cp -avf xmlapi.php /etc/wpu/
cp -avf utils.php /etc/wpu/
## GUI
cp -avf wpu-ui.php /etc/wpu/
cp -avf addon_wpu.php /usr/local/cpanel/whostmgr/docroot/cgi/
cp -avf wpu /usr/local/cpanel/whostmgr/docroot/cgi/
##
## Set Permissions
## 
chmod +x /etc/wpu/hooks.php

##
## Install Hooks
##
/usr/local/cpanel/bin/manage_hooks add script /etc/wpu/hooks.php


##
## Wordpress Fetch
##
read -p "Do you want to get the latest Wordpress? (y/n): " RESP
if [ "$RESP" = "y" ]; then
    echo "Fetching Latest Wordpress from Wordpress.com"
    mkdir wputemp
    cd wputemp
    wget http://wordpress.org/latest.tar.gz
    tar -xvzf latest.tar.gz
    if [ -e "wordpress" ]; then
        echo "Moving Files to Skel directory!"
        if [ ! -e "/etc/wpu/skel/wp-skel/public_html/wp-admin" ]; then
                mv -f wordpress/* /etc/wpu/skel/wp-skel/public_html/
        else
                ## Old version is there try rsync
                echo "Existing files detected... Switching to rsync"
                sleep 5
                rsync -avh wordpress/* /etc/wpu/skel/wp-skel/public_html/
                echo "Rsync completed!"
        fi
        echo "Done!"
    fi
else
    echo
    echo "Please upload your copy to /etc/wpu/skel/wp-skel/public_html"
    echo
fi
cd /etc/wpu
##
##  Zend Framework 2
##
echo 
read -p "Do you want to get the latest Zend Framework 2? (y/n): " RESP
echo
if [ "$RESP" = "y" ]; then
    if [ ! -e "wputemp" ]; then
	mkdir wputemp
    fi
    cd wputemp
    wget https://github.com/zendframework/zf2/archive/master.zip
    unzip master
    if [ -e "zf2-master" ]; then
	echo "Moving Files to Skel directory!"
    fi
else
    echo
    echo "Please upload your copy to /etc/wpu/skel/zf2-skel/vendor/ZF2"
    echo
fi