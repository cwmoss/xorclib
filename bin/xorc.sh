#!/bin/sh
if [ -z "$XORC_HOME" ] ; then
  XORC_HOME="@PEAR-DIR@"
fi

if (test -z "$PHP_COMMAND") ; then
  # echo "WARNING: PHP_COMMAND environment not set. (Assuming php on PATH)"
  export PHP_COMMAND=@PHP-BIN@
fi

#if (test -z "$PHP_CLASSPATH") ; then
#  PHP_CLASSPATH=$XORC_HOME/lib
#  export PHP_CLASSPATH
#fi

$PHP_COMMAND -d html_errors=off -qC $XORC_HOME/xorc/bin/xorc $*