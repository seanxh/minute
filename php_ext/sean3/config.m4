dnl $Id$
dnl config.m4 for extension sean3

dnl Comments in this file start with the string 'dnl'.
dnl Remove where necessary. This file will not work
dnl without editing.

dnl If your extension references something external, use with:

PHP_ARG_WITH(sean3, for sean3 support,
Make sure that the comment is aligned:
[  --with-sean3             Include sean3 support])

dnl Otherwise use enable:

PHP_ARG_ENABLE(sean3, whether to enable sean3 support,
Make sure that the comment is aligned:
[  --enable-sean3           Enable sean3 support])

if test "$PHP_SEAN3" != "no"; then
  dnl Write more examples of tests here...

  dnl # --with-sean3 -> check with-path
  dnl SEARCH_PATH="/usr/local /usr"     # you might want to change this
  dnl SEARCH_FOR="/include/sean3.h"  # you most likely want to change this
  dnl if test -r $PHP_SEAN3/$SEARCH_FOR; then # path given as parameter
  dnl   SEAN3_DIR=$PHP_SEAN3
  dnl else # search default path list
  dnl   AC_MSG_CHECKING([for sean3 files in default path])
  dnl   for i in $SEARCH_PATH ; do
  dnl     if test -r $i/$SEARCH_FOR; then
  dnl       SEAN3_DIR=$i
  dnl       AC_MSG_RESULT(found in $i)
  dnl     fi
  dnl   done
  dnl fi
  dnl
  dnl if test -z "$SEAN3_DIR"; then
  dnl   AC_MSG_RESULT([not found])
  dnl   AC_MSG_ERROR([Please reinstall the sean3 distribution])
  dnl fi

  dnl # --with-sean3 -> add include path
  dnl PHP_ADD_INCLUDE($SEAN3_DIR/include)

  dnl # --with-sean3 -> check for lib and symbol presence
  dnl LIBNAME=sean3 # you may want to change this
  dnl LIBSYMBOL=sean3 # you most likely want to change this 

  dnl PHP_CHECK_LIBRARY($LIBNAME,$LIBSYMBOL,
  dnl [
  dnl   PHP_ADD_LIBRARY_WITH_PATH($LIBNAME, $SEAN3_DIR/lib, SEAN3_SHARED_LIBADD)
  dnl   AC_DEFINE(HAVE_SEAN3LIB,1,[ ])
  dnl ],[
  dnl   AC_MSG_ERROR([wrong sean3 lib version or lib not found])
  dnl ],[
  dnl   -L$SEAN3_DIR/lib -lm
  dnl ])
  dnl
  dnl PHP_SUBST(SEAN3_SHARED_LIBADD)

  PHP_NEW_EXTENSION(sean3, sean3.c, $ext_shared)
fi
