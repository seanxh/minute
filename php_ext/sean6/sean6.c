/*
  +----------------------------------------------------------------------+
  | PHP Version 5                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2013 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Author:                                                              |
  +----------------------------------------------------------------------+
*/

/* $Id$ */

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "php_sean6.h"

/* If you declare any globals in php_sean6.h uncomment this:
ZEND_DECLARE_MODULE_GLOBALS(sean6)
*/

/* True global resources - no need for thread safety here */
static int le_sean6;

/* {{{ sean6_functions[]
 *
 * Every user visible function must have an entry in sean6_functions[].
 */
const zend_function_entry sean6_functions[] = {
	PHP_FE(confirm_sean6_compiled,	NULL)		/* For testing, remove later. */
    PHP_FE(sean6_test_new_class_and_call, NULL)
	PHP_FE_END	/* Must be the last line in sean6_functions[] */
};
/* }}} */

zend_class_entry *myclass_ce;

ZEND_METHOD( myclass_ce , public_method ){
    php_printf("我是public类型的方法\n");
}

ZEND_METHOD( myclass_ce , __construct ){
    php_printf("我是__construct方法\n");
}

ZEND_METHOD(myclass_ce, call_self){
    zval *this,*ret = NULL;
    this = getThis();
    
    zval *function_name;
//    //函数名
    MAKE_STD_ZVAL(function_name);
    ZVAL_STRING(function_name, "public_method", 1);
    
    zend_fcall_info fci;
//    zend_fcall_info fci = {
//        sizeof(fci),
//        &Z_OBJCE_P(this)->function_table,
//        function_name,
//        NULL,
//        &ret,
//        0,
//        NULL,
//        this,
//        1
//    };
    
    fci.size =sizeof(fci);
    fci.function_table = &Z_OBJCE_P(this)->function_table;
    fci.function_name = function_name;
    fci.symbol_table = NULL;
    fci.retval_ptr_ptr = &ret;
    fci.param_count = 0;
    fci.params = NULL;
    fci.object_ptr = this;
    fci.no_separation =1;
    
    zend_call_function(&fci, NULL TSRMLS_CC);
    
    zval_dtor(function_name);
    efree(function_name);
    efree(ret);
}

static zend_function_entry myclass_method[]={
    ZEND_ME(myclass_ce,    public_method,  NULL,   ZEND_ACC_PUBLIC)
    ZEND_ME(myclass_ce,    __construct,    NULL,   ZEND_ACC_PUBLIC|ZEND_ACC_CTOR)
    ZEND_ME(myclass_ce, call_self, NULL, ZEND_ACC_PUBLIC)
    {NULL,NULL,NULL}
};

/* {{{ sean6_module_entry
 */
zend_module_entry sean6_module_entry = {
#if ZEND_MODULE_API_NO >= 20010901
	STANDARD_MODULE_HEADER,
#endif
	"sean6",
	sean6_functions,
	PHP_MINIT(sean6),
	PHP_MSHUTDOWN(sean6),
	PHP_RINIT(sean6),		/* Replace with NULL if there's nothing to do at request start */
	PHP_RSHUTDOWN(sean6),	/* Replace with NULL if there's nothing to do at request end */
	PHP_MINFO(sean6),
#if ZEND_MODULE_API_NO >= 20010901
	PHP_SEAN6_VERSION,
#endif
	STANDARD_MODULE_PROPERTIES
};
/* }}} */

#ifdef COMPILE_DL_SEAN6
ZEND_GET_MODULE(sean6)
#endif

/* {{{ PHP_INI
 */
/* Remove comments and fill if you need to have entries in php.ini
PHP_INI_BEGIN()
    STD_PHP_INI_ENTRY("sean6.global_value",      "42", PHP_INI_ALL, OnUpdateLong, global_value, zend_sean6_globals, sean6_globals)
    STD_PHP_INI_ENTRY("sean6.global_string", "foobar", PHP_INI_ALL, OnUpdateString, global_string, zend_sean6_globals, sean6_globals)
PHP_INI_END()
*/
/* }}} */

/* {{{ php_sean6_init_globals
 */
/* Uncomment this function if you have INI entries
static void php_sean6_init_globals(zend_sean6_globals *sean6_globals)
{
	sean6_globals->global_value = 0;
	sean6_globals->global_string = NULL;
}
*/
/* }}} */

/* {{{ PHP_MINIT_FUNCTION
 */
PHP_MINIT_FUNCTION(sean6)
{
	/* If you have INI entries, uncomment these lines 
	REGISTER_INI_ENTRIES();
	*/
    zend_class_entry ce;
    INIT_CLASS_ENTRY(ce,"Sean\\Test6",myclass_method);
    
    myclass_ce = zend_register_internal_class(&ce TSRMLS_CC);
    
    //定义属性
    zend_declare_property_null(myclass_ce, "public_var", strlen("public_var"), ZEND_ACC_PUBLIC TSRMLS_CC);
    
    
    zend_declare_property_null(myclass_ce, "protected_var", strlen("protected_var"), ZEND_ACC_PROTECTED TSRMLS_CC);
    
    zend_declare_property_null(myclass_ce, "private_var", strlen("private_var"), ZEND_ACC_PRIVATE TSRMLS_CC);
    
    zval *zv;
    //不能用MAKE_STD_ZVAL哦
//    MAKE_STD_ZVAL(zv);
    ALLOC_ZVAL(zv);
    ZVAL_STRING(zv,"test",1);
    
    zend_declare_class_constant(myclass_ce, "TEST",sizeof("TEST")-1, zv TSRMLS_DC);
//    zend_declare_class_constant_long(myclass_ce, "TEST",strlen("TEST"), 1 TSRMLS_DC);
    
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MSHUTDOWN_FUNCTION
 */
PHP_MSHUTDOWN_FUNCTION(sean6)
{
	/* uncomment this line if you have INI entries
	UNREGISTER_INI_ENTRIES();
	*/
    
    return SUCCESS;
}
/* }}} */

/* Remove if there's nothing to do at request start */
/* {{{ PHP_RINIT_FUNCTION
 */
PHP_RINIT_FUNCTION(sean6)
{
	return SUCCESS;
}
/* }}} */

/* Remove if there's nothing to do at request end */
/* {{{ PHP_RSHUTDOWN_FUNCTION
 */
PHP_RSHUTDOWN_FUNCTION(sean6)
{
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MINFO_FUNCTION
 */
PHP_MINFO_FUNCTION(sean6)
{
	php_info_print_table_start();
	php_info_print_table_header(2, "sean6 support", "enabled");
	php_info_print_table_end();

	/* Remove comments if you have entries in php.ini
	DISPLAY_INI_ENTRIES();
	*/
}
/* }}} */


/* Remove the following function when you have successfully modified config.m4
   so that your module can be compiled into PHP, it exists only for testing
   purposes. */

/* Every user-visible function in PHP should document itself in the source */
/* {{{ proto string confirm_sean6_compiled(string arg)
   Return a string to confirm that the module is compiled in */
PHP_FUNCTION(confirm_sean6_compiled)
{
	char *arg = NULL;
	int arg_len, len;
	char *strg;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &arg, &arg_len) == FAILURE) {
		return;
	}

	len = spprintf(&strg, 0, "Congratulations! You have successfully modified ext/%.78s/config.m4. Module %.78s is now compiled into PHP.", "sean6", arg);
	RETURN_STRINGL(strg, len, 0);
}

PHP_FUNCTION(sean6_test_new_class_and_call)
{
    zval *obj;
    MAKE_STD_ZVAL(obj);
    object_init_ex(obj, myclass_ce);
    
    zval *ret = NULL;
    
    zval *function_name;
    //函数名
    MAKE_STD_ZVAL(function_name);
    ZVAL_STRING(function_name, "public_method", 1);
    
    zend_fcall_info fci;
    fci.size =sizeof(fci);
    fci.function_table = &Z_OBJCE_P(obj)->function_table;
    fci.function_name = function_name;
    fci.symbol_table = NULL;
    fci.retval_ptr_ptr = &ret;
    fci.param_count = 0;
    fci.params = NULL;
    fci.object_ptr = obj;
    fci.no_separation =1;
    
    zend_call_function(&fci, NULL TSRMLS_CC);
    
    zval_dtor(function_name);
    zval_dtor(obj);
    efree(ret);

    
}
/* }}} */
/* The previous line is meant for vim and emacs, so it can correctly fold and 
   unfold functions in source code. See the corresponding marks just before 
   function definition, where the functions purpose is also documented. Please 
   follow this convention for the convenience of others editing your code.
*/


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
