<?php

/**
1）merchant_private_key，商户私钥;merchant_public_key,商户公钥；商户需要按照《密钥对获取工具说明》操作并获取商户私钥，商户公钥。
2）demo提供的merchant_private_key、merchant_public_key是测试商户号1111110166的商户私钥和商户公钥，请商家自行获取并且替换；
3）使用商户私钥加密时需要调用到openssl_sign函数,需要在php_ini文件里打开php_openssl插件
4）php的商户私钥在格式上要求换行，如下所示；
*/

    $key_config['merchant_private_key'] = '-----BEGIN PRIVATE KEY-----
MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBALe5VyoyPsCg8tRJ
Y5til+6gxSu712KEjTW1Ou+9NZb078Qs+KsIDsmQobl+GkzPz/1EXgl5UYVh5SSJ
+pW+4kv52m50yMXJ2WP0xEjM/2CEcOaJJpxcdhYUii86yMhkCOBpncl8qh4ubpdc
hlftPcq0wOV4IbUafMwazJdBVwbLAgMBAAECgYA373zDQxLp8NadnU5vM4BQTbBa
FVGJFBQuAuRTs0aKlD4fexWmdMiTw64JXIRDWI3ZbSQ4PDB+rIRoMH4Tc09QEE1N
+3RYAALW1EwFbDxSDdw1HSYgH6XRkhVtmtozeQUb2DNEa3/n1Jd70tXveWDj41ON
dF16KVsVFGQDD9Lf0QJBANqF22xS3Sn2rLveOJ70G9Cls0e8i14XHwBo4/KQxaan
NYEx0+XtE4gU4JITY2sQawtgU9AP2oSy7Zcx/sAVH9MCQQDXO6eQe03p/p+TFW+L
BwSTLRg3w1J+lVlvkg/wZh47YQPXsJRxIQxjaVlBQmOWgiOZ1ZHmvvOUlFLJpHth
8JopAkEAqPDxydZqa+XsdzX/WkxpMK7aYuyOZsjDTALLsB1i4UvGXsKSCuF1xzA0
ylo48233BA2N3n5TN2JJsymQxRnPxwJBAMQARjLTpvtc7bKCxcYkiO0CFtjJHXm6
xexNZgh05jkKuvYTjsqK3v40tJwyOgCY2JTBoZEw+R6oB9Aq4lUpRykCQA8rsn/K
9hby9eWWeygxZs2VBviKO7d6xAnqZKULCG10v/V2DCJIHLNS5aO7gQxDAvXG1Jy3
SL0OC3w8gJsD75k=
-----END PRIVATE KEY-----';

	//merchant_public_key,商户公钥，按照说明文档上传此密钥到智付商家后台，位置为"支付设置"->"公钥管理"->"设置商户公钥"，代码中不使用到此变量
	//demo提供的merchant_public_key已经上传到测试商家号后台
    $key_config['merchant_public_key'] = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC3uVcqMj7AoPLUSWObYpfuoMUr
u9dihI01tTrvvTWW9O/ELPirCA7JkKG5fhpMz8/9RF4JeVGFYeUkifqVvuJL+dpu
dMjFydlj9MRIzP9ghHDmiSacXHYWFIovOsjIZAjgaZ3JfKoeLm6XXIZX7T3KtMDl
eCG1GnzMGsyXQVcGywIDAQAB
-----END PUBLIC KEY-----';


/**
1)dinpay_public_key，智付公钥，每个商家对应一个固定的智付公钥（不是使用工具生成的密钥merchant_public_key，不要混淆），
即为智付商家后台"公钥管理"->"智付公钥"里的绿色字符串内容,复制出来之后调成4行（换行位置任意，前面三行对齐），
并加上注释"-----BEGIN PUBLIC KEY-----"和"-----END PUBLIC KEY-----"
2)demo提供的dinpay_public_key是测试商户号1111110166的智付公钥，请自行复制对应商户号的智付公钥进行调整和替换。
3）使用智付公钥验证时需要调用openssl_verify函数进行验证,需要在php_ini文件里打开php_openssl插件
*/
    $key_config['dinpay_public_key'] = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCeemlkpscjCu6urFfpXaGf8NA5
+p9oCcGCUtCcZ4sXSrJJg4TEx/sQkLGfAPWj25z0d843nzHYMeBTlphHOB6SyuPP
rMmOXJeoZ9UQHdZTubRG0sjSq0pwJAFR8Kmyc1iZxTyi7N+I4BtOjX8JTgJir9xI
Pe04GPIRIQiEpN0ZfQIDAQAB
-----END PUBLIC KEY-----';



    return $key_config;

?>