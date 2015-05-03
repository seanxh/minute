dnl $Id$
dnl config.m4 for extension sean6

dnl Comments in this file start with the string 'dnl'.
dnl Remove where necessary. This file will not work
dnl without editing.

dnl If your extension references something external, use with:

PHP_ARG_WITH(sean6, for sean6 support,
Make sure that the comment is aligned:
[  --with-sean6             Include sean6 support])

dnl Otherwise use enable:

PHP_ARG_ENABLE(sean6, whether to enable sean6 support,
Make sure that the comment is aligned:
[  --enable-sean6           Enable sean6 support])

if test "$PHP_SEAN6" != "no"; then
  dnl Write more examples of tests here...

  dnl # --with-sean6 -> check with-path
  dnl SEARCH_PATH="/usr/local /usr"     # you might want to change this
  dnl SEARCH_FOR="/include/sean6.h"  # you most likely want to change this
  dnl if test -r $PHP_SEAN6/$SEARCH_FOR; then # path given as parameter
  dnl   SEAN6_DIR=$PHP_SEAN6
  dnl else # search default path list
  dnl   AC_MSG_CHECKING([for sean6 files in default path])
  dnl   for i in $SEARCH_PATH ; do
  dnl     if test -r $i/$SEARCH_FOR; then
  dnl       SEAN6_DIR=$i
  dnl       AC_MSG_RESULT(found in $i)
  dnl     fi
  dnl   done
  dnl fi
  dnl
  dnl if test -z "$SEAN6_DIR"; then
  dnl   AC_MSG_RESULT([not found])
  dnl   AC_MSG_ERROR([Please reinstall the sean6 distribution])
  dnl fi

  dnl # --with-sean6 -> add include path
  dnl PHP_ADD_INCLUDE($SEAN6_DIR/include)

  dnl # --with-sean6 -> check for lib and symbol presence
  dnl LIBNAME=sean6 # you may want to change this
  dnl LIBSYMBOL=sean6 # you most likely want to change this 

  dnl PHP_CHECK_LIBRARY($LIBNAME,$LIBSYMBOL,
  dnl [
  dnl   PHP_ADD_LIBRARY_WITH_PATH($LIBNAME, $SEAN6_DIR/lib, SEAN6_SHARED_LIBADD)
  dnl   AC_DEFINE(HAVE_SEAN6LIB,1,[ ])
  dnl ],[
  dnl   AC_MSG_ERROR([wrong sean6 lib version or lib not found])
  dnl ],[
  dnl   -L$SEAN6_DIR/lib -lm
  dnl ])
  dnl
  dnl PHP_SUBST(SEAN6_SHARED_LIBADD)

  PHP_NEW_EXTENSION(sean6, sean6.c, $ext_shared)
fi
