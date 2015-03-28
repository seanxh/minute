dnl $Id$
dnl config.m4 for extension sean2

dnl Comments in this file start with the string 'dnl'.
dnl Remove where necessary. This file will not work
dnl without editing.

dnl If your extension references something external, use with:

dnl PHP_ARG_WITH(sean2, for sean2 support,
dnl Make sure that the comment is aligned:
dnl [  --with-sean2             Include sean2 support])

dnl Otherwise use enable:

PHP_ARG_ENABLE(sean2, whether to enable sean2 support,
Make sure that the comment is aligned:
[  --enable-sean2           Enable sean2 support])

if test "$PHP_SEAN2" != "no"; then
  dnl Write more examples of tests here...

  dnl # --with-sean2 -> check with-path
  dnl SEARCH_PATH="/usr/local /usr"     # you might want to change this
  dnl SEARCH_FOR="/include/sean2.h"  # you most likely want to change this
  dnl if test -r $PHP_SEAN2/$SEARCH_FOR; then # path given as parameter
  dnl   SEAN2_DIR=$PHP_SEAN2
  dnl else # search default path list
  dnl   AC_MSG_CHECKING([for sean2 files in default path])
  dnl   for i in $SEARCH_PATH ; do
  dnl     if test -r $i/$SEARCH_FOR; then
  dnl       SEAN2_DIR=$i
  dnl       AC_MSG_RESULT(found in $i)
  dnl     fi
  dnl   done
  dnl fi
  dnl
  dnl if test -z "$SEAN2_DIR"; then
  dnl   AC_MSG_RESULT([not found])
  dnl   AC_MSG_ERROR([Please reinstall the sean2 distribution])
  dnl fi

  dnl # --with-sean2 -> add include path
  dnl PHP_ADD_INCLUDE($SEAN2_DIR/include)

  dnl # --with-sean2 -> check for lib and symbol presence
  dnl LIBNAME=sean2 # you may want to change this
  dnl LIBSYMBOL=sean2 # you most likely want to change this 

  dnl PHP_CHECK_LIBRARY($LIBNAME,$LIBSYMBOL,
  dnl [
  dnl   PHP_ADD_LIBRARY_WITH_PATH($LIBNAME, $SEAN2_DIR/lib, SEAN2_SHARED_LIBADD)
  dnl   AC_DEFINE(HAVE_SEAN2LIB,1,[ ])
  dnl ],[
  dnl   AC_MSG_ERROR([wrong sean2 lib version or lib not found])
  dnl ],[
  dnl   -L$SEAN2_DIR/lib -lm
  dnl ])
  dnl
  dnl PHP_SUBST(SEAN2_SHARED_LIBADD)

  PHP_NEW_EXTENSION(sean2, sean2.c, $ext_shared)
fi
