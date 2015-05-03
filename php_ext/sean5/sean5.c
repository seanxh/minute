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
#include "php_sean5.h"

/* If you declare any globals in php_sean5.h uncomment this:
ZEND_DECLARE_MODULE_GLOBALS(sean5)
*/

/* True global resources - no need for thread safety here */
static int le_sean5;

/* {{{ sean5_functions[]
 *
 * Every user visible function must have an entry in sean5_functions[].
 */
const zend_function_entry sean5_functions[] = {
	PHP_FE(confirm_sean5_compiled,	NULL)		/* For testing, remove later. */
    PHP_FE(sean5_return_simple_array, NULL)
    PHP_FE(sean5_return_complex_array, NULL)
    PHP_FE(sean5_get_user_array,NULL)
	PHP_FE_END	/* Must be the last line in sean5_functions[] */
};
/* }}} */

/* {{{ sean5_module_entry
 */
zend_module_entry sean5_module_entry = {
#if ZEND_MODULE_API_NO >= 20010901
	STANDARD_MODULE_HEADER,
#endif
	"sean5",
	sean5_functions,
	PHP_MINIT(sean5),
	PHP_MSHUTDOWN(sean5),
	PHP_RINIT(sean5),		/* Replace with NULL if there's nothing to do at request start */
	PHP_RSHUTDOWN(sean5),	/* Replace with NULL if there's nothing to do at request end */
	PHP_MINFO(sean5),
#if ZEND_MODULE_API_NO >= 20010901
	PHP_SEAN5_VERSION,
#endif
	STANDARD_MODULE_PROPERTIES
};
/* }}} */

#ifdef COMPILE_DL_SEAN5
ZEND_GET_MODULE(sean5)
#endif

/* {{{ PHP_INI
 */
/* Remove comments and fill if you need to have entries in php.ini
PHP_INI_BEGIN()
    STD_PHP_INI_ENTRY("sean5.global_value",      "42", PHP_INI_ALL, OnUpdateLong, global_value, zend_sean5_globals, sean5_globals)
    STD_PHP_INI_ENTRY("sean5.global_string", "foobar", PHP_INI_ALL, OnUpdateString, global_string, zend_sean5_globals, sean5_globals)
PHP_INI_END()
*/
/* }}} */

/* {{{ php_sean5_init_globals
 */
/* Uncomment this function if you have INI entries
static void php_sean5_init_globals(zend_sean5_globals *sean5_globals)
{
	sean5_globals->global_value = 0;
	sean5_globals->global_string = NULL;
}
*/
/* }}} */

/* {{{ PHP_MINIT_FUNCTION
 */
PHP_MINIT_FUNCTION(sean5)
{
	/* If you have INI entries, uncomment these lines 
	REGISTER_INI_ENTRIES();
	*/
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MSHUTDOWN_FUNCTION
 */
PHP_MSHUTDOWN_FUNCTION(sean5)
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
PHP_RINIT_FUNCTION(sean5)
{
	return SUCCESS;
}
/* }}} */

/* Remove if there's nothing to do at request end */
/* {{{ PHP_RSHUTDOWN_FUNCTION
 */
PHP_RSHUTDOWN_FUNCTION(sean5)
{
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MINFO_FUNCTION
 */
PHP_MINFO_FUNCTION(sean5)
{
	php_info_print_table_start();
	php_info_print_table_header(2, "sean5 support", "enabled");
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
/* {{{ proto string confirm_sean5_compiled(string arg)
   Return a string to confirm that the module is compiled in */
PHP_FUNCTION(confirm_sean5_compiled)
{
	char *arg = NULL;
	int arg_len, len;
	char *strg;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &arg, &arg_len) == FAILURE) {
		return;
	}

	len = spprintf(&strg, 0, "Congratulations! You have successfully modified ext/%.78s/config.m4. Module %.78s is now compiled into PHP.", "sean5", arg);
	RETURN_STRINGL(strg, len, 0);
}
/* }}} */
/* The previous line is meant for vim and emacs, so it can correctly fold and 
   unfold functions in source code. See the corresponding marks just before 
   function definition, where the functions purpose is also documented. Please 
   follow this convention for the convenience of others editing your code.
*/
PHP_FUNCTION(sean5_return_simple_array){
    array_init(return_value);
    add_next_index_long(return_value, 1);
}

PHP_FUNCTION(sean5_return_complex_array){
    zval *subarray;
    
    array_init(return_value);
    
    /* Add some scalars */
    add_assoc_long(return_value, "life", 42);
    add_index_bool(return_value, 123, 1);
    add_next_index_double(return_value, 3.1415926535);
    
    /* Toss in a static string, dup'd by PHP */
    add_next_index_string(return_value, "Foo", 1);
    
    /* Now a manually dup'd string */
    add_next_index_string(return_value, estrdup("Bar"), 0);
    
    /* Create a subarray */
    MAKE_STD_ZVAL(subarray);
    array_init(subarray);
    
    /* Populate it with some numbers */
    add_next_index_long(subarray, 1);
    add_next_index_long(subarray, 20);
    add_next_index_long(subarray, 300);
    
    /* Place the subarray in the parent */
    add_index_zval(return_value, 444, subarray);
}

int php_foreach(zval **val,int num_args,va_list args,zend_hash_key *hash_key){
    //重新copy一个zval，防止破坏原数据
    zval tmpcopy = **val;
    /* tsrm_ls is needed by output functions */
    TSRMLS_FETCH();
    zval_copy_ctor(&tmpcopy);
    INIT_PZVAL(&tmpcopy);
    
    //执行输出
    if (hash_key->nKeyLength){
        //如果是字符串类型的key
        PHPWRITE(hash_key->arKey, hash_key->nKeyLength);
    }else{
        //如果是数字类型的key
        php_printf("%ld", hash_key->h);
    }
    
    php_printf("=>");
    
    if(Z_TYPE(tmpcopy) == IS_STRING ){
        PHPWRITE(Z_STRVAL(tmpcopy), Z_STRLEN(tmpcopy));
    }else if(Z_TYPE(tmpcopy) == IS_LONG){
        php_printf("%ld", Z_LVAL(tmpcopy));
    }else if(Z_TYPE(tmpcopy) == IS_DOUBLE){
        php_printf("%f", Z_DVAL(tmpcopy));
    }else{
        php_printf("unsupport type");
    }
    
    php_printf("\n");
    
    //毁尸灭迹
    zval_dtor(&tmpcopy);
    /* continue; */
    return ZEND_HASH_APPLY_KEEP;
}


PHP_FUNCTION(sean5_get_user_array){
 
    zval *vars = NULL;
    
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &vars) == FAILURE) {
        return;
    }
    
    HashTable *ht_tmp = Z_ARRVAL_P(vars);
    zend_hash_apply_with_arguments(ht_tmp,php_foreach, 0);
    
}


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
