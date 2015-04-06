dnl $Id$
dnl config.m4 for extension sean4

dnl Comments in this file start with the string 'dnl'.
dnl Remove where necessary. This file will not work
dnl without editing.

dnl If your extension references something external, use with:

PHP_ARG_WITH(sean4, for sean4 support,
Make sure that the comment is aligned:
[  --with-sean4             Include sean4 support])

dnl Otherwise use enable:

PHP_ARG_ENABLE(sean4, whether to enable sean4 support,
Make sure that the comment is aligned:
[  --enable-sean4           Enable sean4 support])

if test "$PHP_SEAN4" != "no"; then
  dnl Write more examples of tests here...

  dnl # --with-sean4 -> check with-path
  dnl SEARCH_PATH="/usr/local /usr"     # you might want to change this
  dnl SEARCH_FOR="/include/sean4.h"  # you most likely want to change this
  dnl if test -r $PHP_SEAN4/$SEARCH_FOR; then # path given as parameter
  dnl   SEAN4_DIR=$PHP_SEAN4
  dnl else # search default path list
  dnl   AC_MSG_CHECKING([for sean4 files in default path])
  dnl   for i in $SEARCH_PATH ; do
  dnl     if test -r $i/$SEARCH_FOR; then
  dnl       SEAN4_DIR=$i
  dnl       AC_MSG_RESULT(found in $i)
  dnl     fi
  dnl   done
  dnl fi
  dnl
  dnl if test -z "$SEAN4_DIR"; then
  dnl   AC_MSG_RESULT([not found])
  dnl   AC_MSG_ERROR([Please reinstall the sean4 distribution])
  dnl fi

  dnl # --with-sean4 -> add include path
  dnl PHP_ADD_INCLUDE($SEAN4_DIR/include)

  dnl # --with-sean4 -> check for lib and symbol presence
  dnl LIBNAME=sean4 # you may want to change this
  dnl LIBSYMBOL=sean4 # you most likely want to change this 

  dnl PHP_CHECK_LIBRARY($LIBNAME,$LIBSYMBOL,
  dnl [
  dnl   PHP_ADD_LIBRARY_WITH_PATH($LIBNAME, $SEAN4_DIR/lib, SEAN4_SHARED_LIBADD)
  dnl   AC_DEFINE(HAVE_SEAN4LIB,1,[ ])
  dnl ],[
  dnl   AC_MSG_ERROR([wrong sean4 lib version or lib not found])
  dnl ],[
  dnl   -L$SEAN4_DIR/lib -lm
  dnl ])
  dnl
  dnl PHP_SUBST(SEAN4_SHARED_LIBADD)

  PHP_NEW_EXTENSION(sean4, sean4.c, $ext_shared)
fi
