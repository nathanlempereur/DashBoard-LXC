#!/bin/bash

for conteneurs in $(lxc-ls);do
	lxc-stop -n $conteneurs
done
