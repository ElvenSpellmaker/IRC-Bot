#!/bin/bash
find -name '*.php' -exec sed -i 's/    /\t/g' {} +
git commit -a
