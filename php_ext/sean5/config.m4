dnl $Id$
dnl config.m4 for extension sean5

dnl Comments in this file start with the string 'dnl'.
dnl Remove where necessary. This file will not work
dnl without editing.

dnl If your extension references something external, use with:

PHP_ARG_WITH(sean5, for sean5 support,
Make sure that the comment is aligned:
[  --with-sean5             Include sean5 support])

dnl Otherwise use enable:

PHP_ARG_ENABLE(sean5, whether to enable sean5 support,
Make sure that the comment is aligned:
[  --enable-sean5           Enable sean5 support])

if test "$PHP_SEAN5" != "no"; then
  dnl Write more examples of tests here...

  dnl # --with-sean5 -> check with-path
  dnl SEARCH_PATH="/usr/local /usr"     # you might want to change this
  dnl SEARCH_FOR="/include/sean5.h"  # you most likely want to change this
  dnl if test -r $PHP_SEAN5/$SEARCH_FOR; then # path given as parameter
  dnl   SEAN5_DIR=$PHP_SEAN5
  dnl else # search default path list
  dnl   AC_MSG_CHECKING([for sean5 files in default path])
  dnl   for i in $SEARCH_PATH ; do
  dnl     if test -r $i/$SEARCH_FOR; then
  dnl       SEAN5_DIR=$i
  dnl       AC_MSG_RESULT(found in $i)
  dnl     fi
  dnl   done
  dnl fi
  dnl
  dnl if test -z "$SEAN5_DIR"; then
  dnl   AC_MSG_RESULT([not found])
  dnl   AC_MSG_ERROR([Please reinstall the sean5 distribution])
  dnl fi

  dnl # --with-sean5 -> add include path
  dnl PHP_ADD_INCLUDE($SEAN5_DIR/include)

  dnl # --with-sean5 -> check for lib and symbol presence
  dnl LIBNAME=sean5 # you may want to change this
  dnl LIBSYMBOL=sean5 # you most likely want to change this 

  dnl PHP_CHECK_LIBRARY($LIBNAME,$LIBSYMBOL,
  dnl [
  dnl   PHP_ADD_LIBRARY_WITH_PATH($LIBNAME, $SEAN5_DIR/lib, SEAN5_SHARED_LIBADD)
  dnl   AC_DEFINE(HAVE_SEAN5LIB,1,[ ])
  dnl ],[
  dnl   AC_MSG_ERROR([wrong sean5 lib version or lib not found])
  dnl ],[
  dnl   -L$SEAN5_DIR/lib -lm
  dnl ])
  dnl
  dnl PHP_SUBST(SEAN5_SHARED_LIBADD)

  PHP_NEW_EXTENSION(sean5, sean5.c, $ext_shared)
fi
