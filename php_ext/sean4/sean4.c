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
#include "php_sean4.h"

/* If you declare any globals in php_sean4.h uncomment this:
ZEND_DECLARE_MODULE_GLOBALS(sean4)
*/

/* True global resources - no need for thread safety here */
static int le_sean4;

/* {{{ sean4_functions[]
 *
 * Every user visible function must have an entry in sean4_functions[].
 */
const zend_function_entry sean4_functions[] = {
	PHP_FE(confirm_sean4_compiled,	NULL)		/* For testing, remove later. */
    PHP_FE(sean4_iterator_hash_table,NULL)
	PHP_FE_END	/* Must be the last line in sean4_functions[] */
};
/* }}} */

/* {{{ sean4_module_entry
 */
zend_module_entry q = {
#if ZEND_MODULE_API_NO >= 20010901
	STANDARD_MODULE_HEADER,
#endif
	"sean4",
	sean4_functions,
	PHP_MINIT(sean4),
	PHP_MSHUTDOWN(sean4),
	PHP_RINIT(sean4),		/* Replace with NULL if there's nothing to do at request start */
	PHP_RSHUTDOWN(sean4),	/* Replace with NULL if there's nothing to do at request end */
	PHP_MINFO(sean4),
#if ZEND_MODULE_API_NO >= 20010901
	PHP_SEAN4_VERSION,
#endif
	STANDARD_MODULE_PROPERTIES
};
/* }}} */

#ifdef COMPILE_DL_SEAN4
ZEND_GET_MODULE(sean4)
#endif

/* {{{ PHP_INI
 */
/* Remove comments and fill if you need to have entries in php.ini
PHP_INI_BEGIN()
    STD_PHP_INI_ENTRY("sean4.global_value",      "42", PHP_INI_ALL, OnUpdateLong, global_value, zend_sean4_globals, sean4_globals)
    STD_PHP_INI_ENTRY("sean4.global_string", "foobar", PHP_INI_ALL, OnUpdateString, global_string, zend_sean4_globals, sean4_globals)
PHP_INI_END()
*/
/* }}} */

/* {{{ php_sean4_init_globals
 */
/* Uncomment this function if you have INI entries
static void php_sean4_init_globals(zend_sean4_globals *sean4_globals)
{
	sean4_globals->global_value = 0;
	sean4_globals->global_string = NULL;
}
*/
/* }}} */

/* {{{ PHP_MINIT_FUNCTION
 */
PHP_MINIT_FUNCTION(sean4)
{
	/* If you have INI entries, uncomment these lines 
	REGISTER_INI_ENTRIES();
	*/
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MSHUTDOWN_FUNCTION
 */
PHP_MSHUTDOWN_FUNCTION(sean4)
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
PHP_RINIT_FUNCTION(sean4)
{
	return SUCCESS;
}
/* }}} */

/* Remove if there's nothing to do at request end */
/* {{{ PHP_RSHUTDOWN_FUNCTION
 */
PHP_RSHUTDOWN_FUNCTION(sean4)
{
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MINFO_FUNCTION
 */
PHP_MINFO_FUNCTION(sean4)
{
	php_info_print_table_start();
	php_info_print_table_header(2, "sean4 support", "enabled");
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
/* {{{ proto string confirm_sean4_compiled(string arg)
   Return a string to confirm that the module is compiled in */
PHP_FUNCTION(confirm_sean4_compiled)
{
	char *arg = NULL;
	int arg_len, len;
	char *strg;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &arg, &arg_len) == FAILURE) {
		return;
	}

	len = spprintf(&strg, 0, "Congratulations! You have successfully modified ext/%.78s/config.m4. Module %.78s is now compiled into PHP.", "sean4", arg);
	RETURN_STRINGL(strg, len, 0);
}
/* }}} */
/* The previous line is meant for vim and emacs, so it can correctly fold and 
   unfold functions in source code. See the corresponding marks just before 
   function definition, where the functions purpose is also documented. Please 
   follow this convention for the convenience of others editing your code.
*/

int php_print_r_zval_and_key(zval **val,int num_args,va_list args,zend_hash_key *hash_key)
{
    //重新copy一个zval，防止破坏原数据
    zval tmpcopy = **val;
    zval *tmpcopy_ptr = &tmpcopy;
    
    /* tsrm_ls is needed by output functions */
    TSRMLS_FETCH();
    zval_copy_ctor(&tmpcopy);
    INIT_PZVAL(&tmpcopy);
    
    //执行输出
    php_printf("The value of ");
    if (hash_key->nKeyLength){
        //如果是字符串类型的key
        PHPWRITE(hash_key->arKey, hash_key->nKeyLength);
    }else{
        //如果是数字类型的key
        php_printf("%ld", hash_key->h);
    }
    
    php_printf(" is: ");
    
    //调用print_r函数
    zval *function,*ret = NULL;
    //函数名
    MAKE_STD_ZVAL(function);
    ZVAL_STRING(function, "print_r", 0);
    
    //参数是一个zval* 数组
    zval **params[1] = {&tmpcopy_ptr};
    
    zend_fcall_info fci = {
        sizeof(fci),
        EG(function_table),
        function,
        NULL,
        &ret,
        1,
        (zval ***)params,
        NULL,
        1
    };
    zend_call_function(&fci, NULL TSRMLS_CC);
    
    php_printf("\n");
    zval_dtor(function);
    
    //毁尸灭迹
    zval_dtor(&tmpcopy);
    /* continue; */
    return ZEND_HASH_APPLY_KEEP;
}

PHP_FUNCTION(sean4_iterator_hash_table){
    zend_hash_apply_with_arguments(&EG(symbol_table),php_print_r_zval_and_key, 0);
}


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
