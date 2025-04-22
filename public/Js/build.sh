#!/bin/bash

npx google-closure-compiler \
--language_in ECMASCRIPT_NEXT \
--language_out ECMASCRIPT_2017 \
--compilation_level SIMPLE_OPTIMIZATIONS \
--js "script.js" \
--js "loginCheck.js" \
--js "logout.js" \
--js "common.js" \
--js "list.js" \
--js_output_file "log.js";
echo "OK"