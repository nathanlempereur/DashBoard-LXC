#!/bin/bash

for conteneurs in $(lxc-ls);do
	lxc-start -n $conteneurs
done
